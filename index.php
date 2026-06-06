<?php
/**
 * BOOKLY - Sistema de Gestión de Préstamos
 * Página principal - Lista de préstamos
 */

// PROTECCIÓN: Verificar sesión antes de mostrar cualquier contenido
require_once 'config/auth.php';
requireAuth();

require_once 'config/database.php';

$pageTitle = 'Gestión de Préstamos';

// Obtener filtros
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Construir query
$sql = "SELECT l.*, b.title as book_title, b.author as book_author, r.full_name as reader_name, r.email as reader_email
        FROM loans l
        JOIN books b ON l.book_id = b.id
        JOIN readers r ON l.reader_id = r.id
        WHERE 1=1";
$params = [];

if ($statusFilter) {
    $sql .= " AND l.status = ?";
    $params[] = $statusFilter;
}

if ($search) {
    $sql .= " AND (b.title LIKE ? OR r.full_name LIKE ? OR b.author LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY l.created_at DESC";

try {
    $db = getDB();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $loans = $stmt->fetchAll();

    // Obtener estadísticas
    $statsQuery = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue,
        SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as returned
        FROM loans";
    $stats = $db->query($statsQuery)->fetch();
} catch (PDOException $e) {
    $error = "Error al cargar los préstamos: " . $e->getMessage();
    $loans = [];
    $stats = ['total' => 0, 'active' => 0, 'overdue' => 0, 'returned' => 0];
}

// Mensajes de sesión
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

<!-- Estadísticas -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
        </div>
        <div class="stat-content">
            <h3>Total Préstamos</h3>
            <p><?php echo $stats['total']; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon secondary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="stat-content">
            <h3>Activos</h3>
            <p><?php echo $stats['active']; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <div class="stat-content">
            <h3>Vencidos</h3>
            <p><?php echo $stats['overdue']; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <div class="stat-content">
            <h3>Devueltos</h3>
            <p><?php echo $stats['returned']; ?></p>
        </div>
    </div>
</div>

<!-- Encabezado de página -->
<div class="page-header">
    <div>
        <h2 class="page-title">Gestión de Préstamos</h2>
        <p class="page-subtitle">Administra los préstamos de libros de tu biblioteca</p>
    </div>
    <div style="display:flex; align-items:center; gap:0.75rem;">
        <a href="add_loan.php" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Nuevo Préstamo
        </a>
        <a href="logout.php" class="btn btn-secondary" onclick="return confirm('¿Seguro que desea cerrar sesión?')" style="display:flex; align-items:center; gap:0.375rem;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            Cerrar Sesión
        </a>
    </div>
</div>

<!-- Filtros -->
<div class="filters">
    <form method="GET" class="search-box">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input type="text" name="search" class="form-control" placeholder="Buscar por libro, autor o lector..." value="<?php echo htmlspecialchars($search); ?>">
        <?php if ($statusFilter): ?>
        <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
        <?php endif; ?>
    </form>
    <select name="status" class="form-control filter-select" onchange="window.location.href='?status='+this.value+'&search=<?php echo urlencode($search); ?>'">
        <option value="">Todos los estados</option>
        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Activos</option>
        <option value="overdue" <?php echo $statusFilter === 'overdue' ? 'selected' : ''; ?>>Vencidos</option>
        <option value="returned" <?php echo $statusFilter === 'returned' ? 'selected' : ''; ?>>Devueltos</option>
    </select>
</div>

<!-- Tabla de préstamos -->
<div class="card">
    <div class="table-container">
        <?php if (count($loans) > 0): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Libro</th>
                    <th>Lector</th>
                    <th>Fecha Préstamo</th>
                    <th>Fecha Devolución</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($loans as $loan): ?>
                <tr>
                    <td><strong>#<?php echo $loan['id']; ?></strong></td>
                    <td>
                        <div>
                            <strong><?php echo htmlspecialchars($loan['book_title']); ?></strong>
                            <br>
                            <small style="color: var(--color-gray-500);"><?php echo htmlspecialchars($loan['book_author']); ?></small>
                        </div>
                    </td>
                    <td>
                        <div>
                            <strong><?php echo htmlspecialchars($loan['reader_name']); ?></strong>
                            <br>
                            <small style="color: var(--color-gray-500);"><?php echo htmlspecialchars($loan['reader_email']); ?></small>
                        </div>
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($loan['loan_date'])); ?></td>
                    <td>
                        <?php echo date('d/m/Y', strtotime($loan['due_date'])); ?>
                        <?php if ($loan['return_date']): ?>
                        <br>
                        <small style="color: var(--color-gray-500);">Devuelto: <?php echo date('d/m/Y', strtotime($loan['return_date'])); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php 
                        $badgeClass = 'badge-active';
                        $statusText = 'Activo';
                        if ($loan['status'] === 'returned') {
                            $badgeClass = 'badge-returned';
                            $statusText = 'Devuelto';
                        } elseif ($loan['status'] === 'overdue') {
                            $badgeClass = 'badge-overdue';
                            $statusText = 'Vencido';
                        }
                        ?>
                        <span class="badge <?php echo $badgeClass; ?>"><?php echo $statusText; ?></span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a href="edit_loan.php?id=<?php echo $loan['id']; ?>" class="btn btn-secondary btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Editar
                            </a>
                            <?php if ($loan['status'] !== 'returned'): ?>
                            <a href="return_loan.php?id=<?php echo $loan['id']; ?>" class="btn btn-outline btn-sm" onclick="return confirm('¿Confirmar devolución del libro?')">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                Devolver
                            </a>
                            <?php endif; ?>
                            <a href="delete_loan.php?id=<?php echo $loan['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este préstamo?')">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Eliminar
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <h3>No hay préstamos registrados</h3>
            <p>Comienza agregando un nuevo préstamo de libro.</p>
            <a href="add_loan.php" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Nuevo Préstamo
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
