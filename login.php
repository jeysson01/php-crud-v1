<?php
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
session_start();
/**
 * BOOKLY - Sistema de Gestión de Préstamos
 * Página de Login/Registro de Administradores
 */

require_once 'config/database.php';
session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
$errorField = ''; // 'email' | 'password' | 'both'
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'login';

// Procesar formulario de Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $db = getDB();
    
    if ($_POST['action'] === 'login') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? true : false;
        
        if (empty($email) && empty($password)) {
            $error = 'Por favor, ingrese su email y contraseña.';
            $errorField = 'both';
        } elseif (empty($email)) {
            $error = 'El campo email es obligatorio.';
            $errorField = 'email';
        } elseif (empty($password)) {
            $error = 'El campo contraseña es obligatorio.';
            $errorField = 'password';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'El formato del email no es válido.';
            $errorField = 'email';
        } else {
            try {
                // Primero verificar si el email existe
                $stmt = $db->prepare("SELECT * FROM admins WHERE email = ?");
                $stmt->execute([$email]);
                $adminByEmail = $stmt->fetch();

                if (!$adminByEmail) {
                    $error = 'No existe una cuenta registrada con ese email.';
                    $errorField = 'email';
                } elseif ($adminByEmail['status'] !== 'active') {
                    $error = 'Esta cuenta se encuentra desactivada. Contacte al administrador.';
                    $errorField = 'both';
                } elseif (!password_verify($password, $adminByEmail['password'])) {
                    $error = 'La contraseña ingresada es incorrecta.';
                    $errorField = 'password';
                }

                $admin = ($adminByEmail && $adminByEmail['status'] === 'active' && password_verify($password, $adminByEmail['password'])) ? $adminByEmail : null;

                if ($admin) {
                    // Login exitoso
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['full_name'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_role'] = $admin['role'];
                    
                    // Actualizar último login
                    $updateStmt = $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                    $updateStmt->execute([$admin['id']]);
                    
                    // Registrar actividad
                    $logStmt = $db->prepare("INSERT INTO admin_activity_log (admin_id, action, description, ip_address) VALUES (?, 'login', 'Inicio de sesión exitoso', ?)");
                    $logStmt->execute([$admin['id'], $_SERVER['REMOTE_ADDR']]);
                    
                    // Cookie de recordar si se seleccionó
                    if ($remember) {
                        setcookie('bookly_remember', base64_encode($email), time() + (86400 * 30), '/');
                    }
                    
                    header('Location: index.php');
                    exit;
                }
            } catch (PDOException $e) {
                $error = 'Error al procesar la solicitud. Intente nuevamente.';
            }
        }
        $activeTab = 'login';
    }
    
    // Procesar formulario de Registro
    if ($_POST['action'] === 'register') {
        $fullName = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        $secretKey = trim($_POST['secret_key']);
        
        // Validaciones
        if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword) || empty($secretKey)) {
            $error = 'Por favor, complete todos los campos.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Por favor, ingrese un email válido.';
        } elseif (strlen($password) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            try {
                // Verificar clave secreta
                $configStmt = $db->prepare("SELECT config_value FROM system_config WHERE config_key = 'admin_secret_key'");
                $configStmt->execute();
                $config = $configStmt->fetch();
                
                if (!$config || $secretKey !== $config['config_value']) {
                    $error = 'Clave secreta incorrecta. Contacte al administrador del sistema.';
                } else {
                    // Verificar si el email ya existe
                    $checkStmt = $db->prepare("SELECT id FROM admins WHERE email = ?");
                    $checkStmt->execute([$email]);
                    
                    if ($checkStmt->fetch()) {
                        $error = 'Este email ya está registrado en el sistema.';
                    } else {
                        // Crear nuevo administrador
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $insertStmt = $db->prepare("INSERT INTO admins (full_name, email, password, role) VALUES (?, ?, ?, 'admin')");
                        $insertStmt->execute([$fullName, $email, $hashedPassword]);
                        
                        $success = '¡Cuenta creada exitosamente! Ahora puede iniciar sesión.';
                        $activeTab = 'login';
                    }
                }
            } catch (PDOException $e) {
                $error = 'Error al crear la cuenta. Intente nuevamente.';
            }
        }
        
        if ($error) {
            $activeTab = 'register';
        }
    }
}

