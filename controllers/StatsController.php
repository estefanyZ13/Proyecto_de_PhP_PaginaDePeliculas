<?php
/**
 * Controlador de Estadísticas para el Administrador
 */
require_once __DIR__ . '/../config/database.php';

class StatsController {
    
    public static function getDashboardSummary() {
        global $conn;
        
        // 1. Usuarios registrados
        $res_users = $conn->query("SELECT COUNT(*) AS total FROM usuarios");
        $users_count = $res_users->fetch_assoc()['total'];
        
        // 2. Total películas
        $res_movies = $conn->query("SELECT COUNT(*) AS total FROM peliculas");
        $movies_count = $res_movies->fetch_assoc()['total'];
        
        // 3. Total series
        $res_series = $conn->query("SELECT COUNT(*) AS total FROM series");
        $series_count = $res_series->fetch_assoc()['total'];
        
        // 4. Total géneros
        $res_genres = $conn->query("SELECT COUNT(*) AS total FROM generos");
        $genres_count = $res_genres->fetch_assoc()['total'];
        
        return [
            'users' => $users_count,
            'movies' => $movies_count,
            'series' => $series_count,
            'genres' => $genres_count
        ];
    }
    
    public static function getMostVisitedGenres($limit = 5) {
        global $conn;
        
        $sql = "
            SELECT g.nombre, COUNT(v.id) AS visitas 
            FROM visitas v
            JOIN generos g ON v.genero_id = g.id
            GROUP BY v.genero_id
            ORDER BY visitas DESC
            LIMIT ?
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
        return $data;
    }
    
    public static function getMostPopularMovies($limit = 5) {
        global $conn;
        
        $sql = "
            SELECT titulo, clicks, 'pelicula' AS tipo 
            FROM peliculas
            UNION ALL
            SELECT titulo, clicks, 'serie' AS tipo 
            FROM series
            ORDER BY clicks DESC
            LIMIT ?
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
        return $data;
    }
}
?>
