<?php
/**
 * Integracion con The Movie Database (TMDB).
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/tmdb.php';
require_once __DIR__ . '/../models/Genre.php';
require_once __DIR__ . '/../models/Movie.php';
require_once __DIR__ . '/../models/Series.php';

class TMDBService {
    private static function request($endpoint, $params = []) {
        if (TMDB_API_KEY === '') {
            return ['error' => 'No hay API key de TMDB configurada.'];
        }

        $params['api_key'] = TMDB_API_KEY;
        $params['language'] = $params['language'] ?? 'es-ES';

        $url = TMDB_API_BASE_URL . $endpoint . '?' . http_build_query($params);
        $context = stream_context_create([
            'http' => [
                'timeout' => 20,
                'ignore_errors' => true,
                'header' => "Accept: application/json\r\n"
            ]
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return ['error' => 'No se pudo conectar con TMDB.'];
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return ['error' => 'TMDB devolvio una respuesta invalida.'];
        }

        if (isset($data['success']) && $data['success'] === false) {
            return ['error' => $data['status_message'] ?? 'TMDB rechazo la solicitud.'];
        }

        return $data;
    }

    private static function getGenres($type) {
        $endpoint = $type === 'movie' ? '/genre/movie/list' : '/genre/tv/list';
        $data = self::request($endpoint);
        if (isset($data['error'])) {
            return [];
        }

        $genres = [];
        foreach (($data['genres'] ?? []) as $genre) {
            $genres[(int)$genre['id']] = $genre['name'];
        }
        return $genres;
    }

    private static function ensureGenre($name) {
        global $conn;
        $stmt = $conn->prepare("SELECT id FROM generos WHERE LOWER(nombre) = LOWER(?) LIMIT 1");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            return (int)$row['id'];
        }
        return Genre::create($name);
    }

    private static function exists($table, $title, $year) {
        global $conn;
        $stmt = $conn->prepare("SELECT id FROM $table WHERE titulo = ? AND año = ? LIMIT 1");
        $stmt->bind_param("si", $title, $year);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (bool)$row;
    }

    private static function imageUrl($path) {
        return !empty($path) ? TMDB_IMAGE_BASE_URL . $path : 'assets/img/placeholder.svg';
    }

    private static function searchPoster($type, $title, $year) {
        $endpoint = $type === 'movie' ? '/search/movie' : '/search/tv';
        $params = [
            'query' => $title,
            'include_adult' => 'false',
            'page' => 1
        ];

        if ($year > 0) {
            if ($type === 'movie') {
                $params['year'] = $year;
            } else {
                $params['first_air_date_year'] = $year;
            }
        }

        $data = self::request($endpoint, $params);
        if (isset($data['error']) || empty($data['results'])) {
            return '';
        }

        foreach ($data['results'] as $item) {
            if (!empty($item['poster_path'])) {
                return self::imageUrl($item['poster_path']);
            }
        }

        return '';
    }

    public static function updateLocalPosters() {
        global $conn;
        $summary = [
            'movies_updated' => 0,
            'series_updated' => 0,
            'skipped' => 0
        ];

        $movies = $conn->query("SELECT id, titulo, año FROM peliculas WHERE imagen_url NOT LIKE 'http%'");
        while ($movie = $movies->fetch_assoc()) {
            $poster = self::searchPoster('movie', $movie['titulo'], (int)$movie['año']);
            if ($poster === '') {
                $summary['skipped']++;
                continue;
            }

            $stmt = $conn->prepare("UPDATE peliculas SET imagen_url = ? WHERE id = ?");
            $stmt->bind_param("si", $poster, $movie['id']);
            if ($stmt->execute()) {
                $summary['movies_updated']++;
            }
            $stmt->close();
        }

        $series = $conn->query("SELECT id, titulo, año FROM series WHERE imagen_url NOT LIKE 'http%'");
        while ($serie = $series->fetch_assoc()) {
            $poster = self::searchPoster('series', $serie['titulo'], (int)$serie['año']);
            if ($poster === '') {
                $summary['skipped']++;
                continue;
            }

            $stmt = $conn->prepare("UPDATE series SET imagen_url = ? WHERE id = ?");
            $stmt->bind_param("si", $poster, $serie['id']);
            if ($stmt->execute()) {
                $summary['series_updated']++;
            }
            $stmt->close();
        }

        return $summary;
    }

    private static function trailerUrl($type, $tmdbId) {
        $endpoint = $type === 'movie' ? "/movie/$tmdbId/videos" : "/tv/$tmdbId/videos";
        $data = self::request($endpoint);
        if (isset($data['error'])) {
            return '';
        }

        foreach (($data['results'] ?? []) as $video) {
            if (($video['site'] ?? '') === 'YouTube' && in_array(($video['type'] ?? ''), ['Trailer', 'Teaser'], true)) {
                return 'https://www.youtube.com/embed/' . clean($video['key']);
            }
        }
        return '';
    }

    public static function import($type = 'all', $pages = 1) {
        $pages = max(1, min(5, (int)$pages));
        $summary = [
            'movies_imported' => 0,
            'series_imported' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        if ($type === 'all' || $type === 'movie') {
            self::importMovies($pages, $summary);
        }

        if ($type === 'all' || $type === 'series') {
            self::importSeries($pages, $summary);
        }

        return $summary;
    }

    private static function importMovies($pages, &$summary) {
        $genres = self::getGenres('movie');
        for ($page = 1; $page <= $pages; $page++) {
            $data = self::request('/movie/popular', ['page' => $page, 'region' => 'US']);
            if (isset($data['error'])) {
                $summary['errors'][] = $data['error'];
                continue;
            }

            foreach (($data['results'] ?? []) as $movie) {
                $title = clean($movie['title'] ?? '');
                $overview = clean($movie['overview'] ?? '');
                $year = (int)substr($movie['release_date'] ?? '0', 0, 4);
                if ($title === '' || $overview === '' || $year <= 0) {
                    $summary['skipped']++;
                    continue;
                }
                if (self::exists('peliculas', $title, $year)) {
                    $summary['skipped']++;
                    continue;
                }

                $genreName = 'Sin categoria';
                if (!empty($movie['genre_ids'][0]) && isset($genres[(int)$movie['genre_ids'][0]])) {
                    $genreName = $genres[(int)$movie['genre_ids'][0]];
                }
                $genreId = self::ensureGenre($genreName);
                $imageUrl = self::imageUrl($movie['poster_path'] ?? '');
                $trailerUrl = self::trailerUrl('movie', (int)$movie['id']);
                $duration = 120;

                if (Movie::create($title, $overview, $duration, $year, $imageUrl, $trailerUrl, $genreId)) {
                    $summary['movies_imported']++;
                } else {
                    $summary['skipped']++;
                }
            }
        }
    }

    private static function importSeries($pages, &$summary) {
        $genres = self::getGenres('series');
        for ($page = 1; $page <= $pages; $page++) {
            $data = self::request('/tv/popular', ['page' => $page]);
            if (isset($data['error'])) {
                $summary['errors'][] = $data['error'];
                continue;
            }

            foreach (($data['results'] ?? []) as $serie) {
                $title = clean($serie['name'] ?? '');
                $overview = clean($serie['overview'] ?? '');
                $year = (int)substr($serie['first_air_date'] ?? '0', 0, 4);
                if ($title === '' || $overview === '' || $year <= 0) {
                    $summary['skipped']++;
                    continue;
                }
                if (self::exists('series', $title, $year)) {
                    $summary['skipped']++;
                    continue;
                }

                $genreName = 'Sin categoria';
                if (!empty($serie['genre_ids'][0]) && isset($genres[(int)$serie['genre_ids'][0]])) {
                    $genreName = $genres[(int)$serie['genre_ids'][0]];
                }
                $genreId = self::ensureGenre($genreName);
                $imageUrl = self::imageUrl($serie['poster_path'] ?? '');
                $trailerUrl = self::trailerUrl('series', (int)$serie['id']);

                if (Series::create($title, $overview, 1, 1, $year, $imageUrl, $trailerUrl, $genreId)) {
                    $summary['series_imported']++;
                } else {
                    $summary['skipped']++;
                }
            }
        }
    }
}
?>