// Obtener email recordado
$rememberedEmail = isset($_COOKIE['bookly_remember']) ? base64_decode($_COOKIE['bookly_remember']) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema - Bookly Inventory System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
        }
        
        .auth-container {
            width: 100%;
            max-width: 420px;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .auth-logo-icon {
            width: 48px;
            height: 48px;
            border: 2px solid var(--color-black);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .auth-logo-icon svg {
            width: 28px;
            height: 28px;
        }
        
        .auth-logo-text h1 {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 0.05em;
        }
        
        .auth-logo-text span {
            font-size: 0.8125rem;
            font-style: italic;
            color: var(--color-gray-600);
        }
        
        .auth-card {
            background-color: var(--color-white);
            border: 1px solid var(--color-gray-200);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }
        
        .auth-tabs {
            display: flex;
            border-bottom: 1px solid var(--color-gray-200);
        }
        
        .auth-tab {
            flex: 1;
            padding: 1rem;
            text-align: center;
            font-weight: 500;
            font-size: 0.9375rem;
            color: var(--color-gray-500);
            background: var(--color-gray-50);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }
        
        .auth-tab:hover {
            color: var(--color-black);
            background: var(--color-gray-100);
        }
        
        .auth-tab.active {
            background: var(--color-black);
            color: var(--color-white);
        }
        
        .auth-tab svg {
            width: 18px;
            height: 18px;
        }
        
        .auth-body {
            padding: 2rem;
        }
        
        .auth-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--color-black);
            margin-bottom: 0.5rem;
        }
        
        .auth-subtitle {
            font-size: 0.875rem;
            color: var(--color-gray-500);
            margin-bottom: 1.5rem;
        }
        
        .form-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        
        .checkbox-group label {
            font-size: 0.8125rem;
            color: var(--color-gray-600);
            cursor: pointer;
        }
        
        .forgot-link {
            font-size: 0.8125rem;
            color: var(--color-gray-500);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .forgot-link:hover {
            color: var(--color-black);
        }
        
        .btn-auth {
            width: 100%;
            padding: 0.875rem;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .btn-auth svg {
            width: 20px;
            height: 20px;
        }
        
        .auth-footer {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid var(--color-gray-200);
            margin-top: 1.5rem;
        }
        
        .auth-footer p {
            font-size: 0.75rem;
            color: var(--color-gray-500);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .auth-footer svg {
            width: 14px;
            height: 14px;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .alert {
            margin-bottom: 1.5rem;
        }

        /* Error shake animation */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%       { transform: translateX(-6px); }
            40%       { transform: translateX(6px); }
            60%       { transform: translateX(-4px); }
            80%       { transform: translateX(4px); }
        }

        .alert-error {
            animation: shake 0.45s ease;
        }

        .field-error {
            border-color: #dc2626 !important;
            background-color: #fff5f5 !important;
        }

        .error-hint {
            font-size: 0.75rem;
            color: #dc2626;
            margin-top: 0.375rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .error-hint svg {
            width: 13px;
            height: 13px;
            flex-shrink: 0;
        }
        
        .secret-key-info {
            background-color: var(--color-gray-100);
            border: 1px solid var(--color-gray-200);
            border-radius: var(--radius-md);
            padding: 0.75rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }
        
        .secret-key-info svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            margin-top: 2px;
            color: var(--color-gray-600);
        }
        
        .secret-key-info p {
            font-size: 0.75rem;
            color: var(--color-gray-600);
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <div class="auth-logo">
                <div class="auth-logo-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <div class="auth-logo-text">
                    <h1>BOOKLY</h1>
                    <span>INVENTORY SYSTEM</span>
                </div>
            </div>
            <span class="version-badge">V1.0.4-SKETCH</span>
        </div>
        
        <div class="auth-card">
            <div class="auth-tabs">
                <button class="auth-tab <?php echo $activeTab === 'login' ? 'active' : ''; ?>" onclick="switchTab('login')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    Login
                </button>
                <button class="auth-tab <?php echo $activeTab === 'register' ? 'active' : ''; ?>" onclick="switchTab('register')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    Register
                </button>
            </div>
            
            <div class="auth-body">
                <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>
                
                <!-- Tab Login -->
                <div id="tab-login" class="tab-content <?php echo $activeTab === 'login' ? 'active' : ''; ?>">
                    <h2 class="auth-title">SYSTEM ACCESS</h2>
                    <p class="auth-subtitle">Please verify your credentials to continue.</p>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="login">
                        
                        <div class="form-group">
                            <label for="login-email" class="form-label">USERNAME / EMAIL</label>
                            <input
                                type="email"
                                id="login-email"
                                name="email"
                                class="form-control <?php echo ($errorField === 'email' || $errorField === 'both') ? 'field-error' : ''; ?>"
                                placeholder="e.g. librarian_smith"
                                value="<?php echo htmlspecialchars($rememberedEmail ?: ($_POST['email'] ?? '')); ?>"
                                required
                            >
                            <?php if ($errorField === 'email'): ?>
                            <span class="error-hint">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <?php echo htmlspecialchars($error); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="login-password" class="form-label">PASSWORD</label>
                            <input
                                type="password"
                                id="login-password"
                                name="password"
                                class="form-control <?php echo ($errorField === 'password' || $errorField === 'both') ? 'field-error' : ''; ?>"
                                placeholder="••••••••"
                                required
                            >
                            <?php if ($errorField === 'password'): ?>
                            <span class="error-hint">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <?php echo htmlspecialchars($error); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-footer">
                            <div class="checkbox-group">
                                <input type="checkbox" id="remember" name="remember" <?php echo $rememberedEmail ? 'checked' : ''; ?>>
                                <label for="remember">REMEMBER ME</label>
                            </div>
                            <a href="#" class="forgot-link">Forgot Password?</a>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-auth">
                            Login as Admin
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </form>
                </div>
                
                <!-- Tab Register -->
                <div id="tab-register" class="tab-content <?php echo $activeTab === 'register' ? 'active' : ''; ?>">
                    <h2 class="auth-title">CREATE ACCOUNT</h2>
                    <p class="auth-subtitle">Register to access the inventory system.</p>
                    
                    <div class="secret-key-info">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p>Se requiere una <strong>clave secreta</strong> para registrar nuevos administradores. Solicítela al administrador del sistema.</p>
                    </div>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="register">
                        
                        <div class="form-group">
                            <label for="full_name" class="form-label">FULL NAME</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" placeholder="e.g. John Smith" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="register-email" class="form-label">EMAIL ADDRESS</label>
                            <input type="email" id="register-email" name="email" class="form-control" placeholder="e.g. john@library.org" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="register-password" class="form-label">PASSWORD</label>
                            <input type="password" id="register-password" name="password" class="form-control" placeholder="••••••••" minlength="6" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">CONFIRM PASSWORD</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="••••••••" minlength="6" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="secret_key" class="form-label">SECRET KEY</label>
                            <input type="password" id="secret_key" name="secret_key" class="form-control" placeholder="Enter admin secret key" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-auth">
                            Create Account
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                        
                        <div class="auth-footer">
                            <p>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                By registering, you agree to our terms of service and privacy policy.
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(tab) {
            // Actualizar tabs
            document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            // Activar tab seleccionada
            if (tab === 'login') {
                document.querySelectorAll('.auth-tab')[0].classList.add('active');
            } else {
                document.querySelectorAll('.auth-tab')[1].classList.add('active');
            }
            document.getElementById('tab-' + tab).classList.add('active');
            
            // Actualizar URL sin recargar
            history.replaceState(null, '', '?tab=' + tab);
        }
    </script>
</body>
</html>
