<?php
/**
 * Controlador de Autenticación
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Preference.php';

class AuthController {
    
    public static function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = clean($_POST['username'] ?? '');
            $email = clean($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $genre_ids = $_POST['genres'] ?? []; // Array de IDs de géneros favoritos

            // Validación del lado servidor
            if (empty($username) || empty($email) || empty($password)) {
                return ['error' => 'Por favor complete todos los campos obligatorios.'];
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['error' => 'El correo electrónico no es válido.'];
            }
            if ($password !== $confirm_password) {
                return ['error' => 'Las contraseñas no coinciden.'];
            }
            if (strlen($password) < 6) {
                return ['error' => 'La contraseña debe tener al menos 6 caracteres.'];
            }

            // Validar si el usuario ya existe
            if (User::getByUsername($username)) {
                return ['error' => 'El nombre de usuario ya está registrado.'];
            }
            if (User::getByEmail($email)) {
                return ['error' => 'El correo electrónico ya está registrado.'];
            }

            // Crear usuario (rol 2 = Usuario)
            $user_id = User::create($username, $email, $password, 2);
            if ($user_id) {
                // Guardar preferencias de géneros seleccionadas
                if (!empty($genre_ids)) {
                    Preference::saveByUser($user_id, array_map('intval', $genre_ids));
                }
                
                // Iniciar sesión automáticamente
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['rol_nombre'] = 'Usuario';
                
                // Establecer cookie de tema por defecto (oscuro)
                if (!isset($_COOKIE['theme'])) {
                    setCookieSecure('theme', 'dark');
                }

                redirect('views/usuario/home.php');
            } else {
                return ['error' => 'Ocurrió un error al registrar el usuario. Inténtelo más tarde.'];
            }
        }
        return null;
    }

    public static function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = clean($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']);

            // Protección básica contra fuerza bruta en sesión
            if (isset($_SESSION['login_block_until']) && $_SESSION['login_block_until'] > time()) {
                $wait_time = $_SESSION['login_block_until'] - time();
                return ['error' => "Demasiados intentos fallidos. Espere $wait_time segundos."];
            }

            if (empty($username) || empty($password)) {
                return ['error' => 'Por favor complete todos los campos.'];
            }

            $user = User::getByUsername($username);
            
            // Si no existe, probar con correo electrónico
            if (!$user) {
                $user = User::getByEmail($username);
            }

            if ($user && password_verify($password, $user['password'])) {
                // Login exitoso, limpiar intentos fallidos
                unset($_SESSION['failed_logins']);
                unset($_SESSION['login_block_until']);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['rol_nombre'] = $user['rol_nombre'];

                // Cookies: Recordar usuario
                if ($remember) {
                    setCookieSecure('remember_user', $user['username'], 86400 * 30); // 30 días
                } else {
                    // Borrar cookie si no se seleccionó recordar
                    setCookieSecure('remember_user', '', -3600);
                }

                // Cookie de tema por defecto (oscuro) si no está seteado
                if (!isset($_COOKIE['theme'])) {
                    setCookieSecure('theme', 'dark');
                }

                // Redirigir según el rol
                if ($user['rol_nombre'] === 'Administrador') {
                    redirect('views/admin/dashboard.php');
                } else {
                    redirect('views/usuario/home.php');
                }
            } else {
                // Registrar intento fallido
                $_SESSION['failed_logins'] = ($_SESSION['failed_logins'] ?? 0) + 1;
                
                // Si supera los 3 intentos fallidos, bloquear por 30 segundos
                if ($_SESSION['failed_logins'] >= 3) {
                    $_SESSION['login_block_until'] = time() + 30;
                    return ['error' => 'Contraseña incorrecta. Demasiados intentos fallidos. Bloqueado por 30 segundos.'];
                }
                
                return ['error' => 'Nombre de usuario o contraseña incorrectos.'];
            }
        }
        return null;
    }

    public static function logout() {
        // Limpiar variables de sesión
        $_SESSION = [];
        
        // Destruir cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruir sesión
        session_destroy();
        
        // Redirigir al login
        redirect('views/auth/login.php');
    }
}
?>
