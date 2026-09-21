<?php

namespace App\Services;

use App\Models\Marca;
use Illuminate\Support\Str;

/**
 * Convierte el .txt que genera InfoEquipo.cmd en valores del formulario de
 * equipos de cómputo del panel. Devuelve solo lo que el archivo sí trae
 * (esos valores reemplazan a los capturados, porque vienen del propio equipo)
 * y avisos de lo que quedó pendiente de revisar a mano.
 */
class InfoEquipoAFormulario
{
    /** Límites de columna del formulario, para no romper la validación al guardar. */
    private const MAXIMOS = [
        'modelo' => 150,
        'numero_serie' => 200,
        'procesador' => 200,
        'ram' => 100,
        'almacenamiento' => 200,
        'usuario_equipo' => 150,
        'windows_key' => 255,
        'office_clave' => 255,
    ];

    public function __construct(
        private readonly InfoEquipoParser $parser,
        private readonly ResponsivaAFormulario $catalogos,
    ) {}

    /**
     * @return array{datos: array<string, mixed>, avisos: array<int, string>}
     */
    public function mapear(string $contenido): array
    {
        $info = $this->parser->parse($contenido);
        $datos = [];
        $avisos = [];

        foreach (self::MAXIMOS as $campo => $maximo) {
            if ($info[$campo] !== null) {
                $datos[$campo] = mb_substr($info[$campo], 0, $maximo);
            }
        }

        if ($info['marca_detectada'] !== null) {
            $datos['marca_detectada'] = mb_substr($info['marca_detectada'], 0, 100);

            $marcaId = $this->catalogos->buscarId(Marca::activas()->deComputo()->pluck('nombre', 'id'), $info['marca_detectada']);

            if ($marcaId === null) {
                $marcaId = Marca::firstOrCreate(['nombre' => 'Otra'], ['categoria' => 'ambas', 'activo' => true])->id;
                $avisos[] = "La marca «{$info['marca_detectada']}» no está en el catálogo; se dejó «Otra». Puedes darla de alta y elegirla.";
            }

            $datos['marca_id'] = $marcaId;
        }

        if ($info['windows_version'] !== null) {
            // "Windows 11 Home Single Language (Version 10.0...)" -> "Windows 11 Home"
            $version = collect(ResponsivaAFormulario::VERSIONES_WINDOWS)
                ->sortByDesc(fn (string $v) => strlen($v))
                ->first(fn (string $v) => str_contains(Str::lower($info['windows_version']), Str::lower($v)));

            if ($version) {
                $datos['windows_version'] = $version;
            } else {
                $avisos[] = "La versión de Windows «{$info['windows_version']}» no está en las opciones; elígela manualmente.";
            }
        }

        // El .txt lo genera el script para Windows.
        if ($datos !== []) {
            $datos['sistema_operativo'] = 'windows';
        }

        return ['datos' => $datos, 'avisos' => $avisos];
    }
}
