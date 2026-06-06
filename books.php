<?php
/**
 * BOOKLY - Sistema de Gestión de Préstamos
 * Página de gestión de libros
 */
// PROTECCIÓN: Verificar sesión antes de mostrar cualquier contenido
require_once 'config/auth.php';
requireAuth();

require_once 'config/database.php';

$pageTitle = 'Gestión de Libros';
$errors = [];
$editMode = false;
$editBook = null;

// Obtener filtros
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// Obtener libro para editar
if (isset($_GET['edit'])) {
    $editMode = true;
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM books WHERE id = ?");
        $stmt->execute([(int)$_GET['edit']]);
        $editBook = $stmt->fetch();
    } catch (PDOException $e) {
        $errors[] = "Error al cargar el libro.";
    }
}

// Eliminar libro
if (isset($_GET['delete'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM books WHERE id = ?");
        $stmt->execute([(int)$_GET['delete']]);
        header("Location: books.php?success=" . urlencode("Libro eliminado correctamente."));
        exit;
    } catch (PDOException $e) {
        header("Location: books.php?error=" . urlencode("No se puede eliminar el libro porque tiene préstamos asociados."));
        exit;
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $author = isset($_POST['author']) ? trim($_POST['author']) : '';
    $isbn = isset($_POST['isbn']) ? trim($_POST['isbn']) : '';
    $cat = isset($_POST['category']) ? trim($_POST['category']) : '';
    $total_copies = isset($_POST['total_copies']) ? (int)$_POST['total_copies'] : 1;
    $book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;

    if (!$title) $errors[] = "El título es obligatorio.";
    if (!$author) $errors[] = "El autor es obligatorio.";
    if ($total_copies < 1) $errors[] = "Debe haber al menos 1 copia.";

    if (empty($errors)) {
        try {
            $db = getDB();
            
            if ($book_id) {
                // Actualizar
                $stmt = $db->prepare("UPDATE books SET title = ?, author = ?, isbn = ?, category = ?, total_copies = ?, available_copies = ? WHERE id = ?");
                $stmt->execute([$title, $author, $isbn, $cat, $total_copies, $total_copies, $book_id]);
                header("Location: books.php?success=" . urlencode("Libro actualizado correctamente."));
            } else {
                // Insertar
                $stmt = $db->prepare("INSERT INTO books (title, author, isbn, category, total_copies, available_copies) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $author, $isbn, $cat, $total_copies, $total_copies]);
                header("Location: books.php?success=" . urlencode("Libro agregado correctamente."));
            }
            exit;
        } catch (PDOException $e) {
            $errors[] = "Error al guardar: " . $e->getMessage();
        }
    }
}

// Obtener libros
try {
    $db = getDB();
    $sql = "SELECT * FROM books WHERE 1=1";
    $params = [];
    
    if ($search) {
        $sql .= " AND (title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm, $searchTerm];
    }
    
    if ($category) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    
    $sql .= " ORDER BY title";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $books = $stmt->fetchAll();
    
    // Obtener categorías únicas
    $categories = $db->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    $errors[] = "Error al cargar los libros.";
    $books = [];
    $categories = [];
}

$successMsg = isset($_GET['success']) ? $_GET['success'] : '';
$errorMsg = isset($_GET['error']) ? $_GET['error'] : '';

include 'includes/header.php';
?>

<?php if ($successMsg): ?>
<div class="alert alert-success">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
    </svg>
    <?php echo htmlspecialchars($successMsg); ?>
</div>
<?php endif; ?>

<?php if ($errorMsg): ?>
<div class="alert alert-error">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    <?php echo htmlspecialchars($errorMsg); ?>
</div>
<?php endif; ?>

<!-- Encabezado de página -->
<div class="page-header">
    <div>
        <h2 class="page-title">Gestión de Libros</h2>
        <p class="page-subtitle">Administra el catálogo de libros de tu biblioteca</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem;">
    <!-- Formulario -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo $editMode ? 'Editar Libro' : 'Agregar Libro'; ?></h3>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
            <div class="alert alert-error" style="margin-bottom: 1rem;">
                <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?php if ($editBook): ?>
                <input type="hidden" name="book_id" value="<?php echo $editBook['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="title" class="form-label">Título *</label>
                    <input type="text" name="title" id="title" class="form-control" required
                           value="<?php echo $editBook ? htmlspecialchars($editBook['title']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="author" class="form-label">Autor *</label>
                    <input type="text" name="author" id="author" class="form-control" required
                           value="<?php echo $editBook ? htmlspecialchars($editBook['author']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="isbn" class="form-label">ISBN</label>
                    <input type="text" name="isbn" id="isbn" class="form-control"
                           value="<?php echo $editBook ? htmlspecialchars($editBook['isbn']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="category" class="form-label">Categoría</label>
                    <input type="text" name="category" id="category" class="form-control"
                           value="<?php echo $editBook ? htmlspecialchars($editBook['category']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="total_copies" class="form-label">Copias Totales *</label>
                    <input type="number" name="total_copies" id="total_copies" class="form-control" min="1" required
                           value="<?php echo $editBook ? $editBook['total_copies'] : '1'; ?>">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <?php echo $editMode ? 'Actualizar' : 'Agregar'; ?>
                    </button>
                    <?php if ($editMode): ?>
                    <a href="books.php" class="btn btn-secondary" style="width: 100%;">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de libros -->
    <div>
        <div class="filters">
            <form method="GET" class="search-box">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" name="search" class="form-control" placeholder="Buscar..." value="<?php echo htmlspecialchars($search); ?>">
            </form>
            <select class="form-control filter-select" onchange="window.location.href='?category='+this.value+'&search=<?php echo urlencode($search); ?>'">
                <option value="">Todas las categorías</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Autor</th>
                            <th>Categoría</th>
                            <th>Disponibles</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                                <?php if ($book['isbn']): ?>
                                <br><small style="color: var(--color-gray-500);">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                            <td><?php echo htmlspecialchars($book['category'] ?: '-'); ?></td>
                            <td>
                                <span class="badge <?php echo $book['available_copies'] > 0 ? 'badge-active' : 'badge-overdue'; ?>">
                                    <?php echo $book['available_copies']; ?> / <?php echo $book['total_copies']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?edit=<?php echo $book['id']; ?>" class="btn btn-secondary btn-sm">Editar</a>
                                    <a href="?delete=<?php echo $book['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este libro?')">Eliminar</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 900px) {
    div[style*="grid-template-columns: 1fr 2fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
