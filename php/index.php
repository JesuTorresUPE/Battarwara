<?php
session_start();

/**********************************************
 * MANEJO DE IDIOMAS CORREGIDO
 **********************************************/
$available_langs = ['es', 'en'];
$default_lang = 'es';
$cookie_time = time() + (86400 * 30); // 30 días

// Verificar si se solicita cambio de idioma
if (isset($_GET['lang']) && in_array($_GET['lang'], $available_langs)) {
    // Solo actualizar si es diferente al actual
    if ($_GET['lang'] != ($_SESSION['lang'] ?? $default_lang)) {
        $_SESSION['lang'] = $_GET['lang'];
        setcookie('lang', $_GET['lang'], $cookie_time, '/');
    }
    // Redirección limpia para eliminar parámetros GET
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Cargar idioma desde cookie/sesión
$current_lang = $_COOKIE['lang'] ?? $_SESSION['lang'] ?? $default_lang;
$_SESSION['lang'] = $current_lang; // Asegurar consistencia

/**********************************************
 * TRADUCCIONES COMPLETAS
 **********************************************/
$translations = [
    'es' => [
        // Menú
        'inicio' => 'Inicio',
        'productos' => 'Productos',
        'nosotros' => 'Nosotros',
        'contacto' => 'Contáctanos',
        'idiomas' => 'Idiomas',
        'equipo' => 'Equipo 5',
        
        // Contenido
        'favoritos' => 'Favoritos del Mes',
        'comprar_ahora' => 'Comprar Ahora',
        'agregar_carrito' => 'Agregar al Carrito',
        'nosotros_texto' => 'Lorem ipsum dolor sit amet...',
        
        // Formularios
        'nombre' => 'Nombre',
        'email' => 'E-mail',
        'mensaje' => 'Mensaje',
        'enviar' => 'Enviar',
        'usuario' => 'Usuario',
        'password' => 'Contraseña',
        'confirm_password' => 'Confirmar Contraseña',
        
        // Autenticación
        'bienvenido' => 'Bienvenido',
        'salir' => 'Salir',
        'registrate' => '¿No tienes cuenta? Regístrate aquí',
        'ingresar' => 'Ingresar',
        'registrar' => 'Registrar',
        
        // Productos
        'topper_title' => 'Topper Multiusos',
        'dispensador_title' => 'Dispensador',
        'portafruta_title' => 'Porta Fruta',
        'cuchillos_title' => 'Cuchillos Multiusos',
        'portagalletas_title' => 'Porta galletas'
    ],
    'en' => [
        // Menú
        'inicio' => 'Home',
        'productos' => 'Products',
        'nosotros' => 'About Us',
        'contacto' => 'Contact',
        'idiomas' => 'Languages',
        'equipo' => 'Team 5',
        
        // Contenido
        'favoritos' => 'Monthly Favorites',
        'comprar_ahora' => 'Buy Now',
        'agregar_carrito' => 'Add to Cart',
        'nosotros_texto' => 'Lorem ipsum dolor sit amet...',
        
        // Formularios
        'nombre' => 'Name',
        'email' => 'Email',
        'mensaje' => 'Message',
        'enviar' => 'Send',
        'usuario' => 'Username',
        'password' => 'Password',
        'confirm_password' => 'Confirm Password',
        
        // Autenticación
        'bienvenido' => 'Welcome',
        'salir' => 'Logout',
        'registrate' => 'No account? Register here',
        'ingresar' => 'Login',
        'registrar' => 'Register',
        
        // Productos
        'topper_title' => 'Multiuse Topper',
        'dispensador_title' => 'Dispenser',
        'portafruta_title' => 'Fruit Holder',
        'cuchillos_title' => 'Multiuse Knives',
        'portagalletas_title' => 'Cookie Holder'
    ]
];

/**********************************************
 * CONEXIÓN A BASE DE DATOS
 **********************************************/
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "battarwara";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "CREATE TABLE IF NOT EXISTS usuarios (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(50) NOT NULL UNIQUE,
    usuario VARCHAR(30) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($sql)) {
    die("Error creating table: " . $conn->error);
}

