<?php
/**
 * BOOKLY - Sistema de Gestión de Préstamos
 * Página para agregar nuevo préstamo
 */

require_once 'config/database.php';

$pageTitle = 'Nuevo Préstamo';
$errors = [];
$success = false;

// Obtener libros disponibles
try {
    $db = getDB();
    $books = $db->query("SELECT id, title, author, available_copies FROM books WHERE available_copies > 0 ORDER BY title")->fetchAll();
    $readers = $db->query("SELECT id, full_name, email FROM readers WHERE status = 'active' ORDER BY full_name")->fetchAll();
} catch (PDOException $e) {
    $errors[] = "Error al cargar datos: " . $e->getMessage();
    $books = [];
    $readers = [];
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
    $reader_id = isset($_POST['reader_id']) ? (int)$_POST['reader_id'] : 0;
    $loan_date = isset($_POST['loan_date']) ? trim($_POST['loan_date']) : '';
    $due_date = isset($_POST['due_date']) ? trim($_POST['due_date']) : '';
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
    if ($loan_date && $due_date && strtotime($due_date) <= strtotime($loan_date)) {
        $errors[] = "La fecha de devolución debe ser posterior a la fecha de préstamo.";
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Insertar préstamo
            $stmt = $db->prepare("INSERT INTO loans (book_id, reader_id, loan_date, due_date, notes, status) VALUES (?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$book_id, $reader_id, $loan_date, $due_date, $notes]);

            // Actualizar disponibilidad del libro
            $stmt = $db->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ?");
            $stmt->execute([$book_id]);

            $db->commit();
            
            header("Location: index.php?success=" . urlencode("Préstamo registrado correctamente."));
            exit;
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Error al registrar el préstamo: " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<!-- Encabezado de página -->
<div class="page-header">
    <div>
        <h2 class="page-title">Nuevo Préstamo</h2>
        <p class="page-subtitle">Registra un nuevo préstamo de libro</p>
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
                        <option value="<?php echo $book['id']; ?>" <?php echo (isset($_POST['book_id']) && $_POST['book_id'] == $book['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($book['title'] . ' - ' . $book['author']); ?> 
                            (<?php echo $book['available_copies']; ?> disponibles)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="reader_id" class="form-label">Lector *</label>
                    <select name="reader_id" id="reader_id" class="form-control" required>
                        <option value="">Seleccionar lector...</option>
                        <?php foreach ($readers as $reader): ?>
                        <option value="<?php echo $reader['id']; ?>" <?php echo (isset($_POST['reader_id']) && $_POST['reader_id'] == $reader['id']) ? 'selected' : ''; ?>>
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
                           value="<?php echo isset($_POST['loan_date']) ? htmlspecialchars($_POST['loan_date']) : date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label for="due_date" class="form-label">Fecha de Devolución *</label>
                    <input type="date" name="due_date" id="due_date" class="form-control" 
                           value="<?php echo isset($_POST['due_date']) ? htmlspecialchars($_POST['due_date']) : date('Y-m-d', strtotime('+14 days')); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="notes" class="form-label">Notas (opcional)</label>
                <textarea name="notes" id="notes" class="form-control" placeholder="Agregar notas o comentarios..."><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Registrar Préstamo
                </button>
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
