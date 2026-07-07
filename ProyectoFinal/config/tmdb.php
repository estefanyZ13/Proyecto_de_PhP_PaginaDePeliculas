<?php
/**
 * Configuracion de TMDB.
 */

/**
 * La clave se lee desde variable de entorno o desde config/secrets.local.php.
 * Ese archivo local no debe subirse al repositorio.
 */
$local_secrets = __DIR__ . '/secrets.local.php';
if (file_exists($local_secrets)) {
    require_once $local_secrets;
}

if (!defined('TMDB_API_KEY')) {
    $tmdb_api_key = getenv('TMDB_API_KEY') ?: '';
    define('TMDB_API_KEY', $tmdb_api_key);
}
define('TMDB_API_BASE_URL', 'https://api.themoviedb.org/3');
define('TMDB_IMAGE_BASE_URL', 'https://image.tmdb.org/t/p/w500');
define('TMDB_BACKDROP_BASE_URL', 'https://image.tmdb.org/t/p/w1280');
?>
