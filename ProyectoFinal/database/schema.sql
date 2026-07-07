-- Database schema for Movie and Series Recommendation Platform (Proyecto Final)

CREATE DATABASE IF NOT EXISTS `proyectofinal` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `proyectofinal`;

-- 1. Roles table
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Users table
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `rol_id` INT NOT NULL,
    `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`rol_id`) REFERENCES `roles`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Genres table
CREATE TABLE IF NOT EXISTS `generos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. User Preferences table (checkboxes selected during registration)
CREATE TABLE IF NOT EXISTS `preferencias` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT NOT NULL,
    `genero_id` INT NOT NULL,
    UNIQUE KEY `user_genre` (`usuario_id`, `genero_id`),
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`genero_id`) REFERENCES `generos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Movies (Peliculas) table
CREATE TABLE IF NOT EXISTS `peliculas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `titulo` VARCHAR(255) NOT NULL,
    `descripcion` TEXT,
    `duracion` INT NOT NULL COMMENT 'En minutos',
    `año` INT NOT NULL,
    `imagen_url` VARCHAR(255) NOT NULL,
    `video_url` VARCHAR(255) NOT NULL,
    `genero_id` INT NOT NULL,
    `clicks` INT DEFAULT 0,
    `fecha_agregado` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`genero_id`) REFERENCES `generos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Series table
CREATE TABLE IF NOT EXISTS `series` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `titulo` VARCHAR(255) NOT NULL,
    `descripcion` TEXT,
    `temporadas` INT DEFAULT 1,
    `episodios` INT DEFAULT 1,
    `año` INT NOT NULL,
    `imagen_url` VARCHAR(255) NOT NULL,
    `video_url` VARCHAR(255) NOT NULL,
    `genero_id` INT NOT NULL,
    `clicks` INT DEFAULT 0,
    `fecha_agregado` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`genero_id`) REFERENCES `generos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Favorites table
CREATE TABLE IF NOT EXISTS `favoritos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT NOT NULL,
    `pelicula_id` INT DEFAULT NULL,
    `serie_id` INT DEFAULT NULL,
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`pelicula_id`) REFERENCES `peliculas`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`serie_id`) REFERENCES `series`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `user_movie` (`usuario_id`, `pelicula_id`),
    UNIQUE KEY `user_series` (`usuario_id`, `serie_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Watch History table
CREATE TABLE IF NOT EXISTS `historial` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT NOT NULL,
    `pelicula_id` INT DEFAULT NULL,
    `serie_id` INT DEFAULT NULL,
    `progreso` INT DEFAULT 0 COMMENT 'progreso en porcentaje (0-100)',
    `fecha_vista` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`pelicula_id`) REFERENCES `peliculas`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`serie_id`) REFERENCES `series`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Ratings (Calificaciones) table
CREATE TABLE IF NOT EXISTS `calificaciones` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT NOT NULL,
    `pelicula_id` INT DEFAULT NULL,
    `serie_id` INT DEFAULT NULL,
    `calificacion` INT NOT NULL CHECK (`calificacion` >= 1 AND `calificacion` <= 5),
    `comentario` TEXT,
    `fecha` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`pelicula_id`) REFERENCES `peliculas`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`serie_id`) REFERENCES `series`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Genre visits table (for statistics)
CREATE TABLE IF NOT EXISTS `visitas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT DEFAULT NULL,
    `genero_id` INT NOT NULL,
    `fecha_visita` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`genero_id`) REFERENCES `generos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Failed login attempts table (for brute-force protection)
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_login_attempts` (`username`, `ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
