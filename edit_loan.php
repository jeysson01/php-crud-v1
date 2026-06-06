<?php
/**
 * BOOKLY - Sistema de Gestión de Préstamos
 * Página para editar préstamo
 */
// PROTECCIÓN: Verificar sesión antes de mostrar cualquier contenido
require_once 'config/auth.php';
requireAuth();

require_once 'config/database.php';

$pageTitle = 'Editar Préstamo';
$errors = [];
$loan = null;

// Obtener ID del préstamo
$loan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$loan_id) {
    header("Location: index.php?error=" . urlencode("ID de préstamo no válido."));
    exit;
}

try {
    $db = getDB();
    
    // Obtener préstamo
    $stmt = $db->prepare("SELECT * FROM loans WHERE id = ?");
    $stmt->execute([$loan_id]);
    $loan = $stmt->fetch();
    
    if (!$loan) {
        header("Location: index.php?error=" . urlencode("Préstamo no encontrado."));
        exit;
    }
    
    // Obtener libros y lectores
    $books = $db->query("SELECT id, title, author, available_copies FROM books ORDER BY title")->fetchAll();
    $readers = $db->query("SELECT id, full_name, email FROM readers ORDER BY full_name")->fetchAll();
    
} catch (PDOException $e) {
    header("Location: index.php?error=" . urlencode("Error al cargar el préstamo."));
    exit;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
    $reader_id = isset($_POST['reader_id']) ? (int)$_POST['reader_id'] : 0;
    $loan_date = isset($_POST['loan_date']) ? trim($_POST['loan_date']) : '';
    $due_date = isset($_POST['due_date']) ? trim($_POST['due_date']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'active';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

    // Validaciones
    if (!$book_id) {
        $errors[] = "Debes seleccionar un libro.";
    }
    if (!$reader_id) {
        $errors[] = "Debes seleccionar un lector.";
    }
    if (!$loan_date) {
        $errors[] = "La fecha de préstamo es obligatoria.";
    }
    if (!$due_date) {
        $errors[] = "La fecha de devolución es obligatoria.";
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Si cambió el libro, actualizar disponibilidad
            if ($book_id != $loan['book_id']) {
                // Devolver copia al libro anterior (si no estaba devuelto)
                if ($loan['status'] !== 'returned') {
                    $stmt = $db->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
                    $stmt->execute([$loan['book_id']]);
                }
                // Restar copia al nuevo libro
                $stmt = $db->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ?");
                $stmt->execute([$book_id]);
            }
            
            // Si se marca como devuelto y antes no lo estaba
            $return_date = null;
            if ($status === 'returned' && $loan['status'] !== 'returned') {
                $return_date = date('Y-m-d');
                // Devolver la copia
                $stmt = $db->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
                $stmt->execute([$book_id]);
            }
            
            // Actualizar préstamo
            $sql = "UPDATE loans SET book_id = ?, reader_id = ?, loan_date = ?, due_date = ?, status = ?, notes = ?";
            $params = [$book_id, $reader_id, $loan_date, $due_date, $status, $notes];
            
            if ($return_date) {
                $sql .= ", return_date = ?";
                $params[] = $return_date;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $loan_id;
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            $db->commit();
            
            header("Location: index.php?success=" . urlencode("Préstamo actualizado correctamente."));
            exit;
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Error al actualizar el préstamo: " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<!-- Encabezado de página -->
<div class="page-header">
    <div>
        <h2 class="page-title">Editar Préstamo #<?php echo $loan_id; ?></h2>
        <p class="page-subtitle">Modifica la información del préstamo</p>
    </div>
    <a href="index.php" class="btn btn-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Volver
    </a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    <div>
        <?php foreach ($errors as $error): ?>
        <p><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Información del Préstamo</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="book_id" class="form-label">Libro *</label>
                    <select name="book_id" id="book_id" class="form-control" required>
                        <option value="">Seleccionar libro...</option>
                        <?php foreach ($books as $book): ?>
                        <option value="<?php echo $book['id']; ?>" <?php echo ($loan['book_id'] == $book['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($book['title'] . ' - ' . $book['author']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="reader_id" class="form-label">Lector *</label>
                    <select name="reader_id" id="reader_id" class="form-control" required>
                        <option value="">Seleccionar lector...</option>
                        <?php foreach ($readers as $reader): ?>
                        <option value="<?php echo $reader['id']; ?>" <?php echo ($loan['reader_id'] == $reader['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($reader['full_name'] . ' (' . $reader['email'] . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="loan_date" class="form-label">Fecha de Préstamo *</label>
                    <input type="date" name="loan_date" id="loan_date" class="form-control" 
                           value="<?php echo htmlspecialchars($loan['loan_date']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="due_date" class="form-label">Fecha de Devolución *</label>
                    <input type="date" name="due_date" id="due_date" class="form-control" 
                           value="<?php echo htmlspecialchars($loan['due_date']); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Estado *</label>
                <select name="status" id="status" class="form-control" required>
                    <option value="active" <?php echo ($loan['status'] === 'active') ? 'selected' : ''; ?>>Activo</option>
                    <option value="overdue" <?php echo ($loan['status'] === 'overdue') ? 'selected' : ''; ?>>Vencido</option>
                    <option value="returned" <?php echo ($loan['status'] === 'returned') ? 'selected' : ''; ?>>Devuelto</option>
                </select>
            </div>

            <div class="form-group">
                <label for="notes" class="form-label">Notas (opcional)</label>
                <textarea name="notes" id="notes" class="form-control" placeholder="Agregar notas o comentarios..."><?php echo htmlspecialchars($loan['notes']); ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Guardar Cambios
                </button>
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
