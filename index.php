<?php
/**
 * Enrutador principal del proyecto
 */
require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    if (hasRole('Administrador')) {
        redirect('views/admin/dashboard.php');
    } else {
        redirect('views/usuario/home.php');
    }
} else {
    // Redirigir a login si no hay sesión activa
    redirect('views/auth/login.php');
}
?>
