<?php
$projectRootAlum = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..') ?: dirname(__DIR__);
$logPathAlum = $projectRootAlum . DIRECTORY_SEPARATOR . 'centinela_reg.log';
$inicioSemanaActual = new DateTime('monday this week');
$inicioSemanaActual->setTime(0, 0, 0);

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
    function centinelaInicializarSemanas($inicioSemanaActual)
    {
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

if (!function_exists('centinelaAreaDesdeRutaRegistro')) {
    function centinelaAreaDesdeRutaRegistro($ruta, $projectRoot)
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

if (!function_exists('centinelaTituloArea')) {
    function centinelaTituloArea($area)
    {
        $area = (string) $area;
        if ($area === 'raiz') {
            return 'Raiz';
        }

        return ucwords(str_replace('_', ' ', $area));
    }
}

if (!function_exists('centinelaPaletaModulo')) {
    function centinelaPaletaModulo($index)
    {
        $paleta = [
            ['fuerte' => '#1d4ed8', 'suave' => '#93c5fd'],
            ['fuerte' => '#7c3aed', 'suave' => '#c4b5fd'],
            ['fuerte' => '#059669', 'suave' => '#86efac'],
            ['fuerte' => '#c2410c', 'suave' => '#fdba74'],
            ['fuerte' => '#be123c', 'suave' => '#fda4af'],
            ['fuerte' => '#0f766e', 'suave' => '#99f6e4'],
        ];

        return $paleta[$index % count($paleta)];
    }
}

$areasDetectadas = [];
$registrosCentinela = [];

if (file_exists($logPathAlum) && is_readable($logPathAlum)) {
    $handle = @fopen($logPathAlum, 'r');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $line = trim((string) $line);
            if ($line === '' || !preg_match('/^\[(.+?)\]\s+(.+)$/', $line, $matches)) {
                continue;
            }

            $timestamp = strtotime((string) $matches[1]) ?: 0;
            $ruta = trim((string) $matches[2]);
            if ($timestamp <= 0 || $ruta === '') {
                continue;
            }

            $area = centinelaAreaDesdeRutaRegistro($ruta, $projectRootAlum);
            $areasDetectadas[$area] = true;
            $registrosCentinela[] = [
                'timestamp' => $timestamp,
                'area' => $area,
            ];
        }
        fclose($handle);
    }
}

$areasOrdenadas = array_keys($areasDetectadas);
sort($areasOrdenadas, SORT_NATURAL | SORT_FLAG_CASE);

if (empty($areasOrdenadas)) {
    $areasOrdenadas = ['otros'];
}

$modulosCentinela = [
    'todos' => [
        'titulo' => 'Todos',
        'area' => null,
        'color_fuerte' => '#0f172a',
        'color_suave' => '#94a3b8',
    ],
];

foreach ($areasOrdenadas as $indexArea => $area) {
    $colors = centinelaPaletaModulo($indexArea);
    $modulosCentinela[$area] = [
        'titulo' => centinelaTituloArea($area),
        'area' => $area,
        'color_fuerte' => $colors['fuerte'],
        'color_suave' => $colors['suave'],
    ];
}

$estadisticasCentinela = [];
foreach ($modulosCentinela as $claveModulo => $metaModulo) {
    $estadisticasCentinela[$claveModulo] = [
        'semanas' => centinelaInicializarSemanas($inicioSemanaActual),
        'total' => 0,
        'maximo' => 0,
        'pico' => '—',
        'actual' => 0,
    ];
}

foreach ($registrosCentinela as $registro) {
    $claveSemana = date('o-W', (int) $registro['timestamp']);
    foreach ($modulosCentinela as $claveModulo => $metaModulo) {
        if (!isset($estadisticasCentinela[$claveModulo]['semanas'][$claveSemana])) {
            continue;
        }

        if ($claveModulo === 'todos') {
            $estadisticasCentinela[$claveModulo]['semanas'][$claveSemana]['count']++;
            continue;
        }

        if (($metaModulo['area'] ?? '') === ($registro['area'] ?? '')) {
            $estadisticasCentinela[$claveModulo]['semanas'][$claveSemana]['count']++;
        }
    }
}

foreach ($estadisticasCentinela as $claveModulo => &$datosModulo) {
    foreach ($datosModulo['semanas'] as $semana) {
        $datosModulo['total'] += (int) $semana['count'];
        if ((int) $semana['count'] > $datosModulo['maximo']) {
            $datosModulo['maximo'] = (int) $semana['count'];
            $datosModulo['pico'] = $semana['rango'];
        }
    }

    $semanaActual = end($datosModulo['semanas']);
    $datosModulo['actual'] = is_array($semanaActual) ? (int) ($semanaActual['count'] ?? 0) : 0;
}
unset($datosModulo);

