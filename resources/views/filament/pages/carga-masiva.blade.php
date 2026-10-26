<x-filament-panels::page>
    <style>
        @keyframes cm-giro { to { transform: rotate(360deg); } }
        @keyframes cm-brillo { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        @keyframes cm-pulso { 0%, 100% { opacity: .35; transform: scale(.85); } 50% { opacity: 1; transform: scale(1); } }
        .cm-anillo { width: 3.25rem; height: 3.25rem; border-radius: 9999px; border: 4px solid color-mix(in srgb, var(--primary-500) 20%, transparent); border-top-color: var(--primary-600); animation: cm-giro 1s linear infinite; flex-shrink: 0; }
        .cm-barra { position: relative; height: .75rem; width: 100%; overflow: hidden; border-radius: 9999px; background: color-mix(in srgb, var(--gray-400) 25%, transparent); }
        .cm-barra-relleno { height: 100%; border-radius: 9999px; background-color: var(--primary-600); background-image: linear-gradient(110deg, transparent 25%, rgba(255,255,255,.55) 50%, transparent 75%); background-size: 200% 100%; animation: cm-brillo 1.6s linear infinite; transition: width .5s ease; }
        .cm-barra-indeterminada { position: absolute; inset: 0; border-radius: 9999px; background-image: linear-gradient(110deg, transparent 25%, color-mix(in srgb, var(--primary-500) 55%, transparent) 50%, transparent 75%); background-size: 200% 100%; animation: cm-brillo 1.4s linear infinite; }
        .cm-punto { width: .5rem; height: .5rem; border-radius: 9999px; background: var(--primary-600); display: inline-block; animation: cm-pulso 1.2s ease-in-out infinite; }
        .cm-punto:nth-child(2) { animation-delay: .2s; }
        .cm-punto:nth-child(3) { animation-delay: .4s; }
        .cm-tarjeta { border-radius: .75rem; padding: 1.5rem; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.05); border: 1px solid color-mix(in srgb, var(--gray-950) 5%, transparent); }
        .dark .cm-tarjeta { background: var(--gray-900); border-color: rgba(255,255,255,.1); }
        .cm-dato { border-radius: .5rem; padding: .5rem .75rem; background: color-mix(in srgb, var(--gray-400) 12%, transparent); min-width: 6.5rem; }
        .cm-nota { color: var(--gray-500); font-size: .8rem; }
        .cm-tabla th, .cm-tabla td { padding: .5rem .75rem; text-align: start; }
        .cm-tabla th:nth-child(2), .cm-tabla td:nth-child(2) { min-width: 10.5rem; white-space: nowrap; }
        .cm-acciones { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: .75rem; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid color-mix(in srgb, var(--gray-400) 30%, transparent); }
        .cm-acciones > * { margin: 0; }
        .cm-acciones .fi-ac { display: contents; }
        @media (prefers-reduced-motion: reduce) { .cm-anillo, .cm-barra-relleno, .cm-barra-indeterminada, .cm-punto { animation: none; } }
    </style>

    @if ($procesando)
        @php
            $hechos = $total - count($cola);
            $porcentaje = $total > 0 ? round($hechos / $total * 100) : 0;
            $actual = $cola[0]['etiqueta'] ?? $cola[0]['nombre'] ?? null;
            $reintento = ($cola[0]['intentos'] ?? 0) > 0;
            $res = collect($resultados);
            $okCount = $res->whereIn('estado', ['creado', 'duplicado'])->count();
            $malCount = $res->whereIn('estado', ['omitido', 'error'])->count();
            $titulo = match ($modoProceso) {
                'excel' => 'Creando registros desde el Excel',
                'unico' => 'Leyendo el PDF con IA',
                default => 'Leyendo documentos con IA',
            };
        @endphp

        <div
            x-data="{ seg: 0, t: null }"
            x-init="
                t = setInterval(() => seg++, 1000);
                (async () => { try { while (await $wire.procesarSiguiente()) {} } catch (e) { await $wire.procesoInterrumpido() } finally { clearInterval(t) } })()
            "
            class="cm-tarjeta"
            role="status"
            aria-live="polite"
        >
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div class="cm-anillo" aria-hidden="true"></div>

                <div style="min-width: 0; flex: 1;">
                    <p style="font-size: 1.05rem; font-weight: 600; margin: 0;">
                        {{ $titulo }}
                        <span aria-hidden="true" style="display: inline-flex; gap: .25rem; margin-left: .35rem; vertical-align: middle;">
                            <span class="cm-punto"></span><span class="cm-punto"></span><span class="cm-punto"></span>
                        </span>
                    </p>
                    @if ($actual)
                        <p class="cm-nota" style="margin: .15rem 0 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            {{ $reintento ? 'Reintentando (la IA estaba saturada)' : 'Procesando ahora' }}: <strong>{{ $actual }}</strong>
                        </p>
                    @endif
                </div>

                <div style="text-align: end; flex-shrink: 0;">
                    <p style="font-size: 1.75rem; font-weight: 700; line-height: 1; margin: 0; color: var(--primary-600);">{{ $porcentaje }}%</p>
                    <p class="cm-nota" style="margin: .2rem 0 0;" x-text="Math.floor(seg / 60) + ':' + String(seg % 60).padStart(2, '0')">0:00</p>
                </div>
            </div>

            <div class="cm-barra" style="margin-top: 1.25rem;" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $porcentaje }}">
                <div class="cm-barra-indeterminada"></div>
                <div class="cm-barra-relleno" style="position: relative; width: {{ $porcentaje }}%;"></div>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.25rem;">
                <div class="cm-dato">
                    <p class="cm-nota" style="margin: 0;">Procesados</p>
                    <p style="font-weight: 600; margin: 0;">{{ $hechos }} de {{ $total }}</p>
                </div>
                <div class="cm-dato">
                    <p class="cm-nota" style="margin: 0;">Registros creados</p>
                    <p style="font-weight: 600; margin: 0; color: var(--success-600);">{{ $okCount }}</p>
                </div>
                @if ($malCount > 0)
                    <div class="cm-dato">
                        <p class="cm-nota" style="margin: 0;">Con problemas</p>
                        <p style="font-weight: 600; margin: 0; color: var(--danger-600);">{{ $malCount }}</p>
                    </div>
                @endif
            </div>

            <p class="cm-nota" style="margin: 1.25rem 0 0;">
                Esto puede tardar unos minutos según la cantidad de documentos. <strong>No cierres ni recargues esta página</strong> hasta que termine.
            </p>
        </div>
    @elseif ($total === 0)
        <form
            wire:submit="iniciar"
            class="space-y-6"
            x-data="{ subiendo: 0, avance: 0 }"
            x-on:livewire-upload-start.window="subiendo++; avance = 0"
            x-on:livewire-upload-progress.window="avance = $event.detail.progress"
            x-on:livewire-upload-finish.window="subiendo = Math.max(0, subiendo - 1)"
            x-on:livewire-upload-error.window="subiendo = Math.max(0, subiendo - 1)"
            x-on:livewire-upload-cancel.window="subiendo = Math.max(0, subiendo - 1)"
        >
            <div x-show="subiendo > 0" x-cloak x-transition class="cm-tarjeta" role="status" aria-live="polite" style="padding: 1rem 1.25rem;">
                <div style="display: flex; align-items: center; gap: .85rem;">
                    <div class="cm-anillo" style="width: 1.75rem; height: 1.75rem; border-width: 3px;" aria-hidden="true"></div>
                    <div style="flex: 1; min-width: 0;">
                        <p style="font-weight: 600; margin: 0;">
                            Subiendo <span x-text="subiendo > 1 ? subiendo + ' archivos' : 'el archivo'"></span>…
                        </p>
                        <p class="cm-nota" style="margin: 0;">Espera a que termine antes de pulsar «Procesar y crear registros».</p>
                    </div>
                    <p style="font-weight: 700; margin: 0; color: var(--primary-600);" x-text="avance + '%'"></p>
                </div>
                <div class="cm-barra" style="margin-top: .75rem; height: .5rem;">
                    <div class="cm-barra-relleno" :style="'width: ' + avance + '%'"></div>
                </div>
            </div>

            {{ $this->form }}

            <div class="flex items-center justify-end gap-3">
                <p x-show="subiendo > 0" x-cloak class="cm-nota">Subiendo archivos…</p>

                <p wire:loading wire:target="iniciar" class="cm-nota">
                    Preparando los archivos…
                </p>

                <x-filament::button
                    type="submit"
                    icon="heroicon-o-arrow-up-tray"
                    wire:loading.attr="disabled"
                    wire:target="iniciar"
                    x-bind:disabled="subiendo > 0"
                >
                    <span wire:loading.remove wire:target="iniciar">Procesar y crear registros</span>
                    <span wire:loading wire:target="iniciar">Preparando…</span>
                </x-filament::button>
            </div>
        </form>
    @endif

    @if ($resultados !== [])
        @php($creados = collect($resultados)->whereIn('estado', ['creado', 'duplicado'])->count())

        <x-filament::section heading="Resultado" :description="$creados . ' de ' . count($resultados) . ' creados como pendientes'">
            <div class="overflow-x-auto">
                <table class="cm-tabla w-full text-start text-sm">
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
                <div class="cm-acciones">
                    @if ($fallidos !== [])
                        <x-filament::button color="warning" icon="heroicon-o-arrow-path" wire:click="reintentarFallidos" wire:loading.attr="disabled" wire:target="reintentarFallidos">
                            Reintentar {{ count($fallidos) }} pendiente(s)
                        </x-filament::button>
                    @endif

                    {{ $this->deshacerCargaAction }}

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

    <x-filament-actions::modals />
</x-filament-panels::page>
