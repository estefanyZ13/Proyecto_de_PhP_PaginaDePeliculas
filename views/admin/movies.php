<?php
/**
 * CRUD de Películas
 */
require_once __DIR__ . '/../../config/config.php';
requireRole('Administrador');

require_once __DIR__ . '/../../controllers/MovieController.php';
require_once __DIR__ . '/../../models/Movie.php';
require_once __DIR__ . '/../../models/Genre.php';

$user_id = $_SESSION['user_id'];
$genres = Genre::getAll();

// Procesar Acciones
$error = null;
$msg = clean($_GET['msg'] ?? '');

// Eliminar
if (isset($_GET['delete']) && (int)$_GET['delete'] > 0) {
    MovieController::handleDelete((int)$_GET['delete']);
}

// Agregar o Editar (si es POST)
$edit_movie = null;
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
if ($edit_id > 0) {
    $edit_movie = Movie::getById($edit_id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_movie'])) {
        $result = MovieController::handleCreate();
        if (isset($result['error'])) $error = $result['error'];
    } elseif (isset($_POST['edit_movie'])) {
        $result = MovieController::handleUpdate($edit_id);
        if (isset($result['error'])) $error = $result['error'];
    }
}

// Cargar catálogo de películas
$movies = Movie::getAll();

$page_title = "Administrar Películas";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="main-content">
    <div class="admin-wrapper">
        
        <!-- Sidebar lateral -->
        <aside class="admin-sidebar">
            <ul class="admin-sidebar-menu">
                <li><a href="dashboard.php">📊 Panel de Control</a></li>
                <li class="active"><a href="movies.php">🎬 Películas (CRUD)</a></li>
                <li><a href="series.php">📺 Series (CRUD)</a></li>
                <li><a href="genres.php">🏷️ Géneros</a></li>
                <li><a href="users.php">👥 Usuarios</a></li>
                <div class="dropdown-divider" style="margin: 10px 24px;"></div>
                <li><a href="../usuario/home.php">🏠 Volver al Sitio</a></li>
            </ul>
        </aside>
        
        <!-- Contenido principal -->
        <main class="admin-container">
            <div class="admin-header">
                <div>
                    <h1 class="detail-title" style="font-size: 28px; margin-bottom: 4px;">Administrar Películas</h1>
                    <p style="color: var(--text-muted); font-size: 13px;">Agrega, edita o elimina películas de la plataforma.</p>
                </div>
            </div>
            
            <?php if ($msg === 'created'): ?>
                <div class="alert-message alert-success">Película creada con éxito.</div>
            <?php elseif ($msg === 'updated'): ?>
                <div class="alert-message alert-success">Película actualizada con éxito.</div>
            <?php elseif ($msg === 'deleted'): ?>
                <div class="alert-message alert-success">Película eliminada correctamente.</div>
            <?php elseif ($msg === 'error'): ?>
                <div class="alert-message alert-error">Ocurrió un error al procesar la operación.</div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert-message alert-error">⚠️ <?php echo clean($error); ?></div>
            <?php endif; ?>

            <!-- Formulario de Agregar / Editar -->
            <div class="admin-chart-box" style="margin-bottom: 30px;">
                <h3 class="admin-chart-title"><?php echo $edit_movie ? 'Editar Película' : 'Agregar Nueva Película'; ?></h3>
                
                <form action="" method="POST" enctype="multipart/form-data">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                        <div class="form-group">
                            <label for="titulo">Título de la Película *</label>
                            <input type="text" name="titulo" id="titulo" class="form-control" value="<?php echo $edit_movie ? clean($edit_movie['titulo']) : ''; ?>" required placeholder="Ej: Los Vengadores">
                        </div>
                        <div class="form-group">
                            <label for="genero_id">Género de la Película *</label>
                            <select name="genero_id" id="genero_id" class="filter-select" style="padding: 12px;" required>
                                <option value="">Seleccione género</option>
                                <?php foreach ($genres as $g): ?>
                                    <option value="<?php echo $g['id']; ?>" <?php echo ($edit_movie && $edit_movie['genero_id'] == $g['id']) ? 'selected' : ''; ?>>
                                        <?php echo clean($g['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Sinopsis / Descripción *</label>
                        <textarea name="descripcion" id="descripcion" class="form-control" rows="3" required placeholder="Escribe el resumen de la trama aquí..." style="resize:none;"><?php echo $edit_movie ? clean($edit_movie['descripcion']) : ''; ?></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                        <div class="form-group">
                            <label for="duracion">Duración (En Minutos) *</label>
                            <input type="number" name="duracion" id="duracion" class="form-control" min="1" value="<?php echo $edit_movie ? (int)$edit_movie['duracion'] : ''; ?>" required placeholder="Ej: 120">
                        </div>
                        <div class="form-group">
                            <label for="año">Año de Lanzamiento *</label>
                            <input type="number" name="año" id="año" class="form-control" min="1800" max="2100" value="<?php echo $edit_movie ? (int)$edit_movie['año'] : ''; ?>" required placeholder="Ej: 2024">
                        </div>
                        <div class="form-group">
                            <label for="imagen">Póster de la Película</label>
                            <input type="file" name="imagen" id="imagen" class="form-control" style="padding: 8px;">
                            <?php if ($edit_movie): ?>
                                <span style="font-size:11px; color:var(--text-muted);">Póster actual: <a href="<?php echo BASE_URL . $edit_movie['imagen_url']; ?>" target="_blank">Ver archivo</a></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="video_url">URL del Tráiler (YouTube Embed) *</label>
                        <input type="url" name="video_url" id="video_url" class="form-control" value="<?php echo $edit_movie ? clean($edit_movie['video_url']) : ''; ?>" placeholder="Ej: https://www.youtube.com/embed/TcMBFSGVi1c">
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <button type="submit" name="<?php echo $edit_movie ? 'edit_movie' : 'add_movie'; ?>" class="btn btn-primary btn-sm">
                            💾 <?php echo $edit_movie ? 'Actualizar Película' : 'Guardar Película'; ?>
                        </button>
                        <?php if ($edit_movie): ?>
                            <a href="movies.php" class="btn btn-secondary btn-sm">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Tabla de Películas -->
            <div class="admin-chart-box">
                <h3 class="admin-chart-title">Listado de Películas (<?php echo count($movies); ?>)</h3>
                
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Póster</th>
                                <th>Título</th>
                                <th>Género</th>
                                <th>Año</th>
                                <th>Duración</th>
                                <th>Clicks</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($movies)): ?>
                                <?php foreach ($movies as $m): ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo BASE_URL . $m['imagen_url']; ?>" alt="<?php echo clean($m['titulo']); ?>" onerror="this.src='<?php echo BASE_URL; ?>assets/img/placeholder.jpg'">
                                        </td>
                                        <td><strong><?php echo clean($m['titulo']); ?></strong></td>
                                        <td><span class="hero-badge" style="font-size: 11px;"><?php echo clean($m['genero_nombre']); ?></span></td>
                                        <td><?php echo $m['año']; ?></td>
                                        <td><?php echo $m['duracion']; ?> mins</td>
                                        <td>🔥 <?php echo $m['clicks']; ?> clicks</td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="movies.php?edit=<?php echo $m['id']; ?>" class="btn btn-edit btn-sm">✏️ Editar</a>
                                                <a href="movies.php?delete=<?php echo $m['id']; ?>" class="btn btn-delete btn-sm" onclick="return confirm('¿Estás seguro de eliminar esta película?');">🗑️ Eliminar</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">No hay películas en el catálogo.</td>
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
