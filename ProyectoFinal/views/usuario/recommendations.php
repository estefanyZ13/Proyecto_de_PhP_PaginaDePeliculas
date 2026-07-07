<?php
/**
 * Página dedicada a las recomendaciones del usuario
 */
require_once __DIR__ . '/../../config/config.php';
requireLogin();

require_once __DIR__ . '/../../models/Preference.php';
require_once __DIR__ . '/../../models/Genre.php';

$user_id = $_SESSION['user_id'];

// Obtener recomendaciones
$recommendations = Preference::getRecommendations($user_id, 18);

$page_title = "Mis Recomendaciones";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="main-content">
    <div class="section-container" style="padding-top: 40px;">
        <h1 class="detail-title" style="font-size: 32px; margin-bottom: 8px;">Recomendado Para Ti</h1>
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 30px; max-width: 600px;">
            Nuestro sistema analiza tus géneros favoritos y tu historial de reproducción reciente para sugerirte contenidos populares que se ajusten a tus gustos.
        </p>
        
        <?php if (!empty($recommendations)): ?>
            <div class="media-grid">
                <?php foreach ($recommendations as $item): 
                    $tipo = $item['tipo'];
                    $media_id = $item['id'];
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
            <div class="admin-chart-box" style="text-align: center; padding: 40px; border-radius: 8px;">
                <span style="font-size: 40px;">⭐</span>
                <h3 style="font-size: 16px; margin-top: 12px; margin-bottom: 8px;">Aún no tenemos suficientes datos</h3>
                <p style="font-size: 13px; color: var(--text-muted); max-width: 420px; margin: 0 auto 20px;">
                    Para empezar a recibir sugerencias automáticas, por favor selecciona tus géneros favoritos en las configuraciones de tu cuenta.
                </p>
                <a href="profile.php" class="btn btn-primary btn-sm" style="border-radius: 6px;">Configurar mis Géneros</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
