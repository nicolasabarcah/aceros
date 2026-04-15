<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>Dashboard de Finanzas Personales</title>

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
	<link rel="stylesheet" href="assets/css/main.css" />
</head>
<body>
	<div class="app-shell d-lg-flex">
		<aside class="sidebar">
			<div class="brand">
				<i class="bi bi-wallet2"></i>
			</div>

			<nav class="d-flex flex-column nav-stack">
				<a href="#" class="nav-item-minimal active" title="Resumen">
					<i class="bi bi-grid"></i>
				</a>
				<a href="#" class="nav-item-minimal" title="Ingresos">
					<i class="bi bi-arrow-down-left"></i>
				</a>
				<a href="#" class="nav-item-minimal" title="Gastos">
					<i class="bi bi-arrow-up-right"></i>
				</a>
				<a href="#" class="nav-item-minimal" title="Reportes">
					<i class="bi bi-bar-chart"></i>
				</a>
				<a href="#" class="nav-item-minimal" title="Configuración">
					<i class="bi bi-gear"></i>
				</a>
			</nav>
		</aside>

		<main class="main-content flex-grow-1">
			<section class="header-card">
				<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
					<div>
						<h1 class="page-title">Hola, Usuario</h1>
						<p class="page-subtitle">Aquí tienes un resumen claro de tus finanzas personales.</p>
					</div>

					<button class="btn btn-primary">
						<i class="bi bi-plus-lg me-2"></i>
						Añadir Transacción
					</button>
				</div>
			</section>

			<section class="mb-4">
				<div class="row g-4">
					<div class="col-md-4">
						<article class="summary-card">
							<div class="d-flex align-items-start justify-content-between">
								<div>
									<p class="summary-label">Saldo Total</p>
									<h2 class="summary-value">$ 12,480.00</h2>
								</div>
								<span class="summary-icon">
									<i class="bi bi-wallet2"></i>
								</span>
							</div>
						</article>
					</div>

					<div class="col-md-4">
						<article class="summary-card">
							<div class="d-flex align-items-start justify-content-between">
								<div>
									<p class="summary-label">Ingresos del Mes</p>
									<h2 class="summary-value">$ 4,250.00</h2>
								</div>
								<span class="summary-icon">
									<i class="bi bi-graph-up-arrow"></i>
								</span>
							</div>
						</article>
					</div>

					<div class="col-md-4">
						<article class="summary-card">
							<div class="d-flex align-items-start justify-content-between">
								<div>
									<p class="summary-label">Gastos del Mes</p>
									<h2 class="summary-value">$ 1,780.00</h2>
								</div>
								<span class="summary-icon">
									<i class="bi bi-receipt"></i>
								</span>
							</div>
						</article>
					</div>
				</div>
			</section>

			<section class="row g-4">
				<div class="col-xl-8">
					<article class="table-card h-100">
						<div class="d-flex align-items-center justify-content-between mb-4">
							<h3 class="section-title">Transacciones Recientes</h3>
							<button class="btn btn-light btn-sm rounded-3 border">Ver todas</button>
						</div>

						<div class="table-responsive">
							<table class="table custom-table align-middle">
								<thead>
									<tr>
										<th>Fecha</th>
										<th>Concepto</th>
										<th>Categoría</th>
										<th class="text-end">Monto</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>14/04/2026</td>
										<td>Salario mensual</td>
										<td><span class="badge badge-soft-success">Ingreso</span></td>
										<td class="text-end amount-positive">+$ 2,500.00</td>
									</tr>
									<tr>
										<td>12/04/2026</td>
										<td>Supermercado</td>
										<td><span class="badge badge-soft-warning">Alimentación</span></td>
										<td class="text-end amount-negative">-$ 128.40</td>
									</tr>
									<tr>
										<td>10/04/2026</td>
										<td>Suscripción streaming</td>
										<td><span class="badge badge-soft-primary">Servicios</span></td>
										<td class="text-end amount-negative">-$ 18.99</td>
									</tr>
									<tr>
										<td>08/04/2026</td>
										<td>Freelance diseño</td>
										<td><span class="badge badge-soft-success">Ingreso Extra</span></td>
										<td class="text-end amount-positive">+$ 640.00</td>
									</tr>
									<tr>
										<td>06/04/2026</td>
										<td>Transporte</td>
										<td><span class="badge badge-soft-primary">Movilidad</span></td>
										<td class="text-end amount-negative">-$ 42.70</td>
									</tr>
								</tbody>
							</table>
						</div>
					</article>
				</div>

				<div class="col-xl-4">
					<article class="chart-card h-100">
						<div class="d-flex align-items-center justify-content-between mb-4">
							<h3 class="section-title">Crecimiento</h3>
							<i class="bi bi-activity text-primary"></i>
						</div>

						<div class="chart-placeholder">
							<div>
								<i class="bi bi-bar-chart-line"></i>
								<div class="fw-medium">Espacio para Gráfico de Crecimiento</div>
								<div class="small mt-2">Aquí puedes integrar un chart real más adelante.</div>
							</div>
						</div>
					</article>
				</div>
			</section>
		</main>
	</div>
</body>
</html>
