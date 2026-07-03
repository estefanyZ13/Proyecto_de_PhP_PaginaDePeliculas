<?php
/**
 * Modelo de Historial de Visualización
 */
require_once __DIR__ . '/../config/database.php';

class History {
    
    public static function getByUser($user_id, $limit = 10) {
        global $conn;
        
        // Ejecutamos una consulta que combine tanto películas como series vistas
        $stmt = $conn->prepare("
            (SELECT 
                h.id, h.usuario_id, h.progreso, h.fecha_vista, 
                'movie' AS tipo, p.id AS media_id, p.titulo, p.imagen_url, p.año, p.duracion AS duracion_total
             FROM historial h
             JOIN peliculas p ON h.pelicula_id = p.id
             WHERE h.usuario_id = ?)
            UNION ALL
            (SELECT 
                h.id, h.usuario_id, h.progreso, h.fecha_vista, 
                'series' AS tipo, s.id AS media_id, s.titulo, s.imagen_url, s.año, (s.episodios * 45) AS duracion_total
             FROM historial h
             JOIN series s ON h.serie_id = s.id
             WHERE h.usuario_id = ?)
            ORDER BY fecha_vista DESC
            LIMIT ?
        ");
        
        $stmt->bind_param("iii", $user_id, $user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        $stmt->close();
        return $history;
    }

    public static function addOrUpdate($user_id, $pelicula_id = null, $serie_id = null, $progreso = 0) {
        global $conn;
        
        if ($pelicula_id !== null) {
            // Verificar si ya existe en historial
            $stmt = $conn->prepare("SELECT id FROM historial WHERE usuario_id = ? AND pelicula_id = ?");
            $stmt->bind_param("ii", $user_id, $pelicula_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $exist = $result->fetch_assoc();
            $stmt->close();
            
            if ($exist) {
                $stmt = $conn->prepare("UPDATE historial SET progreso = ?, fecha_vista = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->bind_param("ii", $progreso, $exist['id']);
                $success = $stmt->execute();
                $stmt->close();
            } else {
                $stmt = $conn->prepare("INSERT INTO historial (usuario_id, pelicula_id, progreso) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $user_id, $pelicula_id, $progreso);
                $success = $stmt->execute();
                $stmt->close();
            }
        } elseif ($serie_id !== null) {
            $stmt = $conn->prepare("SELECT id FROM historial WHERE usuario_id = ? AND serie_id = ?");
            $stmt->bind_param("ii", $user_id, $serie_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $exist = $result->fetch_assoc();
            $stmt->close();
            
            if ($exist) {
                $stmt = $conn->prepare("UPDATE historial SET progreso = ?, fecha_vista = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->bind_param("ii", $progreso, $exist['id']);
                $success = $stmt->execute();
                $stmt->close();
            } else {
                $stmt = $conn->prepare("INSERT INTO historial (usuario_id, serie_id, progreso) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $user_id, $serie_id, $progreso);
                $success = $stmt->execute();
                $stmt->close();
            }
        } else {
            return false;
        }
        
        return $success;
    }

    public static function clearByUser($user_id) {
        global $conn;
        $stmt = $conn->prepare("DELETE FROM historial WHERE usuario_id = ?");
        $stmt->bind_param("i", $user_id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public static function getRecentActivity($limit = 10) {
        global $conn;
        
        $sql = "
            (SELECT 
                h.id, u.username, h.progreso, h.fecha_vista, 
                'película' AS tipo, p.titulo AS nombre_medio 
             FROM historial h
             JOIN usuarios u ON h.usuario_id = u.id
             JOIN peliculas p ON h.pelicula_id = p.id)
            UNION ALL
            (SELECT 
                h.id, u.username, h.progreso, h.fecha_vista, 
                'serie' AS tipo, s.titulo AS nombre_medio 
             FROM historial h
             JOIN usuarios u ON h.usuario_id = u.id
             JOIN series s ON h.serie_id = s.id)
            ORDER BY fecha_vista DESC
            LIMIT ?
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $activity = [];
        while ($row = $result->fetch_assoc()) {
            $activity[] = $row;
        }
        $stmt->close();
        return $activity;
    }
}
?>
