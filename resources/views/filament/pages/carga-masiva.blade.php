<x-filament-panels::page>
    @if ($procesando)
        @php($hechos = $total - count($cola))

        <div
            x-data
            x-init="(async () => { try { while (await $wire.procesarSiguiente()) {} } catch (e) { await $wire.procesoInterrumpido() } })()"
            class="space-y-3"
        >
            <p class="text-sm font-medium">
                {{ $modoProceso === 'excel' ? 'Creando registros desde el Excel…' : 'Leyendo documentos con IA…' }} {{ $hechos }} de {{ $total }} procesados.
                <span class="text-gray-500">No cierres esta página hasta que termine.</span>
            </p>

            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                <div class="h-2 rounded-full bg-primary-600 transition-all" style="width: {{ $total > 0 ? round($hechos / $total * 100) : 0 }}%"></div>
            </div>
        </div>
    @elseif ($total === 0)
        <form wire:submit="iniciar" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end">
                <x-filament::button type="submit" icon="heroicon-o-arrow-up-tray" wire:loading.attr="disabled" wire:target="iniciar">
                    Procesar y crear registros
                </x-filament::button>
            </div>
        </form>
    @endif

    @if ($resultados !== [])
        @php($creados = collect($resultados)->whereIn('estado', ['creado', 'duplicado'])->count())

        <x-filament::section heading="Resultado" :description="$creados . ' de ' . count($resultados) . ' creados como pendientes'">
            <div class="overflow-x-auto">
                <table class="w-full text-start text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="px-2 py-2 text-start font-semibold">Archivo</th>
                            <th class="px-2 py-2 text-start font-semibold">Estado</th>
                            <th class="px-2 py-2 text-start font-semibold">Persona</th>
                            <th class="px-2 py-2 text-start font-semibold">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($resultados as $fila)
                            <tr class="border-b border-gray-100 align-top dark:border-white/5">
                                <td class="px-2 py-2">{{ $fila['archivo'] }}</td>
                                <td class="px-2 py-2">
                                    <x-filament::badge :color="match ($fila['estado']) { 'creado' => 'success', 'duplicado', 'omitido' => 'warning', default => 'danger' }">
                                        {{ match ($fila['estado']) { 'creado' => 'Creado', 'duplicado' => 'Creado como baja', 'omitido' => 'Omitido', default => 'Error' } }}
                                    </x-filament::badge>
                                </td>
                                <td class="px-2 py-2">{{ $fila['nombre'] ?? '—' }}</td>
                                <td class="px-2 py-2">{{ $fila['mensaje'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @unless ($procesando)
                <div class="mt-4 flex flex-wrap justify-end gap-3">
                    <x-filament::button color="gray" wire:click="nuevaCarga">Nueva carga</x-filament::button>

                    @if ($creados > 0)
                        <x-filament::button tag="a" :href="$this->urlPendientes()" icon="heroicon-o-clipboard-document-check">
                            Revisar pendientes
                        </x-filament::button>
                    @endif
                </div>
            @endunless
        </x-filament::section>
    @endif
</x-filament-panels::page>
