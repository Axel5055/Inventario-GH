<x-filament-panels::page>
    <form wire:submit="descargar" class="space-y-6">
        {{ $this->form }}

        @php($conteos = $this->conteos())

        <div class="flex flex-wrap items-center justify-between gap-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                @if ($conteos)
                    Se exportarán:
                    @foreach ($conteos as $etiqueta => $total)
                        <strong>{{ $etiqueta }}</strong> ({{ $total }})@if (! $loop->last), @endif
                    @endforeach
                    <br>
                    <span class="text-xs">El reporte no incluye contraseñas, PIN, claves de licencia ni CURP.</span>
                @else
                    Selecciona al menos una sección.
                @endif
            </p>

            <x-filament::button type="submit" icon="heroicon-o-arrow-down-tray" wire:loading.attr="disabled" wire:target="descargar">
                Descargar Excel
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
