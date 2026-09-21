<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Marca;
use App\Models\Sucursal;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Convierte lo que devolvió la IA en valores del formulario de Filament:
 * empata sucursal / área / marca contra los catálogos y devuelve solo los
 * campos que sí se encontraron (para no borrar lo que el usuario ya capturó),
 * junto con avisos de lo que quedó pendiente de elegir a mano.
 */
class ResponsivaAFormulario
{
    public const VERSIONES_WINDOWS = [
        'Windows 10 Home', 'Windows 10 Pro', 'Windows 11 Home', 'Windows 11 Pro', 'Windows 11 Enterprise',
    ];

    private const ALIAS = [
        'rh' => 'recursos humanos',
        'rrhh' => 'recursos humanos',
    ];

    /**
     * @param  array<string, mixed>  $d
     * @return array{datos: array<string, mixed>, avisos: array<int, string>}
     */
    public function mapear(array $d, string $tipo): array
    {
        return $tipo === ExtractorResponsivaPdf::TIPO_CELULAR ? $this->celular($d) : $this->computo($d);
    }

    /**
     * @param  array<string, mixed>  $d
     * @return array{datos: array<string, mixed>, avisos: array<int, string>}
     */
    private function computo(array $d): array
    {
        $avisos = [];
        $datos = [];

        $tipos = ['alta', 'baja', 'cambio_equipo', 'reasignacion', 'mantenimiento', 'prestamo_temporal'];
        if (in_array($d['tipo_movimiento'] ?? null, $tipos, true)) {
            $datos['tipo_movimiento'] = $d['tipo_movimiento'];
        }

        $datos['fecha_entrega'] = $this->fecha($d['fecha_movimiento'] ?? null);
        $datos['nombre_usuario'] = $this->limpio($d['nombre_completo'] ?? null);
        $datos['correo_electronico'] = $this->limpio($d['correo_corporativo'] ?? null);
        $datos['ext'] = $this->soloDigitos($d['extension'] ?? null, 5);

        $datos['sucursal_id'] = $this->catalogo(
            Sucursal::where('activo', true)->pluck('nombre', 'id'), $d['sucursal'] ?? null, 'Sucursal', $avisos
        );
        $datos['area_id'] = $this->catalogo(
            Area::where('activo', true)->pluck('nombre', 'id'), $d['area'] ?? null, 'Área', $avisos
        );
        $datos['marca_id'] = $this->catalogo(
            Marca::activas()->deComputo()->pluck('nombre', 'id'), $d['marca'] ?? null, 'Marca', $avisos
        );

        if (in_array($d['tipo_equipo'] ?? null, ['laptop', 'desktop', 'all_in_one', 'workstation', 'mini_pc'], true)) {
            $datos['tipo_equipo'] = $d['tipo_equipo'];
        }

        $datos['modelo'] = $this->limpio($d['modelo'] ?? null);
        $datos['numero_serie'] = $this->limpio($d['numero_serie'] ?? null);
        $datos['procesador'] = $this->limpio($d['procesador'] ?? null);
        $datos['ram'] = $this->capacidad($d['ram'] ?? null);
        $datos['almacenamiento'] = $this->capacidad($d['almacenamiento'] ?? null);

        $so = Str::lower((string) ($d['sistema_operativo'] ?? ''));
        if (str_contains($so, 'windows')) {
            $datos['sistema_operativo'] = 'windows';
            $datos['windows_version'] = collect(self::VERSIONES_WINDOWS)
                ->first(fn ($v) => Str::lower($v) === Str::lower(trim($d['sistema_operativo'])));
        } elseif (str_contains($so, 'mac') || str_contains($so, 'apple')) {
            $datos['sistema_operativo'] = 'apple';
        }

        // Datos que el formulario no tiene como campo: se guardan en observaciones.
        $notas = array_filter([
            $this->limpio($d['observaciones'] ?? null),
            ($p = $this->limpio($d['puesto'] ?? null)) ? "Puesto: {$p}" : null,
            ($t = $this->limpio($d['telefono_directo'] ?? null)) ? "Tel. directo: {$t}" : null,
            ! empty($d['accesorios']) ? 'Accesorios: ' . implode(', ', $d['accesorios']) : null,
        ]);
        $datos['observaciones'] = $notas ? implode("\n", $notas) : null;

        $datos['usuario_referencia'] = $datos['correo_electronico']
            ?? ($datos['nombre_usuario'] ? Str::lower(str_replace(' ', '.', $datos['nombre_usuario'])) : null);

        return ['datos' => $this->sinVacios($datos), 'avisos' => $avisos];
    }

