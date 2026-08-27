<?php

namespace App\Livewire;

use App\Models\Area;
use App\Models\EquipoComputo;
use App\Models\Marca;
use App\Models\Sucursal;
use App\Services\InfoEquipoParser;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.publico')]
class RegistroEquipoComputo extends Component
{
    use WithFileUploads;

    public int $paso = 1;

    // ── Paso 1: datos del usuario ──
    public ?int $sucursal_id = null;

    public string $nombre_usuario = '';

    public ?int $area_id = null;

    public string $correo_local = '';

    public string $ext = '';

    // ── Paso 2: equipo ──
    public string $tipo_equipo = '';

    public string $sistema_operativo = '';

    public ?string $windows_version = null;

    public ?int $marca_id = null;

    // Texto tal cual lo reportó el .txt (para referencia del personal),
    // independiente de a qué marca del catálogo se haya asociado.
    public ?string $marca_detectada = null;

    public string $modelo = '';

    public string $numero_serie = '';

    public ?string $procesador = null;

    public ?string $ram = null;

    public ?string $almacenamiento = null;

    public ?string $usuario_equipo = null;

    // Claves de licencia: solo se llenan automáticamente al parsear el
    // .txt (si el programa las detectó). Nunca se muestran ni se piden
    // manualmente en el formulario.
    public ?string $windows_key = null;

    public ?string $office_clave = null;

    public string $observaciones = '';

    public string $fecha_entrega = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $archivoInfo = null;

    public bool $infoDetectada = false;

    /** @var array<int, string> nombres de los campos que se llenaron automáticamente */
    public array $camposDetectados = [];

    public bool $procesandoArchivo = false;

    // Honeypot anti-spam: los bots suelen rellenar todos los campos, las
    // personas nunca ven este porque está oculto visualmente.
    public string $sitio_web = '';

    public function mount(): void
    {
        $this->fecha_entrega = now()->format('Y-m-d');

        // Garantiza que siempre exista una opción "Otra" en el catálogo de
        // marcas, tanto para el select manual como para cuando el .txt
        // reporta un fabricante que no está dado de alta.
        Marca::firstOrCreate(
            ['nombre' => 'Otra'],
            ['categoria' => 'ambas', 'activo' => true]
        );
    }

    protected function rulesPaso1(): array
    {
        return [
            'sucursal_id' => 'required|exists:sucursales,id',
            'nombre_usuario' => 'required|string|min:3|max:255',
            'area_id' => 'required|exists:areas,id',
            'correo_local' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'ext' => ['nullable', 'regex:/^[0-9]{1,4}$/'],
        ];
    }

    protected function rulesPaso2(): array
    {
        $reglas = [
            'tipo_equipo' => 'required|in:laptop,desktop,all_in_one,workstation,mini_pc',
            'sistema_operativo' => 'required|in:windows,apple',
            'marca_id' => 'required|exists:marcas,id',
            'modelo' => 'required|string|max:150',
            'numero_serie' => 'required|string|max:200',
            'procesador' => 'required|string|max:200',
            'ram' => 'required|string|max:100',
            'almacenamiento' => 'nullable|string|max:200',
            'usuario_equipo' => 'nullable|string|max:150',
            'observaciones' => 'nullable|string|max:1000',
            'fecha_entrega' => 'required|date|before_or_equal:today',
        ];

        return $reglas;
    }

    protected function mensajes(): array
    {
        return [
            'sucursal_id.required' => 'Selecciona tu sucursal.',
            'sucursal_id.exists' => 'La sucursal seleccionada no es válida.',
            'nombre_usuario.required' => 'Escribe tu nombre completo.',
            'nombre_usuario.min' => 'Escribe tu nombre completo.',
            'area_id.required' => 'Selecciona tu área.',
            'area_id.exists' => 'El área seleccionada no es válida.',
            'correo_local.required' => 'Escribe tu correo corporativo.',
            'correo_local.regex' => 'El correo solo puede tener letras, números, puntos, guiones y guion bajo.',
            'ext.regex' => 'La extensión solo puede tener hasta 4 números.',
            'tipo_equipo.required' => 'Selecciona el tipo de equipo.',
            'sistema_operativo.required' => 'Selecciona el sistema operativo del equipo.',
            'marca_id.required' => 'Selecciona la marca del equipo.',
            'marca_id.exists' => 'La marca seleccionada no es válida.',
            'modelo.required' => 'Escribe el modelo del equipo.',
            'numero_serie.required' => 'Escribe el número de serie del equipo.',
            'procesador.required' => 'Escribe el procesador del equipo.',
            'ram.required' => 'Escribe la memoria RAM del equipo.',
            'fecha_entrega.required' => 'Selecciona la fecha aproximada de entrega.',
            'fecha_entrega.before_or_equal' => 'La fecha de entrega no puede ser en el futuro.',
        ];
    }

    public function siguientePaso(): void
    {
        $this->validate($this->rulesPaso1(), $this->mensajes());

        $this->paso = 2;
    }

    public function pasoAnterior(): void
    {
        $this->paso = 1;
    }

