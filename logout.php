<?php
/**
 * BOOKLY - Sistema de Gestión de Préstamos
 * Cerrar sesión de administrador
 */

session_start();

// Registrar actividad de logout si hay sesión activa
if (isset($_SESSION['admin_id'])) {
    require_once 'config/database.php';
    
    try {
        $db = getDB();
        $logStmt = $db->prepare("INSERT INTO admin_activity_log (admin_id, action, description, ip_address) VALUES (?, 'logout', 'Cierre de sesión', ?)");
        $logStmt->execute([$_SESSION['admin_id'], $_SERVER['REMOTE_ADDR']]);
    } catch (PDOException $e) {
        // Ignorar errores de log
    }
}

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Eliminar cookie de recordar
if (isset($_COOKIE['bookly_remember'])) {
    setcookie('bookly_remember', '', time() - 3600, '/');
}

// Redirigir al login
header('Location: login.php?logout=1');
exit;
?>
