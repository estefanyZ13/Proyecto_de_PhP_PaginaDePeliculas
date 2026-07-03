<?php
/**
 * WebService XML para Importar/Exportar Catálogo de Películas y Series
 */
header("Content-Type: application/xml; charset=UTF-8");

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Movie.php';
require_once __DIR__ . '/../../models/Series.php';
require_once __DIR__ . '/../../models/Genre.php';

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    // EXPORTAR CATÁLOGO A XML
    $movies = Movie::getAll();
    $series = Series::getAll();

    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><catalog/>');
    
    // Películas
    $moviesNode = $xml->addChild('movies');
    foreach ($movies as $m) {
        $node = $moviesNode->addChild('movie');
        $node->addChild('id', $m['id']);
        $node->addChild('titulo', htmlspecialchars($m['titulo']));
        $node->addChild('descripcion', htmlspecialchars($m['descripcion']));
        $node->addChild('duracion', $m['duracion']);
        $node->addChild('ano', $m['año']);
        $node->addChild('imagen_url', htmlspecialchars($m['imagen_url']));
        $node->addChild('video_url', htmlspecialchars($m['video_url']));
        $node->addChild('genero', htmlspecialchars($m['genero_nombre']));
    }

    // Series
    $seriesNode = $xml->addChild('series');
    foreach ($series as $s) {
        $node = $seriesNode->addChild('serie');
        $node->addChild('id', $s['id']);
        $node->addChild('titulo', htmlspecialchars($s['titulo']));
        $node->addChild('descripcion', htmlspecialchars($s['descripcion']));
        $node->addChild('temporadas', $s['temporadas']);
        $node->addChild('episodios', $s['episodios']);
        $node->addChild('ano', $s['año']);
        $node->addChild('imagen_url', htmlspecialchars($s['imagen_url']));
        $node->addChild('video_url', htmlspecialchars($s['video_url']));
        $node->addChild('genero', htmlspecialchars($s['genero_nombre']));
    }

    echo $xml->asXML();
    exit;

} elseif ($metodo === 'POST') {
    // IMPORTAR CATÁLOGO DESDE XML
    // Protección por Rol: Solo administradores pueden importar
    if (!hasRole('Administrador')) {
        http_response_code(403);
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><response/>');
        $xml->addChild('status', 'error');
        $xml->addChild('message', 'Acceso denegado. Se requieren permisos de Administrador.');
        echo $xml->asXML();
        exit;
    }

    $xmlData = file_get_contents("php://input");
    
    if (empty($xmlData)) {
        http_response_code(400);
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><response/>');
        $xml->addChild('status', 'error');
        $xml->addChild('message', 'Datos XML vacíos.');
        echo $xml->asXML();
        exit;
    }

    libxml_use_internal_errors(true);
    $parsedXml = simplexml_load_string($xmlData);
    
    if ($parsedXml === false) {
        http_response_code(400);
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><response/>');
        $xml->addChild('status', 'error');
        $xml->addChild('message', 'XML inválido o mal formado.');
        
        $errorsNode = $xml->addChild('errors');
        foreach (libxml_get_errors() as $error) {
            $errorsNode->addChild('error', trim($error->message));
        }
        libxml_clear_errors();
        
        echo $xml->asXML();
        exit;
    }

    $importedMovies = 0;
    $importedSeries = 0;
    
    // Obtener géneros actuales para mapear por nombre
    $genres = Genre::getAll();
    $genresMap = [];
    foreach ($genres as $g) {
        $genresMap[strtolower($g['nombre'])] = $g['id'];
    }

    // 1. Importar Películas
    if (isset($parsedXml->movies->movie)) {
        foreach ($parsedXml->movies->movie as $m) {
            $titulo = clean((string)$m->titulo);
            $descripcion = clean((string)$m->descripcion);
            $duracion = (int)$m->duracion;
            $ano = (int)$m->ano;
            $imagen_url = clean((string)$m->imagen_url);
            $video_url = clean((string)$m->video_url);
            $genero_nombre = clean((string)$m->genero);

            if (empty($titulo) || empty($genero_nombre)) continue;

            // Mapear género, si no existe lo creamos
            $gen_key = strtolower($genero_nombre);
            if (!isset($genresMap[$gen_key])) {
                $new_id = Genre::create($genero_nombre);
                if ($new_id) {
                    $genresMap[$gen_key] = $new_id;
                } else {
                    continue; // saltar si no se pudo crear el género
                }
            }
            $genero_id = $genresMap[$gen_key];

            // Registrar película
            $success = Movie::create($titulo, $descripcion, $duracion, $ano, $imagen_url, $video_url, $genero_id);
            if ($success) {
                $importedMovies++;
            }
        }
    }

    // 2. Importar Series
    if (isset($parsedXml->series->serie)) {
        foreach ($parsedXml->series->serie as $s) {
            $titulo = clean((string)$s->titulo);
            $descripcion = clean((string)$s->descripcion);
            $temporadas = (int)$s->temporadas;
            $episodios = (int)$s->episodios;
            $ano = (int)$s->ano;
            $imagen_url = clean((string)$s->imagen_url);
            $video_url = clean((string)$s->video_url);
            $genero_nombre = clean((string)$s->genero);

            if (empty($titulo) || empty($genero_nombre)) continue;

            $gen_key = strtolower($genero_nombre);
            if (!isset($genresMap[$gen_key])) {
                $new_id = Genre::create($genero_nombre);
                if ($new_id) {
                    $genresMap[$gen_key] = $new_id;
                } else {
                    continue;
                }
            }
            $genero_id = $genresMap[$gen_key];

            $success = Series::create($titulo, $descripcion, $temporadas, $episodios, $ano, $imagen_url, $video_url, $genero_id);
            if ($success) {
                $importedSeries++;
            }
        }
    }

    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><response/>');
    $xml->addChild('status', 'success');
    $xml->addChild('message', 'Importación completada con éxito.');
    $xml->addChild('movies_imported', $importedMovies);
    $xml->addChild('series_imported', $importedSeries);
    echo $xml->asXML();
    exit;

} else {
    http_response_code(405);
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><response/>');
    $xml->addChild('status', 'error');
    $xml->addChild('message', 'Método HTTP no permitido.');
    echo $xml->asXML();
    exit;
}
?>
