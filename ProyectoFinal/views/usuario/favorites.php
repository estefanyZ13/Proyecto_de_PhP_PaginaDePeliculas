<?php
/**
 * Lista de favoritos del usuario
 */
require_once __DIR__ . '/../../config/config.php';
requireLogin();

require_once __DIR__ . '/../../models/Favorite.php';

$user_id = $_SESSION['user_id'];

// Obtener la lista
$favorites = Favorite::getByUser($user_id);

$page_title = "Mi Lista";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="main-content">
    <div class="section-container" style="padding-top: 40px;">
        <h1 class="detail-title" style="font-size: 32px; margin-bottom: 8px;">Mi Lista</h1>
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 30px;">
            Películas y series que has guardado para ver más tarde.
        </p>
        
        <?php if (!empty($favorites)): ?>
            <div class="media-grid">
                <?php foreach ($favorites as $item): 
                    $tipo = $item['tipo'];
                    $media_id = $item['media_id'];
                    $titulo = $item['titulo'];
                    $img = mediaUrl($item['imagen_url']);
                    $año = $item['año'];
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
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 80px 20px; color: var(--text-muted);">
                <span style="font-size: 48px;">➕</span>
                <p style="margin-top: 16px; font-size: 16px;">Aún no has agregado contenido a tu lista.</p>
                <p style="font-size: 13px; margin-top: 6px;">Explora el catálogo y pulsa "+ Mi Lista" para guardarlos aquí.</p>
                <a href="catalog.php" class="btn btn-primary btn-sm" style="margin-top: 20px; border-radius: 6px;">Ver el Catálogo</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
