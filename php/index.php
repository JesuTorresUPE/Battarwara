<?php
session_start(); // Solo una vez al inicio

/**********************************************
 * CONEXIÓN A BASE DE DATOS
 **********************************************/
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "battarwara";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Crear tabla usuarios si no existe
$sql = "CREATE TABLE IF NOT EXISTS usuarios (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(50) NOT NULL UNIQUE,
    usuario VARCHAR(30) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($sql)) {
    die("Error creando tabla: " . $conn->error);
}

/**********************************************
 * MANEJO DE SESIONES Y FORMULARIOS
 **********************************************/
// Procesar registro
$registro_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar'])) {
    // Validación y sanitización de datos
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $usuario = filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $registro_error = "Las contraseñas no coinciden";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Prevenir inyección SQL
        $stmt = $conn->prepare("INSERT INTO usuarios (email, usuario, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $usuario, $password_hash);
        
        if ($stmt->execute()) {
            // Redirección después de registro exitoso
            header("Location: index.php?registro=exito");
            exit;
        } else {
            $registro_error = "Error al registrar: " . $conn->error;
        }
    }
}

// Procesar login
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $usuario = filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['usuario'] = $user['usuario'];
            header("Location: index.php");
            exit;
        } else {
            $login_error = "Contraseña incorrecta";
        }
    } else {
        $login_error = "Usuario no encontrado";
    }
}

// Cerrar sesión
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Battarwara®</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <header class="header">
        <nav class="menu_nav">
            <div class="logo">
                <img src="BattarwaraLogo.PNG" alt="Logo Battarwara" class="logo-img">
            </div>
            <?php if(!isset($_SESSION['usuario'])): ?>
                <i class="fas fa-user-circle user-icon" data-bs-toggle="modal" data-bs-target="#loginModal"></i>
            <?php else: ?>
                <div class="d-flex align-items-center gap-3 user-status">
                    <span class="text-dark">Bienvenido, <?= htmlspecialchars($_SESSION['usuario']) ?></span>
                    <a href="?logout=1" class="btn btn-sm btn-danger">Salir</a>
                </div>
            <?php endif; ?>
        </nav>
