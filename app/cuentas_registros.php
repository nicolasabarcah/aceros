<!DOCTYPE html>
<html lang="es-CL">

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>Aceros APP | Cuentas</title>

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
	<link rel="stylesheet" href="assets/css/main.css?v=20260415-3" />
	<script src="assets/js/components/app-modal.js?v=20260416-1" defer></script>
	<script src="assets/js/pages/cuentas-tabla.js?v=20260416-1" defer></script>
</head>

<?php
// cuentas_tabla.php
// Página para mostrar la tabla de cuentas bancarias

// conexion.php
require_once __DIR__ . '/conexion.php';

$resumen = [
	'saldo_total' => 0.0,
	'ingresos_mes' => 0.0,
	'gastos_mes' => 0.0,
];

$transacciones = [];
$categorias = [];

function formatearMoneda($monto)
{
	return '$ ' . number_format((float) $monto, 2, '.', ',');
}

if (isset($conexion) && $conexion instanceof mysqli) {
	@mysqli_set_charset($conexion, 'utf8mb4');

	$sqlResumen = "
		SELECT
			COALESCE(SUM(CASE WHEN cat.tipo = 'ingreso' THEN ABS(cr.monto) ELSE -ABS(cr.monto) END), 0) AS saldo_total,
			COALESCE(SUM(CASE WHEN cat.tipo = 'ingreso' AND MONTH(cr.fecha) = MONTH(CURDATE()) AND YEAR(cr.fecha) = YEAR(CURDATE()) THEN ABS(cr.monto) ELSE 0 END), 0) AS ingresos_mes,
			COALESCE(SUM(CASE WHEN cat.tipo = 'egreso' AND MONTH(cr.fecha) = MONTH(CURDATE()) AND YEAR(cr.fecha) = YEAR(CURDATE()) THEN ABS(cr.monto) ELSE 0 END), 0) AS gastos_mes
		FROM cuentas_registros cr
		INNER JOIN categorias cat ON cat.id = cr.categoria
	";

	if ($resResumen = @mysqli_query($conexion, $sqlResumen)) {
		$filaResumen = mysqli_fetch_assoc($resResumen);
		if (is_array($filaResumen)) {
			$resumen['saldo_total'] = (float) ($filaResumen['saldo_total'] ?? 0);
			$resumen['ingresos_mes'] = (float) ($filaResumen['ingresos_mes'] ?? 0);
			$resumen['gastos_mes'] = (float) ($filaResumen['gastos_mes'] ?? 0);
		}
		mysqli_free_result($resResumen);
	}

	$sqlTransacciones = "
		SELECT
			cr.id,
			cr.fecha,
			cr.concepto,
			cr.monto,
			cat.nombre AS categoria_nombre,
			cat.tipo AS categoria_tipo
		FROM cuentas_registros cr
		INNER JOIN categorias cat ON cat.id = cr.categoria
		ORDER BY cr.fecha DESC, cr.id DESC
	";

	if ($resTransacciones = @mysqli_query($conexion, $sqlTransacciones)) {
		while ($fila = mysqli_fetch_assoc($resTransacciones)) {
			$transacciones[] = $fila;
		}
		mysqli_free_result($resTransacciones);
	}

	$sqlCategorias = "
		SELECT id, nombre, tipo
		FROM categorias
		WHERE estado = 1
		ORDER BY tipo ASC, nombre ASC
	";

	if ($resCategorias = @mysqli_query($conexion, $sqlCategorias)) {
		while ($fila = mysqli_fetch_assoc($resCategorias)) {
			$categorias[] = $fila;
		}
		mysqli_free_result($resCategorias);
	}
}

?>

