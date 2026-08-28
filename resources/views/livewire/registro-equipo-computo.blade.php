<div
    x-data="registroEquipoComputo({ sucursales: @js($sucursales), areas: @js($areas), marcas: @js($marcas) })"
    x-init="init()"
    class="mx-auto flex min-h-screen w-full max-w-2xl flex-col justify-center px-4 py-10 sm:px-6"
>
    {{-- Encabezado --}}
    <div class="mb-8 text-center">
        <img src="{{ asset('images/logo-grupo-hunan.png') }}" alt="Grupo Hunan" class="mx-auto mb-4 h-36 w-auto">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Registro de Equipo de Cómputo</h1>
        <p class="mt-1 text-sm text-slate-500">Grupo Hunan · Toma menos de 2 minutos</p>
    </div>

    {{-- Indicador de pasos --}}
    <div class="mx-auto mb-8 flex w-full max-w-xs items-center">
        <div class="flex flex-1 flex-col items-center">
            <div @class([
                'flex h-9 w-9 items-center justify-center rounded-full border-2 bg-white text-sm font-semibold transition-colors duration-300',
                'border-slate-900 text-slate-900' => $paso >= 1,
                'border-slate-300 text-slate-400' => $paso < 1,
            ])>1</div>
            <span class="mt-1.5 text-xs font-medium text-slate-500">Tus datos</span>
        </div>
        <div @class([
            'mb-5 h-0 flex-1 border-t-2 border-dashed transition-colors duration-300',
            'border-slate-900' => $paso >= 2,
            'border-slate-300' => $paso < 2,
        ])></div>
        <div class="flex flex-1 flex-col items-center">
            <div @class([
                'flex h-9 w-9 items-center justify-center rounded-full border-2 bg-white text-sm font-semibold transition-colors duration-300',
                'border-slate-900 text-slate-900' => $paso >= 2,
                'border-slate-300 text-slate-400' => $paso < 2,
            ])>2</div>
            <span class="mt-1.5 text-xs font-medium text-slate-500">Tu equipo</span>
        </div>
    </div>

    {{-- Tarjeta principal --}}
    <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-8">

        {{-- Overlay de carga mientras se lee/procesa el .txt --}}
        <div wire:loading.flex wire:target="archivoInfo" class="absolute inset-0 z-20 flex-col items-center justify-center gap-3 rounded-2xl bg-white/75 backdrop-blur-sm" style="display: none;">
            <svg class="h-9 w-9 animate-spin text-slate-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="text-sm font-semibold text-slate-700">Leyendo tu archivo...</p>
        </div>

        <form
            @if ($paso === 1) x-on:submit.prevent="$wire.siguientePaso()" @else x-on:submit.prevent="confirmarYRegistrar()" @endif
            wire:loading.class="pointer-events-none blur-[2px] select-none" wire:target="archivoInfo">

            {{-- Honeypot: invisible para personas, tentador para bots --}}
            <input type="text" wire:model="sitio_web" class="hidden" tabindex="-1" autocomplete="off">

            {{-- ══════════ PASO 1 ══════════ --}}
            @if ($paso === 1)
            <div class="animate-step-in space-y-5">

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Nombre completo</label>
                    <input type="text" wire:model="nombre_usuario" placeholder="Ej. Juan Pérez López"
                        class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10 focus:outline-none">
                    @error('nombre_usuario') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Sucursal</label>
                        <select wire:model="sucursal_id"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10 focus:outline-none">
                            <option value="">Selecciona...</option>
                            @foreach ($sucursales as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        @error('sucursal_id') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Área</label>
                        <select wire:model="area_id"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10 focus:outline-none">
                            <option value="">Selecciona...</option>
                            @foreach ($areas as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        @error('area_id') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Correo corporativo</label>
                        <div class="flex items-stretch overflow-hidden rounded-xl border border-slate-300 shadow-sm transition focus-within:border-slate-900 focus-within:ring-4 focus-within:ring-slate-900/10">
                            <input type="text" wire:model="correo_local" placeholder="jperez"
                                class="w-full min-w-0 border-0 px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-0">
                            <span class="flex items-center whitespace-nowrap bg-slate-50 px-3 text-sm text-slate-400 select-none">@grupohunan.com</span>
                        </div>
                        @error('correo_local') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Extensión <span class="font-normal text-slate-400">(opcional)</span></label>
                        <input type="text" inputmode="numeric" wire:model="ext" placeholder="1234" maxlength="4"
                            x-on:input="$el.value = $el.value.replace(/\D/g, '').slice(0, 4)"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10 focus:outline-none">
                        @error('ext') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <button type="submit"
                    class="mt-2 flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-900/20 transition hover:bg-slate-800 active:scale-[0.99]">
                    Continuar
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </button>
            </div>
            @endif

            {{-- ══════════ PASO 2 ══════════ --}}
            @if ($paso === 2)
            <div class="animate-step-in space-y-5">

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Tipo de equipo</label>
                        <select wire:model="tipo_equipo"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10 focus:outline-none">
                            <option value="">Selecciona...</option>
                            <option value="laptop">Laptop</option>
                            <option value="desktop">Desktop / PC</option>
                            <option value="all_in_one">All in One</option>
                            <option value="workstation">Workstation</option>
                            <option value="mini_pc">Mini PC</option>
                        </select>
                        @error('tipo_equipo') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Sistema operativo</label>
                        <select wire:model.live="sistema_operativo"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10 focus:outline-none">
                            <option value="">Selecciona...</option>
                            <option value="windows">Windows</option>
                            <option value="apple">Apple (macOS)</option>
                        </select>
                        @error('sistema_operativo') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- ── Windows: descarga + subida ── --}}
                @if ($sistema_operativo === 'windows')
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="mb-3 flex items-start gap-1.5 text-xs text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                            <span>Para un proceso más exacto, te recomendamos usar el método del archivo (descargar y ejecutar), pero también puedes ingresar los datos manualmente en los campos de abajo.</span>
                        </p>

                        <p class="mb-3 text-sm font-medium text-slate-700">1. Descarga el programa y ábrelo con doble clic (detecta automáticamente tu versión de Windows):</p>
                        <a href="{{ asset('downloads/InfoEquipo.cmd') }}" download
                            class="flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-400 hover:text-slate-900">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                            Descargar InfoEquipo.cmd
                        </a>

                        <p class="mt-4 mb-2 text-sm font-medium text-slate-700">2. Sube el archivo <span class="font-mono text-xs">InfoEquipo.txt</span> que se generó junto al programa:</p>

                        @if ($archivoInfo)
                            <div class="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5">
                                <div class="flex items-center gap-2 text-sm text-emerald-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    <span class="truncate font-medium">{{ $archivoInfo->getClientOriginalName() }}</span>
                                </div>
                                <button type="button" wire:click="quitarArchivo" class="text-xs font-semibold text-slate-400 hover:text-red-600">Quitar</button>
                            </div>
                        @else
                            <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-300 bg-white px-4 py-4 text-sm text-slate-500 transition hover:border-slate-400 hover:text-slate-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                                <span wire:loading.remove wire:target="archivoInfo">Haz clic para elegir el archivo .txt</span>
                                <span wire:loading wire:target="archivoInfo">Leyendo archivo...</span>
                                <input type="file" wire:model="archivoInfo" accept=".txt" class="hidden">
                            </label>
                        @endif
                        @error('archivoInfo') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror

                        @if ($infoDetectada)
                            <p class="mt-3 flex items-center gap-1.5 text-xs font-medium text-emerald-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                Revisa los datos detectados abajo — puedes corregir cualquier campo.
                            </p>
                        @endif
                    </div>
                @endif

                {{-- ── Apple: tutorial ── --}}
                @if ($sistema_operativo === 'apple')
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                        <p class="mb-2 font-medium text-slate-700"> Cómo obtener los datos de tu Mac:</p>
                        <ol class="list-decimal space-y-1.5 pl-4">
                            <li>Da clic en el logo  (esquina superior izquierda) → <strong>Acerca de este Mac</strong>.</li>
                            <li><strong>Modelo</strong> y <strong>Procesador (Chip)</strong>: aparecen en la pestaña "General".</li>
                            <li><strong>Memoria RAM</strong>: también en "General", junto al chip.</li>
                            <li><strong>Almacenamiento</strong>: pestaña "Almacenamiento", usa la capacidad total del disco.</li>
                            <li><strong>Número de serie</strong>: pestaña "General" o en "Más información del sistema...".</li>
                        </ol>
                    </div>
                @endif

                {{-- ── Campos de ficha técnica (autollenados o manuales) ── --}}
                @if ($sistema_operativo)
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 flex items-center gap-1.5 text-sm font-medium text-slate-700">
                                Marca
                                @if (in_array('marca_detectada', $camposDetectados)) <span class="rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700">detectado</span> @endif
                            </label>
                            <select wire:model="marca_id"
                                class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10 focus:outline-none">
                                <option value="">Selecciona...</option>
                                @foreach ($marcas as $id => $nombre)
                                    <option value="{{ $id }}">{{ $nombre }}</option>
                                @endforeach
                            </select>
                            @error('marca_id') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1.5 flex items-center gap-1.5 text-sm font-medium text-slate-700">
                                Modelo
                                @if (in_array('modelo', $camposDetectados)) <span class="rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700">detectado</span> @endif
                            </label>
                            <input type="text" wire:model="modelo" placeholder="Ej. OptiPlex 7070"
                                class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10 focus:outline-none">
                            @error('modelo') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1.5 flex items-center gap-1.5 text-sm font-medium text-slate-700">
                                Número de serie
                                @if (in_array('numero_serie', $camposDetectados)) <span class="rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700">detectado</span> @endif
                            </label>
                            <input type="text" wire:model="numero_serie" placeholder="Ej. ABC1234"
                                class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10 focus:outline-none">
                            @error('numero_serie') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1.5 flex items-center gap-1.5 text-sm font-medium text-slate-700">
                                Procesador
                                @if (in_array('procesador', $camposDetectados)) <span class="rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700">detectado</span> @endif
                            </label>
                            <input type="text" wire:model="procesador" placeholder="Ej. Intel Core i5-9500"
                                class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10 focus:outline-none">
                            @error('procesador') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1.5 flex items-center gap-1.5 text-sm font-medium text-slate-700">
                                Memoria RAM
                                @if (in_array('ram', $camposDetectados)) <span class="rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700">detectado</span> @endif
                            </label>
                            <input type="text" wire:model="ram" placeholder="Ej. 16 GB"
                                class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10 focus:outline-none">
                            @error('ram') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1.5 flex items-center gap-1.5 text-sm font-medium text-slate-700">
                                Almacenamiento <span class="font-normal text-slate-400">(opcional)</span>
                                @if (in_array('almacenamiento', $camposDetectados)) <span class="rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700">detectado</span> @endif
                            </label>
                            <input type="text" wire:model="almacenamiento" placeholder="Ej. 465.76 GB"
                                class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10 focus:outline-none">
                            @error('almacenamiento') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 flex items-center gap-1.5 text-sm font-medium text-slate-700">
                            Usuario del equipo <span class="font-normal text-slate-400">(opcional)</span>
                            @if (in_array('usuario_equipo', $camposDetectados)) <span class="rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700">detectado</span> @endif
                        </label>
                        <input type="text" wire:model="usuario_equipo" placeholder="Ej. jperez"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10 focus:outline-none">
                        @error('usuario_equipo') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Fecha aproximada de entrega</label>
                        <input type="date" wire:model="fecha_entrega" max="{{ now()->format('Y-m-d') }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10 focus:outline-none">
                        @error('fecha_entrega') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Observaciones <span class="font-normal text-slate-400">(opcional)</span></label>
                        <textarea wire:model="observaciones" rows="3" placeholder="Algo que debamos saber sobre el equipo..."
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10 focus:outline-none"></textarea>
                        @error('observaciones') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="flex gap-3 pt-1">
                    <button type="button" wire:click="pasoAnterior"
                        class="flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                        Atrás
                    </button>
                    <button type="button" x-on:click="confirmarYRegistrar()" wire:loading.attr="disabled" wire:target="registrar"
                        class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-900/20 transition hover:bg-slate-800 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-70">
                        <span wire:loading.remove wire:target="registrar">Registrar equipo</span>
                        <span wire:loading wire:target="registrar">Enviando...</span>
                    </button>
                </div>
            </div>
            @endif
        </form>
    </div>

    <p class="mt-6 text-center text-sm font-medium text-slate-600">¿Tienes dudas? Contacta a Sistemas · Grupo Hunan · Ext. 1110</p>