$alturaGraficoTotalAlum = 320;
$alturaZonaEtiquetasAlum = 52;
$alturaDisponibleBarrasAlum = max(120, $alturaGraficoTotalAlum - $alturaZonaEtiquetasAlum - 18);
?>
<div class="panel" style="margin-top: 5px; margin-bottom: 20px;">
    <div class="panel-head">
        <h2 style="margin:0 0 6px; font-size:20px;">Uso semanal por módulo</h2>
        <div class="meta">
            <span class="badge">Últimas 52 semanas</span>
            <span>Áreas detectadas en el proyecto</span>
        </div>
    </div>
    <div class="content">
        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px;">
            <?php foreach ($modulosCentinela as $claveModulo => $metaModulo): ?>
                <button type="button" class="centinela-switch-btn <?php echo $claveModulo === 'todos' ? 'active' : ''; ?>" data-modulo="<?php echo htmlspecialchars($claveModulo, ENT_QUOTES, 'UTF-8'); ?>" style="padding:8px 12px; border-radius:8px; border:1px solid #d9e2ef; background:<?php echo $claveModulo === 'todos' ? '#1d4ed8' : '#fff'; ?>; color:<?php echo $claveModulo === 'todos' ? '#fff' : '#334155'; ?>; font-weight:600; cursor:pointer;"><?php echo htmlspecialchars($metaModulo['titulo'], ENT_QUOTES, 'UTF-8'); ?></button>
            <?php endforeach; ?>
        </div>

        <?php foreach ($modulosCentinela as $claveModulo => $metaModulo): ?>
            <?php $datosModulo = $estadisticasCentinela[$claveModulo]; ?>
            <div class="centinela-panel-modulo" data-panel-modulo="<?php echo htmlspecialchars($claveModulo, ENT_QUOTES, 'UTF-8'); ?>" style="display:<?php echo $claveModulo === 'todos' ? 'block' : 'none'; ?>;">
                <div class="stats-grid" style="margin-bottom: 14px;">
                    <div class="stat-card">
                        <div class="stat-label">Registros</div>
                        <div class="stat-value"><?php echo (int) $datosModulo['total']; ?></div>
                        <div class="stat-sub">Total acumulado en 52 semanas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Semana actual</div>
                        <div class="stat-value"><?php echo (int) $datosModulo['actual']; ?></div>
                        <div class="stat-sub">Registros de la semana en curso</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Record semanal</div>
                        <div class="stat-value"><?php echo (int) $datosModulo['maximo']; ?></div>
                        <div class="stat-sub"><?php echo htmlspecialchars($datosModulo['pico'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>

                <?php if ($datosModulo['total'] > 0): ?>
                    <div style="border:1px solid #e7edf5; border-radius:12px; background:#fbfdff; padding:14px; overflow-x:auto; overflow-y:hidden;">
                        <div style="display:flex; align-items:flex-end; gap:6px; min-width:900px; height:<?php echo (int) $alturaGraficoTotalAlum; ?>px;">
                            <?php $i = 0; foreach ($datosModulo['semanas'] as $semana): ?>
                                <?php
                                $conteo = (int) $semana['count'];
                                $altoCalculado = $datosModulo['maximo'] > 0 ? (int) round(($conteo / $datosModulo['maximo']) * $alturaDisponibleBarrasAlum) : 6;
                                $alto = max(6, min($alturaDisponibleBarrasAlum, $altoCalculado));
                                $esActual = $i === 51;
                                ?>
                                <div class="centinela-bar-wrap" style="flex:1; min-width:10px; display:flex; flex-direction:column; justify-content:flex-end; align-items:center; height:100%; position:relative;" data-tooltip="<?php echo htmlspecialchars($conteo . ' registros | ' . $semana['rango'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <div style="flex:1; width:100%; display:flex; align-items:flex-end; justify-content:center; overflow:hidden;">
                                        <div style="width:100%; max-width:16px; height:<?php echo (int) $alto; ?>px; border-radius:6px 6px 0 0; background:<?php echo $esActual ? $metaModulo['color_fuerte'] : $metaModulo['color_suave']; ?>;"></div>
                                    </div>
                                    <div style="margin-top:6px; height:<?php echo (int) $alturaZonaEtiquetasAlum; ?>px; display:flex; align-items:flex-start; justify-content:center; font-size:10px; color:#667085; line-height:1; writing-mode:vertical-rl; transform:rotate(180deg); overflow:hidden;">
                                        <?php echo htmlspecialchars($semana['label'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </div>
                            <?php $i++; endforeach; ?>
                        </div>
                    </div>
                    <div class="note">Cada barra representa una semana y considera solo registros detectados en el área <?php echo htmlspecialchars($metaModulo['titulo'], ENT_QUOTES, 'UTF-8'); ?> dentro de centinela_reg.log.</div>
                <?php else: ?>
                    <div class="empty">Aún no hay registros del área <?php echo htmlspecialchars($metaModulo['titulo'], ENT_QUOTES, 'UTF-8'); ?> en las últimas 52 semanas.</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<div id="centinela-tooltip" style="position:fixed; z-index:9999; display:none; pointer-events:none; background:#111827; color:#fff; padding:7px 10px; border-radius:8px; font-size:12px; box-shadow:0 8px 22px rgba(0,0,0,0.18); max-width:260px;"></div>
<script>
(function () {
    var buttons = document.querySelectorAll('.centinela-switch-btn');
    var panels = document.querySelectorAll('.centinela-panel-modulo');
    var tooltip = document.getElementById('centinela-tooltip');
    var barWraps = document.querySelectorAll('.centinela-bar-wrap');

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            var modulo = button.getAttribute('data-modulo');

            buttons.forEach(function (btn) {
                btn.classList.remove('active');
                btn.style.background = '#fff';
                btn.style.color = '#334155';
            });

            button.classList.add('active');
            button.style.background = '#1d4ed8';
            button.style.color = '#fff';

            panels.forEach(function (panel) {
                panel.style.display = panel.getAttribute('data-panel-modulo') === modulo ? 'block' : 'none';
            });
        });
    });

    barWraps.forEach(function (bar) {
        bar.addEventListener('mousemove', function (event) {
            tooltip.textContent = bar.getAttribute('data-tooltip') || '';
            tooltip.style.display = 'block';
            tooltip.style.left = (event.clientX + 14) + 'px';
            tooltip.style.top = (event.clientY - 18) + 'px';
        });

        bar.addEventListener('mouseleave', function () {
            tooltip.style.display = 'none';
        });
    });
})();
</script>
