<?php
/**
 * BOOKLY - Sistema de Gestión de Préstamos
 * Marcar préstamo como devuelto
 */
// PROTECCIÓN: Verificar sesión antes de mostrar cualquier contenido
require_once 'config/auth.php';
requireAuth();

require_once 'config/database.php';

$loan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$loan_id) {
    header("Location: index.php?error=" . urlencode("ID de préstamo no válido."));
    exit;
}

try {
    $db = getDB();
    
    // Obtener préstamo
    $stmt = $db->prepare("SELECT book_id, status FROM loans WHERE id = ?");
    $stmt->execute([$loan_id]);
    $loan = $stmt->fetch();
    
    if (!$loan) {
        header("Location: index.php?error=" . urlencode("Préstamo no encontrado."));
        exit;
    }
    
    if ($loan['status'] === 'returned') {
        header("Location: index.php?error=" . urlencode("Este préstamo ya fue devuelto."));
        exit;
    }
    
    $db->beginTransaction();
    
    // Actualizar préstamo como devuelto
    $stmt = $db->prepare("UPDATE loans SET status = 'returned', return_date = CURDATE() WHERE id = ?");
    $stmt->execute([$loan_id]);
    
    // Devolver la copia del libro
    $stmt = $db->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
    $stmt->execute([$loan['book_id']]);
    
    $db->commit();
    
    header("Location: index.php?success=" . urlencode("Libro devuelto correctamente."));
    exit;
    
} catch (PDOException $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    header("Location: index.php?error=" . urlencode("Error al procesar la devolución."));
    exit;
}
?>
