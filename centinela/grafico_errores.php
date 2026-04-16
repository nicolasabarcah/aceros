<?php
$projectRootErrores = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..') ?: dirname(__DIR__);
$logPathErrores = $projectRootErrores . DIRECTORY_SEPARATOR . 'centinela_error.log';
$inicioSemanaActualErrores = new DateTime('monday this week');
$inicioSemanaActualErrores->setTime(0, 0, 0);

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

if (!function_exists('centinelaAreaDesdeRutaError')) {
    function centinelaAreaDesdeRutaError($ruta, $projectRoot)
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

if (!function_exists('centinelaNormalizarAreaError')) {
    function centinelaNormalizarAreaError($area)
    {
        $area = strtolower(trim((string) $area));
        $area = preg_replace('/[^a-z0-9_\-]/', '', $area) ?? '';
        return $area !== '' ? $area : 'otros';
    }
}

if (!function_exists('centinelaDetectarAreaError')) {
    function centinelaDetectarAreaError($linea, $projectRoot)
    {
        $linea = trim((string) $linea);

        if (preg_match('/^\[[^\]]+\]\s+\[([^\]]+)\]/', $linea, $matches)) {
            $area = centinelaNormalizarAreaError((string) $matches[1]);
            if ($area !== '') {
                return $area;
            }
        }

        if (preg_match_all('/(?:[A-Za-z]:\\\\[^\s\]\)]+|\/{1,2}[^\s\]\)]+|[A-Za-z0-9_\-\.\/\\\\]+\.(?:php|phtml|inc))/i', $linea, $paths)) {
            foreach ($paths[0] as $path) {
                $detected = centinelaAreaDesdeRutaError((string) $path, $projectRoot);
                if ($detected !== 'otros') {
                    return $detected;
                }
            }
        }

        return 'otros';
    }
}

if (!function_exists('centinelaTituloAreaError')) {
    function centinelaTituloAreaError($area)
    {
        if ((string) $area === 'raiz') {
            return 'Raiz';
        }

        return ucwords(str_replace('_', ' ', (string) $area));
    }
}

if (!function_exists('centinelaPaletaModuloError')) {
    function centinelaPaletaModuloError($index)
    {
        $paleta = [
            ['fuerte' => '#991b1b', 'suave' => '#fca5a5'],
            ['fuerte' => '#c2410c', 'suave' => '#fdba74'],
            ['fuerte' => '#1d4ed8', 'suave' => '#93c5fd'],
            ['fuerte' => '#7c3aed', 'suave' => '#c4b5fd'],
            ['fuerte' => '#475569', 'suave' => '#cbd5e1'],
            ['fuerte' => '#065f46', 'suave' => '#6ee7b7'],
        ];

        return $paleta[$index % count($paleta)];
    }
}

$registrosErroresCentinela = [];
$areasErrorDetectadas = [];

if (file_exists($logPathErrores) && is_readable($logPathErrores)) {
    $handle = @fopen($logPathErrores, 'r');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }

            if (!preg_match('/^\[(.+?)\]/', $line, $matchesFecha)) {
                continue;
            }

            $timestamp = strtotime((string) $matchesFecha[1]) ?: 0;
            if ($timestamp <= 0) {
                continue;
            }

            $area = centinelaDetectarAreaError($line, $projectRootErrores);
            $areasErrorDetectadas[$area] = true;
            $registrosErroresCentinela[] = [
                'timestamp' => $timestamp,
                'area' => $area,
            ];
        }
        fclose($handle);
    }
}

$areasErroresOrdenadas = array_keys($areasErrorDetectadas);
sort($areasErroresOrdenadas, SORT_NATURAL | SORT_FLAG_CASE);

if (empty($areasErroresOrdenadas)) {
    $areasErroresOrdenadas = ['otros'];
}

$modulosErroresCentinela = [
    'todos' => [
        'titulo' => 'Todos',
        'grupo' => null,
        'color_fuerte' => '#991b1b',
        'color_suave' => '#fca5a5',
    ],
];