/**********************************************
 * MANEJO DE SESIONES Y FORMULARIOS
 **********************************************/
$registro_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $usuario = filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $registro_error = "Las contraseñas no coinciden";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO usuarios (email, usuario, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $usuario, $password_hash);
        
        if ($stmt->execute()) {
            header("Location: index.php?registro=exito");
            exit;
        } else {
            $registro_error = "Error al registrar: " . $conn->error;
        }
    }
}

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

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Battarwara</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <header class="header">
        <nav class="menu_nav" aria-label="Navegación principal">
            <div class="logo">
                <img src="BattarwaraLogo.PNG" alt="Logo Battarwara" class="logo-img">
            </div>
        </nav>
    </header>

    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $translations[$current_lang]['ingresar'] ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <?php if($login_error): ?>
                            <div class="alert alert-danger d-flex align-items-center gap-2">
                                <i class="fas fa-exclamation-circle"></i>
                                <?= $login_error ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-4">
                            <label class="form-label text-muted small mb-1"><?= $translations[$current_lang]['usuario'] ?></label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" name="usuario" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label text-muted small mb-1"><?= $translations[$current_lang]['password'] ?></label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                        </div>

                        <div class="text-center mb-3">
                            <span class="password-link" data-bs-toggle="modal" data-bs-target="#registroModal">
                                <?= $translations[$current_lang]['registrate'] ?>
                            </span>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="submit" name="login" class="btn btn-primary w-100 py-2">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            <?= $translations[$current_lang]['ingresar'] ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="registroModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $translations[$current_lang]['registrar'] ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <?php if($registro_error): ?>
                            <div class="alert alert-danger"><?= $registro_error ?></div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <input type="email" name="email" class="form-control" placeholder="<?= $translations[$current_lang]['email'] ?>" required>
                            
                        </div>
                        <div class="mb-3">
                            <input type="text" name="usuario" class="form-control" placeholder="<?= $translations[$current_lang]['usuario'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" name="password" class="form-control" placeholder="<?= $translations[$current_lang]['password'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" name="confirm_password" class="form-control" placeholder="<?= $translations[$current_lang]['confirm_password'] ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="registrar" class="btn btn-success w-100"><?= $translations[$current_lang]['registrar'] ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <ul class="nav_links">
        <li><a href="#"><?= $translations[$current_lang]['inicio'] ?></a></li>
        <li><a href="#">|</a></li>
        <li class="dropdown">
            <a href="#" class="dropdown-toggle"><?= $translations[$current_lang]['productos'] ?></a>
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
        <li><a href="#nosotros"><?= $translations[$current_lang]['nosotros'] ?></a></li>
        <li><a href="#">|</a></li>
        <li><a href="#contacto"><?= $translations[$current_lang]['contacto'] ?></a></li>
        <li><a href="#">|</a></li>
        <li class="dropdown">
            <a href="#" class="dropdown-toggle"><?= $translations[$current_lang]['idiomas'] ?></a>
            <div class="dropdown-menu">
                <a href="?lang=en">English</a>
                <a href="?lang=es">Español</a>
            </div>
            <li><a href="#">|</a></li>
        </li>
        <li><a href="#"><?= $translations[$current_lang]['equipo'] ?></a></li>
        <li><a href="#">|</a></li>
        <?php if(!isset($_SESSION['usuario'])): ?>
            <div class="user-icon">
                <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal">
                    <i class="fas fa-user"></i>
                </a>
            </div>
        <?php else: ?>
            <div class="user-status">
                <span><?= $translations[$current_lang]['bienvenido'] ?>, <?= htmlspecialchars($_SESSION['usuario']) ?></span>
                <a href="?logout=1" class="btn btn-sm btn-danger"><?= $translations[$current_lang]['salir'] ?></a>
            </div>
        <?php endif; ?>
    </ul>

    <main class="container">
        <section class="carousel-section">
            <h2><?= $translations[$current_lang]['favoritos'] ?></h2>
            <br>
            <div class="carousel-container">
                <div class="carousel-track">
                    <div class="carousel-slide">
                        <img src="Topper.jpg" alt="Topper" class="category-img">
                        <h3 class="product-title"><?= $translations[$current_lang]['topper_title'] ?></h3>
                        <p class="product-price">$299.00 MXN</p>
                        <button class="buy-btn"><?= $translations[$current_lang]['comprar_ahora'] ?></button>
                        <button class="buy-btn"><?= $translations[$current_lang]['agregar_carrito'] ?></button>
                    </div>
                    <div class="carousel-slide">
                        <img src="Dispensador.jpg" alt="Dispensador" class="category-img">
                        <h3 class="product-title"><?= $translations[$current_lang]['dispensador_title'] ?></h3>
                        <p class="product-price">$199.00 MXN</p>
                        <button class="buy-btn"><?= $translations[$current_lang]['comprar_ahora'] ?></button>
                        <button class="buy-btn"><?= $translations[$current_lang]['agregar_carrito'] ?></button>
                    </div>
                    <div class="carousel-slide">
                        <img src="PortaFruta.jpg" alt="PortaFruta" class="category-img">
                        <h3 class="product-title"><?= $translations[$current_lang]['portafruta_title'] ?></h3>
                        <p class="product-price">$219.00 MXN</p>
                        <button class="buy-btn"><?= $translations[$current_lang]['comprar_ahora'] ?></button>
                        <button class="buy-btn"><?= $translations[$current_lang]['agregar_carrito'] ?></button>
                    </div>
                    <div class="carousel-slide">
                        <img src="Cuchillos.jpg" alt="Cuchillos" class="category-img">
                        <h3 class="product-title"><?= $translations[$current_lang]['cuchillos_title'] ?></h3>
                        <p class="product-price">$223.00 MXN</p>
                        <button class="buy-btn"><?= $translations[$current_lang]['comprar_ahora'] ?></button>
                        <button class="buy-btn"><?= $translations[$current_lang]['agregar_carrito'] ?></button>
                    </div>
                    <div class="carousel-slide">
                        <img src="PortaGalletas.jpg" alt="PortaGalletas" class="category-img">
                        <h3 class="product-title"><?= $translations[$current_lang]['portagalletas_title'] ?></h3>
                        <p class="product-price">$48.00 MXN</p>
                        <button class="buy-btn"><?= $translations[$current_lang]['comprar_ahora'] ?></button>
                        <button class="buy-btn"><?= $translations[$current_lang]['agregar_carrito'] ?></button>
                    </div>
                </div>
                <button class="carousel-btn prev-btn">&lt;</button>
                <button class="carousel-btn next-btn">&gt;</button>
            </div>
        </section>        
        
        <section class="about-section" id="nosotros">
        <h2><?= $translations[$current_lang]['nosotros'] ?></h2>
        <p><?= $translations[$current_lang]['nosotros_texto'] ?></p>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum ut diam quis quam vestibulum varius at vel nunc. Eliam eu felis nec quam porta lobortis. Quisque condimentum nibh ante, aliquam accumsan dolor laculis in. Ut porta, lacus eu auctor ornare, uma nisl tempor tellus, tempor pretium quam nulla la diam. Phasellus at lacinia lorem, pellentesque tristique risus. Vivamus sit amet elit cursus, ultrices dolor sed, dolichidum eran. Donec ornare nisl enim, vitae dictum lacus finibus et.</p>
        </section>

        <section class="contact-section" id="contacto">
            <h2><?= $translations[$current_lang]['contacto'] ?></h2>
            <form class="contact-form">
                <div class="form-row">
                    <input type="text" placeholder="<?= $translations[$current_lang]['nombre'] ?>">
                    <input type="email" placeholder="<?= $translations[$current_lang]['email'] ?>">
                </div>
                <textarea placeholder="<?= $translations[$current_lang]['mensaje'] ?>"></textarea>
                <button type="submit"><?= $translations[$current_lang]['enviar'] ?></button>
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