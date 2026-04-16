<?php
// Establecer zona horaria a Chile
require_once __DIR__ . DIRECTORY_SEPARATOR . 'centinela-config.php';

$logPath = $CENTINELA_REG_LOG;

function normalizarRutaCentinela($ruta, $projectRoot)
{
	$ruta = trim((string) $ruta);
	if ($ruta === '') {
		return '';
	}

	$ruta = str_replace('\\', '/', $ruta);
	$root = str_replace('\\', '/', trim((string) $projectRoot));

	if ($root !== '' && stripos($ruta, $root) === 0) {
		$ruta = ltrim(substr($ruta, strlen($root)), '/');
	}

	return ltrim((string) $ruta, '/');
}

function formatearFechaCentinela($fecha)
{
	if (empty($fecha)) {
		return '—';
	}

	$timestamp = strtotime((string) $fecha);
	return $timestamp ? date('d/m/Y H:i:s', $timestamp) : (string) $fecha;
}

function obtenerEstadoAntiguedadCentinela($timestamp)
{
	if (empty($timestamp)) {
		return ['color' => 'rojo', 'dias' => 'sin uso'];
	}

	$dias = max(0, floor((time() - (int) $timestamp) / 86400));

	if ($dias <= 90) {
		$color = 'verde';
	} elseif ($dias <= 180) {
		$color = 'amarillo';
	} elseif ($dias <= 365) {
		$color = 'naranjo';
	} else {
		$color = 'rojo';
	}

	return ['color' => $color, 'dias' => $dias . ' días'];
}

function insertarEnArbolCentinela(&$nodo, $partes, $rutaCompleta, $meta)
{
	if (empty($partes)) {
		return;
	}

	$actual = array_shift($partes);

	if (empty($partes)) {
		$nodo['children'][$actual] = [
			'type' => 'file',
			'path' => $rutaCompleta,
			'meta' => $meta,
		];
		return;
	}

	if (!isset($nodo['children'][$actual])) {
		$nodo['children'][$actual] = [
			'type' => 'folder',
			'children' => [],
		];
	}

	insertarEnArbolCentinela($nodo['children'][$actual], $partes, $rutaCompleta, $meta);
}

