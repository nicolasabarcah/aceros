<?php
// Ver últimos logs de error - Compatible con localhost y producción
require_once __DIR__ . DIRECTORY_SEPARATOR . 'centinela-config.php';

function normalizarGrupoMaestro($grupo)
{
    $grupo = strtolower(trim((string) $grupo));
    $grupo = preg_replace('/[^a-z0-9_\-]/', '', $grupo) ?? '';
    return $grupo !== '' ? $grupo : 'otros';
}

function inferirGrupoMaestro($path, $projectRoot)
{
    $path = str_replace('\\', '/', trim((string) $path));
    $root = str_replace('\\', '/', trim((string) $projectRoot));

    if ($path === '' || $root === '') {
        return 'otros';
    }

    if (strpos($path, $root) === 0) {
        $relative = ltrim(substr($path, strlen($root)), '/');
        if ($relative === '') {
            return 'raiz';
        }

        $parts = explode('/', $relative);
        $first = trim((string) ($parts[0] ?? ''));
        if ($first === '' || strpos($first, '.') !== false) {
            return 'raiz';
        }

        return normalizarGrupoMaestro($first);
    }

    $segments = array_values(array_filter(explode('/', trim($path, '/'))));
    $count = count($segments);
    if ($count >= 2) {
        return normalizarGrupoMaestro($segments[$count - 2]);
    }

    return 'otros';
}

function tituloGrupoMaestro($grupo)
{
    if ((string) $grupo === 'raiz') {
        return 'Raiz';
    }

    return ucwords(str_replace('_', ' ', (string) $grupo));
}

function extraerRutaMaestro($line)
{
    if (preg_match('~((?:[A-Za-z]:[\\/]|/)[^|\r\n]+?\.php)(?::\d+)?~i', (string) $line, $matches)) {
        return trim((string) $matches[1]);
    }

    return 'sin_ruta';
}

$logs_maestro = [];

$max_errors = 300;
$dias_atras = 15;
$fecha_limite = time() - ($dias_atras * 24 * 60 * 60);

