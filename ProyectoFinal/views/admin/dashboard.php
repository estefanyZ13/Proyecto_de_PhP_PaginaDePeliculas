<?php
/**
 * Panel de Administración - Dashboard Principal
 */
require_once __DIR__ . '/../../config/config.php';
requireRole('Administrador'); // Guardia de acceso de administrador

require_once __DIR__ . '/../../controllers/StatsController.php';
require_once __DIR__ . '/../../models/History.php';

// 1. Cargar Estadísticas
$summary = StatsController::getDashboardSummary();
$popular_genres = StatsController::getMostVisitedGenres(5);
$popular_media = StatsController::getMostPopularMovies(5);
$recent_activity = History::getRecentActivity(8);

// Preparar datos para los gráficos de CSS
$max_visitas = !empty($popular_genres) ? max(array_column($popular_genres, 'visitas')) : 1;
$max_clicks = !empty($popular_media) ? max(array_column($popular_media, 'clicks')) : 1;

$page_title = "Panel Administrativo";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="main-content">
    <div class="admin-wrapper">
        
        <!-- Sidebar lateral -->
        <aside class="admin-sidebar">
            <ul class="admin-sidebar-menu">
                <li class="active"><a href="dashboard.php">📊 Panel de Control</a></li>
                <li><a href="movies.php">🎬 Películas (CRUD)</a></li>
                <li><a href="series.php">📺 Series (CRUD)</a></li>
                <li><a href="genres.php">🏷️ Géneros</a></li>
                <li><a href="users.php">👥 Usuarios</a></li>
                <li><a href="tmdb_import.php">⬇ Importar TMDB</a></li>
                <div class="dropdown-divider" style="margin: 10px 24px;"></div>
                <li><a href="../usuario/home.php">🏠 Volver al Sitio</a></li>
            </ul>
        </aside>
        
        <!-- Contenido principal del admin -->
        <main class="admin-container">
            <div class="admin-header">
                <div>
                    <h1 class="detail-title" style="font-size: 28px; margin-bottom: 4px;">Panel de Control</h1>
                    <p style="color: var(--text-muted); font-size: 13px;">Resumen operativo de Proyecto Final.</p>
                </div>
            </div>
            
            <!-- Grid de Indicadores -->
            <div class="admin-summary-grid">
                <div class="admin-summary-card">
                    <span class="admin-summary-title">Usuarios Registrados</span>
                    <span class="admin-summary-value">👥 <?php echo $summary['users']; ?></span>
                </div>
                <div class="admin-summary-card">
                    <span class="admin-summary-title">Total Películas</span>
                    <span class="admin-summary-value">🎬 <?php echo $summary['movies']; ?></span>
                </div>
                <div class="admin-summary-card">
                    <span class="admin-summary-title">Total Series</span>
                    <span class="admin-summary-value">📺 <?php echo $summary['series']; ?></span>
                </div>
                <div class="admin-summary-card">
                    <span class="admin-summary-title">Total Géneros</span>
                    <span class="admin-summary-value">🏷️ <?php echo $summary['genres']; ?></span>
                </div>
            </div>
            
            <!-- Grid de Estadísticas con barras CSS -->
            <div class="admin-charts-grid">
                
                <!-- Géneros más visitados -->
                <div class="admin-chart-box">
                    <h3 class="admin-chart-title">Géneros Más Visitados (Vistas)</h3>
                    <div class="css-bar-chart">
                        <?php if (!empty($popular_genres)): ?>
                            <?php foreach ($popular_genres as $g): 
                                $pct = round(($g['visitas'] / $max_visitas) * 100);
                            ?>
                                <div class="css-bar-row">
                                    <div class="css-bar-label">
                                        <span><?php echo clean($g['nombre']); ?></span>
                                        <strong><?php echo $g['visitas']; ?> visitas</strong>
                                    </div>
                                    <div class="css-bar-wrapper">
                                        <div class="css-bar-progress" style="width: <?php echo $pct; ?>%;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: var(--text-muted); font-size: 13px; text-align:center; padding: 40px 0;">Sin visitas registradas.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Películas más populares -->
                <div class="admin-chart-box">
                    <h3 class="admin-chart-title">Títulos Más Populares (Clicks)</h3>
                    <div class="css-bar-chart">
                        <?php if (!empty($popular_media)): ?>
                            <?php foreach ($popular_media as $m): 
                                $pct = round(($m['clicks'] / $max_clicks) * 100);
                            ?>
                                <div class="css-bar-row">
                                    <div class="css-bar-label">
                                        <span><?php echo clean($m['titulo']); ?> (<?php echo clean($m['tipo']); ?>)</span>
                                        <strong><?php echo $m['clicks']; ?> clicks</strong>
                                    </div>
                                    <div class="css-bar-wrapper">
                                        <div class="css-bar-progress" style="width: <?php echo $pct; ?>%; background: linear-gradient(to right, #0072d2, #ff5252);"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: var(--text-muted); font-size: 13px; text-align:center; padding: 40px 0;">Sin clicks registrados.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
            </div>
            
            <!-- Actividad Reciente -->
            <div class="admin-chart-box" style="margin-bottom: 20px;">
                <h3 class="admin-chart-title" style="margin-bottom: 16px;">Actividad de Reproducción Reciente</h3>
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Tipo</th>
                                <th>Contenido</th>
                                <th>Progreso</th>
                                <th>Fecha y Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_activity)): ?>
                                <?php foreach ($recent_activity as $act): ?>
                                    <tr>
                                        <td><strong>@<?php echo clean($act['username']); ?></strong></td>
                                        <td><span class="hero-badge" style="font-size: 10px;"><?php echo clean($act['tipo']); ?></span></td>
                                        <td><?php echo clean($act['nombre_medio']); ?></td>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:8px;">
                                                <div class="css-bar-wrapper" style="width: 100px; height: 8px; margin: 0; background: var(--bg-hover);">
                                                    <div class="css-bar-progress" style="width: <?php echo $act['progreso']; ?>%; height: 100%; border-radius: 6px; background: var(--accent);"></div>
                                                </div>
                                                <span><?php echo $act['progreso']; ?>%</span>
                                            </div>
                                        </td>
                                        <td style="color: var(--text-muted); font-size:12px;"><?php echo date("d/m/Y H:i:s", strtotime($act['fecha_vista'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 24px;">No hay historial registrado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </main>
        
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
