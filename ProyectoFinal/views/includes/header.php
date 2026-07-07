<?php
/**
 * Header común del proyecto
 */
require_once __DIR__ . '/../../config/config.php';

// Leer cookie de tema (oscuro por defecto)
$theme = getCookieVal('theme', 'dark');
$theme_class = ($theme === 'light') ? 'theme-light' : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? clean($page_title) . " | PopCornTime" : "PopCornTime - Plataforma de Streaming"; ?></title>
    
    <!-- Hojas de estilo -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/js/css/style.css">
    
    <!-- Inyectar BASE_URL para JS -->
    <script>
        window.BASE_URL = "<?php echo BASE_URL; ?>";
        window.CSRF_TOKEN = "<?php echo csrfToken(); ?>";
    </script>
</head>
<body class="<?php echo $theme_class; ?>">
