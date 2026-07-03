<?php
/**
 * Destructor de sesión y cierre de sesión
 */
require_once __DIR__ . '/../../controllers/AuthController.php';

AuthController::logout();
?>