function renderArbolCentinela($nodo, $nivel = 0)
{
	$html = '';
	$children = $nodo['children'] ?? [];

	uksort($children, function ($a, $b) use ($children) {
		$tipoA = $children[$a]['type'] ?? 'file';
		$tipoB = $children[$b]['type'] ?? 'file';

		if ($tipoA !== $tipoB) {
			return $tipoA === 'file' ? -1 : 1;
		}

		if ($tipoA === 'file') {
			$fechaA = (int) ($children[$a]['meta']['timestamp'] ?? 0);
			$fechaB = (int) ($children[$b]['meta']['timestamp'] ?? 0);
			if ($fechaA !== $fechaB) {
				return $fechaB <=> $fechaA;
			}
		}

		return strnatcasecmp($a, $b);
	});

	foreach ($children as $nombre => $child) {
		$padding = $nivel * 18;

		if (($child['type'] ?? '') === 'folder') {
			$html .= '<div class="tree-row folder" style="padding-left:' . $padding . 'px">';
			$html .= '<span class="tree-name">[' . htmlspecialchars((string) $nombre, ENT_QUOTES, 'UTF-8') . ']</span>';
			$html .= '<span class="tree-meta"></span>';
			$html .= '</div>';
			$html .= renderArbolCentinela($child, $nivel + 1);
			continue;
		}

		$fecha = htmlspecialchars((string) ($child['meta']['fecha_formateada'] ?? '—'), ENT_QUOTES, 'UTF-8');
		$total = (int) ($child['meta']['count'] ?? 0);
		$antiguedad = obtenerEstadoAntiguedadCentinela((int) ($child['meta']['timestamp'] ?? 0));

		$html .= '<div class="tree-row file" style="padding-left:' . $padding . 'px">';
		$html .= '<span class="tree-name">└─ [' . htmlspecialchars((string) $nombre, ENT_QUOTES, 'UTF-8') . ']</span>';
		$html .= '<span class="tree-meta">' . $fecha . ' · ' . $total . ' registros · <span class="edad ' . htmlspecialchars((string) $antiguedad['color'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars((string) $antiguedad['dias'], ENT_QUOTES, 'UTF-8') . '</span></span>';
		$html .= '</div>';
	}

	return $html;
}

if (!file_exists($logPath)) {
	@touch($logPath);
}

$lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$archivosPhp = [];

foreach ($lines as $line) {
	$archivo = trim((string) $line);
	$fecha = '';

	if (preg_match('/^\[(.+?)\]\s*(.+)$/', $line, $matches)) {
		$fecha = trim((string) $matches[1]);
		$archivo = trim((string) $matches[2]);
	}

	if (!preg_match('/\.php$/i', $archivo)) {
		continue;
	}

	$rutaRelativa = normalizarRutaCentinela($archivo, $CENTINELA_PROJECT_ROOT);
	if ($rutaRelativa === '') {
		continue;
	}

	if (!isset($archivosPhp[$rutaRelativa])) {
		$archivosPhp[$rutaRelativa] = [
			'count' => 0,
			'fecha' => '',
			'timestamp' => 0,
		];
	}

	$archivosPhp[$rutaRelativa]['count']++;

	$timestamp = $fecha !== '' ? strtotime($fecha) : 0;
	if ($timestamp && $timestamp >= $archivosPhp[$rutaRelativa]['timestamp']) {
		$archivosPhp[$rutaRelativa]['fecha'] = $fecha;
		$archivosPhp[$rutaRelativa]['timestamp'] = $timestamp;
	}
}

$arbol = ['type' => 'folder', 'children' => []];
foreach ($archivosPhp as $ruta => $meta) {
	$meta['fecha_formateada'] = formatearFechaCentinela($meta['fecha']);
	insertarEnArbolCentinela($arbol, explode('/', $ruta), $ruta, $meta);
}

$totalArchivos = count($archivosPhp);
$totalRegistros = 0;
foreach ($archivosPhp as $meta) {
	$totalRegistros += (int) $meta['count'];
}
?>
<!doctype html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Centinela | Árbol de Directorio</title>
	<style>
		body {
			margin: 0;
			padding: 20px;
			background: #f6f8fb;
			color: #243044;
			font-family: "Segoe UI", Tahoma, Arial, sans-serif;
		}
		.page {
			max-width: 1280px;
			margin: 0 auto;
		}
		.topbar {
			display: flex;
			gap: 10px;
			flex-wrap: wrap;
			margin-bottom: 14px;
		}
		.nav-btn {
			padding: 9px 14px;
			border-radius: 8px;
			text-decoration: none;
			border: 1px solid #d9e2ef;
			background: #fff;
			color: #1d4ed8;
			font-weight: 600;
		}
		.nav-btn.active {
			background: #1d4ed8;
			color: #fff;
			border-color: #1d4ed8;
		}
		.panel {
			background: #fff;
			border: 1px solid #e5eaf1;
			border-radius: 14px;
			box-shadow: 0 6px 22px rgba(15, 23, 42, 0.05);
			overflow: hidden;
		}
		.panel-head {
			padding: 18px 20px 10px;
			border-bottom: 1px solid #edf2f7;
		}
		.panel-head h1 {
			margin: 0 0 6px;
			font-size: 24px;
		}
		.meta {
			display: flex;
			gap: 10px;
			flex-wrap: wrap;
			color: #667085;
			font-size: 13px;
		}
		.badge {
			display: inline-flex;
			align-items: center;
			padding: 4px 9px;
			border-radius: 999px;
			background: #eef4ff;
			color: #1d4ed8;
			font-weight: 600;
		}
		.content {
			padding: 18px 20px 20px;
		}
		.tag {
			display: inline-flex;
			padding: 4px 9px;
			border-radius: 999px;
			background: #fff8dd;
			color: #8a6d1f;
			border: 1px solid #f0d98a;
			font-size: 12px;
			font-weight: 600;
			margin-bottom: 12px;
		}
		.header-row,
		.tree-row {
			display: flex;
			justify-content: space-between;
			align-items: center;
			gap: 12px;
			padding: 8px 0;
		}
		.header-row {
			font-size: 11px;
			color: #667085;
			border-bottom: 1px solid #edf2f7;
			margin-bottom: 6px;
			padding-bottom: 8px;
			text-transform: uppercase;
			letter-spacing: 0.04em;
		}
		.tree-row {
			border-bottom: 1px solid rgba(15, 23, 42, 0.06);
			transition: background-color 0.15s ease;
		}
		.tree-row:hover {
			background-color: #f8fbff;
		}
		.tree-row:last-child {
			border-bottom: none;
		}
		.tree-name {
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
			font-family: Consolas, "Courier New", monospace;
		}
		.folder .tree-name {
			font-weight: 700;
			color: #1d4ed8;
		}
		.file .tree-name {
			color: #243044;
		}
		.tree-meta {
			font-size: 12px;
			color: #667085;
			white-space: nowrap;
			font-family: Consolas, "Courier New", monospace;
		}
		.edad {
			font-weight: 700;
		}
		.edad.verde { color: #16a34a; }
		.edad.amarillo { color: #ca8a04; }
		.edad.naranjo { color: #ea580c; }
		.edad.rojo { color: #dc2626; }
		.empty {
			padding: 18px;
			border: 1px dashed #d7dfeb;
			border-radius: 12px;
			background: #fafcff;
			color: #667085;
		}
	</style>
</head>
<body>
	<div class="page">
		<?php $centinelaMenuActivo = 'directorio'; include __DIR__ . '/menu_centinela.php'; ?>

		<div class="panel">
			<div class="panel-head">
				<h1>Directorio de uso PHP</h1>
				<div class="meta">
				<span class="badge"><?php echo $CENTINELA_ENTORNO_ACTUAL === 'produccion' ? 'PRODUCCIÓN' : 'LOCALHOST'; ?></span>
				<span><?php echo (int) $totalArchivos; ?> archivos</span>
				<span><?php echo (int) $totalRegistros; ?> registros</span>
				<span>Host: <?php echo htmlspecialchars((string) $CENTINELA_HOST_ACTUAL, ENT_QUOTES, 'UTF-8'); ?></span>
				</div>
			</div>

			<div class="content">
				<?php if ($CENTINELA_ENTORNO_ACTUAL === 'produccion'): ?>
					<div class="tag">Entorno: producción</div>
				<?php else: ?>
					<div class="tag">Entorno: localhost</div>
				<?php endif; ?>

				<div class="header-row">
					<span>archivo</span>
					<span>último registro · total</span>
				</div>

				<?php
				if ($totalArchivos === 0) {
					echo '<div class="empty">No se encontraron archivos PHP en el log.</div>';
				} else {
					echo renderArbolCentinela($arbol);
				}
				?>
			</div>
		</div>
	</div>
</body>
</html>
