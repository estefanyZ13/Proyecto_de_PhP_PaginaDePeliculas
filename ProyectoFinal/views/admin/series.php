<?php
/**
 * CRUD de Series
 */
require_once __DIR__ . '/../../config/config.php';
requireRole('Administrador');

require_once __DIR__ . '/../../controllers/SeriesController.php';
require_once __DIR__ . '/../../models/Series.php';
require_once __DIR__ . '/../../models/Genre.php';

$genres = Genre::getAll();
$error = null;
$msg = clean($_GET['msg'] ?? '');

if (isset($_GET['delete']) && (int)$_GET['delete'] > 0) {
    SeriesController::handleDelete((int)$_GET['delete']);
}

$edit_serie = null;
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
if ($edit_id > 0) {
    $edit_serie = Series::getById($edit_id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_serie'])) {
        $result = SeriesController::handleCreate();
        if (isset($result['error'])) $error = $result['error'];
    } elseif (isset($_POST['edit_serie'])) {
        $result = SeriesController::handleUpdate($edit_id);
        if (isset($result['error'])) $error = $result['error'];
    }
}

$series = Series::getAll();

$page_title = "Administrar Series";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="main-content">
    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <ul class="admin-sidebar-menu">
                <li><a href="dashboard.php">📊 Panel de Control</a></li>
                <li><a href="movies.php">🎬 Películas (CRUD)</a></li>
                <li class="active"><a href="series.php">📺 Series (CRUD)</a></li>
                <li><a href="genres.php">🏷️ Géneros</a></li>
                <li><a href="users.php">👥 Usuarios</a></li>
                <div class="dropdown-divider" style="margin: 10px 24px;"></div>
                <li><a href="../usuario/home.php">🏠 Volver al Sitio</a></li>
            </ul>
        </aside>
        <main class="admin-container">
            <div class="admin-header">
                <div>
                    <h1 class="detail-title" style="font-size: 28px; margin-bottom: 4px;">Administrar Series</h1>
                    <p style="color: var(--text-muted); font-size: 13px;">Agrega, edita o elimina series de la plataforma.</p>
                </div>
            </div>

            <?php if ($msg === 'created'): ?><div class="alert-message alert-success">Serie creada con éxito.</div>
            <?php elseif ($msg === 'updated'): ?><div class="alert-message alert-success">Serie actualizada con éxito.</div>
            <?php elseif ($msg === 'deleted'): ?><div class="alert-message alert-success">Serie eliminada correctamente.</div>
            <?php elseif ($msg === 'error'): ?><div class="alert-message alert-error">Error al procesar la operación.</div>
            <?php endif; ?>
            <?php if ($error): ?><div class="alert-message alert-error">⚠️ <?php echo clean($error); ?></div><?php endif; ?>

            <!-- Formulario -->
            <div class="admin-chart-box" style="margin-bottom: 30px;">
                <h3 class="admin-chart-title"><?php echo $edit_serie ? 'Editar Serie' : 'Agregar Nueva Serie'; ?></h3>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                        <div class="form-group">
                            <label>Título de la Serie *</label>
                            <input type="text" name="titulo" class="form-control" value="<?php echo $edit_serie ? clean($edit_serie['titulo']) : ''; ?>" required placeholder="Ej: The Mandalorian">
                        </div>
                        <div class="form-group">
                            <label>Género *</label>
                            <select name="genero_id" class="filter-select" style="padding:12px;" required>
                                <option value="">Seleccione género</option>
                                <?php foreach ($genres as $g): ?>
                                    <option value="<?php echo $g['id']; ?>" <?php echo ($edit_serie && $edit_serie['genero_id'] == $g['id']) ? 'selected' : ''; ?>><?php echo clean($g['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Sinopsis *</label>
                        <textarea name="descripcion" class="form-control" rows="3" required style="resize:none;" placeholder="Resumen de la serie..."><?php echo $edit_serie ? clean($edit_serie['descripcion']) : ''; ?></textarea>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                        <div class="form-group">
                            <label>Temporadas *</label>
                            <input type="number" name="temporadas" class="form-control" min="1" value="<?php echo $edit_serie ? (int)$edit_serie['temporadas'] : 1; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Episodios *</label>
                            <input type="number" name="episodios" class="form-control" min="1" value="<?php echo $edit_serie ? (int)$edit_serie['episodios'] : 1; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Año *</label>
                            <input type="number" name="año" class="form-control" min="1800" max="2100" value="<?php echo $edit_serie ? (int)$edit_serie['año'] : ''; ?>" required placeholder="2024">
                        </div>
                        <div class="form-group">
                            <label>Póster</label>
                            <input type="file" name="imagen" class="form-control" style="padding:8px;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>URL del Tráiler (YouTube Embed)</label>
                        <input type="url" name="video_url" class="form-control" value="<?php echo $edit_serie ? clean($edit_serie['video_url']) : ''; ?>" placeholder="https://www.youtube.com/embed/...">
                    </div>
                    <div style="display:flex; gap:10px; margin-top:10px;">
                        <button type="submit" name="<?php echo $edit_serie ? 'edit_serie' : 'add_serie'; ?>" class="btn btn-primary btn-sm">💾 <?php echo $edit_serie ? 'Actualizar Serie' : 'Guardar Serie'; ?></button>
                        <?php if ($edit_serie): ?><a href="series.php" class="btn btn-secondary btn-sm">Cancelar</a><?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Tabla -->
            <div class="admin-chart-box">
                <h3 class="admin-chart-title">Listado de Series (<?php echo count($series); ?>)</h3>
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead><tr><th>Póster</th><th>Título</th><th>Género</th><th>Temporadas</th><th>Año</th><th>Clicks</th><th>Acciones</th></tr></thead>
                        <tbody>
                            <?php if (!empty($series)): ?>
                                <?php foreach ($series as $s): ?>
                                    <tr>
                                        <td><img src="<?php echo BASE_URL . $s['imagen_url']; ?>" alt="" onerror="this.src='<?php echo BASE_URL; ?>assets/img/placeholder.jpg'"></td>
                                        <td><strong><?php echo clean($s['titulo']); ?></strong></td>
                                        <td><span class="hero-badge" style="font-size:11px;"><?php echo clean($s['genero_nombre']); ?></span></td>
                                        <td><?php echo $s['temporadas']; ?> T / <?php echo $s['episodios']; ?> Ep</td>
                                        <td><?php echo $s['año']; ?></td>
                                        <td>🔥 <?php echo $s['clicks']; ?></td>
                                        <td><div class="action-buttons">
                                            <a href="series.php?edit=<?php echo $s['id']; ?>" class="btn btn-edit btn-sm">✏️ Editar</a>
                                            <a href="series.php?delete=<?php echo $s['id']; ?>" class="btn btn-delete btn-sm" onclick="return confirm('¿Eliminar esta serie?');">🗑️ Eliminar</a>
                                        </div></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" style="text-align:center; color:var(--text-muted); padding:24px;">No hay series en el catálogo.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
