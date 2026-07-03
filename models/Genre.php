<?php
/**
 * Modelo de Género
 */
require_once __DIR__ . '/../config/database.php';

class Genre {
    
    public static function getAll() {
        global $conn;
        $sql = "SELECT * FROM generos ORDER BY nombre ASC";
        $result = $conn->query($sql);
        $genres = [];
        while ($row = $result->fetch_assoc()) {
            $genres[] = $row;
        }
        return $genres;
    }

    public static function getById($id) {
        global $conn;
        $stmt = $conn->prepare("SELECT * FROM generos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $genre = $result->fetch_assoc();
        $stmt->close();
        return $genre;
    }

    public static function create($nombre) {
        global $conn;
        $stmt = $conn->prepare("INSERT INTO generos (nombre) VALUES (?)");
        $stmt->bind_param("s", $nombre);
        $success = $stmt->execute();
        $insert_id = $conn->insert_id;
        $stmt->close();
        return $success ? $insert_id : false;
    }

    public static function update($id, $nombre) {
        global $conn;
        $stmt = $conn->prepare("UPDATE generos SET nombre = ? WHERE id = ?");
        $stmt->bind_param("si", $nombre, $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public static function delete($id) {
        global $conn;
        $stmt = $conn->prepare("DELETE FROM generos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public static function logVisit($user_id, $genre_id) {
        global $conn;
        $stmt = $conn->prepare("
            INSERT INTO visitas (usuario_id, genero_id) 
            VALUES (?, ?)
        ");
        $uid = $user_id > 0 ? (int)$user_id : null;
        $stmt->bind_param("ii", $uid, $genre_id);
        $stmt->execute();
        $stmt->close();
    }
}
?>
