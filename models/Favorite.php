<?php
/**
 * Modelo de Favoritos (Marcadores)
 */
require_once __DIR__ . '/../config/database.php';

class Favorite {
    
    public static function getByUser($user_id) {
        global $conn;
        
        $stmt = $conn->prepare("
            (SELECT 
                f.id, f.usuario_id, 'movie' AS tipo, p.id AS media_id, p.titulo, p.imagen_url, p.año, p.descripcion 
             FROM favoritos f
             JOIN peliculas p ON f.pelicula_id = p.id
             WHERE f.usuario_id = ?)
            UNION ALL
            (SELECT 
                f.id, f.usuario_id, 'series' AS tipo, s.id AS media_id, s.titulo, s.imagen_url, s.año, s.descripcion 
             FROM favoritos f
             JOIN series s ON f.serie_id = s.id
             WHERE f.usuario_id = ?)
            ORDER BY id DESC
        ");
        
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $favorites = [];
        while ($row = $result->fetch_assoc()) {
            $favorites[] = $row;
        }
        $stmt->close();
        return $favorites;
    }

    public static function isFavorite($user_id, $pelicula_id = null, $serie_id = null) {
        global $conn;
        
        if ($pelicula_id !== null) {
            $stmt = $conn->prepare("SELECT id FROM favoritos WHERE usuario_id = ? AND pelicula_id = ?");
            $stmt->bind_param("ii", $user_id, $pelicula_id);
        } elseif ($serie_id !== null) {
            $stmt = $conn->prepare("SELECT id FROM favoritos WHERE usuario_id = ? AND serie_id = ?");
            $stmt->bind_param("ii", $user_id, $serie_id);
        } else {
            return false;
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $is_fav = $result->num_rows > 0;
        $stmt->close();
        return $is_fav;
    }

    public static function add($user_id, $pelicula_id = null, $serie_id = null) {
        global $conn;
        
        if (self::isFavorite($user_id, $pelicula_id, $serie_id)) {
            return true; // Ya es favorito
        }
        
        if ($pelicula_id !== null) {
            $stmt = $conn->prepare("INSERT INTO favoritos (usuario_id, pelicula_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $pelicula_id);
        } elseif ($serie_id !== null) {
            $stmt = $conn->prepare("INSERT INTO favoritos (usuario_id, serie_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $serie_id);
        } else {
            return false;
        }
        
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public static function remove($user_id, $pelicula_id = null, $serie_id = null) {
        global $conn;
        
        if ($pelicula_id !== null) {
            $stmt = $conn->prepare("DELETE FROM favoritos WHERE usuario_id = ? AND pelicula_id = ?");
            $stmt->bind_param("ii", $user_id, $pelicula_id);
        } elseif ($serie_id !== null) {
            $stmt = $conn->prepare("DELETE FROM favoritos WHERE usuario_id = ? AND serie_id = ?");
            $stmt->bind_param("ii", $user_id, $serie_id);
        } else {
            return false;
        }
        
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}
?>
