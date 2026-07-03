<?php
/**
 * Catálogo general de Películas y Series
 */
require_once __DIR__ . '/../../config/config.php';
requireLogin();

require_once __DIR__ . '/../../models/Movie.php';
require_once __DIR__ . '/../../models/Series.php';
require_once __DIR__ . '/../../models/Genre.php';

$user_id = $_SESSION['user_id'];

// Obtener filtros del GET
$type_filter = clean($_GET['type'] ?? 'all'); // all, movie, series
$genre_filter = isset($_GET['genre_id']) ? (int)$_GET['genre_id'] : 0;
$search_query = clean($_GET['q'] ?? '');

// Registrar visitas de estadísticas si filtran por género
if ($genre_filter > 0) {
    Genre::logVisit($user_id, $genre_filter);
}

// Cargar géneros para el filtro
$genres = Genre::getAll();

// Obtener películas y series según filtros
$items = [];

if ($type_filter === 'all' || $type_filter === 'movie') {
    $movies = Movie::getAll($search_query, $genre_filter);
    foreach ($movies as $m) {
        $m['tipo'] = 'movie';
        $items[] = $m;
    }
}

if ($type_filter === 'all' || $type_filter === 'series') {
    $series = Series::getAll($search_query, $genre_filter);
    foreach ($series as $s) {
        $s['tipo'] = 'series';
        $items[] = $s;
    }
}

// Si es "all", reordenar la lista mezclada alfabéticamente por título
if ($type_filter === 'all') {
    usort($items, function($a, $b) {
        return strcasecmp($a['titulo'], $b['titulo']);
    });
}

$page_title = "Catálogo";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="main-content">
    <div class="section-container" style="padding-top: 40px;">
        
        <div class="catalog-header">
            <h1 class="detail-title" style="font-size: 32px; margin-bottom: 0;">Explorar Catálogo</h1>
            
            <!-- Barra de filtros -->
            <form action="" method="GET" class="filters-bar">
                <!-- Buscador de texto -->
                <input type="text" name="q" class="form-control" style="width: 240px; padding: 10px 16px; border-radius: 6px;" 
                       placeholder="Buscar título..." value="<?php echo clean($search_query); ?>">
                
                <!-- Filtro Tipo -->
                <select name="type" class="filter-select">
                    <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>Todo el catálogo</option>
                    <option value="movie" <?php echo $type_filter === 'movie' ? 'selected' : ''; ?>>Películas</option>
                    <option value="series" <?php echo $type_filter === 'series' ? 'selected' : ''; ?>>Series</option>
                </select>
                
                <!-- Filtro Género -->
                <select name="genre_id" class="filter-select">
                    <option value="0">Todos los géneros</option>
                    <?php foreach ($genres as $g): ?>
                        <option value="<?php echo $g['id']; ?>" <?php echo $genre_filter === $g['id'] ? 'selected' : ''; ?>>
                            <?php echo clean($g['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" class="btn btn-primary" style="border-radius: 6px;">Filtrar</button>
                <?php if (!empty($search_query) || $genre_filter > 0 || $type_filter !== 'all'): ?>
                    <a href="catalog.php" class="btn btn-secondary" style="border-radius: 6px;">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Grilla de contenidos -->
        <?php if (!empty($items)): ?>
            <div class="media-grid">
                <?php foreach ($items as $item): 
                    $tipo = $item['tipo'];
                    $media_id = $item['id'];
                    $titulo = $item['titulo'];
                    $img = BASE_URL . $item['imagen_url'];
                    $año = $item['año'];
                ?>
                    <div class="media-card" onclick="location.href='<?php echo BASE_URL; ?>views/usuario/detail.php?id=<?php echo $media_id; ?>&tipo=<?php echo $tipo; ?>'">
                        <img src="<?php echo $img; ?>" alt="<?php echo clean($titulo); ?>" onerror="this.src='<?php echo BASE_URL; ?>assets/img/placeholder.jpg'">
                        <div class="media-card-overlay">
                            <div class="media-card-title"><?php echo clean($titulo); ?></div>
                            <div class="media-card-meta">
                                <span><?php echo $año; ?></span>
                                <span><?php echo ($tipo === 'movie') ? 'Película' : 'Serie'; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 80px 20px; color: var(--text-muted);">
                <span style="font-size: 48px;">🔍</span>
                <p style="margin-top: 16px; font-size: 16px;">No encontramos películas ni series que coincidan con tu búsqueda.</p>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
