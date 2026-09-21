<?php

namespace App\Filament\Actions;

use App\Services\ExtractorResponsivaPdf;
use App\Services\ResponsivaAFormulario;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Botón "Cargar desde PDF" para las pantallas de crear registro: lee la
 * responsiva con IA y precarga el formulario. Si el archivo es un PDF, además
 * queda cargado en el campo "Cargar PDF" del formulario (para no subirlo dos
 * veces); las fotos solo se leen y se descartan. El usuario revisa y completa
 * lo que falte antes de guardar.
 */
class CargarDesdePdfAction
{
    public static function make(string $tipo): Action
    {
        return Action::make('cargarDesdePdf')
            ->label('Cargar desde PDF')
            ->icon('heroicon-o-document-arrow-up')
            ->color('gray')
            ->modalHeading('Cargar datos desde la responsiva')
            ->modalDescription('Sube la carta responsiva (PDF, escaneo o foto): se leerá con IA y se precargarán los campos del formulario. Revisa todo antes de guardar.')
            ->modalSubmitActionLabel('Leer documento')
            ->form([
                FileUpload::make('pdf')
                    ->label('Responsiva (PDF o foto)')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(20480)
                    ->disk('local')
                    ->directory('tmp/responsivas-ia')
                    ->required(),
            ])
            ->action(function (array $data, $livewire) use ($tipo) {
                $archivo = $data['pdf'];
                $disco = Storage::disk('local');
                $conservado = false;

                try {
                    $extraido = app(ExtractorResponsivaPdf::class)->extraer($disco->path($archivo), $tipo);

                    $resultado = app(ResponsivaAFormulario::class)->mapear($extraido, $tipo);

                    $estado = $livewire->form->getRawState();

                    if ($disco->mimeType($archivo) === 'application/pdf') {
                        $directorio = $tipo === ExtractorResponsivaPdf::TIPO_CELULAR ? 'responsivas/celulares' : 'responsivas/computo';
                        $nombre = $resultado['datos']['nombre_usuario'] ?? $estado['nombre_usuario'] ?? null;
                        $destino = $directorio . '/' . Str::slug($nombre ?: 'equipo') . '-' . Str::random(6) . '.pdf';

                        $disco->move($archivo, $destino);
                        $conservado = true;

                        // En "Crear" todo PDF que ya esté en el campo salió de una carga anterior
                        // (se reemplaza para no dejar archivos huérfanos).
                        foreach ((array) ($estado['responsiva_pdf'] ?? []) as $anterior) {
                            if (is_string($anterior) && str_starts_with($anterior, $directorio . '/')) {
                                $disco->delete($anterior);
                            }
                        }

                        $resultado['datos']['responsiva_pdf'] = $destino;
                    }
                } catch (Throwable $e) {
                    report($e);

                    Notification::make()
                        ->title('No se pudo leer el PDF')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    return;
                } finally {
                    if (! $conservado) {
                        $disco->delete($archivo);
                    }
                }

                $livewire->form->fill(array_merge($estado, $resultado['datos']));

                $notificacion = Notification::make()
                    ->title('Datos precargados desde el PDF')
                    ->body(collect(['Revisa los campos y completa lo que falte antes de guardar.', ...$resultado['avisos']])->implode("
"));

                $resultado['avisos'] ? $notificacion->warning() : $notificacion->success();

                $notificacion->send();
            });
    }
}
