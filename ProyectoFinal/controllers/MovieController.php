<?php
/**
 * Controlador de Películas
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Movie.php';

class MovieController {
    
    public static function handleCreate() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titulo = clean($_POST['titulo'] ?? '');
            $descripcion = clean($_POST['descripcion'] ?? '');
            $duracion = (int)($_POST['duracion'] ?? 0);
            $año = (int)($_POST['año'] ?? 0);
            $video_url = clean($_POST['video_url'] ?? '');
            $genero_id = (int)($_POST['genero_id'] ?? 0);
            
            // Validación
            if (empty($titulo) || empty($descripcion) || $duracion <= 0 || $año <= 0 || $genero_id <= 0) {
                return ['error' => 'Por favor complete todos los campos requeridos con valores válidos.'];
            }

            // Manejo de carga de imagen
            $imagen_url = 'assets/img/placeholder.svg'; // por defecto
            
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['imagen']['tmp_name'];
                $file_name = $_FILES['imagen']['name'];
                $file_size = $_FILES['imagen']['size'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
                if (!in_array($file_ext, $allowed_extensions)) {
                    return ['error' => 'Extensión de imagen no permitida. Solo JPG, JPEG, PNG y WEBP.'];
                }
                
                if ($file_size > 2 * 1024 * 1024) { // límite de 2MB
                    return ['error' => 'La imagen es demasiado grande. El límite es de 2MB.'];
                }
                
                $new_filename = uniqid('movie_', true) . '.' . $file_ext;
                $upload_dir = dirname(__DIR__) . '/uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                    $imagen_url = 'uploads/' . $new_filename;
                } else {
                    return ['error' => 'Error al mover la imagen subida.'];
                }
            }

            $result = Movie::create($titulo, $descripcion, $duracion, $año, $imagen_url, $video_url, $genero_id);
            if ($result) {
                redirect('views/admin/movies.php?msg=created');
            } else {
                return ['error' => 'No se pudo guardar la película en la base de datos.'];
            }
        }
        return null;
    }

    public static function handleUpdate($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titulo = clean($_POST['titulo'] ?? '');
            $descripcion = clean($_POST['descripcion'] ?? '');
            $duracion = (int)($_POST['duracion'] ?? 0);
            $año = (int)($_POST['año'] ?? 0);
            $video_url = clean($_POST['video_url'] ?? '');
            $genero_id = (int)($_POST['genero_id'] ?? 0);
            
            // Obtener datos actuales de la película
            $movie = Movie::getById($id);
            if (!$movie) {
                return ['error' => 'Pelicula no encontrada.'];
            }
            
            // Validación
            if (empty($titulo) || empty($descripcion) || $duracion <= 0 || $año <= 0 || $genero_id <= 0) {
                return ['error' => 'Por favor complete todos los campos requeridos con valores válidos.'];
            }

            // Mantener imagen anterior por defecto
            $imagen_url = $movie['imagen_url'];
            
            // Si subieron una nueva imagen
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['imagen']['tmp_name'];
                $file_name = $_FILES['imagen']['name'];
                $file_size = $_FILES['imagen']['size'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
                if (!in_array($file_ext, $allowed_extensions)) {
                    return ['error' => 'Extensión de imagen no permitida. Solo JPG, JPEG, PNG y WEBP.'];
                }
                
                if ($file_size > 2 * 1024 * 1024) {
                    return ['error' => 'La imagen es demasiado grande. El límite es de 2MB.'];
                }
                
                $new_filename = uniqid('movie_', true) . '.' . $file_ext;
                $upload_dir = dirname(__DIR__) . '/uploads/';
                if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                    // Opcionalmente borrar imagen anterior si estaba en uploads/
                    if (strpos($movie['imagen_url'], 'uploads/') === 0 && file_exists(dirname(__DIR__) . '/' . $movie['imagen_url'])) {
                        unlink(dirname(__DIR__) . '/' . $movie['imagen_url']);
                    }
                    $imagen_url = 'uploads/' . $new_filename;
                } else {
                    return ['error' => 'Error al mover la imagen subida.'];
                }
            }

            $result = Movie::update($id, $titulo, $descripcion, $duracion, $año, $imagen_url, $video_url, $genero_id);
            if ($result) {
                redirect('views/admin/movies.php?msg=updated');
            } else {
                return ['error' => 'No se pudo actualizar la película.'];
            }
        }
        return null;
    }

    public static function handleDelete($id) {
        $movie = Movie::getById($id);
        if ($movie) {
            // Borrar imagen si estaba en uploads
            if (strpos($movie['imagen_url'], 'uploads/') === 0 && file_exists(dirname(__DIR__) . '/' . $movie['imagen_url'])) {
                unlink(dirname(__DIR__) . '/' . $movie['imagen_url']);
            }
            if (Movie::delete($id)) {
                redirect('views/admin/movies.php?msg=deleted');
            }
        }
        redirect('views/admin/movies.php?msg=error');
    }
}
?>
