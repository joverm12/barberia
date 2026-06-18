<?php
session_start();
require_once 'conexion.php';

// Evaluamos si el usuario ya está logeado y qué rol tiene
$sesion_activa = isset($_SESSION['user_id']);
$rol_usuario   = $sesion_activa ? $_SESSION['user_rol'] : '';
$nombre_usuario = $sesion_activa ? $_SESSION['user_name'] : '';

// Simulación de las reseñas basadas en tu Figma para que la vista renderice perfecto
$comentarios = [
    ['nombre' => 'JOVER MOREIRA', 'texto' => 'Increible servicio. Muy buena atención. Recomendadisimo!!!', 'tiempo' => 'hace 2 horas'],
    ['nombre' => 'DARWIN MERO', 'texto' => 'Exelentes trabajos. Volvere pronto!', 'tiempo' => 'hace 15 min']
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barber House - Tu Estilo, Nuestra Pasión</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600&family=Sawarabi+Mincho&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #FCF6ED; font-family: 'Instrument Sans', sans-serif; color: #FFEED5; overflow-x: hidden; }

        /* --- NAVBAR SUPERIOR --- */
        .navbar {
            background-color: #52131E;
            width: 100%;
            height: 109px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 50px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 10;
        }
        .nav-logo img { width: 250px; height: 125px; object-fit: contain; }
        .nav-links { display: flex; gap: 40px; list-style: none; width: 925px; height: 100px; align-items: center; justify-content: center; }
        .nav-links a { color: #FFEED5; text-decoration: none; font-size: 18px; font-weight: 400; transition: color 0.3s; }
        .nav-links a:hover { color: #EDC484; }

        /* --- ZONA DE USUARIO Y DROPDOWN CORREGIDO --- */
        .nav-user-zone { 
            display: flex; 
            align-items: center; 
            gap: 20px; 
        }
        .user-dropdown-container {
            position: relative;
            display: inline-block;
        }
        .user-dropdown-btn {
            background-color: #231918; border: 1px solid #EDC484; padding: 10px 18px; border-radius: 12px;
            color: #EDC484; font-size: 14px; display: flex; align-items: center; gap: 10px; cursor: pointer; text-decoration: none;
            white-space: nowrap;
        }
        .dropdown-menu {
            position: absolute; top: 125%; right: 0; background-color: #231918; border: 1px solid #EDC484;
            border-radius: 12px; width: 200px; box-shadow: 0px 8px 24px rgba(0,0,0,0.5); display: none; flex-direction: column; overflow: hidden; z-index: 999;
        }
        .dropdown-menu.show { display: flex; }
        .dropdown-menu a { color: #FFEED5; padding: 14px 16px; text-decoration: none; font-size: 14px; transition: background 0.3s; text-align: left; }
        .dropdown-menu a:hover { background-color: #52131E; color: #EDC484; }

        /* --- HERO SECCIÓN (IMAGEN DE FONDO + DEGRADADO) --- */
        .hero-section {
            width: 100%;
            max-width: 1440px;
            margin: 0 auto;
            background-image: linear-gradient(180deg, rgba(66, 5, 22, 0.7) 0%, rgba(41, 3, 14, 0.9) 100%), url('imagenes/hero_bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }

        /* --- HERO TEXT --- */
        .hero-content { 
            text-align: center; 
            padding: 130px 20px; 
        }
        .hero-content h2 { font-family: 'Sawarabi Mincho', serif; font-size: 56px; color: #FFFFFF; font-weight: 400; letter-spacing: 2px; margin-bottom: 10px; }
        .hero-content p { font-size: 24px; color: #EDC484; font-style: italic; }

        /* --- SECCIÓN SOBRE NOSOTROS --- */
        .about-section { background-color: #29030E; padding: 60px 50px; display: flex; justify-content: center; gap: 50px; align-items: center; border-top: 4px solid #EDC484; }
        .about-img { width: 523px; height: 515px; border: 2px solid #EDC484; padding: 10px; object-fit: cover; }
        .about-text-box { width: 635px; height: 503px; display: flex; flex-direction: column; justify-content: center; text-align: center; }
        .about-text-box h3 { font-family: 'Sawarabi Mincho', serif; color: #EDC484; font-size: 28px; margin-bottom: 15px; font-style: italic; }
        .about-text-box p { font-size: 16px; line-height: 1.6; margin-bottom: 25px; opacity: 0.9; }

        /* --- SECCIÓN SERVICIOS DESTACADOS --- */
        .services-section { max-width: 1440px; margin: 0 auto; padding: 80px 50px; text-align: center; color: #29030E; }
        .services-section h2 { font-family: 'Sawarabi Mincho', serif; font-size: 36px; margin-bottom: 10px; }
        .services-container { display: flex; justify-content: center; gap: 40px; margin-top: 50px; }
        .service-card { width: 431px; height: 356px; background-color: #52131E; border-radius: 20px; display: flex; overflow: hidden; box-shadow: 0px 10px 25px rgba(0,0,0,0.2); text-align: left; color: #FFEED5; }
        .service-img { width: 50%; height: 100%; object-fit: cover; }
        .service-info { width: 50%; padding: 25px; position: relative; display: flex; flex-direction: column; justify-content: space-between; }
        .service-info h4 { font-size: 20px; color: #EDC484; font-family: 'Sawarabi Mincho', serif; }
        .service-badge { position: absolute; top: 25px; right: 20px; background-color: #420516; padding: 4px 10px; border-radius: 10px; font-size: 12px; color: #EDC484; }

        /* --- SECCIÓN RESEÑAS --- */
        .reviews-section { background-color: #52131E; padding: 60px 50px; color: #FFEED5; }
        .reviews-container { max-width: 1119px; margin: 0 auto; }
        .reviews-section h3 { font-family: 'Sawarabi Mincho', serif; font-size: 28px; color: #EDC484; font-style: italic; }
        .comment-input-box { display: flex; align-items: center; gap: 20px; margin-top: 25px; margin-bottom: 40px; }
        .avatar-review { width: 58px; height: 52px; border-radius: 50%; background-color: #EDC484; color: #29030E; display: flex; justify-content: center; align-items: center; font-weight: bold; font-size: 20px; }
        .review-input { width: 1040px; height: 50px; background-color: #FCF6ED; border: none; border-radius: 25px; padding: 0 25px; font-size: 15px; color: #29030E; }
        .review-card { background-color: #FCF6ED; color: #29030E; border-radius: 15px; padding: 20px 25px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .review-details h5 { font-size: 16px; color: #52131E; font-weight: 600; }
        .review-details p { font-size: 14px; margin-top: 6px; opacity: 0.9; }

        /* --- FOOTER --- */
        .main-footer { background-color: #1A1211; padding: 60px 100px; display: flex; justify-content: space-between; }
        .footer-brand { width: 350px; }
        .footer-brand h4 { font-family: 'Sawarabi Mincho', serif; color: #EDC484; font-size: 24px; margin-bottom: 15px; }
        .footer-brand p { font-size: 14px; opacity: 0.7; line-height: 1.6; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-logo">
            <img src="imagenes/logo.png" alt="Barber House">
        </div>
        <ul class="nav-links">
            <li><a href="inicio.php" style="color: #EDC484; font-weight: 500;">Inicio</a></li>
            <li><a href="servicios.php">Servicios</a></li>
            <li><a href="#">Sucursales</a></li>
            <li><a href="#">Trabaja con nosotros</a></li>
        </ul>
        <div class="nav-user-zone">
            <?php if ($sesion_activa): ?>
                <div class="user-dropdown-container">
                    <button class="user-dropdown-btn" id="dropdownBtn">
                        <span>Usuario: <?php echo htmlspecialchars($rol_usuario); ?></span>
                        <span style="font-size: 10px;">▼</span>
                    </button>
                    <div class="dropdown-menu" id="dropdownMenu">
                        <?php if ($rol_usuario === 'Cliente'): ?>
                            <a href="perfil_cliente.php">👤 Mis Citas</a>
                        <?php else: ?>
                            <a href="perfil_trabajador.php">📅 Mi Panel de Trabajo</a>
                        <?php endif; ?>
                        <a href="logout.php" style="color: #EA4335; border-top: 1px solid rgba(237,196,132,0.1);">🚪 Cerrar Sesión</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="index.php" class="user-dropdown-btn"><span>Ingresar al Sistema</span></a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="hero-section">
        <div class="hero-content">
            <h2>TU ESTILO, NUESTRA PASIÓN</h2>
            <p>Experiencia • Precisión • Excelencia</p>
        </div>
    </div>

    <div class="about-section">
        <img src="imagenes/sobre_nosotros.jpg" alt="Corte" class="about-img">
        <div class="about-text-box">
            <h3>Sobre Nosotros</h3>
            <p>En Barber House Ecuador combinamos estilo, precisión y atención personalizada para que cada cliente viva una experiencia única de barbería. Cortes, afeitados y coloración profesional con un ambiente elegante y moderno.</p>
            <h3>Misión</h3>
            <p>Ofrecer servicios de barbería y cuidado capilar de alta calidad, resaltando el estilo y la confianza de cada cliente.</p>
            <h3>Visión</h3>
            <p>Ser una barbería referente por nuestra excelencia, innovación y experiencia premium en el cuidado.</p>
        </div>
    </div>

    <div class="services-section">
        <p style="color: #52131E; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Selección Premium</p>
        <h2>Servicios Destacados</h2>
        
        <div class="services-container">
            <div class="service-card">
                <img src="imagenes/barba.jpg" alt="Barba" class="service-img">
                <div class="service-info">
                    <span class="service-badge">35 min</span>
                    <h4>Perfilado & Ritual de Barba</h4>
                    <p style="font-size: 13px; opacity: 0.8; margin-top: 10px;">Asesoría de estilo personalizada, corte con tijera/maquinaria y lavado premium con productos tonificantes.</p>
                    <span style="font-weight: 600; color: #EDC484; margin-top: 10px;">$18.00</span>
                </div>
            </div>
            <div class="service-card">
                <img src="imagenes/mascarilla.jpg" alt="Mascarilla" class="service-img">
                <div class="service-info">
                    <span class="service-badge">20 min</span>
                    <h4>Mascarilla Facial Purificante</h4>
                    <p style="font-size: 13px; opacity: 0.8; margin-top: 10px;">Aplicación de mascarilla negra o arcilla verde fría para desintoxicar los poros y remover impurezas.</p>
                    <span style="font-weight: 600; color: #EDC484; margin-top: 10px;">$15.00</span>
                </div>
            </div>
        </div>
    </div>

    <div class="reviews-section">
        <div class="reviews-container">
            <h3>Reseñas</h3>
            <p style="opacity: 0.7; margin-bottom: 20px;">2 COMENTARIOS</p>
            
            <div class="comment-input-box">
                <div class="avatar-review">?</div>
                <input type="text" class="review-input" placeholder="Deja un comentario...">
            </div>

            <?php foreach ($comentarios as $com): ?>
                <div class="review-card">
                    <div style="display: flex; gap: 20px; align-items: center;">
                        <div class="avatar-review" style="background-color: #52131E; color: #EDC484;">
                            <?php echo substr($com['nombre'], 0, 1); ?>
                        </div>
                        <div class="review-details">
                            <h5><?php echo htmlspecialchars($com['nombre']); ?></h5>
                            <p><?php echo htmlspecialchars($com['texto']); ?></p>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 12px; opacity: 0.6; display: block; margin-bottom: 5px;"><?php echo $com['tiempo']; ?></span>
                        <span style="color: #F57C00;">★★★★★</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <footer class="main-footer">
        <div class="footer-brand">
            <h4>BARBER HOUSE</h4>
            <p>Transforma tu estilo, realza tu confianza; porque cada detalle cuenta en tu imagen.</p>
        </div>
    </footer>

    <script>
        const dropdownBtn = document.getElementById('dropdownBtn');
        const dropdownMenu = document.getElementById('dropdownMenu');
        if (dropdownBtn && dropdownMenu) {
            dropdownBtn.addEventListener('click', function(e) { 
                e.stopPropagation(); 
                dropdownMenu.classList.toggle('show'); 
            });
            document.addEventListener('click', function() { 
                dropdownMenu.classList.remove('show'); 
            });
        }
    </script>
</body>
</html>