    public function updatedSistemaOperativo(string $valor): void
    {
        if ($valor === 'apple') {
            $this->archivoInfo = null;
            $this->infoDetectada = false;
            $this->camposDetectados = [];
            $this->windows_version = null;
            $this->marca_detectada = null;
            $this->marca_id = Marca::activas()->deComputo()->where('nombre', 'Apple')->value('id');
            $this->windows_key = null;
            $this->office_clave = null;
        } else {
            $this->marca_detectada = null;
            $this->marca_id = null;
            $this->modelo = '';
            $this->numero_serie = '';
            $this->procesador = null;
            $this->ram = null;
            $this->almacenamiento = null;
            $this->usuario_equipo = null;
            $this->windows_key = null;
            $this->office_clave = null;
        }
    }

    public function updatedArchivoInfo(): void
    {
        $this->procesandoArchivo = true;

        $this->validate([
            'archivoInfo' => 'file|mimes:txt|max:512',
        ], [
            'archivoInfo.mimes' => 'El archivo debe ser el .txt que generó el script.',
            'archivoInfo.max' => 'El archivo es demasiado grande, ¿seguro que es el .txt correcto?',
        ]);

        $contenido = $this->archivoInfo->get();
        $datos = app(InfoEquipoParser::class)->parse($contenido);

        $detectados = [];

        foreach ($datos as $campo => $valor) {
            if ($valor !== null) {
                $this->{$campo} = $valor;
                $detectados[] = $campo;
            }
        }

        if ($this->marca_detectada !== null) {
            $this->marca_id = $this->resolverMarcaId($this->marca_detectada);
        }

        $this->camposDetectados = $detectados;
        $this->infoDetectada = $detectados !== [];
        $this->procesandoArchivo = false;

        if ($this->infoDetectada) {
            $this->dispatch('info-detectada');
        } else {
            $this->dispatch('info-no-detectada');
        }
    }

    public function quitarArchivo(): void
    {
        $this->archivoInfo = null;
        $this->infoDetectada = false;
        $this->camposDetectados = [];
    }

    /**
     * Compara el fabricante que reportó el .txt contra el catálogo de
     * marcas (comparación flexible: sin importar mayúsculas ni espacios
     * extra). Si no hay coincidencia, cae en la marca "Otra".
     */
    private function resolverMarcaId(string $marcaDetectada): int
    {
        $normalizado = Str::lower(trim($marcaDetectada));

        $coincidencia = Marca::activas()->deComputo()->get()
            ->first(fn (Marca $marca) => Str::lower(trim($marca->nombre)) === $normalizado);

        if ($coincidencia) {
            return $coincidencia->id;
        }

        return Marca::firstOrCreate(
            ['nombre' => 'Otra'],
            ['categoria' => 'ambas', 'activo' => true]
        )->id;
    }

    public function registrar(): void
    {
        // Honeypot: si un bot rellenó este campo oculto, simulamos éxito
        // sin guardar nada.
        if ($this->sitio_web !== '') {
            $this->dispatch('registro-exitoso', nombre: $this->nombre_usuario);

            return;
        }

        $limiteClave = 'registro-equipo-computo:' . request()->ip();

        if (RateLimiter::tooManyAttempts($limiteClave, maxAttempts: 5)) {
            $this->dispatch('registro-error', mensaje: 'Alcanzaste el límite de registros por hoy desde este dispositivo. Si necesitas ayuda, contacta a Sistemas.');

            return;
        }

        $datos = $this->validate(
            [...$this->rulesPaso1(), ...$this->rulesPaso2()],
            $this->mensajes()
        );

        RateLimiter::hit($limiteClave, decaySeconds: 60 * 60 * 24);

        $rutaArchivoInfo = $this->archivoInfo?->store('reportes-equipo/computo', 'local');

        EquipoComputo::create([
            'tipo_movimiento' => 'alta',
            'fecha_entrega' => $datos['fecha_entrega'],
            'razon_social_id' => null,
            'nombre_usuario' => $datos['nombre_usuario'],
            'correo_electronico' => $datos['correo_local'] . '@grupohunan.com',
            'sucursal_id' => $datos['sucursal_id'],
            'area_id' => $datos['area_id'],
            'ext' => $datos['ext'] !== '' ? $datos['ext'] : null,
            'tipo_equipo' => $datos['tipo_equipo'],
            'marca_id' => $datos['marca_id'],
            'marca_detectada' => $this->marca_detectada,
            'archivo_info_txt' => $rutaArchivoInfo,
            'modelo' => $datos['modelo'],
            'numero_serie' => $datos['numero_serie'],
            'procesador' => $datos['procesador'],
            'ram' => $datos['ram'],
            'almacenamiento' => $datos['almacenamiento'],
            'usuario_equipo' => $datos['usuario_equipo'],
            'observaciones' => $datos['observaciones'] !== '' ? $datos['observaciones'] : null,
            'sistema_operativo' => $datos['sistema_operativo'],
            'windows_version' => $this->windows_version,
            'windows_key' => $this->windows_key,
            'office_clave' => $this->office_clave,
            'usuario_referencia' => $datos['correo_local'] . '@grupohunan.com',
            'origen_registro' => 'publico',
            'estado_revision' => 'pendiente',
        ]);

        $this->dispatch('registro-exitoso', nombre: $datos['nombre_usuario']);

        $this->reset();
        $this->mount();
    }

    public function render()
    {
        return view('livewire.registro-equipo-computo', [
            'sucursales' => Sucursal::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id'),
            'areas' => Area::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id'),
            'marcas' => Marca::activas()->deComputo()
                ->orderByRaw("(nombre = 'Otra') asc")
                ->orderBy('nombre')
                ->pluck('nombre', 'id'),
        ]);
    }
}
