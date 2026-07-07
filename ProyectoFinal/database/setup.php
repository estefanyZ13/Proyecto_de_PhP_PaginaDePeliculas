<?php
/**
 * Script de inicialización y carga de datos para el Proyecto Final
 */

header("Content-Type: text/html; charset=UTF-8");
echo "<h2>Inicializando la Base de Datos 'Proyecto Final'...</h2>";

$host = "localhost";
$user = "root";
$password = "";

// 1. Establecer conexión inicial a MySQL sin base de datos seleccionada
$conn = new mysqli($host, $user, $password);

if ($conn->connect_error) {
    die("<p style='color:red;'>Error de conexión al servidor MySQL: " . $conn->connect_error . "</p>");
}

echo "<p style='color:green;'>✓ Conexión exitosa a MySQL.</p>";

// 2. Leer y ejecutar el archivo schema.sql
$schema_file = __DIR__ . '/schema.sql';
if (!file_exists($schema_file)) {
    die("<p style='color:red;'>Error: No se encontró el archivo schema.sql en: $schema_file</p>");
}

$sql_schema = file_get_contents($schema_file);

if ($conn->multi_query($sql_schema)) {
    do {
        // Limpiar el buffer de resultados de multi_query
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    echo "<p style='color:green;'>✓ Esquema de base de datos cargado exitosamente.</p>";
} else {
    die("<p style='color:red;'>Error al cargar el esquema: " . $conn->error . "</p>");
}

// Cerrar conexión temporal y volver a conectar seleccionando la BD
$conn->close();
$dbname = "proyectofinal";
$conn = new mysqli($host, $user, $password, $dbname);
$conn->set_charset("utf8mb4");

// 3. Insertar datos de prueba (Seed Data)
echo "<h3>Sembrando datos iniciales...</h3>";

// Roles
$conn->query("INSERT IGNORE INTO roles (id, nombre) VALUES (1, 'Administrador'), (2, 'Usuario')");
echo "<p>✓ Roles registrados.</p>";

// Usuarios (password_hash para seguridad)
$pass_admin = password_hash('admin123', PASSWORD_DEFAULT);
$pass_user1 = password_hash('user123', PASSWORD_DEFAULT);
$pass_user2 = password_hash('maria123', PASSWORD_DEFAULT);
$pass_user3 = password_hash('juan123', PASSWORD_DEFAULT);

$conn->query("INSERT IGNORE INTO usuarios (id, username, email, password, rol_id) VALUES 
    (1, 'admin', 'admin@proyectofinal.com', '$pass_admin', 1),
    (2, 'user', 'user@proyectofinal.com', '$pass_user1', 2),
    (3, 'maria', 'maria@gmail.com', '$pass_user2', 2),
    (4, 'juan', 'juan@gmail.com', '$pass_user3', 2)
");
echo "<p>✓ Usuarios de prueba creados.</p>";

// Géneros
$generos = [
    1 => 'Acción',
    2 => 'Terror',
    3 => 'Comedia',
    4 => 'Romance',
    5 => 'Ciencia ficción',
    6 => 'Drama',
    7 => 'Animación',
    8 => 'Documentales'
];
foreach ($generos as $id => $nombre) {
    $conn->query("INSERT IGNORE INTO generos (id, nombre) VALUES ($id, '$nombre')");
}
echo "<p>✓ Géneros insertados.</p>";

// Preferencias de géneros (usuario 2 - user)
$conn->query("INSERT IGNORE INTO preferencias (usuario_id, genero_id) VALUES (2, 1), (2, 3), (2, 5)");
// Preferencias de géneros (usuario 3 - maria)
$conn->query("INSERT IGNORE INTO preferencias (usuario_id, genero_id) VALUES (3, 4), (3, 6), (3, 7)");
// Preferencias de géneros (usuario 4 - juan)
$conn->query("INSERT IGNORE INTO preferencias (usuario_id, genero_id) VALUES (4, 2), (4, 8)");
echo "<p>✓ Preferencias iniciales guardadas.</p>";

// Películas (id, titulo, descripcion, duracion, año, imagen_url, video_url, genero_id, clicks)
// Nota: Las imágenes apuntarán a marcadores en assets/img/ o paths relativos. Usamos URLs ilustrativas.
$peliculas = [
    [1, 'Avengers: Endgame', 'Los Vengadores se reúnen una vez más para intentar revertir las acciones de Thanos y restaurar el orden en el universo.', 181, 2019, 'assets/img/endgame.svg', 'https://www.youtube.com/embed/TcMBFSGVi1c', 1, 150],
    [2, 'Free Guy', 'Un cajero de banco descubre que es un personaje secundario en un videojuego de mundo abierto y decide convertirse en el héroe de su propia historia.', 115, 2021, 'assets/img/freeguy.svg', 'https://www.youtube.com/embed/X2m-08cRItM', 3, 90],
    [3, 'Un Lugar en Silencio', 'Una familia lucha por sobrevivir en un mundo postapocalíptico habitado por monstruos ciegos con un oído ultra sensible.', 90, 2018, 'assets/img/quietplace.svg', 'https://www.youtube.com/embed/p9wE8dyzEJE', 2, 70],
    [4, 'El Conjuro', 'Los investigadores paranormales Ed y Lorraine Warren acuden al llamado de una familia aterrorizada por una presencia oscura en su granja.', 112, 2013, 'assets/img/conjuring.svg', 'https://www.youtube.com/embed/k10ETZ41q5o', 2, 60],
    [5, 'Intensa-Mente 2', 'Riley entra a la adolescencia y su cuartel general sufre una repentina demolición para hacer espacio a algo totalmente inesperado: ¡nuevas emociones!', 96, 2024, 'assets/img/insideout2.svg', 'https://www.youtube.com/embed/LEjhY15iCx0', 7, 300],
    [6, 'Coco', 'Miguel es un niño que sueña con ser músico y, por accidente, viaja a la colorida Tierra de los Muertos para descubrir el misterio de su familia.', 105, 2017, 'assets/img/coco.svg', 'https://www.youtube.com/embed/awzWdtCezDo', 7, 180],
    [7, 'La La Land', 'Un pianista de jazz y una aspirante a actriz se enamoran en Los Ángeles mientras intentan conciliar sus aspiraciones y su carrera.', 128, 2016, 'assets/img/lalaland.svg', 'https://www.youtube.com/embed/0pdqf4P9MB8', 4, 95],
    [8, 'Avatar: El Camino del Agua', 'Jake Sully y Neytiri forman una familia y hacen todo lo posible por permanecer juntos, explorando las regiones de Pandora.', 192, 2022, 'assets/img/avatar2poster.svg', 'https://www.youtube.com/embed/a8Gx8mBstG8', 5, 210],
    [9, 'Interstellar', 'Un grupo de científicos y exploradores espaciales viaja a través de un agujero de gusano para encontrar un nuevo hogar para la humanidad.', 169, 2014, 'assets/img/interstellar.svg', 'https://www.youtube.com/embed/zSWdZAIB3nY', 5, 140],
    [10, 'El Rey León (Live Action)', 'Tras la muerte de su padre, el pequeño león Simba huye de su reino para descubrir el verdadero significado de la responsabilidad y el valor.', 118, 2019, 'assets/img/lionking.svg', 'https://www.youtube.com/embed/7TavVZMewpY', 6, 120],
    [11, 'Limitless con Chris Hemsworth', 'Chris Hemsworth explora formas de combatir el envejecimiento y descubrir el verdadero potencial del cuerpo humano.', 60, 2022, 'assets/img/limitless.svg', 'https://www.youtube.com/embed/i0COx_K7oQk', 8, 45]
];

foreach ($peliculas as $p) {
    $stmt = $conn->prepare("INSERT IGNORE INTO peliculas (id, titulo, descripcion, duracion, año, imagen_url, video_url, genero_id, clicks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississsii", $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7], $p[8]);
    $stmt->execute();
    $stmt->close();
}
echo "<p>✓ Películas registradas.</p>";

// Series (id, titulo, descripcion, temporadas, episodios, año, imagen_url, video_url, genero_id, clicks)
$series = [
    [1, 'The Mandalorian', 'Un cazarrecompensas solitario viaja por los confines de la galaxia, lejos de la autoridad de la Nueva República.', 3, 24, 2019, 'assets/img/mandalorian.svg', 'https://www.youtube.com/embed/aOC8E8z_ifw', 1, 240],
    [2, 'Loki', 'El voluble villano Loki retoma su papel como el Dios de las Travesuras en una nueva serie que transcurre tras los acontecimientos de Avengers: Endgame.', 2, 12, 2021, 'assets/img/loki.svg', 'https://www.youtube.com/embed/nW948Va-l10', 1, 190],
    [3, 'WandaVision', 'Combina el estilo de sitcoms clásicas con el Universo Cinematográfico de Marvel para contar la historia de Wanda Maximoff y Vision.', 1, 9, 2021, 'assets/img/wandavision.svg', 'https://www.youtube.com/embed/sj9J2ecsSpo', 5, 110],
    [4, 'Shōgun', 'En el Japón del año 1600, el señor feudal Lord Yoshii Toranaga lucha por su vida mientras sus enemigos en el Consejo de Regentes se alían.', 1, 10, 2024, 'assets/img/shogun.svg', 'https://www.youtube.com/embed/yAN5sR24nS0', 6, 175],
    [5, 'X-Men \'97', 'Banda de mutantes que usan sus extraños dones para proteger a un mundo que los teme y los odia en los años 90.', 1, 10, 2024, 'assets/img/xmen97.svg', 'https://www.youtube.com/embed/pv3PBja97K0', 7, 230],
    [6, 'Bridgerton', 'Ocho hermanos de la poderosa familia Bridgerton intentan encontrar el amor y la felicidad en la alta sociedad londinense.', 3, 24, 2020, 'assets/img/bridgerton.svg', 'https://www.youtube.com/embed/gpv7ayf_tyE', 4, 85],
    [7, 'Welcome to Earth', 'Will Smith viaja a los lugares más extremos, activos e inexplorados del planeta en una aventura única.', 1, 6, 2021, 'assets/img/welcomeearth.svg', 'https://www.youtube.com/embed/K955A434sZ4', 8, 50],
    [8, 'Monsters at Work', 'Tylor Tuskmon se gradúa como el mejor de su clase en Monsters University y llega a Monsters, Inc. justo cuando el susto se cambia por la risa.', 2, 20, 2021, 'assets/img/monsterswork.svg', 'https://www.youtube.com/embed/d8J8nB47K_4', 7, 95]
];

foreach ($series as $s) {
    $stmt = $conn->prepare("INSERT IGNORE INTO series (id, titulo, descripcion, temporadas, episodios, año, imagen_url, video_url, genero_id, clicks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issiisssii", $s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $s[8], $s[9]);
    $stmt->execute();
    $stmt->close();
}
echo "<p>✓ Series registradas.</p>";

// Favoritos iniciales
$conn->query("INSERT IGNORE INTO favoritos (usuario_id, pelicula_id) VALUES (2, 1), (2, 8)");
$conn->query("INSERT IGNORE INTO favoritos (usuario_id, serie_id) VALUES (2, 1)");
$conn->query("INSERT IGNORE INTO favoritos (usuario_id, pelicula_id) VALUES (3, 7), (3, 10)");
echo "<p>✓ Favoritos iniciales insertados.</p>";

// Historial (usuario 2 - user)
$conn->query("INSERT IGNORE INTO historial (usuario_id, pelicula_id, progreso) VALUES (2, 2, 45)"); // Free Guy (45%)
$conn->query("INSERT IGNORE INTO historial (usuario_id, serie_id, progreso) VALUES (2, 2, 80)");   // Loki (80%)
echo "<p>✓ Historial inicial insertado.</p>";

// Calificaciones de prueba
$conn->query("INSERT IGNORE INTO calificaciones (usuario_id, pelicula_id, calificacion, comentario) VALUES 
    (2, 1, 5, 'Excelente película, el mejor cierre para la saga.'),
    (3, 7, 4, 'Hermosa banda sonora y actuaciones increíbles.'),
    (4, 3, 5, 'Terror del bueno, te mantiene al borde del asiento.')
");
$conn->query("INSERT IGNORE INTO calificaciones (usuario_id, serie_id, calificacion, comentario) VALUES 
    (2, 1, 5, 'Baby Yoda es genial y la música espectacular.'),
    (3, 4, 5, 'Una obra maestra de época, muy bien producida.')
");
echo "<p>✓ Calificaciones y comentarios sembrados.</p>";

// Visitas iniciales para simular estadísticas (generos)
$visitas = [
    [2, 1], [2, 1], [2, 5], [2, 3], // Acción (2), CF (1), Comedia (1)
    [3, 7], [3, 7], [3, 4], [3, 6], // Animación (2), Romance (1), Drama (1)
    [4, 2], [4, 2], [4, 2], [4, 8]  // Terror (3), Doc (1)
];
foreach ($visitas as $v) {
    $usuario_id = (int)$v[0];
    $genero_id = (int)$v[1];
    $exists = $conn->query("SELECT id FROM visitas WHERE usuario_id = $usuario_id AND genero_id = $genero_id LIMIT 1");
    if ($exists && $exists->num_rows === 0) {
        $conn->query("INSERT INTO visitas (usuario_id, genero_id) VALUES ($usuario_id, $genero_id)");
    }
}
echo "<p>✓ Visitas de estadísticas registradas.</p>";

// Crear directorio de subida de imágenes si no existe
$uploads_dir = dirname(__DIR__) . '/uploads';
if (!file_exists($uploads_dir)) {
    mkdir($uploads_dir, 0777, true);
}
// Copiar o crear imágenes ficticias en assets/img para que no se caigan las referencias
$img_dir = dirname(__DIR__) . '/assets/img';
if (!file_exists($img_dir)) {
    mkdir($img_dir, 0777, true);
}

// Crear archivos de imagen ficticios (vacíos o con un pixel) para que no den 404
$mock_images = [
    'endgame.svg', 'freeguy.svg', 'quietplace.svg', 'conjuring.svg',
    'insideout2.svg', 'coco.svg', 'lalaland.svg', 'avatar2poster.svg',
    'interstellar.svg', 'lionking.svg', 'limitless.svg',
    'mandalorian.svg', 'loki.svg', 'wandavision.svg', 'shogun.svg',
    'xmen97.svg', 'bridgerton.svg', 'welcomeearth.svg', 'monsterswork.svg',
    'placeholder.svg'
];
foreach ($mock_images as $img) {
    $img_path = $img_dir . '/' . $img;
    if (!file_exists($img_path)) {
        // Crear una imagen PNG/JPG mínima o simplemente tocar el archivo
        file_put_contents($img_path, ""); 
    }
}
echo "<p>✓ Archivos de imagen simulados en assets/img/.</p>";

echo "<h4 style='color:green;'>¡Base de datos y datos de prueba configurados correctamente!</h4>";
echo "<p><a href='../index.php'>Volver al Inicio</a></p>";

$conn->close();
?>
