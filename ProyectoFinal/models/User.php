<?php
/**
 * Modelo de Usuario
 */
require_once __DIR__ . '/../config/database.php';

class User {
    
    public static function getById($id) {
        global $conn;
        $stmt = $conn->prepare("
            SELECT u.*, r.nombre AS rol_nombre 
            FROM usuarios u 
            JOIN roles r ON u.rol_id = r.id 
            WHERE u.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    }

    public static function getByUsername($username) {
        global $conn;
        $stmt = $conn->prepare("
            SELECT u.*, r.nombre AS rol_nombre 
            FROM usuarios u 
            JOIN roles r ON u.rol_id = r.id 
            WHERE u.username = ?
        ");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    }

    public static function getByEmail($email) {
        global $conn;
        $stmt = $conn->prepare("
            SELECT u.*, r.nombre AS rol_nombre 
            FROM usuarios u 
            JOIN roles r ON u.rol_id = r.id 
            WHERE u.email = ?
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    }

    public static function create($username, $email, $password, $rol_id) {
        global $conn;
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            INSERT INTO usuarios (username, email, password, rol_id) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("sssi", $username, $email, $hashed_password, $rol_id);
        $success = $stmt->execute();
        $insert_id = $conn->insert_id;
        $stmt->close();
        return $success ? $insert_id : false;
    }

    public static function getAll() {
        global $conn;
        $sql = "
            SELECT u.id, u.username, u.email, u.rol_id, u.fecha_registro, r.nombre AS rol_nombre 
            FROM usuarios u 
            JOIN roles r ON u.rol_id = r.id 
            ORDER BY u.fecha_registro DESC
        ";
        $result = $conn->query($sql);
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        return $users;
    }

    public static function update($id, $username, $email, $rol_id) {
        global $conn;
        $stmt = $conn->prepare("
            UPDATE usuarios 
            SET username = ?, email = ?, rol_id = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("ssii", $username, $email, $rol_id, $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public static function updatePassword($id, $new_password) {
        global $conn;
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            UPDATE usuarios 
            SET password = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("si", $hashed_password, $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public static function delete($id) {
        global $conn;
        $stmt = $conn->prepare("
            DELETE FROM usuarios 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}
?>
