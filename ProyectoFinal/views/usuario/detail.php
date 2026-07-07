<?php
/**
 * Página de Detalle e Interacción de un Contenido
 */
require_once __DIR__ . '/../../config/config.php';
requireLogin();

require_once __DIR__ . '/../../models/Movie.php';
require_once __DIR__ . '/../../models/Series.php';
require_once __DIR__ . '/../../models/Favorite.php';
require_once __DIR__ . '/../../models/Genre.php';
require_once __DIR__ . '/../../models/Preference.php';

$user_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tipo = clean($_GET['tipo'] ?? 'movie'); // movie, series

if ($id <= 0) {
    redirect('views/usuario/home.php');
}

// 1. Cargar datos del contenido e incrementar clicks
$item = null;
if ($tipo === 'movie') {
    $item = Movie::getById($id);
    if ($item) {
        Movie::incrementClicks($id);
        Genre::logVisit($user_id, $item['genero_id']);
    }
} else {
    $item = Series::getById($id);
    if ($item) {
        Series::incrementClicks($id);
        Genre::logVisit($user_id, $item['genero_id']);
    }
}

if (!$item) {
    redirect('views/usuario/home.php');
}

// 2. Procesar nueva calificación / reseña (Seguridad: Prepared statements y sanitización)
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    if (!verifyCsrfToken()) {
        $msg = ['error' => 'La sesión expiró. Recargue la página e intente nuevamente.'];
    } else {
    $stars = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comentario = clean($_POST['comentario'] ?? '');
    
    if ($stars < 1 || $stars > 5) {
        $msg = ['error' => 'Por favor selecciona una puntuación entre 1 y 5 estrellas.'];
    } else {
        // Guardar calificación
        global $conn;
        
        // Verificar si ya calificó
        if ($tipo === 'movie') {
            $stmt = $conn->prepare("SELECT id FROM calificaciones WHERE usuario_id = ? AND pelicula_id = ?");
            $stmt->bind_param("ii", $user_id, $id);
        } else {
            $stmt = $conn->prepare("SELECT id FROM calificaciones WHERE usuario_id = ? AND serie_id = ?");
            $stmt->bind_param("ii", $user_id, $id);
        }
        $stmt->execute();
        $exist = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($exist) {
            // Actualizar
            $stmt = $conn->prepare("UPDATE calificaciones SET calificacion = ?, comentario = ?, fecha = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("isi", $stars, $comentario, $exist['id']);
        } else {
            // Insertar
            if ($tipo === 'movie') {
                $stmt = $conn->prepare("INSERT INTO calificaciones (usuario_id, pelicula_id, calificacion, comentario) VALUES (?, ?, ?, ?)");
            } else {
                $stmt = $conn->prepare("INSERT INTO calificaciones (usuario_id, serie_id, calificacion, comentario) VALUES (?, ?, ?, ?)");
            }
            $stmt->bind_param("iiis", $user_id, $id, $stars, $comentario);
        }
        
        if ($stmt->execute()) {
            $msg = ['success' => '¡Tu valoración ha sido guardada con éxito!'];
        } else {
            $msg = ['error' => 'Error al guardar la calificación.'];
        }
        $stmt->close();
    }
    }
}

