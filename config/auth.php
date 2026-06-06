<?php
/**
 * BOOKLY - Sistema de Gestión de Préstamos
 * Middleware de autenticación
 * 
 * Incluir este archivo al inicio de cada página protegida:
 * require_once 'config/auth.php';
 */

session_start();

/**
 * Verificar si el usuario está autenticado
 */
function isAuthenticated() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Requerir autenticación - Redirigir a login si no está autenticado
 */
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/**
 * Verificar si el usuario es super admin
 */
function isSuperAdmin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'super_admin';
}

/**
 * Requerir rol de super admin
 */
function requireSuperAdmin() {
    requireAuth();
    if (!isSuperAdmin()) {
        header('Location: index.php?error=No tienes permisos para acceder a esta sección');
        exit;
    }
}

/**
 * Obtener información del admin actual
 */
function getCurrentAdmin() {
    if (!isAuthenticated()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['admin_id'],
        'name' => $_SESSION['admin_name'] ?? '',
        'email' => $_SESSION['admin_email'] ?? '',
        'role' => $_SESSION['admin_role'] ?? 'admin'
    ];
}

/**
 * Registrar actividad del administrador
 */
function logActivity($action, $description = '') {
    if (!isAuthenticated()) {
        return false;
    }
    
    require_once __DIR__ . '/database.php';
    
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO admin_activity_log (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['admin_id'],
            $action,
            $description,
            $_SERVER['REMOTE_ADDR']
        ]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Auto-verificar autenticación si se incluye este archivo
// Comentar la siguiente línea si deseas controlar manualmente la verificación
// requireAuth();
?>
