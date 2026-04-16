<?php
date_default_timezone_set('America/Santiago');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$hostActual = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
$entornoActual = in_array($_SERVER['SERVER_NAME'] ?? 'localhost', ['localhost', '127.0.0.1', '::1'], true)
    ? 'localhost'
    : 'produccion';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centinela | Gráficos</title>
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
        .note {
            margin-top: 8px;
            color: #667085;
            font-size: 13px;
        }
        .empty {
            border: 1px dashed #d9e2ef;
            border-radius: 12px;
            background: #fbfdff;
            padding: 16px;
            color: #667085;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="page">
        <?php $centinelaMenuActivo = 'grafico'; include __DIR__ . '/menu_centinela.php'; ?>

        <div class="panel">
            <div class="panel-head">
                <h1>Gráficos de Centinela</h1>
                <div class="meta">
                    <span class="badge"><?php echo $entornoActual === 'localhost' ? 'LOCALHOST' : 'PRODUCCIÓN'; ?></span>
                    <span>Host: <?php echo htmlspecialchars((string) $hostActual, ENT_QUOTES, 'UTF-8'); ?></span>
                    <span>Actualizado: <?php echo date('d/m/Y H:i'); ?></span>
                </div>
            </div>

            <div class="content">
                <?php include __DIR__ . '/grafico_errores.php'; ?>
                <?php include __DIR__ . '/grafico_actividad_semanal.php'; ?>
            </div>
        </div>
    </div>
</body>
</html>