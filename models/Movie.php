<?php
/**
 * Modelo de Película
 */
require_once __DIR__ . '/../config/database.php';

class Movie {
    
    public static function getById($id) {
        global $conn;
        $stmt = $conn->prepare("
            SELECT p.*, g.nombre AS genero_nombre 
            FROM peliculas p 
            JOIN generos g ON p.genero_id = g.id 
            WHERE p.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $movie = $result->fetch_assoc();
        $stmt->close();
        return $movie;
    }

    public static function getAll($search = '', $genre_id = null) {
        global $conn;
        
        $sql = "
            SELECT p.*, g.nombre AS genero_nombre 
            FROM peliculas p 
            JOIN generos g ON p.genero_id = g.id 
            WHERE 1=1
        ";
        
        $params = [];
        $types = "";
        
        if (!empty($search)) {
            $sql .= " AND (p.titulo LIKE ? OR p.descripcion LIKE ?)";
            $search_param = "%" . $search . "%";
            $params[] = $search_param;
            $params[] = $search_param;
            $types .= "ss";
        }
        
        if ($genre_id !== null && $genre_id > 0) {
            $sql .= " AND p.genero_id = ?";
            $params[] = (int)$genre_id;
            $types .= "i";
        }
        
        $sql .= " ORDER BY p.titulo ASC";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $movies = [];
        while ($row = $result->fetch_assoc()) {
            $movies[] = $row;
        }
        $stmt->close();
        return $movies;
    }

    public static function create($titulo, $descripcion, $duracion, $año, $imagen_url, $video_url, $genero_id) {
        global $conn;
        $stmt = $conn->prepare("
            INSERT INTO peliculas (titulo, descripcion, duracion, año, imagen_url, video_url, genero_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssisssi", $titulo, $descripcion, $duracion, $año, $imagen_url, $video_url, $genero_id);
        $success = $stmt->execute();
        $insert_id = $conn->insert_id;
        $stmt->close();
        return $success ? $insert_id : false;
    }

    public static function update($id, $titulo, $descripcion, $duracion, $año, $imagen_url, $video_url, $genero_id) {
        global $conn;
        $stmt = $conn->prepare("
            UPDATE peliculas 
            SET titulo = ?, descripcion = ?, duracion = ?, año = ?, imagen_url = ?, video_url = ?, genero_id = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("ssisssii", $titulo, $descripcion, $duracion, $año, $imagen_url, $video_url, $genero_id, $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public static function delete($id) {
        global $conn;
        $stmt = $conn->prepare("
            DELETE FROM peliculas 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public static function incrementClicks($id) {
        global $conn;
        $stmt = $conn->prepare("
            UPDATE peliculas 
            SET clicks = clicks + 1 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    public static function getPopular($limit = 10) {
        global $conn;
        $stmt = $conn->prepare("
            SELECT p.*, g.nombre AS genero_nombre 
            FROM peliculas p 
            JOIN generos g ON p.genero_id = g.id 
            ORDER BY p.clicks DESC, p.titulo ASC 
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $movies = [];
        while ($row = $result->fetch_assoc()) {
            $movies[] = $row;
        }
        $stmt->close();
        return $movies;
    }

    public static function getRecent($limit = 10) {
        global $conn;
        $stmt = $conn->prepare("
            SELECT p.*, g.nombre AS genero_nombre 
            FROM peliculas p 
            JOIN generos g ON p.genero_id = g.id 
            ORDER BY p.fecha_agregado DESC, p.id DESC 
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $movies = [];
        while ($row = $result->fetch_assoc()) {
            $movies[] = $row;
        }
        $stmt->close();
        return $movies;
    }

    public static function getByGenre($genre_id, $limit = 10) {
        global $conn;
        $stmt = $conn->prepare("
            SELECT p.*, g.nombre AS genero_nombre 
            FROM peliculas p 
            JOIN generos g ON p.genero_id = g.id 
            WHERE p.genero_id = ? 
            ORDER BY p.titulo ASC 
            LIMIT ?
        ");
        $stmt->bind_param("ii", $genre_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $movies = [];
        while ($row = $result->fetch_assoc()) {
            $movies[] = $row;
        }
        $stmt->close();
        return $movies;
    }
}
?>
