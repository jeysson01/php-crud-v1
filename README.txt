=============================================
BOOKLY - Sistema de Gestion de Prestamos
Version 1.0.0
=============================================

REQUISITOS:
- PHP 7.4 o superior
- MySQL 5.7 o superior (o MariaDB 10.3+)
- Servidor web (Apache, Nginx, XAMPP, WAMP, MAMP, etc.)

INSTALACION:
1. Copia la carpeta "php-crud" a tu servidor web (htdocs, www, public_html, etc.)
2. Importa el archivo "database.sql" en tu base de datos MySQL:
   - Via phpMyAdmin: Importar > Seleccionar archivo database.sql
   - Via linea de comandos: mysql -u root -p < database.sql
3. Edita el archivo "config/database.php" con tus credenciales:
   - DB_HOST: direccion del servidor MySQL (normalmente 'localhost')
   - DB_NAME: nombre de la base de datos (por defecto 'bookly_db')
   - DB_USER: usuario de MySQL (por defecto 'root')
   - DB_PASS: contrasena de MySQL
4. Accede a http://localhost/php-crud/ en tu navegador

ESTRUCTURA DE ARCHIVOS:
/php-crud
  /assets
    /css
      styles.css          - Estilos CSS del sistema
  /config
    database.php          - Configuracion de base de datos
  /includes
    header.php            - Cabecera comun
    footer.php            - Pie de pagina comun
  index.php               - Lista de prestamos (pagina principal)
  add_loan.php            - Agregar nuevo prestamo
  edit_loan.php           - Editar prestamo existente
  delete_loan.php         - Eliminar prestamo
  return_loan.php         - Marcar libro como devuelto
  books.php               - CRUD de libros
  readers.php             - CRUD de lectores
  database.sql            - Script SQL para crear la base de datos

FUNCIONALIDADES:
- Gestion completa de prestamos (crear, ver, editar, eliminar)
- Gestion de libros (agregar, editar, eliminar)
- Gestion de lectores/usuarios (agregar, editar, eliminar)
- Control de copias disponibles por libro
- Estados de prestamo: activo, vencido, devuelto
- Filtros y busqueda en todas las listas
- Estadisticas en tiempo real
- Diseno responsivo (funciona en moviles y tablets)
- Estilo visual minimalista en blanco y negro

SOPORTE:
Sistema desarrollado como ejercicio educativo.
