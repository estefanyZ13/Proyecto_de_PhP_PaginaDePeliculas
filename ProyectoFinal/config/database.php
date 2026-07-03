<?php
/**
 * Conexión a la base de datos MySQL
 */

$host = "localhost";
$user = "root";
$password = "";
$dbname = "proyectofinal";

// Crear conexión
$conn = new mysqli($host, $user, $password, $dbname);

// Validar errores
if ($conn->connect_error) {
    // Registrar error y detener ejecución sin exponer credenciales
    error_log("Error de conexión a la BD: " . $conn->connect_error);
    die("Error crítico: No se pudo conectar a la base de datos.");
}

// Establecer conjunto de caracteres UTF-8
$conn->set_charset("utf8mb4");

// Exponer la conexión globalmente
global $conn;
?>
