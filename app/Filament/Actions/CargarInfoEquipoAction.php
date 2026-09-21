<?php

namespace App\Filament\Actions;

use App\Services\InfoEquipoAFormulario;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Botón "Cargar info de equipo": lee el .txt que genera InfoEquipo.cmd (el
 * mismo que sube el usuario en el formulario público) y lo vuelca en el
 * formulario. Como esa información sale del propio equipo, REEMPLAZA lo que
 * ya estuviera capturado en esos campos. El .txt queda adjunto al registro.
 */
class CargarInfoEquipoAction
{
    public static function make(): Action
    {
        return Action::make('cargarInfoEquipo')
            ->label('Cargar info de equipo')
            ->icon('heroicon-o-computer-desktop')
            ->color('gray')
            ->modalHeading('Cargar información del equipo')
            ->modalDescription('Sube el archivo .txt que genera InfoEquipo.cmd. Marca, modelo, serie, procesador, RAM, almacenamiento, Windows y licencias se llenarán con esos datos, reemplazando lo que ya estuviera escrito.')
            ->modalSubmitActionLabel('Cargar información')
            ->form([
                FileUpload::make('archivo')
                    ->label('Archivo .txt del equipo')
                    ->acceptedFileTypes(['text/plain'])
                    ->maxSize(512)
                    ->disk('local')
                    ->directory('reportes-equipo/computo')
                    ->required(),
            ])
            ->action(function (array $data, $livewire) {
                $disco = Storage::disk('local');
                $ruta = $data['archivo'];
                $conservado = false;

                try {
                    $resultado = app(InfoEquipoAFormulario::class)->mapear((string) $disco->get($ruta));

                    if ($resultado['datos'] === []) {
                        Notification::make()
                            ->title('No se encontró información del equipo')
                            ->body('Verifica que sea el .txt generado por InfoEquipo.cmd.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $estado = $livewire->form->getRawState();

                    // En "Crear" un .txt previo en el formulario es de una carga anterior:
                    // se reemplaza. En "Editar" pertenece al registro y no se toca.
                    if ($livewire instanceof CreateRecord && is_string($estado['archivo_info_txt'] ?? null)) {
                        $disco->delete($estado['archivo_info_txt']);
                    }

                    $resultado['datos']['archivo_info_txt'] = $ruta;
                    $conservado = true;

                    $reemplazados = collect($resultado['datos'])
                        ->except(['archivo_info_txt', 'marca_detectada', 'sistema_operativo'])
                        ->filter(fn ($valor, $campo) => filled($estado[$campo] ?? null) && $estado[$campo] != $valor)
                        ->count();
                } catch (Throwable $e) {
                    report($e);

                    Notification::make()
                        ->title('No se pudo leer el archivo')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    return;
                } finally {
                    if (! $conservado) {
                        $disco->delete($ruta);
                    }
                }

                $livewire->form->fill(array_merge($estado, $resultado['datos']));

                $resumen = 'Información del equipo cargada' . ($reemplazados ? " ({$reemplazados} " . ($reemplazados === 1 ? 'campo reemplazado' : 'campos reemplazados') . ').' : '.');

                $notificacion = Notification::make()
                    ->title('Datos cargados desde el archivo')
                    ->body(collect([$resumen, ...$resultado['avisos']])->implode("\n"));

                $resultado['avisos'] ? $notificacion->warning() : $notificacion->success();

                $notificacion->send();
            });
    }
}
