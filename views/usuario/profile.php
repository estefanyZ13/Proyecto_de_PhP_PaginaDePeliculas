<?php
/**
 * Vista de Perfil y Configuración del Usuario
 */
require_once __DIR__ . '/../../config/config.php';
requireLogin();

require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Genre.php';
require_once __DIR__ . '/../../models/Preference.php';
require_once __DIR__ . '/../../models/History.php';

$user_id = $_SESSION['user_id'];
$user = User::getById($user_id);

// 1. Cargar géneros y preferencias actuales
$genres = Genre::getAll();
$user_prefs = Preference::getByUser($user_id);

// 2. Cargar historial
$history = History::getByUser($user_id, 10);

$msg = null;

// 3. Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Actualizar datos del usuario
        $email = clean($_POST['email'] ?? '');
        $username = clean($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $avatar = isset($_POST['avatar']) ? (int)$_POST['avatar'] : 1;
        $genre_ids = $_POST['genres'] ?? [];

        if (empty($email) || empty($username)) {
            $msg = ['error' => 'Nombre de usuario y correo electrónico son obligatorios.'];
        } else {
            // Guardar cambios del usuario
            $update_ok = User::update($user_id, $username, $email, $user['rol_id']);
            if ($update_ok) {
                // Guardar avatar en sesión
                $_SESSION['user_avatar'] = $avatar;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                
                // Guardar preferencias de géneros
                Preference::saveByUser($user_id, array_map('intval', $genre_ids));
                
                // Actualizar contraseña si se ingresó una nueva
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $msg = ['error' => 'Los datos básicos se guardaron, pero la contraseña debe tener al menos 6 caracteres y no se modificó.'];
                    } else {
                        User::updatePassword($user_id, $password);
                    }
                }
                
                if (!$msg) {
                    $msg = ['success' => '¡Perfil y preferencias actualizadas correctamente!'];
                }
                
                // Recargar variables locales
                $user = User::getById($user_id);
                $user_prefs = Preference::getByUser($user_id);
            } else {
                $msg = ['error' => 'El nombre de usuario o correo ya existen.'];
            }
        }
    } elseif (isset($_POST['clear_history'])) {
        // Limpiar historial
        History::clearByUser($user_id);
        $history = [];
        $msg = ['success' => 'Historial de reproducción borrado.'];
    } elseif (isset($_POST['clear_cookies'])) {
        // Borrar cookies
        setCookieSecure('remember_user', '', -3600);
        setCookieSecure('theme', '', -3600);
        $msg = ['success' => 'Preferencias y cookies del navegador restablecidas.'];
    }
}

// Asegurar avatar por defecto en sesión si no existe
if (!isset($_SESSION['user_avatar'])) {
    $_SESSION['user_avatar'] = 1;
}

$page_title = "Mi Perfil";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="main-content">
    <div class="section-container" style="padding-top: 40px;">
        
        <?php if ($msg): ?>
            <div class="alert-message <?php echo isset($msg['error']) ? 'alert-error' : 'alert-success'; ?>" style="max-width: 900px; margin: 0 auto 30px;">
                <?php echo clean($msg['error'] ?? $msg['success']); ?>
            </div>
        <?php endif; ?>
        
        <div class="profile-grid" style="max-width: 900px; margin: 0 auto;">
            
            <!-- Izquierda: Ficha de Perfil -->
            <div style="display: flex; flex-direction: column; gap: 24px;">
                <div class="profile-card">
                    <?php
                    $avatar_num = $_SESSION['user_avatar'];
                    $avatar_url = BASE_URL . "assets/img/avatar" . $avatar_num . ".png";
                    ?>
                    <img src="<?php echo $avatar_url; ?>" alt="Avatar" style="width: 96px; height: 96px; border-radius:50%; border: 3px solid var(--accent); object-fit: cover; margin-bottom: 16px;" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'">
                    <h2 style="font-size: 20px; font-weight: 700;">@<?php echo clean($user['username']); ?></h2>
                    <p style="font-size: 13px; color: var(--text-muted);"><?php echo clean($user['email']); ?></p>
                    <div class="dropdown-divider" style="margin: 16px 0;"></div>
                    <p style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Miembro desde: <?php echo date("d M Y", strtotime($user['fecha_registro'])); ?></p>
                </div>
                
                <!-- Acciones Rápidas / Cookies -->
                <div class="admin-chart-box">
                    <h3 style="font-size: 14px; margin-bottom: 16px; font-weight: 700;">Mantenimiento y Cookies</h3>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <form action="" method="POST">
                            <button type="submit" name="clear_history" class="btn btn-secondary btn-sm" style="width: 100%; border-radius: 6px; justify-content: flex-start; color: var(--danger);">
                                🗑️ Limpiar Historial de Visualización
                            </button>
                        </form>
                        <form action="" method="POST">
                            <button type="submit" name="clear_cookies" class="btn btn-secondary btn-sm" style="width: 100%; border-radius: 6px; justify-content: flex-start;">
                                🍪 Restablecer Cookies y Tema
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Derecha: Editar Datos & Preferencias -->
            <div class="admin-chart-box">
                <h3 style="font-size: 18px; margin-bottom: 20px; font-weight: 700;">Configuración de Cuenta</h3>
                
                <form action="" method="POST" id="profile-form">
                    
                    <!-- Selección de Avatar -->
                    <div class="form-group">
                        <label>Elige tu Avatar</label>
                        <div class="profile-avatar-select">
                            <?php for ($i=1; $i<=4; $i++): 
                                $av_opt_url = BASE_URL . "assets/img/avatar" . $i . ".png";
                            ?>
                                <div class="avatar-option <?php echo ($avatar_num === $i) ? 'selected' : ''; ?>" data-avatar-id="<?php echo $i; ?>">
                                    <img src="<?php echo $av_opt_url; ?>" alt="Avatar Opt" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'">
                                </div>
                            <?php endfor; ?>
                        </div>
                        <!-- Campo oculto para enviar el avatar seleccionado -->
                        <input type="hidden" name="avatar" id="selected-avatar-input" value="<?php echo $avatar_num; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Nombre de Usuario</label>
                        <input type="text" name="username" id="username" class="form-control" value="<?php echo clean($user['username']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Correo Electrónico</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?php echo clean($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Nueva Contraseña (Dejar vacío para no cambiar)</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="••••••••">
                    </div>
                    
                    <!-- Checkboxes de géneros -->
                    <div class="form-group" style="margin-top: 10px;">
                        <label>Mis Géneros Preferidos</label>
                        <div class="preferences-grid">
                            <?php foreach ($genres as $g): ?>
                                <div class="preference-item">
                                    <label class="form-check">
                                        <input type="checkbox" name="genres[]" value="<?php echo $g['id']; ?>" 
                                            <?php echo in_array($g['id'], $user_prefs) ? 'checked' : ''; ?>>
                                        <?php echo clean($g['nombre']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary" style="width: 100%; border-radius: 6px; margin-top: 20px;">
                        Guardar Cambios
                    </button>
                    
                </form>
            </div>
            
        </div>
        
    </div>
</div>

<script>
// Manejar visualización de avatares interactiva
document.querySelectorAll('.avatar-option').forEach(card => {
    card.addEventListener('click', function() {
        // Remover clase seleccionada de los demás
        document.querySelectorAll('.avatar-option').forEach(c => c.classList.remove('selected'));
        
        // Agregar al actual
        this.classList.add('selected');
        
        // Guardar id en input oculto
        const avId = this.dataset.avatarId;
        document.getElementById('selected-avatar-input').value = avId;
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
