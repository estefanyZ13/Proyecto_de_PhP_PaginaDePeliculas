<?php
/**
 * Configuración global y funciones de utilidad
 */

// Iniciar sesión de forma segura si no ha sido iniciada
if (session_status() == PHP_SESSION_NONE) {
    // Aumentar seguridad de la sesión
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Configuración de errores - No mostrar errores sensibles en producción
ini_set('display_errors', 0);
ini_set('log_errors', 1);
$log_dir = dirname(__DIR__) . '/logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0777, true);
}
ini_set('error_log', $log_dir . '/app.log');

// Determinar dinámicamente la URL base del proyecto
// Usa la ubicación física de config.php (siempre en /config/) para calcular la raíz del proyecto
$config_dir = str_replace('\\', '/', __DIR__);              // .../ProyectoFinal/config
$project_dir_abs = str_replace('\\', '/', dirname($config_dir)); // .../ProyectoFinal
$doc_root = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/'));

// Obtener la ruta relativa del proyecto respecto al document root
$project_dir = str_replace($doc_root, '', $project_dir_abs);
$project_dir = '/' . trim($project_dir, '/') . '/';

// Asegurar que no quede doble slash
if ($project_dir === '//') {
    $project_dir = '/';
}

define('BASE_URL', $project_dir);

/**
 * Redirecciona a una ruta interna del proyecto
 */
function redirect($path) {
    header("Location: " . BASE_URL . ltrim($path, '/'));
    exit;
}

/**
 * Sanitiza datos de entrada para prevenir XSS
 */
function clean($data) {
    if (is_array($data)) {
        return array_map('clean', $data);
    }
    return htmlspecialchars(trim(strip_tags($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Registra un mensaje de error en el log del proyecto
 */
function log_app_error($message) {
    error_log("[" . date("Y-m-d H:i:s") . "] " . $message . PHP_EOL, 3, dirname(__DIR__) . '/logs/app.log');
}

/**
 * Verifica si el usuario ha iniciado sesión
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Verifica el rol del usuario actual
 */
function getRoleName() {
    return $_SESSION['rol_nombre'] ?? '';
}

/**
 * Verifica si el usuario tiene un rol determinado
 */
function hasRole($role) {
    return isLoggedIn() && getRoleName() === $role;
}

/**
 * Guardia de ruta: Requiere que el usuario esté logueado
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('views/auth/login.php');
    }
}

/**
 * Guardia de ruta: Requiere que el usuario tenga un rol específico
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        // Redirigir según el rol del usuario para evitar loops
        if (getRoleName() === 'Administrador') {
            redirect('views/admin/dashboard.php');
        } else {
            redirect('views/usuario/home.php');
        }
    }
}

/**
 * Obtiene el valor de una cookie de forma segura
 */
function getCookieVal($name, $default = '') {
    return isset($_COOKIE[$name]) ? clean($_COOKIE[$name]) : $default;
}

/**
 * Establece una cookie de forma segura
 */
function setCookieSecure($name, $value, $expiry = 86400 * 30) {
    // 30 días por defecto
    setcookie($name, $value, time() + $expiry, BASE_URL, "", false, true);
}
?>
