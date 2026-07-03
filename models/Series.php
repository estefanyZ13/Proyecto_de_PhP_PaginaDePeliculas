<?php
/**
 * Modelo de Serie
 */
require_once __DIR__ . '/../config/database.php';

class Series {
    
    public static function getById($id) {
        global $conn;
        $stmt = $conn->prepare("
            SELECT s.*, g.nombre AS genero_nombre 
            FROM series s 
            JOIN generos g ON s.genero_id = g.id 
            WHERE s.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $serie = $result->fetch_assoc();
        $stmt->close();
        return $serie;
    }

    public static function getAll($search = '', $genre_id = null) {
        global $conn;
        
        $sql = "
            SELECT s.*, g.nombre AS genero_nombre 
            FROM series s 
            JOIN generos g ON s.genero_id = g.id 
            WHERE 1=1
        ";
        
        $params = [];
        $types = "";
        
        if (!empty($search)) {
            $sql .= " AND (s.titulo LIKE ? OR s.descripcion LIKE ?)";
            $search_param = "%" . $search . "%";
            $params[] = $search_param;
            $params[] = $search_param;
            $types .= "ss";
        }
        
        if ($genre_id !== null && $genre_id > 0) {
            $sql .= " AND s.genero_id = ?";
            $params[] = (int)$genre_id;
            $types .= "i";
        }
        
        $sql .= " ORDER BY s.titulo ASC";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $series = [];
        while ($row = $result->fetch_assoc()) {
            $series[] = $row;
        }
        $stmt->close();
        return $series;
    }

    public static function create($titulo, $descripcion, $temporadas, $episodios, $año, $imagen_url, $video_url, $genero_id) {
        global $conn;
        $stmt = $conn->prepare("
            INSERT INTO series (titulo, descripcion, temporadas, episodios, año, imagen_url, video_url, genero_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssiiisss", $titulo, $descripcion, $temporadas, $episodios, $año, $imagen_url, $video_url, $genero_id);
        $success = $stmt->execute();
        $insert_id = $conn->insert_id;
        $stmt->close();
        return $success ? $insert_id : false;
    }

    public static function update($id, $titulo, $descripcion, $temporadas, $episodios, $año, $imagen_url, $video_url, $genero_id) {
        global $conn;
        $stmt = $conn->prepare("
            UPDATE series 
            SET titulo = ?, descripcion = ?, temporadas = ?, episodios = ?, año = ?, imagen_url = ?, video_url = ?, genero_id = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("ssiiisssi", $titulo, $descripcion, $temporadas, $episodios, $año, $imagen_url, $video_url, $genero_id, $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public static function delete($id) {
        global $conn;
        $stmt = $conn->prepare("
            DELETE FROM series 
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
            UPDATE series 
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
            SELECT s.*, g.nombre AS genero_nombre 
            FROM series s 
            JOIN generos g ON s.genero_id = g.id 
            ORDER BY s.clicks DESC, s.titulo ASC 
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $series = [];
        while ($row = $result->fetch_assoc()) {
            $series[] = $row;
        }
        $stmt->close();
        return $series;
    }

    public static function getRecent($limit = 10) {
        global $conn;
        $stmt = $conn->prepare("
            SELECT s.*, g.nombre AS genero_nombre 
            FROM series s 
            JOIN generos g ON s.genero_id = g.id 
            ORDER BY s.fecha_agregado DESC, s.id DESC 
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $series = [];
        while ($row = $result->fetch_assoc()) {
            $series[] = $row;
        }
        $stmt->close();
        return $series;
    }

    public static function getByGenre($genre_id, $limit = 10) {
        global $conn;
        $stmt = $conn->prepare("
            SELECT s.*, g.nombre AS genero_nombre 
            FROM series s 
            JOIN generos g ON s.genero_id = g.id 
            WHERE s.genero_id = ? 
            ORDER BY s.titulo ASC 
            LIMIT ?
        ");
        $stmt->bind_param("ii", $genre_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $series = [];
        while ($row = $result->fetch_assoc()) {
            $series[] = $row;
        }
        $stmt->close();
        return $series;
    }
}
?>
