<?php

// Conectamos con centinela
$centinelaBootstrap = realpath(__DIR__ . '/../centinela/centinela.php');
if ($centinelaBootstrap !== false) {
    require_once $centinelaBootstrap;
}

// Conectamos con localhost
$servidor = 'localhost';
$nombre_bd = 'acerosapp';
$usuario = 'root';
$password = '';

// Crear conexión
$conexion = mysqli_connect($servidor, $usuario, $password, $nombre_bd);

// Forzar selección de base de datos
mysqli_select_db($conexion, $nombre_bd);

// Verificar la conexión
if (!$conexion) {
    die("La conexión falló: " . mysqli_connect_error());
}

?>