<?php
/**
 * Controlador de Series
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Series.php';

class SeriesController {
    
    public static function handleCreate() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titulo = clean($_POST['titulo'] ?? '');
            $descripcion = clean($_POST['descripcion'] ?? '');
            $temporadas = (int)($_POST['temporadas'] ?? 1);
            $episodios = (int)($_POST['episodios'] ?? 1);
            $año = (int)($_POST['año'] ?? 0);
            $video_url = clean($_POST['video_url'] ?? '');
            $genero_id = (int)($_POST['genero_id'] ?? 0);
            
            // Validación
            if (empty($titulo) || empty($descripcion) || $temporadas <= 0 || $episodios <= 0 || $año <= 0 || $genero_id <= 0) {
                return ['error' => 'Por favor complete todos los campos requeridos con valores válidos.'];
            }

            // Manejo de carga de imagen
            $imagen_url = 'assets/img/placeholder.jpg'; // por defecto
            
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
                
                $new_filename = uniqid('series_', true) . '.' . $file_ext;
                $upload_dir = dirname(__DIR__) . '/uploads/';
                if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                    $imagen_url = 'uploads/' . $new_filename;
                } else {
                    return ['error' => 'Error al mover la imagen subida.'];
                }
            }

            $result = Series::create($titulo, $descripcion, $temporadas, $episodios, $año, $imagen_url, $video_url, $genero_id);
            if ($result) {
                redirect('views/admin/series.php?msg=created');
            } else {
                return ['error' => 'No se pudo guardar la serie en la base de datos.'];
            }
        }
        return null;
    }

    public static function handleUpdate($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titulo = clean($_POST['titulo'] ?? '');
            $descripcion = clean($_POST['descripcion'] ?? '');
            $temporadas = (int)($_POST['temporadas'] ?? 1);
            $episodios = (int)($_POST['episodios'] ?? 1);
            $año = (int)($_POST['año'] ?? 0);
            $video_url = clean($_POST['video_url'] ?? '');
            $genero_id = (int)($_POST['genero_id'] ?? 0);
            
            $serie = Series::getById($id);
            if (!$serie) {
                return ['error' => 'Serie no encontrada.'];
            }
            
            // Validación
            if (empty($titulo) || empty($descripcion) || $temporadas <= 0 || $episodios <= 0 || $año <= 0 || $genero_id <= 0) {
                return ['error' => 'Por favor complete todos los campos requeridos con valores válidos.'];
            }

            $imagen_url = $serie['imagen_url'];
            
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
                
                $new_filename = uniqid('series_', true) . '.' . $file_ext;
                $upload_dir = dirname(__DIR__) . '/uploads/';
                if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                    if (strpos($serie['imagen_url'], 'uploads/') === 0 && file_exists(dirname(__DIR__) . '/' . $serie['imagen_url'])) {
                        unlink(dirname(__DIR__) . '/' . $serie['imagen_url']);
                    }
                    $imagen_url = 'uploads/' . $new_filename;
                } else {
                    return ['error' => 'Error al mover la imagen subida.'];
                }
            }

            $result = Series::update($id, $titulo, $descripcion, $temporadas, $episodios, $año, $imagen_url, $video_url, $genero_id);
            if ($result) {
                redirect('views/admin/series.php?msg=updated');
            } else {
                return ['error' => 'No se pudo actualizar la serie.'];
            }
        }
        return null;
    }

    public static function handleDelete($id) {
        $serie = Series::getById($id);
        if ($serie) {
            if (strpos($serie['imagen_url'], 'uploads/') === 0 && file_exists(dirname(__DIR__) . '/' . $serie['imagen_url'])) {
                unlink(dirname(__DIR__) . '/' . $serie['imagen_url']);
            }
            if (Series::delete($id)) {
                redirect('views/admin/series.php?msg=deleted');
            }
        }
        redirect('views/admin/series.php?msg=error');
    }
}
?>
