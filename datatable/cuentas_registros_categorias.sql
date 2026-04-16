-- Script SQL: tablas y datos para datatable
-- Base sugerida: MySQL/MariaDB

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Opcional: selecciona tu base de datos
-- USE acerosapp;

DROP TABLE IF EXISTS cuentas_registros;
DROP TABLE IF EXISTS categorias;

CREATE TABLE categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    tipo ENUM('ingreso', 'egreso') NOT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_categorias_nombre_tipo (nombre, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cuentas_registros (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    concepto VARCHAR(180) NOT NULL,
    categoria INT UNSIGNED NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    descripcion VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_cuentas_fecha (fecha),
    KEY idx_cuentas_categoria (categoria),
    CONSTRAINT fk_cuentas_categoria
        FOREIGN KEY (categoria)
        REFERENCES categorias(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categorias inventadas
INSERT INTO categorias (id, nombre, tipo) VALUES
(1, 'Salario', 'ingreso'),
(2, 'Ingreso Extra', 'ingreso'),
(3, 'Alimentacion', 'egreso'),
(4, 'Servicios', 'egreso'),
(5, 'Movilidad', 'egreso'),
(6, 'Salud', 'egreso'),
(7, 'Entretenimiento', 'egreso'),
(8, 'Inversiones', 'ingreso');

-- Registros de ejemplo (incluye los de tu tabla actual y algunos extra)
INSERT INTO cuentas_registros (fecha, concepto, categoria, monto, descripcion) VALUES
('2026-04-14', 'Salario mensual', 1, 2500.00, 'Deposito de sueldo del mes'),
('2026-04-12', 'Supermercado', 3, -128.40, 'Compra semanal de alimentos'),
('2026-04-10', 'Suscripcion streaming', 4, -18.99, 'Plan mensual de video'),
('2026-04-08', 'Freelance diseno', 2, 640.00, 'Proyecto de branding para cliente'),
('2026-04-06', 'Transporte', 5, -42.70, 'Carga de bencina y peajes'),
('2026-04-03', 'Control medico', 6, -55.00, 'Consulta preventiva'),
('2026-03-28', 'Dividendos fondo', 8, 95.30, 'Rendimiento mensual de inversion'),
('2026-03-20', 'Cine y comida', 7, -34.50, 'Salida fin de semana'),
('2026-03-15', 'Venta de equipo usado', 2, 180.00, 'Venta por marketplace'),
('2026-03-12', 'Pago de internet', 4, -27.90, 'Servicio hogar');
