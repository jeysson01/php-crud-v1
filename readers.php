<?php
/**
 * BOOKLY - Sistema de Gestión de Préstamos
 * Página de gestión de lectores
 */
// PROTECCIÓN: Verificar sesión antes de mostrar cualquier contenido
require_once 'config/auth.php';
requireAuth();

require_once 'config/database.php';

$pageTitle = 'Gestión de Lectores';
$errors = [];
$editMode = false;
$editReader = null;

// Obtener filtros
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// Obtener lector para editar
if (isset($_GET['edit'])) {
    $editMode = true;
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM readers WHERE id = ?");
        $stmt->execute([(int)$_GET['edit']]);
        $editReader = $stmt->fetch();
    } catch (PDOException $e) {
        $errors[] = "Error al cargar el lector.";
    }
}

// Eliminar lector
if (isset($_GET['delete'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM readers WHERE id = ?");
        $stmt->execute([(int)$_GET['delete']]);
        header("Location: readers.php?success=" . urlencode("Lector eliminado correctamente."));
        exit;
    } catch (PDOException $e) {
        header("Location: readers.php?error=" . urlencode("No se puede eliminar el lector porque tiene préstamos asociados."));
        exit;
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $readerStatus = isset($_POST['status']) ? trim($_POST['status']) : 'active';
    $reader_id = isset($_POST['reader_id']) ? (int)$_POST['reader_id'] : 0;

    if (!$full_name) $errors[] = "El nombre es obligatorio.";
    if (!$email) $errors[] = "El email es obligatorio.";
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "El email no es válido.";

    if (empty($errors)) {
        try {
            $db = getDB();
            
            if ($reader_id) {
                // Actualizar
                $stmt = $db->prepare("UPDATE readers SET full_name = ?, email = ?, phone = ?, address = ?, status = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $phone, $address, $readerStatus, $reader_id]);
                header("Location: readers.php?success=" . urlencode("Lector actualizado correctamente."));
            } else {
                // Insertar
                $stmt = $db->prepare("INSERT INTO readers (full_name, email, phone, address, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$full_name, $email, $phone, $address, $readerStatus]);
                header("Location: readers.php?success=" . urlencode("Lector agregado correctamente."));
            }
            exit;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $errors[] = "Ya existe un lector con ese email.";
            } else {
                $errors[] = "Error al guardar: " . $e->getMessage();
            }
        }
    }
}

// Obtener lectores
try {
    $db = getDB();
    $sql = "SELECT r.*, 
            (SELECT COUNT(*) FROM loans WHERE reader_id = r.id AND status = 'active') as active_loans
            FROM readers r WHERE 1=1";
    $params = [];
    
    if ($search) {
        $sql .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm, $searchTerm];
    }
    
    if ($status) {
        $sql .= " AND r.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY full_name";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $readers = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $errors[] = "Error al cargar los lectores.";
    $readers = [];
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
        <h2 class="page-title">Gestión de Lectores</h2>
        <p class="page-subtitle">Administra los lectores registrados en tu biblioteca</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem;">
    <!-- Formulario -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo $editMode ? 'Editar Lector' : 'Agregar Lector'; ?></h3>
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
                <?php if ($editReader): ?>
                <input type="hidden" name="reader_id" value="<?php echo $editReader['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="full_name" class="form-label">Nombre Completo *</label>
                    <input type="text" name="full_name" id="full_name" class="form-control" required
                           value="<?php echo $editReader ? htmlspecialchars($editReader['full_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" name="email" id="email" class="form-control" required
                           value="<?php echo $editReader ? htmlspecialchars($editReader['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone" class="form-label">Teléfono</label>
                    <input type="text" name="phone" id="phone" class="form-control"
                           value="<?php echo $editReader ? htmlspecialchars($editReader['phone']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="address" class="form-label">Dirección</label>
                    <textarea name="address" id="address" class="form-control" rows="2"><?php echo $editReader ? htmlspecialchars($editReader['address']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="status" class="form-label">Estado</label>
                    <select name="status" id="status" class="form-control">
                        <option value="active" <?php echo ($editReader && $editReader['status'] === 'active') || !$editReader ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactive" <?php echo ($editReader && $editReader['status'] === 'inactive') ? 'selected' : ''; ?>>Inactivo</option>
                        <option value="suspended" <?php echo ($editReader && $editReader['status'] === 'suspended') ? 'selected' : ''; ?>>Suspendido</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <?php echo $editMode ? 'Actualizar' : 'Agregar'; ?>
                    </button>
                    <?php if ($editMode): ?>
                    <a href="readers.php" class="btn btn-secondary" style="width: 100%;">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de lectores -->
    <div>
        <div class="filters">
            <form method="GET" class="search-box">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" name="search" class="form-control" placeholder="Buscar..." value="<?php echo htmlspecialchars($search); ?>">
            </form>
            <select class="form-control filter-select" onchange="window.location.href='?status='+this.value+'&search=<?php echo urlencode($search); ?>'">
                <option value="">Todos los estados</option>
                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Activos</option>
                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactivos</option>
                <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspendidos</option>
            </select>
        </div>
        
        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Préstamos</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($readers as $reader): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($reader['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($reader['email']); ?></td>
                            <td><?php echo htmlspecialchars($reader['phone'] ?: '-'); ?></td>
                            <td>
                                <?php if ($reader['active_loans'] > 0): ?>
                                <span class="badge badge-active"><?php echo $reader['active_loans']; ?> activos</span>
                                <?php else: ?>
                                <span style="color: var(--color-gray-400);">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $badgeClass = 'badge-active';
                                $statusText = 'Activo';
                                if ($reader['status'] === 'inactive') {
                                    $badgeClass = 'badge-returned';
                                    $statusText = 'Inactivo';
                                } elseif ($reader['status'] === 'suspended') {
                                    $badgeClass = 'badge-overdue';
                                    $statusText = 'Suspendido';
                                }
                                ?>
                                <span class="badge <?php echo $badgeClass; ?>"><?php echo $statusText; ?></span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?edit=<?php echo $reader['id']; ?>" class="btn btn-secondary btn-sm">Editar</a>
                                    <a href="?delete=<?php echo $reader['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este lector?')">Eliminar</a>
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
