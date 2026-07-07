<?php
/**
 * Vista de Registro de Usuario
 */
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../models/Genre.php';

// Si ya está logueado, redirigir
if (isLoggedIn()) {
    redirect('views/usuario/home.php');
}

// Cargar géneros para las preferencias
$genres = Genre::getAll();

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = AuthController::register();
    if (isset($result['error'])) {
        $error = $result['error'];
    }
}

$page_title = "Registrarse";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="auth-wrapper" style="background-image: url('https://assets.nflxext.com/ffe/siteui/vlv3/ab180a27-b661-44d7-a6d9-940cb32f2f4a/7fb6287d-854d-450f-90e8-ebafc47a461d/PA-es-20231009-popsignuptwoweeks-perspective_alpha_website_large.jpg');">
    <div class="auth-card" style="max-width: 580px;">
        <h2 class="auth-title">Crear Cuenta</h2>
        
        <?php if ($error): ?>
            <div class="alert-message alert-error">
                ⚠️ <?php echo clean($error); ?>
            </div>
        <?php endif; ?>
        
        <form action="" method="POST" id="register-form">
            <?php csrfField(); ?>
            <div class="form-group">
                <label for="username">Nombre de Usuario</label>
                <input type="text" name="username" id="username" class="form-control" placeholder="Ej: miguel_utp" required value="<?php echo isset($_POST['username']) ? clean($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="Ej: miguel@correo.com" required value="<?php echo isset($_POST['email']) ? clean($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group" style="margin-bottom: 12px;">
                <label for="password">Contraseña</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Mínimo 12 caracteres, mayúscula, minúscula, número y símbolo" required>
            </div>
            
            <div class="form-group" style="margin-bottom: 24px;">
                <label for="confirm_password">Confirmar Contraseña</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Repita su contraseña" required>
            </div>
            
            <!-- Preferencias de géneros -->
            <div class="form-group" style="margin-bottom: 24px;">
                <label>Selecciona tus Géneros Favoritos</label>
                <p style="font-size: 11px; color: var(--text-muted); margin-top:-4px; margin-bottom:8px;">Nos ayudará a recomendarte el mejor contenido.</p>
                <div class="preferences-grid">
                    <?php foreach ($genres as $g): ?>
                        <div class="preference-item">
                            <label class="form-check">
                                <input type="checkbox" name="genres[]" value="<?php echo $g['id']; ?>" 
                                    <?php echo (isset($_POST['genres']) && in_array($g['id'], $_POST['genres'])) ? 'checked' : ''; ?>>
                                <?php echo clean($g['nombre']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary auth-submit-btn">Crear Cuenta e Iniciar</button>
        </form>
        
        <div class="auth-redirect">
            ¿Ya tienes una cuenta? <a href="login.php">Inicia sesión</a>.
        </div>
    </div>
</div>

<!-- Validación básica en cliente -->
<script>
document.getElementById('register-form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;
    
    const strongPassword = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{12,}$/;

    if (!strongPassword.test(password)) {
        e.preventDefault();
        alert('La contraseña debe tener mínimo 12 caracteres e incluir mayúscula, minúscula, número y símbolo.');
        return;
    }
    
    if (password !== confirm) {
        e.preventDefault();
        alert('Las contraseñas no coinciden.');
        return;
    }
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
