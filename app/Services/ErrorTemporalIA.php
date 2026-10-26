<?php

namespace App\Services;

use RuntimeException;

/**
 * Falla pasajera de la IA (límite por minuto, servicio saturado o respuesta
 * ilegible): vale la pena conservar el archivo y volver a intentarlo.
 */
class ErrorTemporalIA extends RuntimeException {}