    /**
     * @param  array<string, mixed>  $d
     * @return array{datos: array<string, mixed>, avisos: array<int, string>}
     */
    private function celular(array $d): array
    {
        $avisos = [];
        $datos = [];

        $datos['fecha_entrega'] = $this->fecha($d['fecha'] ?? null);
        $datos['nombre_usuario'] = $this->limpio($d['nombre_colaborador'] ?? null);
        $datos['numero_telefonico'] = $this->limpio($d['telefono'] ?? null, 20);
        $datos['curp'] = ($c = $this->limpio($d['curp'] ?? null, 20)) ? Str::upper($c) : null;

        // "Puesto / Área" viene en un solo campo: si coincide con un área se usa
        // como área, si no se guarda como puesto.
        $puestoArea = $this->limpio($d['puesto_area'] ?? null);
        $areaId = $puestoArea ? $this->buscarId(Area::where('activo', true)->pluck('nombre', 'id'), $puestoArea) : null;
        if ($areaId) {
            $datos['area_id'] = $areaId;
        } else {
            $datos['puesto'] = $puestoArea;
        }

        $datos['sucursal_id'] = $this->catalogo(
            Sucursal::where('activo', true)->pluck('nombre', 'id'), $d['sucursal'] ?? null, 'Sucursal', $avisos
        );
        $datos['marca_id'] = $this->catalogo(
            Marca::activas()->deCelular()->pluck('nombre', 'id'), $d['marca'] ?? null, 'Marca', $avisos
        );

        if (in_array($d['tipo_equipo'] ?? null, ['celular', 'tablet', 'ipad', 'otro'], true)) {
            $datos['tipo_equipo'] = $d['tipo_equipo'];
        }

        $datos['modelo'] = $this->limpio($d['modelo'] ?? null);
        $datos['imei'] = $this->limpio($d['imei'] ?? null, 20);
        $datos['iccid'] = $this->limpio($d['iccid'] ?? null, 22);
        $datos['observaciones'] = $this->limpio($d['observaciones'] ?? null);

        if ($datos['nombre_usuario']) {
            $datos['usuario_referencia'] = Str::lower(str_replace(' ', '.', $datos['nombre_usuario']));
        }

        return ['datos' => $this->sinVacios($datos), 'avisos' => $avisos];
    }

    /**
     * Devuelve el id del catálogo que coincide con el texto; si no hay
     * coincidencia agrega un aviso para que se elija a mano.
     *
     * @param  Collection<int, string>  $opciones
     * @param  array<int, string>  $avisos
     */
    private function catalogo(Collection $opciones, mixed $texto, string $etiqueta, array &$avisos): ?int
    {
        $texto = $this->limpio($texto);

        if ($texto === null) {
            return null;
        }

        $id = $this->buscarId($opciones, $texto);

        if ($id === null) {
            $avisos[] = "{$etiqueta} «{$texto}» no está en el catálogo, elígela manualmente.";
        }

        return $id;
    }

    /**
     * @param  Collection<int, string>  $opciones
     */
    public function buscarId(Collection $opciones, string $texto): ?int
    {
        $buscado = $this->normalizar($texto);

        foreach ($opciones as $id => $nombre) {
            if ($this->normalizar($nombre) === $buscado) {
                return (int) $id;
            }
        }

        foreach ($opciones as $id => $nombre) {
            $n = $this->normalizar($nombre);

            if ($n !== '' && (str_contains($n, $buscado) || str_contains($buscado, $n))) {
                return (int) $id;
            }
        }

        // Abreviaturas comunes (ej. "RH" = Recursos Humanos).
        $alias = self::ALIAS[preg_replace('/[^a-z]/', '', $buscado)] ?? null;
        if ($alias) {
            foreach ($opciones as $id => $nombre) {
                if ($this->normalizar($nombre) === $alias) {
                    return (int) $id;
                }
            }
        }

        // Errores de dedo (ej. "Costituyentes"): se compara sin números y
        // se tolera una diferencia pequeña, solo con textos de cierto largo.
        if (mb_strlen($buscado) >= 6) {
            foreach ($opciones as $id => $nombre) {
                $n = trim(preg_replace('/\d+/', '', $this->normalizar($nombre)));

                if ($n !== '' && levenshtein($buscado, $n) <= max(1, intdiv(mb_strlen($n), 8))) {
                    return (int) $id;
                }
            }
        }

        return null;
    }

    private function normalizar(string $valor): string
    {
        return trim(preg_replace('/\s+/', ' ', Str::lower(Str::ascii($valor))));
    }

    private function limpio(mixed $valor, ?int $max = null): ?string
    {
        if (! is_string($valor) || trim($valor) === '') {
            return null;
        }

        $valor = trim($valor);

        return $max ? mb_substr($valor, 0, $max) : $valor;
    }

    /**
     * "16gb" / "512 gb" -> "16 GB" / "512 GB" (mismo formato que el resto del
     * sistema). Si trae otra cosa, se deja tal cual está escrita.
     */
    private function capacidad(mixed $valor): ?string
    {
        $texto = $this->limpio($valor);

        if ($texto !== null && preg_match('/^(\d+(?:[.,]\d+)?)\s*(gb|tb|mb)$/i', $texto, $m)) {
            return str_replace(',', '.', $m[1]) . ' ' . Str::upper($m[2]);
        }

        return $texto;
    }

    private function soloDigitos(mixed $valor, int $max): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) $valor);

        return $digitos === '' ? null : substr($digitos, 0, $max);
    }

    private function fecha(mixed $valor): ?string
    {
        if (! is_string($valor) || trim($valor) === '') {
            return null;
        }

        try {
            return Carbon::parse($valor)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private function sinVacios(array $datos): array
    {
        return array_filter($datos, fn ($v) => $v !== null && $v !== '');
    }
}
