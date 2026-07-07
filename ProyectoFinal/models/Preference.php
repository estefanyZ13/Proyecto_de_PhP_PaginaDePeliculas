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

    private static function addGenreWeight(&$weights, $genre_id, $points) {
        $genre_id = (int)$genre_id;
        if ($genre_id <= 0) {
            return;
        }

        $weights[$genre_id] = ($weights[$genre_id] ?? 0) + $points;
    }

    private static function getGenreWeights($user_id, $current_genre_id = null) {
        global $conn;
        $weights = [];

        // Preferencias seleccionadas por el usuario.
        foreach (self::getByUser($user_id) as $genre_id) {
            self::addGenreWeight($weights, $genre_id, 3);
        }

        // Contenido que el usuario agregó a "Mi Lista".
        $stmt = $conn->prepare("
            SELECT p.genero_id FROM favoritos f JOIN peliculas p ON f.pelicula_id = p.id WHERE f.usuario_id = ?
            UNION
            SELECT s.genero_id FROM favoritos f JOIN series s ON f.serie_id = s.id WHERE f.usuario_id = ?
        ");
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            self::addGenreWeight($weights, $row['genero_id'], 5);
        }
        $stmt->close();

        // Historial de reproducción reciente.
        $stmt = $conn->prepare("
            SELECT p.genero_id FROM historial h JOIN peliculas p ON h.pelicula_id = p.id WHERE h.usuario_id = ?
            UNION
            SELECT s.genero_id FROM historial h JOIN series s ON h.serie_id = s.id WHERE h.usuario_id = ?
        ");
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            self::addGenreWeight($weights, $row['genero_id'], 4);
        }
        $stmt->close();

        // Géneros que el usuario abrió o exploró con más frecuencia.
        $stmt = $conn->prepare("
            SELECT genero_id, COUNT(*) AS total
            FROM visitas
            WHERE usuario_id = ?
            GROUP BY genero_id
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            self::addGenreWeight($weights, $row['genero_id'], min(4, (int)$row['total']));
        }
        $stmt->close();

        // La película o serie abierta en este momento tiene el peso más alto.
        if ($current_genre_id !== null) {
            self::addGenreWeight($weights, $current_genre_id, 8);
        }

        arsort($weights);
        return $weights;
    }

    private static function genreScoreCase($field, $weights) {
        $case = "CASE $field";
        foreach ($weights as $genre_id => $weight) {
            $case .= " WHEN " . (int)$genre_id . " THEN " . (int)$weight;
        }
        return $case . " ELSE 0 END";
    }

    /**
     * Algoritmo de Recomendación Dinámico.
     * Usa preferencias, Mi Lista, historial, visitas y el contenido abierto.
     */
    public static function getRecommendations($user_id, $limit = 10, $context = []) {
        global $conn;

        $user_id = (int)$user_id;
        $limit = max(1, (int)$limit);
        $current_tipo = $context['tipo'] ?? null;
        $current_id = isset($context['id']) ? (int)$context['id'] : 0;
        $current_genre_id = isset($context['genero_id']) ? (int)$context['genero_id'] : null;
        $weights = self::getGenreWeights($user_id, $current_genre_id);

        $movie_conditions = ["NOT EXISTS (SELECT 1 FROM favoritos f WHERE f.usuario_id = $user_id AND f.pelicula_id = p.id)"];
        $series_conditions = ["NOT EXISTS (SELECT 1 FROM favoritos f WHERE f.usuario_id = $user_id AND f.serie_id = s.id)"];

        if ($current_tipo === 'movie' && $current_id > 0) {
            $movie_conditions[] = "p.id <> $current_id";
        }
        if ($current_tipo === 'series' && $current_id > 0) {
            $series_conditions[] = "s.id <> $current_id";
        }

        if (!empty($weights)) {
            $genre_list = implode(",", array_map('intval', array_keys($weights)));
            $movie_conditions[] = "p.genero_id IN ($genre_list)";
            $series_conditions[] = "s.genero_id IN ($genre_list)";
            $movie_score = self::genreScoreCase('p.genero_id', $weights);
            $series_score = self::genreScoreCase('s.genero_id', $weights);
        } else {
            $movie_score = "0";
            $series_score = "0";
        }

        $movie_where = implode(" AND ", $movie_conditions);
        $series_where = implode(" AND ", $series_conditions);

        $sql = "
            (SELECT
                'movie' AS tipo, p.id, p.titulo, p.descripcion, p.imagen_url, p.año, p.clicks, p.genero_id,
                g.nombre AS genero_nombre, ($movie_score) AS score
             FROM peliculas p
             JOIN generos g ON p.genero_id = g.id
             WHERE $movie_where)
            UNION ALL
            (SELECT
                'series' AS tipo, s.id, s.titulo, s.descripcion, s.imagen_url, s.año, s.clicks, s.genero_id,
                g.nombre AS genero_nombre, ($series_score) AS score
             FROM series s
             JOIN generos g ON s.genero_id = g.id
             WHERE $series_where)
            ORDER BY score DESC, clicks DESC, titulo ASC
            LIMIT ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        
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
