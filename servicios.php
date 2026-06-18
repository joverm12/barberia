<?php
/**
 * ARCHIVO: servicios.php
 * DESCRIPCIÓN: 
 * Este módulo actúa como el catálogo público de Barber House. Evalúa dinámicamente 
 * el estado de la sesión para adecuar el menú superior y realiza una consulta relacional 
 * anidada: primero extrae los paquetes globales (Packs) y luego, por cada paquete, 
 * filtra de forma limpia los servicios vinculados. Estructura el diseño mediante bloques 
 * asimétricos alternos e incluye un motor de búsqueda rápida en el front-end.
 */

// Iniciamos la sesión para comprobar el estado del visitante en la app
session_start();
// Importamos el puente de conexión centralizada
require_once 'conexion.php';

// Evaluar si el usuario inició sesión para moldear las opciones del navbar
$sesion_activa = isset($_SESSION['user_id']);
$rol_usuario   = $sesion_activa ? $_SESSION['user_rol'] : '';
$nombre_usuario = $sesion_activa ? $_SESSION['user_name'] : '';

// 1. Obtener todos los paquetes (Packs) disponibles de forma ascendente
try {
    $stmtPacks = $pdo->query("SELECT * FROM pack ORDER BY id_pack ASC");
    $packs = $stmtPacks->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la base de datos falla, inicializamos en vacío para evitar rupturas de sintaxis
    $packs = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barber House - Catálogo de Servicios</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600&family=Sawarabi+Mincho&display=swap" rel="stylesheet">
    
    <style>
        /* Ajustes maestros de normalización visual */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #FCF6ED; font-family: 'Instrument Sans', sans-serif; color: #29030E; overflow-x: hidden; }

        /* --- NAVBAR SUPERIOR CONFIGURADA --- */
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

        /* --- MENÚ DESPLEGABLE (DROPDOWN DE NAVEGACIÓN) --- */
        .nav-user-zone { display: flex; align-items: center; gap: 20px; }
        .user-dropdown-container { position: relative; display: inline-block; }
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

        /* --- HERO BANNER DE INVITACIÓN --- */
        .hero-banner {
            width: 100%;
            max-width: 1440px;
            margin: 0 auto;
            height: 380px;
            background-image: linear-gradient(180deg, rgba(66, 5, 22, 0.6) 0%, rgba(41, 3, 14, 0.9) 100%), url('imagenes/hero_bg.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: #FFEED5;
        }
        .hero-banner p { letter-spacing: 3px; font-size: 14px; margin-bottom: 10px; font-weight: 500; color: #EDC484; }
        .hero-banner h1 { font-family: 'Sawarabi Mincho', serif; font-size: 48px; color: #FFFFFF; font-weight: 400; margin-bottom: 25px; letter-spacing: 1px; }
        
        .btn-reserve { 
            background-color: #52131E; border: 2px solid #EDC484; color: #EDC484; padding: 14px 35px; 
            font-size: 16px; font-weight: 600; border-radius: 8px; cursor: pointer; text-decoration: none; 
            display: inline-block; transition: all 0.3s; letter-spacing: 1px;
        }
        .btn-reserve:hover { background-color: #EDC484; color: #29030E; }

        /* --- CABECERA DE SECCIÓN --- */
        .catalog-header { text-align: center; padding: 60px 20px 30px; max-width: 800px; margin: 0 auto; }
        .catalog-header h2 { font-family: 'Sawarabi Mincho', serif; font-size: 38px; color: #29030E; margin-bottom: 12px; }
        .catalog-header p { font-size: 16px; color: #52131E; opacity: 0.8; line-height: 1.5; }

        /* --- BARRA DE BÚSQUEDA --- */
        .search-container { width: 991px; max-width: 90%; margin: 0 auto 50px; position: relative; }
        .search-input { 
            width: 100%; height: 55px; border: 1px solid #52131E; border-radius: 30px; 
            padding: 0 30px; font-size: 16px; background-color: #FFFFFF; color: #29030E; outline: none;
            box-shadow: 0px 4px 10px rgba(0,0,0,0.05);
        }

        /* --- LAYOUT DE BLOQUES ASIMÉTRICOS (DISEÑO FIEL A FIGMA) --- */
        .category-block { width: 100%; max-width: 1440px; margin: 0 auto 60px; display: flex; min-height: 520px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        
        /* Modificador para invertir las posiciones de imagen e información de manera fluida */
        .category-block.reverse { flex-direction: row-reverse; }
        .block-img { width: 50%; background-size: cover; background-position: center; min-height: 520px; }
        
        .block-info { width: 50%; background-color: #52131E; color: #FFEED5; padding: 60px; display: flex; flex-direction: column; justify-content: center; }
        .block-info h3 { font-family: 'Sawarabi Mincho', serif; font-size: 34px; color: #EDC484; margin-bottom: 35px; border-bottom: 2px solid rgba(237,196,132,0.2); padding-bottom: 15px; font-style: italic; }
        
        /* FILAS E ÍTEMS DEL MENÚ DE TARIFAS */
        .menu-item { display: flex; justify-content: space-between; margin-bottom: 30px; align-items: flex-start; }
        .item-detail { width: 80%; }
        .item-detail h4 { font-size: 19px; color: #FFFFFF; font-weight: 500; }
        .item-detail p { font-size: 14px; opacity: 0.75; margin-top: 6px; line-height: 1.5; }
        .item-price { font-size: 20px; color: #EDC484; font-weight: 600; font-family: 'Sawarabi Mincho', serif; }

        .main-footer { background-color: #1A1211; padding: 40px 100px; text-align: center; color: #FFEED5; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-logo"><img src="imagenes/logo.png" alt="Barber House"></div>
        <ul class="nav-links">
            <li><a href="inicio.php">Inicio</a></li>
            <li><a href="servicios.php" style="color: #EDC484;">Servicios</a></li>
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

    <div class="hero-banner">
        <p>TU ESTILO, TU MOMENTO.</p>
        <h1>RESERVA TU SILLA.</h1>
        <a href="agendar_cita.php" class="btn-reserve">AGENDAR CITA</a>
    </div>

    <div class="catalog-header">
        <h2>Nuestro Catálogo de Servicios</h2>
        <p>Cuidado de primer nivel e higiene excepcional para garantizar resultados impecables en tu imagen.</p>
    </div>

    <div class="search-container">
        <input type="text" class="search-input" id="catalogSearch" placeholder="Buscar por servicio en particular...">
    </div>

    <?php 
    $contador_bloques = 0;
    foreach ($packs as $p): 
        $id_pack = $p['id_pack'];
        $nombre_pack = $p['nombre']; 
        
        // 2. Extraemos mediante sentencias preparadas los servicios vinculados estrictamente a este id_pack
        $stmtServ = $pdo->prepare("SELECT * FROM servicio WHERE id_pack = ? ORDER BY id_servicio ASC");
        $stmtServ->execute([$id_pack]);
        $servicios_del_pack = $stmtServ->fetchAll(PDO::FETCH_ASSOC);
        
        // Evaluamos de manera preventiva: Solo pintamos el bloque si el Pack cuenta con servicios cargados
        if (count($servicios_del_pack) > 0):
            $contador_bloques++;
            
            // Alternamos dinámicamente la clase reverse para lograr el efecto asimétrico del diseño original
            $clase_reverse = ($contador_bloques % 2 === 0) ? 'reverse' : '';
            
            // Variable configurable para setear imágenes de portada por bloque en el futuro
            $imagen_bloque = 'imagenes/sobre_nosotros.jpg'; 
    ?>
        <div class="category-block <?php echo $clase_reverse; ?>">
            <div class="block-img" style="background-image: url('<?php echo $imagen_bloque; ?>');"></div>
            
            <div class="block-info" style="<?php echo ($contador_bloques % 2 === 0) ? 'background-color: #420516;' : ''; ?>">
                <h3><?php echo htmlspecialchars($nombre_pack); ?></h3>
                
                <?php foreach ($servicios_del_pack as $s): ?>
                    <div class="menu-item">
                        <div class="item-detail">
                            <h4><?php echo htmlspecialchars($s['nombre']); ?></h4>
                            <p>
                                <?php if (!empty($s['duracion'])): ?>
                                    <strong>Duración:</strong> <?php echo htmlspecialchars($s['duracion']); ?> min. 
                                <?php endif; ?>
                                <?php echo htmlspecialchars($s['descripcion'] ?? 'Servicio de alta gama ejecutado por profesionales certificados.'); ?>
                            </p>
                        </div>
                        <div class="item-price">$<?php echo number_format($s['precio'], 2); ?></div>
                    </div>
                <?php endforeach; ?>
                
            </div>
        </div>
    <?php 
        endif;
    endforeach; 
    ?>

    <footer class="main-footer">
        <h4>BARBER HOUSE</h4>
    </footer>

    <script>
        // Control y apertura del Dropdown de sesión
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

        // Buscador predictivo en tiempo real en el Front-end
        document.getElementById('catalogSearch').addEventListener('input', function(e) {
            let filter = e.target.value.toLowerCase();
            let items = document.querySelectorAll('.menu-item');
            
            items.forEach(function(item) {
                // Comparamos el texto del input con el título (H4) de cada fila de servicio
                let text = item.querySelector('h4').innerText.toLowerCase();
                
                // Si el texto coincide, conservamos el 'flex', de lo contrario ocultamos la fila
                item.style.display = text.includes(filter) ? "flex" : "none";
            });
        });
    </script>
</body>
</html>
