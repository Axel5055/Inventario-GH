<?php

namespace App\Support;

use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Str;

/**
 * Campo opcional "Responsiva de baja" para las acciones que dan de baja un
 * registro (modal de duplicados y botón "Dar de Baja"). Es un archivo aparte de
 * la responsiva de entrega, que nunca se reemplaza.
 */
class ResponsivaBaja
{
    public static function subida(string $nombre, string $carpeta): FileUpload
    {
        return FileUpload::make($nombre)
            ->label('Responsiva de baja (opcional)')
            ->helperText('PDF. Se guarda aparte de la responsiva de entrega, que se conserva. Puedes cargarla después editando el registro.')
            ->acceptedFileTypes(['application/pdf'])
            ->disk('local')
            ->directory($carpeta)
            ->maxSize(10240)
            ->getUploadedFileNameForStorageUsing(fn () => 'baja-' . Str::random(12) . '.pdf');
    }
}
