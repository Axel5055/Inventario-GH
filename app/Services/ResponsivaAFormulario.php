<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Marca;
use App\Models\RazonSocial;
use App\Models\Sucursal;
use App\Models\TipoDispositivo;
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

    /** @var array<string, Collection<int, string>> catálogos ya consultados (una carga masiva los pide por cada fila) */
    private array $catalogos = [];

    /**
     * @param  \Closure(): Collection<int, string>  $consulta
     * @return Collection<int, string>
     */
    private function catalogoDe(string $clave, \Closure $consulta): Collection
    {
        return $this->catalogos[$clave] ??= $consulta();
    }

    /** @return Collection<int, string> */
    private function sucursales(): Collection
    {
        return $this->catalogoDe('sucursales', fn () => Sucursal::where('activo', true)->pluck('nombre', 'id'));
    }

    /** @return Collection<int, string> */
    private function razonesSociales(): Collection
    {
        return $this->catalogoDe('razones_sociales', fn () => RazonSocial::where('activo', true)->pluck('nombre', 'id'));
    }

    /** @return Collection<int, string> */
    private function areas(): Collection
    {
        return $this->catalogoDe('areas', fn () => Area::where('activo', true)->pluck('nombre', 'id'));
    }

    /**
     * @param  array<string, mixed>  $d
     * @return array{datos: array<string, mixed>, avisos: array<int, string>}
     */
    public function mapear(array $d, string $tipo): array
    {
        return match ($tipo) {
            ExtractorResponsivaPdf::TIPO_CELULAR => $this->celular($d),
            ExtractorResponsivaPdf::TIPO_DISPOSITIVOS => $this->dispositivos($d),
            default => $this->computo($d),
        };
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
        $this->revisarCorreo($datos['correo_electronico'], $avisos);
        $datos['ext'] = $this->soloDigitos($d['extension'] ?? null, 5);

        $datos['sucursal_id'] = $this->catalogo(
            $this->sucursales(), $d['sucursal'] ?? null, 'Sucursal', $avisos
        );
        $datos['area_id'] = $this->catalogo(
            $this->areas(), $d['area'] ?? null, 'Área', $avisos
        );
        $datos['marca_id'] = $this->catalogo(
            $this->catalogoDe('marcas_computo', fn () => Marca::activas()->deComputo()->pluck('nombre', 'id')), $d['marca'] ?? null, 'Marca', $avisos
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

        if (in_array($d['tipo_movimiento'] ?? null, ['alta', 'baja', 'cambio_equipo', 'reasignacion', 'mantenimiento', 'prestamo_temporal'], true)) {
            $datos['tipo_movimiento'] = $d['tipo_movimiento'];
        }

        $datos['fecha_entrega'] = $this->fecha($d['fecha'] ?? null);
        $datos['nombre_usuario'] = $this->limpio($d['nombre_colaborador'] ?? null);
        $datos['numero_telefonico'] = $this->limpio($d['telefono'] ?? null, 20);
        $datos['curp'] = ($c = $this->limpio($d['curp'] ?? null, 20)) ? Str::upper($c) : null;

        $this->revisarTelefono($datos['numero_telefonico'], $avisos);
        $this->revisarCurp($datos['curp'], $avisos);

        // "Puesto / Área" viene en un solo campo: si coincide con un área se usa
        // como área; si no, como el formulario ya no tiene puesto, se anota en observaciones.
        $puestoArea = $this->limpio($d['puesto_area'] ?? null);
        $areaId = $puestoArea ? $this->buscarId($this->areas(), $puestoArea) : null;
        $notaPuesto = null;
        if ($areaId) {
            $datos['area_id'] = $areaId;
        } elseif ($puestoArea) {
            $notaPuesto = "Puesto / Área: {$puestoArea}";
        }

        $datos['sucursal_id'] = $this->catalogo(
            $this->sucursales(), $d['sucursal'] ?? null, 'Sucursal', $avisos
        );
        $datos['marca_id'] = $this->catalogo(
            $this->catalogoDe('marcas_celular', fn () => Marca::activas()->deCelular()->pluck('nombre', 'id')), $d['marca'] ?? null, 'Marca', $avisos
        );

        if (in_array($d['tipo_equipo'] ?? null, ['celular', 'tablet', 'ipad', 'otro'], true)) {
            $datos['tipo_equipo'] = $d['tipo_equipo'];
        }

        $datos['modelo'] = $this->limpio($d['modelo'] ?? null);
        $datos['imei'] = $this->imei($d['imei'] ?? null, $avisos);
        $datos['iccid'] = $this->limpio($d['iccid'] ?? null, 22);
        $notas = array_filter([$this->limpio($d['observaciones'] ?? null), $notaPuesto]);
        $datos['observaciones'] = $notas ? implode("\n", $notas) : null;

        if ($datos['nombre_usuario']) {
            $datos['usuario_referencia'] = Str::lower(str_replace(' ', '.', $datos['nombre_usuario']));
        }

        return ['datos' => $this->sinVacios($datos), 'avisos' => $avisos];
    }

    /**
     * Dispositivos Externos: el tipo se empata con el catálogo editable de tipos
     * (si no existe cae en «Otro») y la marca, que es texto libre, se normaliza al
     * nombre del catálogo cuando coincide.
     *
     * @param  array<string, mixed>  $d
     * @return array{datos: array<string, mixed>, avisos: array<int, string>}
     */
    private function dispositivos(array $d): array
    {
        $avisos = [];
        $datos = [];

        if (in_array($d['tipo_movimiento'] ?? null, ['entrega', 'devolucion'], true)) {
            $datos['tipo_movimiento'] = $d['tipo_movimiento'];
        }

        $fecha = $this->fecha($d['fecha'] ?? null);
        $datos['fecha_entrega'] = $fecha ? substr($fecha, 0, 10) : null;
        $datos['nombre_usuario'] = $this->limpio($d['nombre_completo'] ?? null);
        $datos['correo_electronico'] = $this->limpio($d['correo'] ?? null);
        $this->revisarCorreo($datos['correo_electronico'], $avisos);

        $datos['razon_social_id'] = $this->catalogo(
            $this->razonesSociales(), $d['razon_social'] ?? null, 'Razón social', $avisos
        );
        $datos['sucursal_id'] = $this->catalogo(
            $this->sucursales(), $d['sucursal'] ?? null, 'Sucursal', $avisos
        );
        $datos['area_id'] = $this->catalogo(
            $this->areas(), $d['area'] ?? null, 'Área', $avisos
        );

        if ($tipoTexto = $this->limpio($d['tipo_dispositivo'] ?? null)) {
            $tipos = $this->catalogoDe('tipos', fn () => TipoDispositivo::where('activo', true)->pluck('nombre', 'id'));
            $tipoId = $this->buscarId($tipos, $tipoTexto);

            if ($tipoId) {
                $datos['tipo_dispositivo'] = $tipos[$tipoId];
            } else {
                $datos['tipo_dispositivo'] = 'Otro';
                $avisos[] = "Tipo de dispositivo «{$tipoTexto}» no está en el catálogo; se usó «Otro».";
            }
        }

        $marcaTexto = $this->limpio($d['marca'] ?? null, 100);
        $marcas = $this->catalogoDe('marcas_todas', fn () => Marca::activas()->pluck('nombre', 'id'));
        $marcaId = $marcaTexto ? $this->buscarId($marcas, $marcaTexto) : null;
        $datos['marca'] = $marcaId ? $marcas[$marcaId] : $marcaTexto;

        $datos['modelo'] = $this->limpio($d['modelo'] ?? null, 150);
        $datos['numero_serie'] = $this->limpio($d['numero_serie'] ?? null, 200);
        $datos['cantidad'] = $this->cantidad($d['cantidad'] ?? null);
        $datos['observaciones'] = $this->limpio($d['observaciones'] ?? null);

        $descripcion = $this->limpio($d['descripcion'] ?? null, 255);
        $datos['descripcion'] = $descripcion ?: Str::limit(trim(implode(' ', array_filter([$datos['tipo_dispositivo'] ?? null, $datos['marca'], $datos['modelo']]))), 250, '');

        $datos['usuario_referencia'] = $datos['correo_electronico']
            ?? ($datos['nombre_usuario'] ? Str::lower(str_replace(' ', '.', $datos['nombre_usuario'])) : null);

        return ['datos' => $this->sinVacios($datos), 'avisos' => $avisos];
    }

    /**
     * Devuelve el id del catálogo que coincide con el texto; si no hay
     * coincidencia (o hay varias igual de probables) agrega un aviso para que se
     * elija a mano.
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

        ['id' => $id, 'ambiguas' => $ambiguas] = $this->coincidencia($opciones, $texto);

        if ($id === null) {
            $avisos[] = $ambiguas
                ? "{$etiqueta} «{$texto}» podría ser " . implode(' o ', array_map(fn ($n) => "«{$n}»", $ambiguas)) . ', elígela manualmente.'
                : "{$etiqueta} «{$texto}» no está en el catálogo, elígela manualmente.";
        }

        return $id;
    }

    /**
     * @param  Collection<int, string>  $opciones
     */
    public function buscarId(Collection $opciones, string $texto): ?int
    {
        return $this->coincidencia($opciones, $texto)['id'];
    }

    /**
     * Busca el texto en las opciones del catálogo, de lo más seguro a lo más flexible:
     * nombre exacto, abreviatura conocida, una palabra completa dentro del otro texto
     * y, por último, un error de dedo pequeño. Si varias opciones son igual de
     * probables no se elige ninguna (se devuelven en "ambiguas").
     *
     * @param  Collection<int, string>  $opciones
     * @return array{id: ?int, ambiguas: array<int, string>}
     */
    public function coincidencia(Collection $opciones, string $texto): array
    {
        $buscado = $this->normalizar($texto);

        if ($buscado === '') {
            return ['id' => null, 'ambiguas' => []];
        }

        $normalizadas = $opciones->map(fn ($nombre) => $this->normalizar($nombre));

        foreach ($normalizadas as $id => $n) {
            if ($n === $buscado) {
                return ['id' => (int) $id, 'ambiguas' => []];
            }
        }

        // Abreviaturas comunes (ej. "RH" = Recursos Humanos).
        $alias = self::ALIAS[preg_replace('/[^a-z]/', '', $buscado)] ?? null;
        if ($alias) {
            foreach ($normalizadas as $id => $n) {
                if ($n === $alias) {
                    return ['id' => (int) $id, 'ambiguas' => []];
                }
            }
        }

        // Una palabra (o frase) completa dentro de la otra: "HP Inc" ~ "HP". Se exigen
        // límites de palabra para que "TI" no coincida con "Logística".
        $candidatos = [];
        foreach ($normalizadas as $id => $n) {
            if ($n === '') {
                continue;
            }

            [$corto, $largo] = mb_strlen($n) <= mb_strlen($buscado) ? [$n, $buscado] : [$buscado, $n];

            if (mb_strlen($corto) >= 2 && preg_match('/(?<![a-z0-9])' . preg_quote($corto, '/') . '(?![a-z0-9])/', $largo)) {
                $candidatos[$id] = mb_strlen($corto) / mb_strlen($largo);
            }
        }

        if ($candidatos !== []) {
            return $this->elegir($candidatos, $opciones, mayor: true);
        }

        // Errores de dedo (ej. "Costituyentes"): se tolera una diferencia pequeña, solo
        // con textos de cierto largo. Si lo escrito no trae número se compara contra el
        // nombre sin su número ("Constituyentes" ~ "Constituyentes 1080"); si lo trae,
        // el número debe coincidir ("Polanco 3" no se confunde con "Polanco 2").
        if (mb_strlen($buscado) >= 6) {
            $conNumero = (bool) preg_match('/\d/', $buscado);

            foreach ($normalizadas as $id => $nombre) {
                $n = $conNumero ? $nombre : trim(preg_replace('/\d+/', '', $nombre));
                $distancia = $n !== '' ? levenshtein($buscado, $n) : null;

                if ($distancia !== null && $distancia <= max(1, intdiv(mb_strlen($n), 8))) {
                    $candidatos[$id] = $distancia;
                }
            }

            if ($candidatos !== []) {
                return $this->elegir($candidatos, $opciones, mayor: false);
            }
        }

        return ['id' => null, 'ambiguas' => []];
    }

    /**
     * Se queda con la mejor puntuación; si hay empate, no elige.
     *
     * @param  array<int, float|int>  $puntos  id => puntuación
     * @param  Collection<int, string>  $opciones
     * @return array{id: ?int, ambiguas: array<int, string>}
     */
    private function elegir(array $puntos, Collection $opciones, bool $mayor): array
    {
        $mayor ? arsort($puntos) : asort($puntos);

        $mejor = array_key_first($puntos);
        $empatadas = array_keys(array_filter($puntos, fn ($p) => $p === $puntos[$mejor]));

        if (count($empatadas) === 1) {
            return ['id' => (int) $mejor, 'ambiguas' => []];
        }

        return ['id' => null, 'ambiguas' => array_map(fn ($id) => $opciones[$id], $empatadas)];
    }

    /**
     * IMEI con solo dígitos cuando son 15. Si no tiene 15 dígitos o no pasa el dígito
     * verificador (Luhn) se conserva lo leído y se avisa: lo más probable es un
     * carácter mal leído del escaneo.
     *
     * @param  array<int, string>  $avisos
     */
    private function imei(mixed $valor, array &$avisos): ?string
    {
        $imei = $this->limpio($valor, 30);

        if ($imei === null) {
            return null;
        }

        $digitos = preg_replace('/\D/', '', $imei);

        if (strlen($digitos) !== 15) {
            $avisos[] = "IMEI «{$imei}» no tiene 15 dígitos; revísalo contra el documento.";

            return mb_substr($imei, 0, 20);
        }

        $suma = 0;
        foreach (str_split(strrev($digitos)) as $i => $digito) {
            $n = (int) $digito * ($i % 2 === 1 ? 2 : 1);
            $suma += $n > 9 ? $n - 9 : $n;
        }

        if ($suma % 10 !== 0) {
            $avisos[] = "IMEI «{$imei}» no pasa la validación del dígito verificador; revisa que se haya leído bien.";
        }

        return $digitos;
    }

    /** @param  array<int, string>  $avisos */
    private function revisarTelefono(?string $telefono, array &$avisos): void
    {
        if ($telefono === null) {
            return;
        }

        $digitos = preg_replace('/\D/', '', $telefono);
        $digitos = preg_replace('/^(?:521|52)(?=\d{10}$)/', '', $digitos);

        if (strlen($digitos) !== 10) {
            $avisos[] = "Teléfono «{$telefono}» no tiene 10 dígitos; revísalo.";
        }
    }

    /** @param  array<int, string>  $avisos */
    private function revisarCurp(?string $curp, array &$avisos): void
    {
        if ($curp !== null && ! preg_match('/^[A-Z]{4}\d{6}[HMX][A-Z]{2}[A-Z]{3}[A-Z0-9]\d$/', $curp)) {
            $avisos[] = "CURP «{$curp}» no tiene el formato esperado (18 caracteres); revísala.";
        }
    }

    /** @param  array<int, string>  $avisos */
    private function revisarCorreo(?string $correo, array &$avisos): void
    {
        if ($correo !== null && ! filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $avisos[] = "Correo «{$correo}» no parece válido; revísalo.";
        }
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
     * Piezas entregadas: entero de 1 en adelante (vacío o inválido = 1).
     */
    private function cantidad(mixed $valor): int
    {
        $texto = trim((string) $valor);

        if (is_numeric($texto)) {
            return max(1, (int) floor((float) $texto));
        }

        return max(1, preg_match('/\d+/', $texto, $m) ? (int) $m[0] : 1);
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