if (file_exists($CENTINELA_ERROR_LOG) && is_readable($CENTINELA_ERROR_LOG)) {
    $master_lines = file($CENTINELA_ERROR_LOG, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $contador = 0;

    for ($i = count($master_lines) - 1; $i >= 0 && $contador < $max_errors; $i--) {
        $master_line = trim((string) $master_lines[$i]);
        if ($master_line === '') {
            continue;
        }

        $grupo = 'otros';
        $path = 'sin_ruta';
        $contenido = $master_line . PHP_EOL;
        $timestamp = 0;

        if (preg_match('/^\[(.+?)\]\s+\[([^\]]+)\](?:\s+\[(.+?)\])?\s+(.+?)\s+\|\|\s+(.*)$/i', $master_line, $matches)) {
            $timestamp = strtotime((string) $matches[1]) ?: 0;
            $grupo = normalizarGrupoMaestro((string) $matches[2]);
            $path = trim((string) $matches[4]);
            $contenido = '[' . $matches[1] . '] ' . trim((string) $matches[5]) . PHP_EOL;
        } else {
            if (preg_match('/^\[(.+?)\]/', $master_line, $fechaMatch)) {
                $timestamp = strtotime((string) $fechaMatch[1]) ?: 0;
            }
            $path = extraerRutaMaestro($master_line);
            $grupo = inferirGrupoMaestro($path, $CENTINELA_PROJECT_ROOT);
        }

        if ($timestamp > 0 && $timestamp < $fecha_limite) {
            continue;
        }

        if (!isset($logs_maestro[$grupo])) {
            $logs_maestro[$grupo] = [];
        }

        if (!isset($logs_maestro[$grupo][$path])) {
            $logs_maestro[$grupo][$path] = [];
        }

        $logs_maestro[$grupo][$path][] = $contenido;
        $contador++;
    }

    foreach ($logs_maestro as $grupo => $archivos) {
        foreach ($archivos as $path => $lines) {
            $logs_maestro[$grupo][$path] = array_reverse($lines);
        }
    }
}

$orden_grupos = array_keys($logs_maestro);
sort($orden_grupos, SORT_NATURAL | SORT_FLAG_CASE);
if (in_array('otros', $orden_grupos, true)) {
    $orden_grupos = array_values(array_diff($orden_grupos, ['otros']));
    $orden_grupos[] = 'otros';
}

if (empty($orden_grupos)) {
    $orden_grupos = ['otros'];
    $logs_maestro['otros'] = [];
}

$totales_grupo = [];

foreach ($orden_grupos as $nombre_grupo) {
    if (!isset($totales_grupo[$nombre_grupo])) {
        $totales_grupo[$nombre_grupo] = 0;
    }

    foreach (($logs_maestro[$nombre_grupo] ?? []) as $path => $lines) {
        $totales_grupo[$nombre_grupo] += count($lines);
    }
}

$hay_logs = array_sum($totales_grupo) > 0;
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centinela | Logs</title>
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

        .toolbar {
            padding: 14px 20px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            border-bottom: 1px solid #edf2f7;
        }

        .filter-btn {
            border: 1px solid #d8dee9;
            background: #fff;
            color: #334155;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .filter-btn.active {
            background: #1d4ed8;
            color: #fff;
            border-color: #1d4ed8;
        }

        .content {
            padding: 18px 20px 20px;
        }

        .grupo-logs {
            margin-bottom: 20px;
        }

        .grupo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .grupo-header h2 {
            margin: 0;
            font-size: 18px;
        }

        .grupo-total {
            font-size: 13px;
            color: #667085;
        }

        .log-card {
            border: 1px solid #e7edf5;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 12px;
            background: #fbfdff;
        }

        .log-card-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            background: #f8fbff;
            border-bottom: 1px solid #e7edf5;
        }

        .log-path {
            font-weight: 600;
            color: #243044;
            word-break: break-all;
        }

        .log-count {
            white-space: nowrap;
            color: #475467;
            font-size: 13px;
        }

        .log-pre {
            margin: 0;
            padding: 14px;
            max-height: 300px;
            overflow: auto;
            background: #fcfcfd;
            color: #1f2937;
            font-family: Consolas, "Courier New", monospace;
            font-size: 12px;
            line-height: 1.5;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .empty {
            padding: 18px;
            border: 1px dashed #d7dfeb;
            border-radius: 12px;
            background: #fafcff;
            color: #667085;
        }

        .hidden {
            display: none;
        }
    </style>
</head>

<body>
    <div class="page">
        <?php $centinelaMenuActivo = 'logs'; include __DIR__ . '/menu_centinela.php'; ?>

        <div class="panel">
            <div class="panel-head">
                <h1>Logs de error</h1>
                <div class="meta">
                    <span class="badge"><?php echo $CENTINELA_ENTORNO_ACTUAL === 'localhost' ? 'LOCALHOST' : 'PRODUCCIÓN'; ?></span>
                    <span>SO: <?php echo stripos(PHP_OS_FAMILY, 'win') !== false ? 'Windows' : 'Linux'; ?></span>
                    <span>PHP: <?php echo htmlspecialchars((string) phpversion(), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span>Período: últimos <?php echo (int) $dias_atras; ?> días</span>
                    <span>Máximo: <?php echo (int) $max_errors; ?> por archivo</span>
                    <span>Archivo maestro: <?php echo htmlspecialchars((string) $CENTINELA_ERROR_LOG, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>

            <div class="toolbar">
                <button class="filter-btn active" onclick="mostrarGrupo('all', this)">Todos</button>
                <?php foreach ($orden_grupos as $nombre_grupo): ?>
                    <button class="filter-btn" onclick="mostrarGrupo('<?php echo htmlspecialchars((string) $nombre_grupo, ENT_QUOTES, 'UTF-8'); ?>', this)"><?php echo htmlspecialchars(tituloGrupoMaestro($nombre_grupo), ENT_QUOTES, 'UTF-8'); ?></button>
                <?php endforeach; ?>
            </div>

            <div class="content">
                <?php if ($hay_logs): ?>
                    <?php foreach ($orden_grupos as $nombre_grupo): ?>
                        <?php if (($totales_grupo[$nombre_grupo] ?? 0) <= 0) continue; ?>
                        <section id="grupo_<?php echo htmlspecialchars((string) $nombre_grupo, ENT_QUOTES, 'UTF-8'); ?>" class="grupo-logs">
                            <div class="grupo-header">
                                <div>
                                    <h2><?php echo htmlspecialchars((string) tituloGrupoMaestro($nombre_grupo), ENT_QUOTES, 'UTF-8'); ?></h2>
                                    <div class="grupo-total"><?php echo (int) $totales_grupo[$nombre_grupo]; ?> registros encontrados</div>
                                </div>
                            </div>

                            <?php foreach (($logs_maestro[$nombre_grupo] ?? []) as $path => $lines): ?>
                                <article class="log-card">
                                    <div class="log-card-head">
                                        <div class="log-path"><?php echo htmlspecialchars((string) $path, ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="log-count"><?php echo count($lines); ?> registros</div>
                                    </div>
                                    <pre class="log-pre"><?php echo htmlspecialchars(implode('', $lines), ENT_QUOTES, 'UTF-8'); ?></pre>
                                </article>
                            <?php endforeach; ?>
                        </section>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty">
                        No se encontraron logs con contenido para el período configurado. El archivo centinela_error.log ya fue creado y queda listo en la raíz del proyecto.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function mostrarGrupo(grupo, boton) {
            const grupos = Array.from(document.querySelectorAll('[id^="grupo_"]')).map(section => section.id.replace('grupo_', ''));
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            if (boton) boton.classList.add('active');

            grupos.forEach(g => {
                const elem = document.getElementById('grupo_' + g);
                if (!elem) return;
                elem.classList.toggle('hidden', grupo !== 'all' && g !== grupo);
            });
        }
    </script>
</body>

</html>