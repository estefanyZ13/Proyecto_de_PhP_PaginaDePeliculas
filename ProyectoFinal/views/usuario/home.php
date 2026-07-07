<?php
/**
 * Página Principal del Usuario (Home)
 */
require_once __DIR__ . '/../../config/config.php';
requireLogin(); // Proteger página

require_once __DIR__ . '/../../models/Movie.php';
require_once __DIR__ . '/../../models/Series.php';
require_once __DIR__ . '/../../models/History.php';
require_once __DIR__ . '/../../models/Preference.php';
require_once __DIR__ . '/../../models/Genre.php';
require_once __DIR__ . '/../../models/Favorite.php';

$user_id = $_SESSION['user_id'];

// 1. Obtener Datos para los carruseles
$continue_watching = History::getByUser($user_id, 8);
$recommended = Preference::getRecommendations($user_id, 10);

// Populares (Mezcla de películas y series más visitadas)
$popular_movies = Movie::getPopular(5);
$popular_series = Series::getPopular(5);
$popular = array_merge($popular_movies, $popular_series);
usort($popular, function($a, $b) {
    return $b['clicks'] <=> $a['clicks'];
});
$popular = array_slice($popular, 0, 10);

// Nuevos Lanzamientos (Ordenados por año)
$recent_movies = Movie::getRecent(5);
$recent_series = Series::getRecent(5);
$new_releases = array_merge($recent_movies, $recent_series);
usort($new_releases, function($a, $b) {
    return $b['año'] <=> $a['año'];
});
$new_releases = array_slice($new_releases, 0, 10);

// Contenido por Géneros
$action_content = array_merge(Movie::getByGenre(1, 6), Series::getByGenre(1, 6)); // Acción
$horror_content = array_merge(Movie::getByGenre(2, 6), Series::getByGenre(2, 6)); // Terror
$comedy_content = array_merge(Movie::getByGenre(3, 6), Series::getByGenre(3, 6)); // Comedia
$drama_content  = array_merge(Movie::getByGenre(6, 6), Series::getByGenre(6, 6)); // Drama

// 2. Título de la página y Featured Hero Content
// Elegimos el primer elemento del catálogo recomendado o popular como película destacada en el banner
$featured = !empty($recommended) ? $recommended[0] : (!empty($popular) ? $popular[0] : null);

// Si no hay ninguno, usamos un mock por defecto
if (!$featured) {
    $featured = [
        'id' => 1,
        'tipo' => 'series',
        'titulo' => 'The Mandalorian',
        'descripcion' => 'Un cazarrecompensas solitario viaja por los confines de la galaxia, lejos de la autoridad de la Nueva República.',
        'año' => 2019,
        'imagen_url' => 'assets/img/mandalorian.svg',
        'video_url' => 'https://www.youtube.com/embed/aOC8E8z_ifw'
    ];
} else {
    // Si viene de BD, cargar sus urls y video
    if ($featured['tipo'] === 'movie') {
        $detail = Movie::getById($featured['id']);
    } else {
        $detail = Series::getById($featured['id']);
    }
    $featured['video_url'] = $detail['video_url'] ?? '';
}

// Comprobar si el contenido destacado está en favoritos
$is_featured_fav = Favorite::isFavorite($user_id, 
    $featured['tipo'] === 'movie' ? $featured['id'] : null,
    $featured['tipo'] === 'series' ? $featured['id'] : null
);

$page_title = "Inicio";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