<body>
	<div class="app-shell d-lg-flex">

		<!-- Menú -->
		<?php include 'menu.php'; ?>

		<main class="main-content flex-grow-1">
			<section class="header-card">
				<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
					<div>
						<h1 class="page-title">Hola, Usuario</h1>
						<p class="page-subtitle">Aquí tienes un resumen claro de tus finanzas personales.</p>
					</div>
					<div class="d-flex align-items-center gap-2 flex-wrap header-actions">
						<div class="month-nav" aria-label="Navegador mensual general" style="margin-top: 2px;">
							<button id="headerMonthPrev" type="button" class="month-nav__btn" aria-label="Mes anterior">
								<i class="bi bi-chevron-left"></i>
							</button>
							<div id="headerMonthLabel" class="month-nav__label" aria-live="polite">ABR 2026</div>
							<button id="headerMonthNext" type="button" class="month-nav__btn" aria-label="Mes siguiente">
								<i class="bi bi-chevron-right"></i>
							</button>
						</div>
						<button id="openTransactionModal" type="button" class="btn btn-primary header-actions__btn" data-modal-target="#transactionModal">
							<i class="bi bi-plus-lg me-2"></i>
							Añadir Transacción
						</button>
					</div>
				</div>
			</section>

			<section class="mb-4">
				<div class="row g-4">
					<div class="col-md-4">
						<article class="summary-card">
							<div class="d-flex align-items-center justify-content-between">
								<div>
									<p class="summary-label">Saldo Total</p>
									<h2 class="summary-value"><?php echo htmlspecialchars(formatearMoneda($resumen['saldo_total']), ENT_QUOTES, 'UTF-8'); ?></h2>
								</div>
								<span class="summary-icon summary-icon-total">
									<i class="bi bi-wallet2"></i>
								</span>
							</div>
						</article>
					</div>

					<div class="col-md-4">
						<article class="summary-card">
							<div class="d-flex align-items-center justify-content-between">
								<div>
									<p class="summary-label">Ingresos del Mes</p>
									<h2 class="summary-value"><?php echo htmlspecialchars(formatearMoneda($resumen['ingresos_mes']), ENT_QUOTES, 'UTF-8'); ?></h2>
								</div>
								<span class="summary-icon summary-icon-income">
									<i class="bi bi bi-arrow-up-left"></i>
								</span>
							</div>
						</article>
					</div>

					<div class="col-md-4">
						<article class="summary-card">
							<div class="d-flex align-items-center justify-content-between">
								<div>
									<p class="summary-label">Gastos del Mes</p>
									<h2 class="summary-value"><?php echo htmlspecialchars(formatearMoneda($resumen['gastos_mes']), ENT_QUOTES, 'UTF-8'); ?></h2>
								</div>
								<span class="summary-icon summary-icon-expense">
									<i class="bi bi bi-arrow-down-right"></i>
								</span>
							</div>
						</article>
					</div>
				</div>
			</section>

			<section class="row g-4">
				<div class="col-xl-12">
					<article class="table-card h-100">
						<div class="d-flex align-items-center justify-content-between mb-5">
							<h3 class="section-title">Transacciones Recientes</h3>
							<div class="d-flex align-items-center gap-2">
								<div class="table-search-wrap">
									<i class="bi bi-search"></i>
									<input id="txSearch" type="search" class="table-search" placeholder="Buscador" aria-label="Buscar transacciones">
								</div>
								<select id="txPageSize" class="table-page-size" aria-label="Cantidad de registros por página">
									<option value="25" selected>25 reg.</option>
									<option value="50">50 reg.</option>
									<option value="75">75 reg.</option>
									<option value="100">100 reg.</option>
								</select>
							</div>
						</div>

						<div class="table-responsive">
							<table id="transactionsTable" class="table custom-table align-middle">
								<thead>
									<tr>
										<th>Fecha</th>
										<th>Concepto</th>
										<th>Categoría</th>
										<th class="text-end">Monto</th>
										<th class="text-center action-col" style="min-width: 90px;">Editar</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($transacciones as $tx): ?>
										<?php
										$tipo = (string) ($tx['categoria_tipo'] ?? 'egreso');
										$montoAbs = abs((float) ($tx['monto'] ?? 0));
										$signo = $tipo === 'ingreso' ? '+' : '-';
										$badgeClass = $tipo === 'ingreso' ? 'badge-soft-success' : 'badge-soft-warning';
										?>
										<tr>
											<td><?php echo htmlspecialchars(date('d/m/Y', strtotime((string) $tx['fecha'])), ENT_QUOTES, 'UTF-8'); ?></td>
											<td><?php echo htmlspecialchars((string) ($tx['concepto'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
											<td><span class="badge <?php echo htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($tx['categoria_nombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
											<td class="text-end"><?php echo htmlspecialchars($signo . formatearMoneda($montoAbs), ENT_QUOTES, 'UTF-8'); ?></td>
											<td class="text-center action-col"><a href="#" class="edit-action" title="Editar"><i class="bi bi-pencil"></i></a></td>
										</tr>
									<?php endforeach; ?>
									<tr id="txEmptyState" class="d-none">
										<td colspan="5" class="text-center py-4">No hay resultados para tu búsqueda.</td>
									</tr>
								</tbody>
							</table>
						</div>

						<div class="table-footer mt-3">
							<div class="table-footer-left">
								<small id="txInfo" class="table-info"></small>
							</div>
							<div id="txPagination" class="table-pagination" aria-label="Paginación de transacciones"></div>
						</div>
					</article>
				</div>
			</section>
		</main>
	</div>

	<!-- MODAL Añadir Transacción -->
	<div id="transactionModal" class="app-modal" data-modal role="dialog" aria-modal="true" aria-labelledby="transactionModalTitle" aria-hidden="true">
		<div class="app-modal__backdrop" data-close-modal></div>
		<div class="app-modal__dialog" role="document">
			<div class="app-modal__header">
				<h2 id="transactionModalTitle" class="app-modal__title">Nueva Transacción</h2>
				<button type="button" class="app-modal__close" aria-label="Cerrar" data-close-modal>
					<i class="bi bi-x-lg"></i>
				</button>
			</div>
			<div class="app-modal__body">
				<form id="transactionForm">
					<div class="app-modal__grid">

						<!-- Tipo -->
						<div class="app-field">
							<label for="txTypeSwitch" style="margin-bottom: 5px;">Tipo</label>
							<div class="tx-type-switch" role="group" aria-label="Tipo de transacción">
								<span class="tx-type-label is-active" data-switch-label="ingreso">Ingreso</span>
								<label class="tx-switch" for="txTypeSwitch">
									<input id="txTypeSwitch" type="checkbox" aria-label="Cambiar tipo de transacción" data-modal-variant-switch data-checked-value="egreso" data-unchecked-value="ingreso" data-default-checked="false">
									<span class="tx-switch__track"><span class="tx-switch__thumb"></span></span>
								</label>
								<span class="tx-type-label" data-switch-label="egreso">Gasto</span>
							</div>
							<input id="txType" name="tipo" type="hidden" value="ingreso" data-modal-type-input>
						</div>

						<!-- Fecha -->
						<div class="app-field">
							<label for="txDate">Fecha</label>
							<input id="txDate" name="fecha" type="date" required>
						</div>

						<!-- Concepto -->
						<div class="app-field full">
							<label for="txConcept">Concepto</label>
							<input id="txConcept" name="concepto" type="text" placeholder="Ej: Pago proveedor" required>
						</div>

						<!-- Categoría -->
						<div class="app-field">
							<label for="txCategory">Categoría</label>
							<select id="txCategory" name="categoria" required>
								<option value="" selected disabled>Selecciona una categoría</option>
								<?php foreach ($categorias as $cat): ?>
									<option value="<?php echo (int) ($cat['id'] ?? 0); ?>"><?php echo htmlspecialchars((string) ($cat['nombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<!-- Monto -->
						<div class="app-field">
							<label for="txAmount">Monto (CLP)</label>
							<input id="txAmount" name="monto" type="number" min="0" step="1" placeholder="Ej: 250000" required>
						</div>

						<!-- Descripción -->
						<div class="app-field full">
							<label for="txNote">Descripción</label>
							<textarea id="txNote" name="descripcion" placeholder="Detalle opcional de la transacción"></textarea>
						</div>
					</div>

					<div class="app-modal__actions">
						<button type="button" class="btn btn-light" data-close-modal>Cancelar</button>
						<button type="submit" class="btn btn-primary">Guardar</button>
					</div>
				</form>
			</div>
		</div>
	</div>

</body>

</html>