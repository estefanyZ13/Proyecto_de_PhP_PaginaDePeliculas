<?php
/**
 * Modelo de Preferencias y Algoritmo de Recomendación
 */
require_once __DIR__ . '/../config/database.php';

class Preference {
    
    public static function getByUser($user_id) {
        global $conn;
        $stmt = $conn->prepare("
            SELECT genero_id 
            FROM preferencias 
            WHERE usuario_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $genre_ids = [];
        while ($row = $result->fetch_assoc()) {
            $genre_ids[] = (int)$row['genero_id'];
        }
        $stmt->close();
        return $genre_ids;
    }

    public static function saveByUser($user_id, $genre_ids) {
        global $conn;
        
        // Empezar transacción
        $conn->begin_transaction();
        try {
            // 1. Eliminar anteriores
            $stmt = $conn->prepare("DELETE FROM preferencias WHERE usuario_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            
            // 2. Insertar nuevos
            if (!empty($genre_ids)) {
                $stmt = $conn->prepare("INSERT INTO preferencias (usuario_id, genero_id) VALUES (?, ?)");
                foreach ($genre_ids as $gid) {
                    $stmt->bind_param("ii", $user_id, $gid);
                    $stmt->execute();
                }
                $stmt->close();
            }
            
            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollback();
            return false;
        }
    }

    /**
     * Algoritmo de Recomendación Dinámico
     */
    public static function getRecommendations($user_id, $limit = 10) {
        global $conn;
        
        // 1. Obtener géneros favoritos del usuario desde preferencias
        $fav_genres = self::getByUser($user_id);
        
        // 2. Obtener géneros de contenidos vistos recientemente en el historial
        $stmt = $conn->prepare("
            SELECT DISTINCT p.genero_id FROM historial h JOIN peliculas p ON h.pelicula_id = p.id WHERE h.usuario_id = ?
            UNION
            SELECT DISTINCT s.genero_id FROM historial h JOIN series s ON h.serie_id = s.id WHERE h.usuario_id = ?
        ");
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $fav_genres[] = (int)$row['genero_id'];
        }
        $stmt->close();
        
        $fav_genres = array_unique($fav_genres);
        
        // Si no hay preferencias ni historial, recomendamos los contenidos más populares en general
        if (empty($fav_genres)) {
            $sql = "
                (SELECT 'movie' AS tipo, id, titulo, descripcion, imagen_url, año, clicks, genero_id FROM peliculas)
                UNION ALL
                (SELECT 'series' AS tipo, id, titulo, descripcion, imagen_url, año, clicks, genero_id FROM series)
                ORDER BY clicks DESC 
                LIMIT ?
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $limit);
        } else {
            // Si hay géneros preferidos, recomendamos películas/series de esos géneros, ordenadas por clicks (popularidad)
            $genre_list = implode(",", array_map('intval', $fav_genres));
            
            $sql = "
                (SELECT 'movie' AS tipo, id, titulo, descripcion, imagen_url, año, clicks, genero_id FROM peliculas WHERE genero_id IN ($genre_list))
                UNION ALL
                (SELECT 'series' AS tipo, id, titulo, descripcion, imagen_url, año, clicks, genero_id FROM series WHERE genero_id IN ($genre_list))
                ORDER BY clicks DESC, titulo ASC 
                LIMIT ?
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $limit);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $recommendations = [];
        while ($row = $result->fetch_assoc()) {
            $recommendations[] = $row;
        }
        $stmt->close();
        return $recommendations;
    }
}
?>
