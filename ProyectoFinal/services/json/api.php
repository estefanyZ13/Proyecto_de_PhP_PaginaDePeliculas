<?php
/**
 * WebService JSON para la plataforma
 */
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Movie.php';
require_once __DIR__ . '/../../models/Series.php';
require_once __DIR__ . '/../../models/Preference.php';
require_once __DIR__ . '/../../models/Favorite.php';
require_once __DIR__ . '/../../models/History.php';

$action = clean($_GET['action'] ?? '');

switch ($action) {
    case 'get_movies':
        $search = clean($_GET['q'] ?? '');
        $genre_id = isset($_GET['genre_id']) ? (int)$_GET['genre_id'] : null;
        $movies = Movie::getAll($search, $genre_id);
        echo json_encode($movies, JSON_UNESCAPED_UNICODE);
        break;

    case 'recommendations':
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Usuario no autenticado.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $user_id = $_SESSION['user_id'];
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $recs = Preference::getRecommendations($user_id, $limit);
        echo json_encode($recs, JSON_UNESCAPED_UNICODE);
        break;

    case 'toggle_favorite':
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Usuario no autenticado.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        $user_id = $_SESSION['user_id'];
        $movie_id = isset($data['movie_id']) && $data['movie_id'] > 0 ? (int)$data['movie_id'] : null;
        $series_id = isset($data['series_id']) && $data['series_id'] > 0 ? (int)$data['series_id'] : null;

        if ($movie_id === null && $series_id === null) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de película o serie faltante.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $is_fav = Favorite::isFavorite($user_id, $movie_id, $series_id);
        if ($is_fav) {
            $success = Favorite::remove($user_id, $movie_id, $series_id);
            $status = 'removed';
        } else {
            $success = Favorite::add($user_id, $movie_id, $series_id);
            $status = 'added';
        }

        echo json_encode(['success' => $success, 'status' => $status], JSON_UNESCAPED_UNICODE);
        break;

    case 'update_history':
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Usuario no autenticado.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $user_id = $_SESSION['user_id'];
        $movie_id = isset($data['movie_id']) && $data['movie_id'] > 0 ? (int)$data['movie_id'] : null;
        $series_id = isset($data['series_id']) && $data['series_id'] > 0 ? (int)$data['series_id'] : null;
        $progreso = isset($data['progreso']) ? (int)$data['progreso'] : 0;

        if ($progreso < 0) $progreso = 0;
        if ($progreso > 100) $progreso = 100;

        if ($movie_id === null && $series_id === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan parámetros de ID.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $success = History::addOrUpdate($user_id, $movie_id, $series_id, $progreso);
        echo json_encode(['success' => $success], JSON_UNESCAPED_UNICODE);
        break;

    case 'search':
        $q = clean($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $movies = Movie::getAll($q);
        $series = Series::getAll($q);
        
        $results = [];
        foreach ($movies as $m) {
            $results[] = [
                'id' => $m['id'],
                'titulo' => $m['titulo'],
                'tipo' => 'movie',
                'imagen_url' => $m['imagen_url'],
                'año' => $m['año']
            ];
        }
        foreach ($series as $s) {
            $results[] = [
                'id' => $s['id'],
                'titulo' => $s['titulo'],
                'tipo' => 'series',
                'imagen_url' => $s['imagen_url'],
                'año' => $s['año']
            ];
        }
        
        echo json_encode($results, JSON_UNESCAPED_UNICODE);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Acción no encontrada.'], JSON_UNESCAPED_UNICODE);
        break;
}
?>
