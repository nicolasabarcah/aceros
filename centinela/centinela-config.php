<?php
/**
 * centinela-config.php
 * Configuración centralizada para el sistema Centinela
 * Permite portabilidad entre proyectos y evita duplicación de código
 */

date_default_timezone_set('America/Santiago');

// ===========================
// CONFIGURACIÓN BASE
// ===========================
$CENTINELA_PROJECT_ROOT = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..') ?: dirname(__DIR__);
$CENTINELA_REG_LOG = $CENTINELA_PROJECT_ROOT . DIRECTORY_SEPARATOR . 'centinela_reg.log';
$CENTINELA_ERROR_LOG = $CENTINELA_PROJECT_ROOT . DIRECTORY_SEPARATOR . 'centinela_error.log';

// Crear logs si no existen
if (!file_exists($CENTINELA_REG_LOG)) {
	@touch($CENTINELA_REG_LOG);
}
if (!file_exists($CENTINELA_ERROR_LOG)) {
	@touch($CENTINELA_ERROR_LOG);
}

@ini_set('log_errors', '1');
@ini_set('error_log', $CENTINELA_ERROR_LOG);

// ===========================
// DETECCIÓN DE ENTORNO
// ===========================
$CENTINELA_HOST_ACTUAL = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
$CENTINELA_ENTORNO_ACTUAL = in_array($_SERVER['SERVER_NAME'] ?? 'localhost', ['localhost', '127.0.0.1', '::1'], true)
	? 'localhost'
	: 'produccion';

// ===========================
// FUNCIONES COMPARTIDAS
// ===========================

if (!function_exists('centinelaFormatearBytes')) {
	function centinelaFormatearBytes($bytes)
	{
		$bytes = (float) $bytes;
		if ($bytes <= 0) {
			return '0 B';
		}
		$units = ['B', 'KB', 'MB', 'GB', 'TB'];
		$pow = (int) floor(log($bytes, 1024));
		$pow = max(0, min($pow, count($units) - 1));
		$bytes /= pow(1024, $pow);
		return number_format($bytes, $pow === 0 ? 0 : 2, ',', '.') . ' ' . $units[$pow];
	}
}

if (!function_exists('centinelaContarLineas')) {
	function centinelaContarLineas($ruta)
	{
		if (!file_exists($ruta) || !is_readable($ruta)) {
			return 0;
		}
		$handle = @fopen($ruta, 'r');
		if (!$handle) {
			return 0;
		}
		$count = 0;
		while (!feof($handle)) {
			$line = fgets($handle);
			if ($line !== false && trim($line) !== '') {
				$count++;
			}
		}
		fclose($handle);
		return $count;
	}
}

if (!function_exists('centinelaFechaArchivo')) {
	function centinelaFechaArchivo($ruta)
	{
		if (!file_exists($ruta)) {
			return 'Sin registros';
		}
		$mtime = @filemtime($ruta);
		if (!$mtime) {
			return 'Sin registros';
		}
		return date('d/m/Y H:i', $mtime);
	}
}

if (!function_exists('centinelaDetectarAreaDesdeRuta')) {
	function centinelaDetectarAreaDesdeRuta($ruta, $projectRoot)
	{
		$ruta = str_replace('\\', '/', trim((string) $ruta));
		$root = str_replace('\\', '/', trim((string) $projectRoot));

		if ($ruta === '' || $root === '') {
			return 'otros';
		}

		if (strpos($ruta, $root) === 0) {
			$relative = ltrim(substr($ruta, strlen($root)), '/');
			if ($relative === '') {
				return 'raiz';
			}

			$parts = explode('/', $relative);
			$first = strtolower(trim((string) ($parts[0] ?? '')));
			if ($first === '') {
				return 'otros';
			}

			if (strpos($first, '.') !== false) {
				return 'raiz';
			}

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

if (!function_exists('centinelaNormalizarArea')) {
	function centinelaNormalizarArea($area)
	{
		$area = strtolower(trim((string) $area));
		$area = preg_replace('/[^a-z0-9_\-]/', '', $area) ?? '';
		return $area !== '' ? $area : 'otros';
	}
}

if (!function_exists('centinelaFormatearAreaNombre')) {
	function centinelaFormatearAreaNombre($area)
	{
		return htmlspecialchars(strtoupper(str_replace(['_', '-'], ' ', (string) $area)), ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('centinelaMesCorto')) {
	function centinelaMesCorto($numeroMes)
	{
		$meses = [
			1 => 'ENE',
			2 => 'FEB',
			3 => 'MAR',
			4 => 'ABR',
			5 => 'MAY',
			6 => 'JUN',
			7 => 'JUL',
			8 => 'AGO',
			9 => 'SEP',
			10 => 'OCT',
			11 => 'NOV',
			12 => 'DIC',
		];
		return $meses[(int) $numeroMes] ?? '---';
	}
}

if (!function_exists('centinelaInicializarSemanas')) {
	function centinelaInicializarSemanas($inicioSemanaActual = null)
	{
		if ($inicioSemanaActual === null) {
			$inicioSemanaActual = new DateTime('monday this week');
			$inicioSemanaActual->setTime(0, 0, 0);
		}

		$semanas = [];
		for ($i = 51; $i >= 0; $i--) {
			$inicio = clone $inicioSemanaActual;
			$inicio->modify('-' . $i . ' weeks');
			$fin = clone $inicio;
			$fin->modify('+6 days');

			$clave = $inicio->format('o-W');
			$labelSemana = $inicio->format('d') . ' ' . centinelaMesCorto($inicio->format('n')) . ' ' . $inicio->format('y');
			$rangoFin = $fin->format('d') . ' ' . centinelaMesCorto($fin->format('n')) . ' ' . $fin->format('y');

			$semanas[$clave] = [
				'label' => $labelSemana,
				'rango' => $labelSemana . ' al ' . $rangoFin,
				'count' => 0,
			];
		}
		return $semanas;
	}
}

if (!function_exists('centinelaFormatearFecha')) {
	function centinelaFormatearFecha($fecha)
	{
		if (empty($fecha)) {
			return '—';
		}
		$timestamp = strtotime((string) $fecha);
		return $timestamp ? date('d/m/Y H:i:s', $timestamp) : (string) $fecha;
	}
}

if (!function_exists('centinelaObtenerEstadoAntiguedad')) {
	function centinelaObtenerEstadoAntiguedad($timestamp)
	{
		if (empty($timestamp)) {
			return ['color' => 'rojo', 'dias' => 'sin uso'];
		}

		$fecha = strtotime((string) $timestamp);
		if (!$fecha) {
			return ['color' => 'rojo', 'dias' => 'error'];
		}

		$ahora = time();
		$diferencia = $ahora - $fecha;
		$dias = (int) floor($diferencia / 86400);

		if ($dias === 0) {
			return ['color' => 'verde', 'dias' => 'hoy'];
		} elseif ($dias === 1) {
			return ['color' => 'verde', 'dias' => 'ayer'];
		} elseif ($dias < 7) {
			return ['color' => 'amarillo', 'dias' => $dias . ' días'];
		} elseif ($dias < 30) {
			$semanas = (int) floor($dias / 7);
			return ['color' => 'naranja', 'dias' => $semanas . ' sem'];
		} else {
			$meses = (int) floor($dias / 30);
			return ['color' => 'rojo', 'dias' => $meses . ' meses'];
		}
	}
}
