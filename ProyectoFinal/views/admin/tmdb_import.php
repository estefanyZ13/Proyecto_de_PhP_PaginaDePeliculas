<?php
/**
 * Importador de catalogo desde TMDB.
 */
require_once __DIR__ . '/../../config/config.php';
requireRole('Administrador');

require_once __DIR__ . '/../../services/TMDBService.php';

$result = null;
$error = null;
$type = clean($_POST['type'] ?? 'all');
$pages = isset($_POST['pages']) ? (int)$_POST['pages'] : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'La sesión expiró. Recargue la página e intente nuevamente.';
    } elseif (!in_array($type, ['all', 'movie', 'series'], true)) {
        $type = 'all';
        $pages = max(1, min(5, $pages));
        $result = TMDBService::import($type, $pages);
    } else {
        $pages = max(1, min(5, $pages));
        $result = TMDBService::import($type, $pages);
    }

    if ($result && !empty($result['errors'])) {
        $error = implode(' ', array_unique($result['errors']));
    }
}

$page_title = "Importar TMDB";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="main-content">
    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <ul class="admin-sidebar-menu">
                <li><a href="dashboard.php">📊 Panel de Control</a></li>
                <li><a href="movies.php">🎬 Películas (CRUD)</a></li>
                <li><a href="series.php">📺 Series (CRUD)</a></li>
                <li><a href="genres.php">🏷️ Géneros</a></li>
                <li><a href="users.php">👥 Usuarios</a></li>
                <li class="active"><a href="tmdb_import.php">⬇ Importar TMDB</a></li>
                <div class="dropdown-divider" style="margin: 10px 24px;"></div>
                <li><a href="../usuario/home.php">🏠 Volver al Sitio</a></li>
            </ul>
        </aside>

        <main class="admin-container">
            <div class="admin-header">
                <div>
                    <h1 class="detail-title" style="font-size: 28px; margin-bottom: 4px;">Importar desde TMDB</h1>
                    <p style="color: var(--text-muted); font-size: 13px;">Agrega películas y series populares al catálogo local.</p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert-message alert-error">⚠️ <?php echo clean($error); ?></div>
            <?php endif; ?>

            <?php if ($result): ?>
                <div class="alert-message alert-success">
                    Importación terminada. Películas: <?php echo (int)$result['movies_imported']; ?>,
                    series: <?php echo (int)$result['series_imported']; ?>,
                    omitidos: <?php echo (int)$result['skipped']; ?>.
                </div>
            <?php endif; ?>

            <div class="admin-chart-box" style="margin-bottom: 30px;">
                <h3 class="admin-chart-title">Traer catálogo</h3>

                <form action="" method="POST">
                    <?php csrfField(); ?>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                        <div class="form-group">
                            <label for="type">Contenido</label>
                            <select name="type" id="type" class="filter-select" style="padding: 12px;">
                                <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>Películas y series</option>
                                <option value="movie" <?php echo $type === 'movie' ? 'selected' : ''; ?>>Solo películas</option>
                                <option value="series" <?php echo $type === 'series' ? 'selected' : ''; ?>>Solo series</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="pages">Cantidad</label>
                            <select name="pages" id="pages" class="filter-select" style="padding: 12px;">
                                <option value="1" <?php echo $pages === 1 ? 'selected' : ''; ?>>20 resultados por tipo</option>
                                <option value="2" <?php echo $pages === 2 ? 'selected' : ''; ?>>40 resultados por tipo</option>
                                <option value="3" <?php echo $pages === 3 ? 'selected' : ''; ?>>60 resultados por tipo</option>
                                <option value="5" <?php echo $pages === 5 ? 'selected' : ''; ?>>100 resultados por tipo</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm">⬇ Importar catálogo</button>
                </form>
            </div>

            <div class="admin-chart-box">
                <h3 class="admin-chart-title">Notas</h3>
                <p style="color: var(--text-muted); font-size: 13px; line-height: 1.7; margin: 0;">
                    Se importan títulos populares de TMDB, pósters externos y géneros. Los títulos repetidos por nombre y año se omiten para evitar duplicados.
                    Los tráilers se agregan cuando TMDB tiene video de YouTube disponible.
                </p>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
