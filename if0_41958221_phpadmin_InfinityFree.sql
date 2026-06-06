-- =============================================
-- BOOKLY - Sistema de Gestión de Préstamos
-- Script de creación de base de datos
-- =============================================

CREATE DATABASE IF NOT EXISTS if0_41958221_bookly_db;
USE if0_41958221_bookly_db;

-- =============================================
-- TABLA DE ADMINISTRADORES
-- =============================================
-- CLAVE SECRETA PARA REGISTRO: BOOKLY_ADMIN_2024
-- (Cambia esta clave en producción por una más segura)
-- =============================================

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin') DEFAULT 'admin',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de configuración del sistema (para la clave secreta)
CREATE TABLE IF NOT EXISTS system_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertar clave secreta para registro de administradores
-- IMPORTANTE: Cambia 'BOOKLY_ADMIN_2024' por tu propia clave secreta en producción
INSERT INTO system_config (config_key, config_value, description) VALUES
('admin_secret_key', 'BOOKLY_ADMIN_2024', 'Clave secreta requerida para registrar nuevos administradores'),
('system_name', 'Bookly Inventory System', 'Nombre del sistema'),
('system_version', 'V1.0.4-SKETCH', 'Versión del sistema');

-- Crear administrador por defecto (contraseña: admin123)
-- La contraseña está hasheada con password_hash()
INSERT INTO admins (full_name, email, password, role) VALUES
('Administrador Principal', 'admin@bookly.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');

-- Tabla de libros
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    isbn VARCHAR(20) UNIQUE,
    category VARCHAR(100),
    available_copies INT DEFAULT 1,
    total_copies INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de usuarios/lectores
CREATE TABLE IF NOT EXISTS readers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    membership_date DATE DEFAULT (CURRENT_DATE),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de préstamos
CREATE TABLE IF NOT EXISTS loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    reader_id INT NOT NULL,
    loan_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE NULL,
    status ENUM('active', 'returned', 'overdue') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (reader_id) REFERENCES readers(id) ON DELETE CASCADE
);

-- Tabla de registro de actividad de administradores
CREATE TABLE IF NOT EXISTS admin_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);

-- Datos de ejemplo: Libros
INSERT INTO books (title, author, isbn, category, available_copies, total_copies) VALUES
('Cien años de soledad', 'Gabriel García Márquez', '978-0307474728', 'Ficción', 3, 5),
('Don Quijote de la Mancha', 'Miguel de Cervantes', '978-8420412146', 'Clásicos', 2, 3),
('El principito', 'Antoine de Saint-Exupéry', '978-0156012195', 'Infantil', 4, 4),
('1984', 'George Orwell', '978-0451524935', 'Ciencia Ficción', 1, 2),
('Orgullo y prejuicio', 'Jane Austen', '978-0141439518', 'Romance', 2, 2);

-- Datos de ejemplo: Lectores
INSERT INTO readers (full_name, email, phone, address) VALUES
('Juan Pérez García', 'juan.perez@email.com', '+34 612 345 678', 'Calle Mayor 123, Madrid'),
('María López Sánchez', 'maria.lopez@email.com', '+34 623 456 789', 'Av. Principal 456, Barcelona'),
('Carlos Rodríguez Martín', 'carlos.rodriguez@email.com', '+34 634 567 890', 'Plaza Central 789, Valencia'),
('Ana Fernández Ruiz', 'ana.fernandez@email.com', '+34 645 678 901', 'Calle Nueva 321, Sevilla');

-- Datos de ejemplo: Préstamos
INSERT INTO loans (book_id, reader_id, loan_date, due_date, status, notes) VALUES
(1, 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'active', 'Primer préstamo del usuario'),
(2, 2, DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'active', NULL),
(3, 3, DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'overdue', 'Contactar al usuario'),
(4, 1, DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_SUB(CURDATE(), INTERVAL 16 DAY), 'returned', 'Devuelto en buen estado');

-- Actualizar el préstamo devuelto con fecha de retorno
UPDATE loans SET return_date = DATE_SUB(CURDATE(), INTERVAL 18 DAY) WHERE id = 4;

-- =============================================
-- NOTAS IMPORTANTES:
-- =============================================
-- 1. La CLAVE SECRETA para registrar nuevos admins es: BOOKLY_ADMIN_2024
--    Puedes cambiarla en la tabla system_config
--
-- 2. Credenciales del admin por defecto:
--    Email: admin@bookly.com
--    Contraseña: admin123
--
-- 3. Para cambiar la clave secreta ejecuta:
--    UPDATE system_config SET config_value = 'TU_NUEVA_CLAVE' 
--    WHERE config_key = 'admin_secret_key';
-- =============================================
