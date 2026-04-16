<?php
// Panel principal de Centinela con estadísticas y tamaños de logs
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'centinela-config.php';

$logFiles = [
    [
        'nombre' => 'Registro Centinela',
        'ruta' => $CENTINELA_REG_LOG,
        'descripcion' => 'Registro de archivos utilizados por el proyecto.',
    ],
    [
        'nombre' => 'Errores Centinela',
        'ruta' => $CENTINELA_ERROR_LOG,
        'descripcion' => 'Errores, excepciones y fallos del proyecto.',
    ],
];

$detallesLogs = [];
$totalPesoLogs = 0;
$totalLineasLogs = 0;

foreach ($logFiles as $item) {
    $size = file_exists($item['ruta']) ? (int) filesize($item['ruta']) : 0;
    $lines = centinelaContarLineas($item['ruta']);
    $totalPesoLogs += $size;
    $totalLineasLogs += $lines;

    $detallesLogs[] = [
        'nombre' => $item['nombre'],
        'ruta' => $item['ruta'],
        'descripcion' => $item['descripcion'],
        'size' => $size,
        'size_label' => centinelaFormatearBytes($size),
        'lineas' => $lines,
        'modificado' => centinelaFechaArchivo($item['ruta']),
    ];
}

$registrosCentinela = 0;
$archivosUnicos = [];
$ultimaActividadCentinela = 'Sin actividad';
$actividad24h = 0;
$errores24h = 0;
$gruposErrores = [];

$limite24h = time() - 86400;

if (file_exists($CENTINELA_REG_LOG) && is_readable($CENTINELA_REG_LOG)) {
    $handle = @fopen($CENTINELA_REG_LOG, 'r');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }

            $registrosCentinela++;

            if (preg_match('/^\[(.+?)\]\s+(.+)$/', $line, $matches)) {
                $timestamp = strtotime((string) $matches[1]) ?: 0;
                $ruta = trim((string) $matches[2]);

                if ($ruta !== '') {
                    $archivosUnicos[$ruta] = true;
                }
                if ($timestamp >= $limite24h) {
                    $actividad24h++;
                }
            }
        }
        fclose($handle);
    }

    $ultimaActividadCentinela = centinelaFechaArchivo($CENTINELA_REG_LOG);
}

if (file_exists($CENTINELA_ERROR_LOG) && is_readable($CENTINELA_ERROR_LOG)) {
    $handle = @fopen($CENTINELA_ERROR_LOG, 'r');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }

            $area = centinelaDetectarAreaDesdeRuta($line, $CENTINELA_PROJECT_ROOT);
            if (!isset($gruposErrores[$area])) {
                $gruposErrores[$area] = 0;
            }
            $gruposErrores[$area]++;

            if (preg_match('/^\[(.+?)\]/', $line, $matches)) {
                $timestamp = strtotime((string) $matches[1]) ?: 0;
                if ($timestamp >= $limite24h) {
                    $errores24h++;
                }
            }
        }
        fclose($handle);
    }
}

if (empty($gruposErrores)) {
    $gruposErrores['otros'] = 0;
}

ksort($gruposErrores);

$totalErrores = array_sum($gruposErrores);
$totalArchivosMonitoreados = count($archivosUnicos);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centinela | Panel</title>
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }
        .stat-card {
            border: 1px solid #e7edf5;
            border-radius: 12px;
            background: #fbfdff;
            padding: 14px;
        }
        .stat-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #667085;
            margin-bottom: 8px;
        }
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .stat-sub {
            font-size: 13px;
            color: #667085;
        }
        .section-title {
            font-size: 18px;
            margin: 0 0 12px;
        }
        .table-wrap {
            overflow-x: auto;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 11px 12px;
            border-bottom: 1px solid #edf2f7;
            font-size: 14px;
            vertical-align: top;
        }
        th {
            color: #667085;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: #f8fbff;
        }
        .mono {
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            font-size: 12px;
            color: #475467;
            word-break: break-all;
        }
        .group-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }
        .group-box {
            border: 1px solid #e7edf5;
            border-radius: 12px;
            background: #fbfdff;
            padding: 12px 14px;
        }
        .group-name {
            color: #667085;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .group-value {
            font-size: 24px;
            font-weight: 700;
            color: #1d4ed8;
        }
        .note {
            margin-top: 8px;
            color: #667085;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="page">
        <?php $centinelaMenuActivo = 'index'; include __DIR__ . '/menu_centinela.php'; ?>

        <div class="panel">
            <div class="panel-head">
                <h1>Panel general de Centinela</h1>
                <div class="meta">
                    <span class="badge"><?php echo $CENTINELA_ENTORNO_ACTUAL === 'localhost' ? 'LOCALHOST' : 'PRODUCCIÓN'; ?></span>
                    <span>Host: <?php echo htmlspecialchars((string) $CENTINELA_HOST_ACTUAL, ENT_QUOTES, 'UTF-8'); ?></span>
                    <span>Actualizado: <?php echo date('d/m/Y H:i'); ?></span>
                </div>
            </div>

            <div class="content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Archivos monitoreados</div>
                        <div class="stat-value"><?php echo (int) $totalArchivosMonitoreados; ?></div>
                        <div class="stat-sub">Detectados desde centinela_reg.log</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Actividad últimas 24h</div>
                        <div class="stat-value"><?php echo (int) $actividad24h; ?></div>
                        <div class="stat-sub">Registros recientes de uso PHP</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Errores registrados</div>
                        <div class="stat-value"><?php echo (int) $totalErrores; ?></div>
                        <div class="stat-sub"><?php echo (int) $errores24h; ?> en las últimas 24h</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Peso total de logs</div>
                        <div class="stat-value"><?php echo htmlspecialchars($totalPesoLogs > 0 ? centinelaFormatearBytes($totalPesoLogs) : '0 B', ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="stat-sub"><?php echo (int) $totalLineasLogs; ?> líneas acumuladas</div>
                    </div>
                </div>

                <h2 class="section-title">Logs activos del sistema</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Log</th>
                                <th>Descripción</th>
                                <th>Peso</th>
                                <th>Líneas</th>
                                <th>Última modificación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detallesLogs as $log): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($log['nombre'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="mono"><?php echo htmlspecialchars(str_replace('\\', '/', (string) $log['ruta']), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['descripcion'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($log['size_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo (int) $log['lineas']; ?></td>
                                    <td><?php echo htmlspecialchars($log['modificado'], ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <h2 class="section-title">Distribución de errores por área</h2>
                <div class="group-grid">
                    <?php foreach ($gruposErrores as $area => $cantidad): ?>
                        <div class="group-box">
                            <div class="group-name"><?php echo centinelaFormatearAreaNombre((string) $area); ?></div>
                            <div class="group-value"><?php echo (int) $cantidad; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="note">
                    Última actividad detectada en Centinela: <?php echo htmlspecialchars($ultimaActividadCentinela, ENT_QUOTES, 'UTF-8'); ?>.
                </div>
            </div>
        </div>

    </div>

</body>
</html>