// 3. Obtener todas las calificaciones
global $conn;
if ($tipo === 'movie') {
    $stmt = $conn->prepare("
        SELECT c.*, u.username 
        FROM calificaciones c 
        JOIN usuarios u ON c.usuario_id = u.id 
        WHERE c.pelicula_id = ? 
        ORDER BY c.fecha DESC
    ");
} else {
    $stmt = $conn->prepare("
        SELECT c.*, u.username 
        FROM calificaciones c 
        JOIN usuarios u ON c.usuario_id = u.id 
        WHERE c.serie_id = ? 
        ORDER BY c.fecha DESC
    ");
}
$stmt->bind_param("i", $id);
$stmt->execute();
$reviews_res = $stmt->get_result();
$reviews = [];
$total_stars = 0;
while ($row = $reviews_res->fetch_assoc()) {
    $reviews[] = $row;
    $total_stars += $row['calificacion'];
}
$stmt->close();

$avg_stars = count($reviews) > 0 ? round($total_stars / count($reviews), 1) : 0;

// Comprobar si es favorito
$is_fav = Favorite::isFavorite($user_id, $tipo === 'movie' ? $id : null, $tipo === 'series' ? $id : null);

// Recomendaciones relacionadas con el contenido abierto y los gustos del usuario
$recommendations = Preference::getRecommendations($user_id, 8, [
    'tipo' => $tipo,
    'id' => $id,
    'genero_id' => $item['genero_id']
]);

$page_title = $item['titulo'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="main-content">
    <div class="detail-wrapper">
        
        <!-- Backdrop Banner -->
        <div class="detail-backdrop" style="background-image: url('<?php echo mediaUrl($item['imagen_url']); ?>');"></div>
        
        <div class="detail-container">
            
            <!-- Poster -->
            <div class="detail-poster">
                <img src="<?php echo mediaUrl($item['imagen_url']); ?>" alt="<?php echo clean($item['titulo']); ?>" onerror="this.src='<?php echo BASE_URL; ?>assets/img/placeholder.svg'">
            </div>
            
            <!-- Info -->
            <div class="detail-info">
                <h1 class="detail-title"><?php echo clean($item['titulo']); ?></h1>
                
                <div class="detail-meta-list">
                    <span class="hero-badge">U/A 13+</span>
                    <span>Año: <?php echo $item['año']; ?></span>
                    <span>Género: <?php echo clean($item['genero_nombre']); ?></span>
                    
                    <?php if ($tipo === 'movie'): ?>
                        <span>Duración: <?php echo $item['duracion']; ?> mins</span>
                    <?php else: ?>
                        <span>Temporadas: <?php echo $item['temporadas']; ?> (<?php echo $item['episodios']; ?> Ep)</span>
                    <?php endif; ?>
                    
                    <?php if ($avg_stars > 0): ?>
                        <span style="color: var(--warning);">⭐ <?php echo $avg_stars; ?> / 5.0 (<?php echo count($reviews); ?> v)</span>
                    <?php else: ?>
                        <span style="color: var(--text-muted);">Sin valoraciones</span>
                    <?php endif; ?>
                </div>
                
                <p class="detail-synopsis"><?php echo clean($item['descripcion']); ?></p>
                
                <div class="detail-actions">
                    <button class="btn btn-primary" id="play-video-btn" 
                            data-video-url="<?php echo $item['video_url']; ?>" 
                            data-media-id="<?php echo $id; ?>" 
                            data-media-type="<?php echo $tipo; ?>">
                        <span class="btn-icon">▶</span> <span class="btn-text">Ver Ahora</span>
                    </button>
                    
                    <button class="btn btn-secondary" onclick="toggleFavorite(this, <?php echo $id; ?>, '<?php echo $tipo; ?>')"
                            style="<?php echo $is_fav ? 'background-color: var(--success);' : ''; ?>">
                        <span class="btn-icon"><?php echo $is_fav ? '✓' : '+'; ?></span> 
                        <span class="btn-text"><?php echo $is_fav ? 'En Favoritos' : 'Mi Lista'; ?></span>
                    </button>
                </div>
            </div>
            
            <!-- Calificaciones y Comentarios -->
            <div class="rating-section">
                <h2 class="rating-title">Valoraciones y Reseñas</h2>
                
                <?php if ($msg): ?>
                    <div class="alert-message <?php echo isset($msg['error']) ? 'alert-error' : 'alert-success'; ?>">
                        <?php echo clean($msg['error'] ?? $msg['success']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="rating-grid">
                    
                    <!-- Formulario de calificación -->
                    <div class="admin-chart-box" style="height: fit-content;">
                        <h3 style="font-size: 15px; margin-bottom: 16px;">Escribe tu valoración</h3>
                        <form action="" method="POST">
                            <?php csrfField(); ?>
                            <div class="form-group">
                                <label>Puntuación</label>
                                <div class="star-rating">
                                    <input type="radio" id="star5" name="rating" value="5" required><label for="star5">★</label>
                                    <input type="radio" id="star4" name="rating" value="4"><label for="star4">★</label>
                                    <input type="radio" id="star3" name="rating" value="3"><label for="star3">★</label>
                                    <input type="radio" id="star2" name="rating" value="2"><label for="star2">★</label>
                                    <input type="radio" id="star1" name="rating" value="1"><label for="star1">★</label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="comentario">Comentario</label>
                                <textarea name="comentario" id="comentario" class="form-control" rows="4" placeholder="¿Qué te pareció este contenido?..." required style="resize: none;"></textarea>
                            </div>
                            
                            <button type="submit" name="submit_rating" class="btn btn-primary btn-sm" style="width: 100%; border-radius: 6px;">Enviar Valoración</button>
                        </form>
                    </div>
                    
                    <!-- Lista de opiniones -->
                    <div class="reviews-list">
                        <h3 style="font-size: 15px; margin-bottom: 10px;">Opiniones de usuarios (<?php echo count($reviews); ?>)</h3>
                        
                        <?php if (!empty($reviews)): ?>
                            <?php foreach ($reviews as $rev): ?>
                                <div class="review-card">
                                    <div class="review-header">
                                        <span class="review-user">@<?php echo clean($rev['username']); ?></span>
                                        <span class="review-stars"><?php echo str_repeat('⭐', $rev['calificacion']); ?></span>
                                    </div>
                                    <p class="review-comment"><?php echo clean($rev['comentario']); ?></p>
                                    <div class="review-date"><?php echo date("d/m/Y H:i", strtotime($rev['fecha'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: var(--text-muted); font-size: 13px; text-align: center; padding: 40px 0;">Sé el primero en escribir una opinión sobre este contenido.</p>
                        <?php endif; ?>
                    </div>
                    
                </div>
            </div>

            <?php if (!empty($recommendations)): ?>
                <div class="section-container" style="padding-top: 8px; padding-bottom: 48px;">
                    <h2 class="section-title">También te puede gustar</h2>
                    <div class="media-grid">
                        <?php foreach ($recommendations as $rec):
                            $rec_tipo = $rec['tipo'];
                            $rec_id = $rec['id'];
                            $rec_img = mediaUrl($rec['imagen_url']);
                        ?>
                            <div class="media-card" onclick="location.href='<?php echo BASE_URL; ?>views/usuario/detail.php?id=<?php echo $rec_id; ?>&tipo=<?php echo $rec_tipo; ?>'">
                                <img src="<?php echo $rec_img; ?>" alt="<?php echo clean($rec['titulo']); ?>" onerror="this.src='<?php echo BASE_URL; ?>assets/img/placeholder.svg'">
                                <div class="media-card-overlay">
                                    <div class="media-card-title"><?php echo clean($rec['titulo']); ?></div>
                                    <div class="media-card-meta">
                                        <span><?php echo (int)$rec['año']; ?></span>
                                        <span><?php echo ($rec_tipo === 'movie') ? 'Película' : 'Serie'; ?></span>
                                        <?php if (!empty($rec['genero_nombre'])): ?>
                                            <span><?php echo clean($rec['genero_nombre']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
        </div>
        
    </div>
</div>

<!-- Modal del Reproductor -->
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
