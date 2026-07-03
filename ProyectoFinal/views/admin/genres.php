<?php
/**
 * CRUD de Géneros
 */
require_once __DIR__ . '/../../config/config.php';
requireRole('Administrador');
require_once __DIR__ . '/../../models/Genre.php';

$error = null;
$msg = clean($_GET['msg'] ?? '');

if (isset($_GET['delete']) && (int)$_GET['delete'] > 0) {
    Genre::delete((int)$_GET['delete']);
    redirect('views/admin/genres.php?msg=deleted');
}

$edit_genre = null;
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
if ($edit_id > 0) $edit_genre = Genre::getById($edit_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = clean($_POST['nombre'] ?? '');
    if (empty($nombre)) {
        $error = 'El nombre del género es obligatorio.';
    } else {
        if (isset($_POST['edit_genre'])) {
            Genre::update($edit_id, $nombre);
            redirect('views/admin/genres.php?msg=updated');
        } else {
            Genre::create($nombre);
            redirect('views/admin/genres.php?msg=created');
        }
    }
}

$genres = Genre::getAll();

$page_title = "Administrar Géneros";
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
                <li class="active"><a href="genres.php">🏷️ Géneros</a></li>
                <li><a href="users.php">👥 Usuarios</a></li>
                <div class="dropdown-divider" style="margin: 10px 24px;"></div>
                <li><a href="../usuario/home.php">🏠 Volver al Sitio</a></li>
            </ul>
        </aside>
        <main class="admin-container">
            <div class="admin-header">
                <h1 class="detail-title" style="font-size: 28px;">Administrar Géneros</h1>
            </div>

            <?php if ($msg === 'created'): ?><div class="alert-message alert-success">Género creado.</div>
            <?php elseif ($msg === 'updated'): ?><div class="alert-message alert-success">Género actualizado.</div>
            <?php elseif ($msg === 'deleted'): ?><div class="alert-message alert-success">Género eliminado.</div>
            <?php endif; ?>
            <?php if ($error): ?><div class="alert-message alert-error">⚠️ <?php echo $error; ?></div><?php endif; ?>

            <div style="display: grid; grid-template-columns: 320px 1fr; gap: 30px;">
                <div class="admin-chart-box" style="height: fit-content;">
                    <h3 class="admin-chart-title"><?php echo $edit_genre ? 'Editar Género' : 'Nuevo Género'; ?></h3>
                    <form action="" method="POST">
                        <div class="form-group">
                            <label>Nombre del Género *</label>
                            <input type="text" name="nombre" class="form-control" value="<?php echo $edit_genre ? clean($edit_genre['nombre']) : ''; ?>" required placeholder="Ej: Animación">
                        </div>
                        <div style="display:flex; gap:10px;">
                            <button type="submit" name="<?php echo $edit_genre ? 'edit_genre' : 'add_genre'; ?>" class="btn btn-primary btn-sm">💾 <?php echo $edit_genre ? 'Actualizar' : 'Guardar'; ?></button>
                            <?php if ($edit_genre): ?><a href="genres.php" class="btn btn-secondary btn-sm">Cancelar</a><?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="admin-chart-box">
                    <h3 class="admin-chart-title">Géneros Registrados (<?php echo count($genres); ?>)</h3>
                    <div class="admin-table-container">
                        <table class="admin-table">
                            <thead><tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr></thead>
                            <tbody>
                                <?php foreach ($genres as $g): ?>
                                    <tr>
                                        <td>#<?php echo $g['id']; ?></td>
                                        <td><strong><?php echo clean($g['nombre']); ?></strong></td>
                                        <td><div class="action-buttons">
                                            <a href="genres.php?edit=<?php echo $g['id']; ?>" class="btn btn-edit btn-sm">✏️ Editar</a>
                                            <a href="genres.php?delete=<?php echo $g['id']; ?>" class="btn btn-delete btn-sm" onclick="return confirm('¿Eliminar género?');">🗑️ Eliminar</a>
                                        </div></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