</header>

    <!-- Modales -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Iniciar Sesión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <?php if($login_error): ?>
                            <div class="alert alert-danger"><?= $login_error ?></div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <input type="text" name="usuario" class="form-control" placeholder="Usuario" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
                        </div>
                        <div class="text-center">
                            <span class="password-link" data-bs-toggle="modal" data-bs-target="#registroModal">
                                ¿No tienes cuenta? Regístrate aquí
                            </span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="login" class="btn btn-primary w-100">Ingresar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="registroModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registro de Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <?php if($registro_error): ?>
                            <div class="alert alert-danger"><?= $registro_error ?></div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <input type="email" name="email" class="form-control" placeholder="Correo electrónico" required>
                        </div>
                        <div class="mb-3">
                            <input type="text" name="usuario" class="form-control" placeholder="Usuario" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" name="confirm_password" class="form-control" placeholder="Confirmar Contraseña" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="registrar" class="btn btn-success w-100">Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <ul class="nav_links">
        <?php if(isset($_SESSION['usuario'])): ?>
            <li><a href="#">Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario']); ?></a></li>
            <li><a href="#">|</a></li>
            <li><a href="?logout=1">Cerrar Sesión</a></li>
        <?php else: ?>
            <li class="dropdown">
                <a href="#" class="dropdown-toggle">Iniciar Sesión</a>
                <div class="dropdown-menu login-form">
                    <form method="POST">
                        <?php if($error_login): ?>
                            <div class="error"><?php echo $error_login; ?></div>
                        <?php endif; ?>
                        <input type="text" name="username" placeholder="Usuario" required>
                        <input type="password" name="password" placeholder="Contraseña" required>
                        <button type="submit" name="login">Ingresar</button>
                    </form>
                </div>
                <li><a href="#">|</a></li>
            </li>
        <?php endif; ?>
        <li><a href="#">Inicio</a></li>
        <li><a href="#">|</a></li>
        <li class="dropdown">
            <a href="#" class="dropdown-toggle">Productos</a>
            <div class="dropdown-menu">
                <a href="#">Cocina</a>
                <a href="#">Recámara</a>
                <a href="#">Limpieza</a>
                <a href="#">Hogar</a>
                <a href="#">Contigo</a>
                <a href="#">Baño</a>
            </div>
            <li><a href="#">|</a></li>
        </li>
        <li><a href="#">Nosotros</a></li>
        <li><a href="#">|</a></li>
        <li><a href="#">Contáctanos</a></li>
        <li><a href="#">|</a></li>
        <li class="dropdown">
            <a href="#" class="dropdown-toggle">Idiomas</a>
            <div class="dropdown-menu">
                <a href="#">English</a>
                <a href="#">Español</a>
            </div>
            <li><a href="#">|</a></li>
        </li>
        <li><a href="#">Equipo 5</a></li>
        <li><a href="#">|</a></li>
    </ul>

    <main class="container">
        <section class="carousel-section">
            <h2>Favoritos del Mes</h2>
            <br>
            <div class="carousel-container">
                <div class="carousel-track">
                    <div class="carousel-slide">
                        <img src="Topper.jpg" alt="Topper" class="category-img">
                        <h3 class="product-title">Topper Multiusos</h3>
                        <p class="product-price">$299.00 MXN</p>
                        <button class="buy-btn">Comprar Ahora</button>
                        <button class="buy-btn">Agregar al Carrito</button>
                    </div>
                    <div class="carousel-slide">
                        <img src="Dispensador.jpg" alt="Dispensador" class="category-img">
                        <h3 class="product-title">Topper Multiusos</h3>
                        <p class="product-price">$299.00 MXN</p>
                        <button class="buy-btn">Comprar Ahora</button>
                        <button class="buy-btn">Agregar al Carrito</button>
                    </div>
                    <div class="carousel-slide">
                        <img src="PortaFruta.jpg" alt="PortaFruta" class="category-img">
                        <h3 class="product-title">Topper Multiusos</h3>
                        <p class="product-price">$299.00 MXN</p>
                        <button class="buy-btn">Comprar Ahora</button>
                        <button class="buy-btn">Agregar al Carrito</button>
                    </div>
                    <div class="carousel-slide">
                        <img src="Cuchillos.jpg" alt="Cuchillos" class="category-img">
                        <h3 class="product-title">Topper Multiusos</h3>
                        <p class="product-price">$299.00 MXN</p>
                        <button class="buy-btn">Comprar Ahora</button>
                        <button class="buy-btn">Agregar al Carrito</button>
                    </div>
                    <div class="carousel-slide">
                        <img src="PortaGalletas.jpg" alt="PortaGalletas" class="category-img">
                        <h3 class="product-title">Topper Multiusos</h3>
                        <p class="product-price">$299.00 MXN</p>
                        <button class="buy-btn">Comprar Ahora</button>
                        <button class="buy-btn">Agregar al Carrito</button>
                    </div>
                </div>
                <button class="carousel-btn prev-btn">&lt;</button>
                <button class="carousel-btn next-btn">&gt;</button>
            </div>
        </section>        
        
        <section class="about-section">
            <h2>Nosotros</h2>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum ut diam quis quam vestibulum varius at vel nunc. Eliam eu felis nec quam porta lobortis. Quisque condimentum nibh ante, aliquam accumsan dolor laculis in. Ut porta, lacus eu auctor ornare, uma nisl tempor tellus, tempor pretium quam nulla la diam. Phasellus at lacinia lorem, pellentesque tristique risus. Vivamus sit amet elit cursus, ultrices dolor sed, dolichidum eran. Donec ornare nisl enim, vitae dictum lacus finibus et.</p>
        </section>

        <section class="contact-section">
            <h2>Contáctanos</h2>
            <form class="contact-form">
                <div class="form-row">
                    <input type="text" placeholder="Nombre">
                    <input type="email" placeholder="E-mail">
                </div>
                <textarea placeholder="Mensaje"></textarea>
                <button type="submit">Enviar</button>
            </form>
        </section>
    </main>

    <footer class="footer">
        <div class="social-links">
            <a href="#"><i class="fab fa-facebook"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
        </div>
    </footer>


    <!--Funcionamiento del carrucel-->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const track = document.querySelector('.carousel-track');
            const slides = document.querySelectorAll('.carousel-slide');
            const nextBtn = document.querySelector('.next-btn');
            const prevBtn = document.querySelector('.prev-btn');
            
            let currentIndex = 0;
            let isAnimating = false;
            
            // Clonar slides para efecto infinito
            const cloneSlides = () => {
                const clones = [];
                slides.forEach(slide => {
                    clones.push(slide.cloneNode(true));
                });
                return clones;
            };
            
            const originalSlides = Array.from(slides);
            const clonedSlides = cloneSlides();
            track.append(...clonedSlides);
            
            const totalSlides = originalSlides.length + clonedSlides.length;
            const slideWidth = slides[0].offsetWidth + 20;
            
            const moveToSlide = (index) => {
                if (isAnimating) return;
                isAnimating = true;
                
                track.style.transition = 'transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                track.style.transform = `translateX(-${index * slideWidth}px)`;
                
                setTimeout(() => {
                    if (index >= originalSlides.length) {
                        currentIndex = 0;
                        track.style.transition = 'none';
                        track.style.transform = `translateX(0)`;
                    }
                    isAnimating = false;
                }, 600);
            };
            
            nextBtn.addEventListener('click', () => {
                currentIndex = (currentIndex + 1) % totalSlides;
                moveToSlide(currentIndex);
            });
            
            prevBtn.addEventListener('click', () => {
                currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
                moveToSlide(currentIndex);
            });
            
            // Auto-avance infinito
            let autoPlay = setInterval(() => {
                currentIndex = (currentIndex + 1) % totalSlides;
                moveToSlide(currentIndex);
            }, 5000);
            
            // Control de hover
            track.parentElement.addEventListener('mouseenter', () => clearInterval(autoPlay));
            track.parentElement.addEventListener('mouseleave', () => {
                autoPlay = setInterval(() => {
                    currentIndex = (currentIndex + 1) % totalSlides;
                    moveToSlide(currentIndex);
                }, 5000);
            });
        });
    </script>

</body>
</html>