foreach ($areasErroresOrdenadas as $indexArea => $area) {
    $colors = centinelaPaletaModuloError($indexArea + 1);
    $modulosErroresCentinela[$area] = [
        'titulo' => centinelaTituloAreaError($area),
        'grupo' => $area,
        'color_fuerte' => $colors['fuerte'],
        'color_suave' => $colors['suave'],
    ];
}

$estadisticasErroresCentinela = [];
foreach ($modulosErroresCentinela as $claveModulo => $metaModulo) {
    $estadisticasErroresCentinela[$claveModulo] = [
        'semanas' => centinelaInicializarSemanas($inicioSemanaActualErrores),
        'total' => 0,
        'maximo' => 0,
        'pico' => '—',
        'actual' => 0,
    ];
}

foreach ($registrosErroresCentinela as $registroError) {
    $claveSemana = date('o-W', (int) $registroError['timestamp']);
    foreach ($modulosErroresCentinela as $claveModulo => $metaModulo) {
        if (!isset($estadisticasErroresCentinela[$claveModulo]['semanas'][$claveSemana])) {
            continue;
        }

        if ($claveModulo === 'todos') {
            $estadisticasErroresCentinela[$claveModulo]['semanas'][$claveSemana]['count']++;
            continue;
        }

        if (($metaModulo['grupo'] ?? '') === ($registroError['area'] ?? '')) {
            $estadisticasErroresCentinela[$claveModulo]['semanas'][$claveSemana]['count']++;
        }
    }
}

