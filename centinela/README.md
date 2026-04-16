# Centinela

Guía breve para reutilizar este subproyecto en otro sistema.

## Qué hace
Centinela registra actividad de archivos PHP y errores en un panel simple de monitoreo.
Las áreas se detectan automáticamente según la carpeta del proyecto involucrada en cada registro.

## Archivos principales
- centinela.php: registra actividad y envía errores al log maestro.
- index.php: panel principal con estadísticas.
- ver_logs.php: vista de errores agrupados.
- ver_directorio.php: vista de uso de archivos PHP.
- menu_centinela.php: menú compartido del subproyecto.
- centinela_reg.log: log de actividad/uso del proyecto (raíz).
- centinela_error.log: log central de errores del proyecto (raíz).

## Instalación rápida en otro proyecto

**Paso 1**: Copiar la carpeta `centinela/` en la raíz del nuevo proyecto.

**Paso 2**: Agregar esta línea al inicio de `conexion.php`:

```php
require_once __DIR__ . '/centinela/centinela.php';
```

**Paso 3**: Verificar que el servidor tenga permisos de escritura en la raíz del proyecto (para crear `centinela_reg.log` y `centinela_error.log`).

**Paso 4**: Abrir `centinela/index.php` en el navegador.

Listo. Centinela creará los logs automáticamente y comenzará a registrar actividad desde el primer acceso.
