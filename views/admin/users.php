<?php
/**
 * Administración de Usuarios
 */
require_once __DIR__ . '/../../config/config.php';
requireRole('Administrador');
require_once __DIR__ . '/../../models/User.php';

$error = null;
$msg = clean($_GET['msg'] ?? '');

// Eliminar usuario (no puede borrarse a sí mismo)
if (isset($_GET['delete']) && (int)$_GET['delete'] > 0) {
    $del_id = (int)$_GET['delete'];
    if ($del_id !== (int)$_SESSION['user_id']) {
        User::delete($del_id);
    }
    redirect('views/admin/users.php?msg=deleted');
}

// Editar rol de usuario
$edit_user = null;
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
if ($edit_id > 0) $edit_user = User::getById($edit_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $username = clean($_POST['username'] ?? '');
    $email    = clean($_POST['email'] ?? '');
    $rol_id   = (int)($_POST['rol_id'] ?? 2);
    if (!empty($username) && !empty($email)) {
        User::update($edit_id, $username, $email, $rol_id);
        redirect('views/admin/users.php?msg=updated');
    } else {
        $error = 'Nombre de usuario y correo son obligatorios.';
    }
}

$users = User::getAll();

$page_title = "Administrar Usuarios";
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
                <li class="active"><a href="users.php">👥 Usuarios</a></li>
                <div class="dropdown-divider" style="margin: 10px 24px;"></div>
                <li><a href="../usuario/home.php">🏠 Volver al Sitio</a></li>
            </ul>
        </aside>
        <main class="admin-container">
            <div class="admin-header">
                <h1 class="detail-title" style="font-size: 28px;">Administrar Usuarios</h1>
            </div>

            <?php if ($msg === 'updated'): ?><div class="alert-message alert-success">Usuario actualizado correctamente.</div>
            <?php elseif ($msg === 'deleted'): ?><div class="alert-message alert-success">Usuario eliminado.</div>
            <?php endif; ?>
            <?php if ($error): ?><div class="alert-message alert-error">⚠️ <?php echo $error; ?></div><?php endif; ?>

            <?php if ($edit_user): ?>
            <div class="admin-chart-box" style="margin-bottom: 30px; max-width: 500px;">
                <h3 class="admin-chart-title">Editar Usuario: @<?php echo clean($edit_user['username']); ?></h3>
                <form action="" method="POST">
                    <div class="form-group">
                        <label>Nombre de Usuario</label>
                        <input type="text" name="username" class="form-control" value="<?php echo clean($edit_user['username']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Correo Electrónico</label>
                        <input type="email" name="email" class="form-control" value="<?php echo clean($edit_user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Rol del Usuario</label>
                        <select name="rol_id" class="filter-select" style="padding:12px; width:100%;">
                            <option value="1" <?php echo $edit_user['rol_id'] == 1 ? 'selected' : ''; ?>>Administrador</option>
                            <option value="2" <?php echo $edit_user['rol_id'] == 2 ? 'selected' : ''; ?>>Usuario</option>
                        </select>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button type="submit" name="edit_user" class="btn btn-primary btn-sm">💾 Actualizar</button>
                        <a href="users.php" class="btn btn-secondary btn-sm">Cancelar</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div class="admin-chart-box">
                <h3 class="admin-chart-title">Usuarios Registrados (<?php echo count($users); ?>)</h3>
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead><tr><th>ID</th><th>Usuario</th><th>Correo</th><th>Rol</th><th>Registro</th><th>Acciones</th></tr></thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td>#<?php echo $u['id']; ?></td>
                                    <td><strong>@<?php echo clean($u['username']); ?></strong></td>
                                    <td><?php echo clean($u['email']); ?></td>
                                    <td>
                                        <span class="hero-badge" style="font-size:11px; <?php echo $u['rol_nombre'] === 'Administrador' ? 'background:rgba(0,114,210,0.2); color:var(--accent);' : ''; ?>">
                                            <?php echo clean($u['rol_nombre']); ?>
                                        </span>
                                    </td>
                                    <td style="font-size:12px; color:var(--text-muted);"><?php echo date("d/m/Y", strtotime($u['fecha_registro'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="users.php?edit=<?php echo $u['id']; ?>" class="btn btn-edit btn-sm">✏️ Editar</a>
                                            <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                                <a href="users.php?delete=<?php echo $u['id']; ?>" class="btn btn-delete btn-sm" onclick="return confirm('¿Eliminar usuario?');">🗑️ Eliminar</a>
                                            <?php else: ?>
                                                <span class="btn btn-secondary btn-sm" style="opacity:0.4; cursor:default;">Tu cuenta</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