foreach ($estadisticasErroresCentinela as $claveModulo => &$datosModulo) {
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

$alturaGraficoTotalErrores = 320;
$alturaZonaEtiquetasErrores = 52;
$alturaDisponibleBarrasErrores = max(120, $alturaGraficoTotalErrores - $alturaZonaEtiquetasErrores - 18);
?>
<div class="panel" style="margin-top: 5px; margin-bottom: 20px;">
    <div class="panel-head">
        <h2 style="margin:0 0 6px; font-size:20px;">Errores semanales por área</h2>
        <div class="meta">
            <span class="badge">Últimas 52 semanas</span>
            <span>Áreas detectadas en el proyecto</span>
        </div>
    </div>
    <div class="content">
        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px;">
            <?php foreach ($modulosErroresCentinela as $claveModulo => $metaModulo): ?>
                <button type="button" class="centinela-error-switch-btn <?php echo $claveModulo === 'todos' ? 'active' : ''; ?>" data-modulo-error="<?php echo htmlspecialchars($claveModulo, ENT_QUOTES, 'UTF-8'); ?>" style="padding:8px 12px; border-radius:8px; border:1px solid #d9e2ef; background:<?php echo $claveModulo === 'todos' ? '#991b1b' : '#fff'; ?>; color:<?php echo $claveModulo === 'todos' ? '#fff' : '#334155'; ?>; font-weight:600; cursor:pointer;"><?php echo htmlspecialchars($metaModulo['titulo'], ENT_QUOTES, 'UTF-8'); ?></button>
            <?php endforeach; ?>
        </div>

        <?php foreach ($modulosErroresCentinela as $claveModulo => $metaModulo): ?>
            <?php $datosModulo = $estadisticasErroresCentinela[$claveModulo]; ?>
            <div class="centinela-error-panel-modulo" data-panel-modulo-error="<?php echo htmlspecialchars($claveModulo, ENT_QUOTES, 'UTF-8'); ?>" style="display:<?php echo $claveModulo === 'todos' ? 'block' : 'none'; ?>;">
                <div class="stats-grid" style="margin-bottom: 14px;">
                    <div class="stat-card">
                        <div class="stat-label">Errores</div>
                        <div class="stat-value"><?php echo (int) $datosModulo['total']; ?></div>
                        <div class="stat-sub">Total acumulado en 52 semanas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Semana actual</div>
                        <div class="stat-value"><?php echo (int) $datosModulo['actual']; ?></div>
                        <div class="stat-sub">Errores de la semana en curso</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Record semanal</div>
                        <div class="stat-value"><?php echo (int) $datosModulo['maximo']; ?></div>
                        <div class="stat-sub"><?php echo htmlspecialchars($datosModulo['pico'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>

                <?php if ($datosModulo['total'] > 0): ?>
                    <div style="border:1px solid #e7edf5; border-radius:12px; background:#fbfdff; padding:14px; overflow-x:auto; overflow-y:hidden;">
                        <div style="display:flex; align-items:flex-end; gap:6px; min-width:900px; height:<?php echo (int) $alturaGraficoTotalErrores; ?>px;">
                            <?php $i = 0; foreach ($datosModulo['semanas'] as $semana): ?>
                                <?php
                                $conteo = (int) $semana['count'];
                                $altoCalculado = $datosModulo['maximo'] > 0 ? (int) round(($conteo / $datosModulo['maximo']) * $alturaDisponibleBarrasErrores) : 6;
                                $alto = max(6, min($alturaDisponibleBarrasErrores, $altoCalculado));
                                $esActual = $i === 51;
                                ?>
                                <div class="centinela-error-bar-wrap" style="flex:1; min-width:10px; display:flex; flex-direction:column; justify-content:flex-end; align-items:center; height:100%; position:relative;" data-tooltip-error="<?php echo htmlspecialchars($conteo . ' errores | ' . $semana['rango'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <div style="flex:1; width:100%; display:flex; align-items:flex-end; justify-content:center; overflow:hidden;">
                                        <div style="width:100%; max-width:16px; height:<?php echo (int) $alto; ?>px; border-radius:6px 6px 0 0; background:<?php echo $esActual ? $metaModulo['color_fuerte'] : $metaModulo['color_suave']; ?>;"></div>
                                    </div>
                                    <div style="margin-top:6px; height:<?php echo (int) $alturaZonaEtiquetasErrores; ?>px; display:flex; align-items:flex-start; justify-content:center; font-size:10px; color:#667085; line-height:1; writing-mode:vertical-rl; transform:rotate(180deg); overflow:hidden;">
                                        <?php echo htmlspecialchars($semana['label'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </div>
                            <?php $i++; endforeach; ?>
                        </div>
                    </div>
                    <div class="note">Cada barra representa una semana y considera los errores detectados en <?php echo htmlspecialchars($metaModulo['titulo'], ENT_QUOTES, 'UTF-8'); ?> dentro de centinela_error.log.</div>
                <?php else: ?>
                    <div class="empty">Aún no hay errores registrados para <?php echo htmlspecialchars($metaModulo['titulo'], ENT_QUOTES, 'UTF-8'); ?> en las últimas 52 semanas.</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<div id="centinela-tooltip-errores" style="position:fixed; z-index:9999; display:none; pointer-events:none; background:#111827; color:#fff; padding:7px 10px; border-radius:8px; font-size:12px; box-shadow:0 8px 22px rgba(0,0,0,0.18); max-width:260px;"></div>
<script>
(function () {
    var buttons = document.querySelectorAll('.centinela-error-switch-btn');
    var panels = document.querySelectorAll('.centinela-error-panel-modulo');
    var tooltip = document.getElementById('centinela-tooltip-errores');
    var barWraps = document.querySelectorAll('.centinela-error-bar-wrap');

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            var modulo = button.getAttribute('data-modulo-error');

            buttons.forEach(function (btn) {
                btn.classList.remove('active');
                btn.style.background = '#fff';
                btn.style.color = '#334155';
            });

            button.classList.add('active');
            button.style.background = '#991b1b';
            button.style.color = '#fff';

            panels.forEach(function (panel) {
                panel.style.display = panel.getAttribute('data-panel-modulo-error') === modulo ? 'block' : 'none';
            });
        });
    });

    barWraps.forEach(function (bar) {
        bar.addEventListener('mousemove', function (event) {
            tooltip.textContent = bar.getAttribute('data-tooltip-error') || '';
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