// Helper local para renderizar tarjetas
function renderMediaCard($item) {
    $tipo = $item['tipo'] ?? (isset($item['temporadas']) ? 'series' : 'movie');
    $media_id = $item['id'];
    $titulo = $item['titulo'];
    $img = mediaUrl($item['imagen_url']);
    $año = $item['año'];
    $progreso = $item['progreso'] ?? null;
    ?>
    <div class="media-card" onclick="location.href='<?php echo BASE_URL; ?>views/usuario/detail.php?id=<?php echo $media_id; ?>&tipo=<?php echo $tipo; ?>'">
        <img src="<?php echo $img; ?>" alt="<?php echo clean($titulo); ?>" onerror="this.src='<?php echo BASE_URL; ?>assets/img/placeholder.svg'">
        <div class="media-card-overlay">
            <div class="media-card-title"><?php echo clean($titulo); ?></div>
            <div class="media-card-meta">
                <span><?php echo $año; ?></span>
                <span><?php echo ($tipo === 'movie') ? 'Película' : 'Serie'; ?></span>
            </div>
        </div>
        <?php if ($progreso !== null && $progreso > 0): ?>
            <div class="css-bar-wrapper" style="position: absolute; bottom: 0; left: 0; right: 0; height: 6px; border-radius: 0; border: none; background: rgba(0,0,0,0.5);">
                <div class="css-bar-progress" style="width: <?php echo $progreso; ?>%; height: 100%; border-radius: 0; background: linear-gradient(to right, var(--accent), #00d28a);"></div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
?>

<div class="main-content">
    
    <!-- ── FEATURED HERO BANNER ── -->
    <?php
    // Obtener imagen de fondo (backdrop) o usar la misma poster card
    $hero_bg = mediaUrl($featured['imagen_url']);
    ?>
    <div class="hero-banner" style="background-image: url('<?php echo $hero_bg; ?>');">
        <div class="hero-info">
            <h1 class="hero-title"><?php echo clean($featured['titulo']); ?></h1>
            <div class="hero-meta">
                <span class="hero-badge">U/A 13+</span>
                <span><?php echo $featured['año']; ?></span>
                <span><?php echo ($featured['tipo'] === 'movie') ? 'Película' : 'Serie'; ?></span>
            </div>
            <p class="hero-description"><?php echo clean($featured['descripcion']); ?></p>
            
            <div class="hero-buttons">
                <!-- Botón Play lanza el modal del reproductor -->
                <button class="btn btn-primary" id="play-video-btn" 
                        data-video-url="<?php echo $featured['video_url']; ?>" 
                        data-media-id="<?php echo $featured['id']; ?>" 
                        data-media-type="<?php echo $featured['tipo']; ?>">
                    <span class="btn-icon">▶</span> <span class="btn-text">Ver Ahora</span>
                </button>
                
                <!-- Botón Mi Lista togglea favoritos por AJAX -->
                <button class="btn btn-secondary" onclick="toggleFavorite(this, <?php echo $featured['id']; ?>, '<?php echo $featured['tipo']; ?>')" 
                        style="<?php echo $is_featured_fav ? 'background-color: var(--success);' : ''; ?>">
                    <span class="btn-icon"><?php echo $is_featured_fav ? '✓' : '+'; ?></span> 
                    <span class="btn-text"><?php echo $is_featured_fav ? 'En Favoritos' : 'Mi Lista'; ?></span>
                </button>
            </div>
        </div>
    </div>

    <!-- ── COMIENZAN LAS SECCIONES DE CARRUSELES ── -->
    
    <!-- 1. Continuar Viendo (Solo si hay historial) -->
    <?php if (!empty($continue_watching)): ?>
        <div class="section-container">
            <h2 class="section-title">Continuar viendo</h2>
            <div class="carousel-outer">
                <div class="carousel-inner">
                    <?php foreach ($continue_watching as $item) { renderMediaCard($item); } ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 2. Recomendado Para Ti (Recomendaciones dinámicas) -->
    <div class="section-container">
        <h2 class="section-title">Recomendado para ti</h2>
        <div class="carousel-outer">
            <div class="carousel-inner">
                <?php 
                if (!empty($recommended)) {
                    foreach ($recommended as $item) { renderMediaCard($item); }
                } else {
                    echo "<p style='color:var(--text-muted); font-size:14px; padding: 20px 0;'>Selecciona tus géneros favoritos en tu perfil para obtener recomendaciones personalizadas.</p>";
                }
                ?>
            </div>
        </div>
    </div>

    <!-- 3. Populares -->
    <div class="section-container">
        <h2 class="section-title">Populares</h2>
        <div class="carousel-outer">
            <div class="carousel-inner">
                <?php foreach ($popular as $item) { renderMediaCard($item); } ?>
            </div>
        </div>
    </div>

    <!-- 4. Nuevos Lanzamientos -->
    <div class="section-container">
        <h2 class="section-title">Nuevos lanzamientos</h2>
        <div class="carousel-outer">
            <div class="carousel-inner">
                <?php foreach ($new_releases as $item) { renderMediaCard($item); } ?>
            </div>
        </div>
    </div>

    <!-- 5. Carrusel de Acción -->
    <?php if (!empty($action_content)): ?>
        <div class="section-container">
            <h2 class="section-title">Acción</h2>
            <div class="carousel-outer">
                <div class="carousel-inner">
                    <?php foreach ($action_content as $item) { renderMediaCard($item); } ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 6. Carrusel de Comedia -->
    <?php if (!empty($comedy_content)): ?>
        <div class="section-container">
            <h2 class="section-title">Comedia</h2>
            <div class="carousel-outer">
                <div class="carousel-inner">
                    <?php foreach ($comedy_content as $item) { renderMediaCard($item); } ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 7. Carrusel de Terror -->
    <?php if (!empty($horror_content)): ?>
        <div class="section-container">
            <h2 class="section-title">Terror</h2>
            <div class="carousel-outer">
                <div class="carousel-inner">
                    <?php foreach ($horror_content as $item) { renderMediaCard($item); } ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 8. Carrusel de Drama -->
    <?php if (!empty($drama_content)): ?>
        <div class="section-container">
            <h2 class="section-title">Drama</h2>
            <div class="carousel-outer">
                <div class="carousel-inner">
                    <?php foreach ($drama_content as $item) { renderMediaCard($item); } ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- ── MODAL REPRODUCTOR DE VIDEO ── -->
<div class="modal-overlay" id="video-player-modal">
    <div class="modal-content-box">
        <button class="modal-close-btn" id="modal-close-btn">&times;</button>
        <div class="video-container">
            <iframe id="modal-video-iframe" src="" allow="autoplay; encrypted-media" allowfullscreen></iframe>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