</div>

<script>
    function registroEquipoComputo({ sucursales, areas, marcas }) {
        const etiquetasTipoEquipo = {
            laptop: 'Laptop',
            desktop: 'Desktop / PC',
            all_in_one: 'All in One',
            workstation: 'Workstation',
            mini_pc: 'Mini PC',
        };

        return {
            init() {
                Livewire.on('info-detectada', () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Datos detectados',
                        text: 'Ya llenamos lo que encontramos en tu archivo. Revisa que todo esté correcto.',
                        confirmButtonColor: '#0f172a',
                        timer: 3200,
                        timerProgressBar: true,
                    });
                });

                Livewire.on('info-no-detectada', () => {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No pudimos leer el archivo',
                        text: 'Verifica que sea el .txt que generó el script, o llena los datos manualmente.',
                        confirmButtonColor: '#0f172a',
                    });
                });

                Livewire.on('registro-error', (event) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'No se pudo registrar',
                        text: event.mensaje,
                        confirmButtonColor: '#0f172a',
                    });
                });

                Livewire.on('registro-exitoso', (event) => {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Listo, ' + event.nombre.split(' ')[0] + '!',
                        html: 'Tu equipo quedó registrado.<br>Nuestro equipo de Sistemas lo revisará muy pronto.',
                        confirmButtonColor: '#0f172a',
                        confirmButtonText: 'Entendido',
                    });
                });
            },

            confirmarYRegistrar() {
                const fila = (label, value) => value
                    ? `<div style="display:flex;justify-content:space-between;gap:12px;padding:6px 0;border-bottom:1px solid #f1f5f9;">
                            <span style="color:#64748b;">${label}</span>
                            <span style="color:#1e293b;font-weight:600;text-align:right;">${value}</span>
                        </div>`
                    : '';

                const so = this.$wire.sistema_operativo === 'windows' ? 'Windows' : 'Apple (macOS)';
                const correo = this.$wire.correo_local ? `${this.$wire.correo_local}@grupohunan.com` : '';

                const resumen = `
                    <div style="text-align:left;font-size:13px;max-height:320px;overflow-y:auto;">
                        ${fila('Nombre', this.$wire.nombre_usuario)}
                        ${fila('Sucursal', sucursales[this.$wire.sucursal_id] ?? '')}
                        ${fila('Área', areas[this.$wire.area_id] ?? '')}
                        ${fila('Correo', correo)}
                        ${fila('Extensión', this.$wire.ext)}
                        ${fila('Tipo de equipo', etiquetasTipoEquipo[this.$wire.tipo_equipo] ?? this.$wire.tipo_equipo)}
                        ${fila('Sistema operativo', so)}
                        ${fila('Marca', marcas[this.$wire.marca_id] ?? '')}
                        ${fila('Modelo', this.$wire.modelo)}
                        ${fila('No. de serie', this.$wire.numero_serie)}
                        ${fila('Procesador', this.$wire.procesador)}
                        ${fila('Memoria RAM', this.$wire.ram)}
                        ${fila('Almacenamiento', this.$wire.almacenamiento)}
                        ${fila('Usuario del equipo', this.$wire.usuario_equipo)}
                        ${fila('Fecha de entrega', this.$wire.fecha_entrega)}
                        ${fila('Observaciones', this.$wire.observaciones)}
                    </div>
                `;

                Swal.fire({
                    icon: 'question',
                    title: '¿Todo correcto?',
                    html: resumen,
                    width: 480,
                    showCancelButton: true,
                    confirmButtonText: 'Sí, registrar',
                    cancelButtonText: 'No, revisar',
                    confirmButtonColor: '#0f172a',
                    cancelButtonColor: '#94a3b8',
                    reverseButtons: true,
                }).then((resultado) => {
                    if (resultado.isConfirmed) {
                        this.$wire.registrar();
                    }
                });
            },
        };
    }
</script>
