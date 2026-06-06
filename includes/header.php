<!DOCTYPE html>
<!-- Define el tipo de documento HTML5 -->

<html lang="es">
<!-- Inicio de la página en idioma español -->

<head>
    <!-- Contenedor de configuraciones y recursos de la página -->

    <meta charset="UTF-8">
    <!-- Permite usar caracteres especiales y tildes -->

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Hace que la página sea adaptable a celulares y tablets -->

    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Bookly Inventory System</title>
    <!-- Título dinámico de la pestaña usando PHP -->

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <!-- Optimiza la conexión con Google Fonts -->

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Mejora la carga de fuentes externas -->

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Importa la fuente Inter desde Google Fonts -->

    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- Vincula el archivo principal de estilos CSS -->

</head>

<body>
    <!-- Inicio del contenido visible de la página -->

    <header class="header">
        <!-- Encabezado principal del sistema -->

        <div class="container">
            <!-- Contenedor general para mantener alineado el contenido -->

            <div class="header-content">
                <!-- Área que agrupa logo, versión y menú de navegación -->

                <a href="index.php" class="logo">
                    <!-- Logo principal con enlace al inicio -->

                    <div class="logo-icon">
                        <!-- Contenedor del icono SVG del logo -->

                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <!-- Icono vectorial del logo -->

                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            <!-- Diseño gráfico del icono tipo libro -->
                        </svg>

                    </div>

                    <div class="logo-text">
                        <!-- Texto descriptivo del logo -->

                        <h1>BOOKLY</h1>
                        <!-- Nombre principal del sistema -->

                        <span>INVENTORY SYSTEM</span>
                        <!-- Subtítulo del sistema -->
                    </div>

                </a>

                <span class="version-badge">V1.0.0-STABLE</span>
                <!-- Etiqueta que muestra la versión actual del sistema -->

                <nav class="nav">
                    <!-- Menú principal de navegación -->

                    <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                        <!-- Botón de navegación hacia la sección de préstamos -->
                        <!-- La clase active resalta la página actual -->

                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <!-- Icono SVG de préstamos -->

                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>

                        Préstamos
                    </a>

                    <a href="books.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'books.php' ? 'active' : ''; ?>">
                        <!-- Botón de navegación hacia la sección de libros -->

                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <!-- Icono SVG de libros -->

                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>

                        Libros
                    </a>

                    <a href="readers.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'readers.php' ? 'active' : ''; ?>">
                        <!-- Botón de navegación hacia la sección de lectores -->

                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <!-- Icono SVG de usuarios/lectores -->

                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>

                        Lectores
                    </a>
        <a href="logout.php" class="btn btn-secondary" onclick="return confirm('¿Seguro que desea cerrar sesión?')" style="display:flex; align-items:center; gap:0.375rem;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            Cerrar Sesión
        </a>
                </nav>
                <!-- Fin del menú de navegación -->

            </div>
        </div>

    </header>
    <!-- Fin del encabezado principal -->
    <!-- Estructurar y centrar visualmente todo el panel de control (dashboard) -->
    <main class="main">
        <div class="container">
