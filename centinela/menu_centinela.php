<?php
$centinelaMenuActivo = $centinelaMenuActivo ?? 'index';
?>
<div class="topbar">
    <div class="menu-cardinela" style="display:flex; gap:10px; flex-wrap:nowrap; justify-content:flex-start; width:100%; overflow-x:auto;">
        <div class="menu-item" style="display:flex; flex:0 0 auto;">
            <a href="index.php" class="nav-btn <?php echo $centinelaMenuActivo === 'index' ? 'active' : ''; ?>">
                <div class="menu-item-inner" style="display:flex; align-items:center; gap:8px;">
                    <div class="menu-item-text">Centro Centinela</div>
                </div>
            </a>
        </div>

        <div class="menu-item" style="display:flex; flex:0 0 auto;">
            <a href="ver_logs.php" class="nav-btn <?php echo $centinelaMenuActivo === 'logs' ? 'active' : ''; ?>">
                <div class="menu-item-inner" style="display:flex; align-items:center; gap:8px;">
                    <div class="menu-item-text">Ver Logs</div>
                </div>
            </a>
        </div>

        <div class="menu-item" style="display:flex; flex:0 0 auto;">
            <a href="ver_directorio.php" class="nav-btn <?php echo $centinelaMenuActivo === 'directorio' ? 'active' : ''; ?>">
                <div class="menu-item-inner" style="display:flex; align-items:center; gap:8px;">
                    <div class="menu-item-text">Ver Directorio</div>
                </div>
            </a>
        </div>

        <div class="menu-item" style="display:flex; flex:0 0 auto;">
            <a href="grafico.php" class="nav-btn <?php echo $centinelaMenuActivo === 'grafico' ? 'active' : ''; ?>">
                <div class="menu-item-inner" style="display:flex; align-items:center; gap:8px;">
                    <div class="menu-item-text">Gráfico</div>
                </div>
            </a>
        </div>
    </div>
</div>