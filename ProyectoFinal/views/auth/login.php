<?php
/**
 * Vista de Inicio de Sesión
 */
require_once __DIR__ . '/../../controllers/AuthController.php';

// Si ya está logueado, redirigir
if (isLoggedIn()) {
    if (hasRole('Administrador')) {
        redirect('views/admin/dashboard.php');
    } else {
        redirect('views/usuario/home.php');
    }
}

// Procesar login
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = AuthController::login();
    if (isset($result['error'])) {
        $error = $result['error'];
    }
}

// Cargar cookie de recordar usuario
$remembered_user = getCookieVal('remember_user');

$page_title = "Iniciar Sesión";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="auth-wrapper" style="background-image: url('https://assets.nflxext.com/ffe/siteui/vlv3/ab180a27-b661-44d7-a6d9-940cb32f2f4a/7fb6287d-854d-450f-90e8-ebafc47a461d/PA-es-20231009-popsignuptwoweeks-perspective_alpha_website_large.jpg');">
    <div class="auth-card">
        <h2 class="auth-title">Iniciar Sesión</h2>
        
        <?php if ($error): ?>
            <div class="alert-message alert-error">
                ⚠️ <?php echo clean($error); ?>
            </div>
        <?php endif; ?>
        
        <form action="" method="POST">
            <?php csrfField(); ?>
            <div class="form-group">
                <label for="username">Usuario o Correo Electrónico</label>
                <input type="text" name="username" id="username" class="form-control" placeholder="Ej: admin o user@popcorntime.com" value="<?php echo clean($remembered_user); ?>" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>
            
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="remember" <?php echo !empty($remembered_user) ? 'checked' : ''; ?>>
                    Recordar mi usuario
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary auth-submit-btn">Entrar</button>
        </form>
        
        <div class="auth-redirect">
            ¿Nuevo en PopCornTime? <a href="register.php">Regístrate ahora</a>.
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
