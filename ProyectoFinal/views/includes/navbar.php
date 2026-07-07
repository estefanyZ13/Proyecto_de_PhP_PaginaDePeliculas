<?php
/**
 * Barra de navegación superior
 */
require_once __DIR__ . '/../../config/config.php';

$current_user = isLoggedIn() ? $_SESSION['username'] : null;
$user_role = isLoggedIn() ? $_SESSION['rol_nombre'] : null;
$theme_text = getCookieVal('theme', 'dark') === 'light' ? 'Tema Oscuro' : 'Tema Claro';
?>
<nav class="navbar">
    <a href="<?php echo BASE_URL; ?>" class="navbar-logo">
        🍿 Proyecto<span>Final</span>
    </a>
    
    <?php if (isLoggedIn()): ?>
        <ul class="navbar-menu">
            <li><a href="<?php echo BASE_URL; ?>views/usuario/home.php">Inicio</a></li>
            <li><a href="<?php echo BASE_URL; ?>views/usuario/catalog.php">Catálogo</a></li>
            <li><a href="<?php echo BASE_URL; ?>views/usuario/favorites.php">Mi Lista</a></li>
            <li><a href="<?php echo BASE_URL; ?>views/usuario/recommendations.php">Recomendaciones</a></li>
        </ul>
        
        <div class="navbar-actions">
            <!-- Buscador en vivo -->
            <div class="search-container">
                <span class="search-icon">🔍</span>
                <input type="text" id="nav-search-input" class="search-input" placeholder="Buscar películas o series..." autocomplete="off">
                <div id="search-results-dropdown" class="search-results-dropdown"></div>
            </div>
            
            <!-- Avatar y Menú Desplegable -->
            <div class="profile-dropdown">
                <?php
                // Obtener avatar de la sesión o usar por defecto
                $avatar_num = isset($_SESSION['user_avatar']) ? $_SESSION['user_avatar'] : 1;
                $avatar_url = BASE_URL . "assets/img/avatar" . $avatar_num . ".svg";
                ?>
                <div class="profile-avatar-btn" id="profile-avatar-btn">
                    <img src="<?php echo $avatar_url; ?>" alt="Avatar" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'">
                </div>
                
                <ul class="dropdown-menu" id="profile-dropdown-menu">
                    <li style="padding: 10px 20px; font-size: 13px; font-weight: bold; color: var(--text-main);">
                        Hola, @<?php echo clean($current_user); ?>
                    </li>
                    <li style="padding: 0 20px 10px; font-size: 11px; color: var(--accent); text-transform: uppercase; font-weight: 700;">
                        Rol: <?php echo clean($user_role); ?>
                    </li>
                    <div class="dropdown-divider"></div>
                    
                    <li><a href="<?php echo BASE_URL; ?>views/usuario/profile.php">👤 Mi Perfil</a></li>
                    
                    <?php if ($user_role === 'Administrador'): ?>
                        <li><a href="<?php echo BASE_URL; ?>views/admin/dashboard.php">🛠️ Panel Admin</a></li>
                    <?php endif; ?>
                    
                    <li>
                        <button id="theme-toggle-btn">
                            🌓 <span class="theme-text"><?php echo $theme_text; ?></span>
                        </button>
                    </li>
                    <div class="dropdown-divider"></div>
                    <li><a href="<?php echo BASE_URL; ?>views/auth/logout.php" style="color: var(--danger);">🚪 Cerrar Sesión</a></li>
                </ul>
            </div>
        </div>
    <?php else: ?>
        <div class="navbar-actions">
            <a href="<?php echo BASE_URL; ?>views/auth/login.php" class="btn btn-secondary btn-sm">Iniciar Sesión</a>
            <a href="<?php echo BASE_URL; ?>views/auth/register.php" class="btn btn-primary btn-sm">Registrarse</a>
        </div>
    <?php endif; ?>
</nav>
