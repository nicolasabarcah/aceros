<?php
date_default_timezone_set('America/Santiago');

$projectRoot = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
$logFile = $projectRoot . DIRECTORY_SEPARATOR . 'centinela_reg.log';
$masterLogFile = $projectRoot . DIRECTORY_SEPARATOR . 'centinela_error.log';

if (!file_exists($masterLogFile)) {
    @touch($masterLogFile);
}

@ini_set('log_errors', '1');
@ini_set('error_log', $masterLogFile);

if (!function_exists('centinelaGrupoDesdeRuta')) {
    function centinelaGrupoDesdeRuta($ruta)
    {
        $projectRoot = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
        $ruta = str_replace('\\', '/', trim((string) $ruta));
        $root = str_replace('\\', '/', trim((string) $projectRoot));

        if ($ruta === '' || $root === '') {
            return 'otros';
        }

        if (stripos($ruta, $root) === 0) {
            $relative = ltrim(substr($ruta, strlen($root)), '/');
            if ($relative === '') {
                return 'raiz';
            }

            $parts = explode('/', $relative);
            $first = trim((string) ($parts[0] ?? ''));
            if ($first === '') {
                return 'otros';
            }

            if (strpos($first, '.') !== false) {
                return 'raiz';
            }

            $first = strtolower($first);
            $first = preg_replace('/[^a-z0-9_\-]/', '', $first) ?? '';
            return $first !== '' ? $first : 'otros';
        }

        $segments = array_values(array_filter(explode('/', trim($ruta, '/'))));
        $count = count($segments);
        if ($count >= 2) {
            $candidate = strtolower((string) $segments[$count - 2]);
            $candidate = preg_replace('/[^a-z0-9_\-]/', '', $candidate) ?? '';
            return $candidate !== '' ? $candidate : 'otros';
        }

        return 'otros';
    }
}

if (!function_exists('centinelaNombreError')) {
    function centinelaNombreError($errno)
    {
        $mapa = [
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSE',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE_ERROR',
            E_CORE_WARNING => 'CORE_WARNING',
            E_COMPILE_ERROR => 'COMPILE_ERROR',
            E_COMPILE_WARNING => 'COMPILE_WARNING',
            E_USER_ERROR => 'USER_ERROR',
            E_USER_WARNING => 'USER_WARNING',
            E_USER_NOTICE => 'USER_NOTICE',
            E_STRICT => 'STRICT',
            E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
            E_DEPRECATED => 'DEPRECATED',
            E_USER_DEPRECATED => 'USER_DEPRECATED',
        ];

        return $mapa[$errno] ?? 'ERROR';
    }
}

if (!function_exists('centinelaEscribirMaestro')) {
    function centinelaEscribirMaestro($mensaje, $archivo = '', $linea = 0, $tipo = 'ERROR')
    {
        $projectRoot = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
        $masterLogFile = $projectRoot . DIRECTORY_SEPARATOR . 'centinela_error.log';

        if (!file_exists($masterLogFile)) {
            @touch($masterLogFile);
        }

        $archivo = $archivo ?: ($_SERVER['SCRIPT_FILENAME'] ?? 'desconocido');
        $grupo = centinelaGrupoDesdeRuta($archivo);
        $lineaTexto = $linea ? ':' . (int) $linea : '';
        $registro = '[' . date('Y-m-d H:i:s') . '] [' . $grupo . '] [' . $tipo . '] ' . $archivo . $lineaTexto . ' || ' . trim((string) $mensaje) . PHP_EOL;

        @file_put_contents($masterLogFile, $registro, FILE_APPEND | LOCK_EX);
    }
}

if (!defined('CENTINELA_HANDLERS_REGISTRADOS')) {
    define('CENTINELA_HANDLERS_REGISTRADOS', true);

    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return true;
        }

        centinelaEscribirMaestro($errstr, $errfile, $errline, centinelaNombreError($errno));
        return true;
    });

    set_exception_handler(function ($exception) {
        centinelaEscribirMaestro($exception->getMessage(), $exception->getFile(), $exception->getLine(), 'EXCEPTION');
    });

    register_shutdown_function(function () {
        $error = error_get_last();

        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
            centinelaEscribirMaestro($error['message'] ?? 'Error fatal', $error['file'] ?? '', $error['line'] ?? 0, 'FATAL');
        }
    });
}

$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
$files = [];
$selfFile = realpath(__FILE__);
$conexionFile = realpath(__DIR__ . '/conexion.php');

foreach ($trace as $frame) {
    if (empty($frame['file'])) {
        continue;
    }

    $file = realpath($frame['file']) ?: $frame['file'];

    if ($file === $selfFile || $file === $conexionFile) {
        continue;
    }

    if (!in_array($file, $files, true)) {
        $files[] = $file;
    }
}

if (empty($files) && !empty($_SERVER['SCRIPT_FILENAME'])) {
    $files[] = $_SERVER['SCRIPT_FILENAME'];
}

foreach ($files as $file) {
    $linea = '[' . date('Y-m-d H:i:s') . '] ' . $file . PHP_EOL;
    @file_put_contents($logFile, $linea, FILE_APPEND | LOCK_EX);
}
