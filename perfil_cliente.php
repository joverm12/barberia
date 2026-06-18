<?php
session_start();
require_once 'conexion.php';

// SEGURIDAD: Si no está logeado o su rol no es 'Cliente', directo al login
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'Cliente') {
    header('Location: index.php');
    exit;
}

$id_usuario = $_SESSION['user_id'];
$rol_cliente = $_SESSION['user_rol'];

// 1. Cargar datos actualizados del usuario logeado desde la base de datos
try {
    $stmtUser = $pdo->prepare("SELECT * FROM usuario WHERE id_usuario = ?");
    $stmtUser->execute([$id_usuario]);
    $usuario_real = $stmtUser->fetch();
} catch (PDOException $e) {
    $usuario_real = [];
}

$nombre   = $usuario_real['nombre'] ?? '';
$apellido = $usuario_real['apellido'] ?? '';
$correo   = $usuario_real['correo'] ?? '';
$cedula   = $usuario_real['cedula'] ?? ''; 
$telefono = $usuario_real['telefono'] ?? ''; 

// 2. Obtener el id_cliente asociado al usuario
try {
    $stmtCli = $pdo->prepare("SELECT id_cliente FROM cliente WHERE id_usuario = ?");
    $stmtCli->execute([$id_usuario]);
    $cliente = $stmtCli->fetch();
    $id_cliente = $cliente['id_cliente'] ?? 0;

    // 3. Traer el historial de citas vinculadas
    $sqlCitas = "
        SELECT c.*, s.nombre AS servicio_nombre, s.precio AS servicio_precio
        FROM cita c
        LEFT JOIN servicio s ON c.id_servicio = s.id_servicio
        WHERE c.id_cliente = ?
        ORDER BY c.hora ASC
    ";
    $stmtCitas = $pdo->prepare($sqlCitas);
    $stmtCitas->execute([$id_cliente]);
    $all_citas = $stmtCitas->fetchAll();
} catch (PDOException $e) {
    $all_citas = [];
}

$proximas = []; 
$historial = [];
foreach ($all_citas as $c) {
    $estado_u = strtoupper($c['estado']);
    if ($estado_u === 'FINALIZADA' || $estado_u === 'CANCELADA') { 
        $historial[] = $c; 
    } else { 
        $proximas[] = $c; 
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barber House - Mi Perfil</title>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600&family=Sawarabi+Mincho&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #FCF6ED; font-family: 'Instrument Sans', sans-serif; color: #29030E; }
        
        /* --- NAVBAR CORREGIDA CON ENLACES DE NAVEGACIÓN --- */
        .navbar { 
            background-color: #52131E; 
            width: 100%; 
            height: 109px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 0 50px; 
            box-shadow: 0px 4px 15px rgba(0,0,0,0.3); 
        }
        .nav-logo img { height: 65px; width: auto; }
        .nav-links { display: flex; gap: 40px; list-style: none; margin: 0 auto; }
        .nav-links a { color: #FFEED5; text-decoration: none; font-size: 18px; font-weight: 400; transition: color 0.3s; }
        .nav-links a:hover { color: #EDC484; }
        
        .nav-user-zone { display: flex; align-items: center; gap: 20px; }
        .btn-logout { background-color: #231918; border: 1px solid #EDC484; padding: 10px 18px; border-radius: 12px; color: #EDC484; font-size: 14px; text-decoration: none; cursor: pointer; }

        .profile-banner { width: 1400px; max-width: 95%; margin: 30px auto 0; background-color: #EDC484; padding: 25px 40px; border-radius: 20px; color: #52131E; }
        .profile-banner h1 { font-family: 'Sawarabi Mincho', serif; font-size: 32px; font-weight: 400; }
        
        .layout-grid { width: 1400px; max-width: 95%; margin: 30px auto; display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 30px; }
        .white-card { background-color: #FFFFFF; border-radius: 20px; padding: 35px; box-shadow: 0 8px 25px rgba(0,0,0,0.03); border: 1px solid rgba(82, 19, 30, 0.05); }
        
        .section-title { font-size: 22px; font-family: 'Sawarabi Mincho', serif; color: #52131E; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        
        label { font-size: 13px; font-weight: 600; display: block; margin-top: 15px; margin-bottom: 5px; color: #52131E; text-transform: uppercase; letter-spacing: 0.5px; }
        input { width: 100%; height: 46px; border: 1px solid #DCD6CD; border-radius: 8px; padding: 0 15px; font-size: 15px; background-color: #FDFBF9; color: #29030E; outline: none; }
        input[readonly] { background-color: #F0ECE6; color: #777; cursor: not-allowed; border: 1px solid #D1CAbF; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        
        /* Botón de Enviar Formulario */
        .btn-submit-changes { background-color: #52131E; color: #FFEED5; border: 1px solid #EDC484; padding: 12px 30px; border-radius: 8px; font-weight: 600; font-size: 14px; text-transform: uppercase; cursor: pointer; display: flex; align-items: center; gap: 8px; margin-top: 30px; margin-left: auto; transition: all 0.3s; }
        .btn-submit-changes:hover { background-color: #29030E; color: white; }
        
        /* Tarjetas de Citas (Columna Derecha) */
        .cita-box { border: 1px solid rgba(82,19,30,0.15); border-left: 4px solid #52131E; border-radius: 8px; padding: 20px; margin-bottom: 20px; position: relative; background-color: #FFFDFB; }
        .badge-status { position: absolute; top: 20px; right: 20px; background-color: #FFF5F5; color: #52131E; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 12px; text-transform: uppercase; border: 1px solid #52131E; }
        .cita-box h4 { font-size: 16px; margin-bottom: 5px; color: #29030E; }
        .cita-box p { font-size: 14px; opacity: 0.7; }
        
        .alert-msg { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 500; text-align: center; display: none; }
        .alert-success { background-color: #E8F5E9; color: #2E7D32; border: 1px solid #A5D6A7; }
    </style>
</head>
<body>

    <!-- --- NAVBAR SUPERIOR RESTAURADA --- -->
    <nav class="navbar">
        <div class="nav-logo">
            <img src="imagenes/logo.png" alt="Barber House">
        </div>
        <!-- Botones de navegación solicitados -->
        <ul class="nav-links">
            <li><a href="inicio.php">Inicio</a></li>
            <li><a href="servicios.php">Servicios</a></li>
            <li><a href="agendar_cita.php">Agendar Cita</a></li>
        </ul>
        <div class="nav-user-zone">
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </nav>

    <div class="profile-banner">
        <h1>Mi perfil</h1>
        <p style="font-size: 14px; opacity: 0.8;">Configuración y actualización de tu cuenta de usuario</p>
    </div>

    <div class="layout-grid">
        
        <!-- COLUMNA IZQUIERDA: Formulario de Datos Personales con Edición -->
        <div class="white-card">
            <div class="section-title">Datos Personales</div>
            
            <!-- Mensaje de confirmación en caso de éxito -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert-msg alert-success" style="display: block;">¡Tus datos personales se actualizaron correctamente!</div>
            <?php endif; ?>

            <form action="actualizar_perfil.php" method="POST">
                <div class="grid-2">
                    <div>
                        <label>Nombres</label>
                        <input type="text" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required>
                    </div>
                    <div>
                        <label>Apellidos</label>
                        <input type="text" name="apellido" value="<?php echo htmlspecialchars($apellido); ?>" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div>
                        <label>Cédula (No editable)</label>
                        <input type="text" value="<?php echo htmlspecialchars($cedula); ?>" readonly>
                    </div>
                    <div>
                        <label>Teléfono</label>
                        <input type="text" name="telefono" value="<?php echo htmlspecialchars($telefono); ?>">
                    </div>
                </div>

                <label>Correo Electrónico</label>
                <input type="email" name="correo" value="<?php echo htmlspecialchars($correo); ?>" required>

                <!-- Botón de guardado incorporado -->
                <button type="submit" class="btn-submit-changes">Guardar Cambios</button>
            </form>
        </div>

        <!-- COLUMNA DERECHA: Control de Citas Agendadas -->
        <div class="white-card" style="background-color: #FDFBF9;">
            <div class="section-title">📅 Próximas Citas</div>
            
            <?php if (count($proximas) > 0): ?>
                <?php foreach ($proximas as $p): ?>
                    <div class="cita-box">
                        <span class="badge-status"><?php echo htmlspecialchars($p['estado']); ?></span>
                        <h4><?php echo htmlspecialchars($p['servicio_nombre'] ?? 'Corte de cabello y barba'); ?></h4>
                        <p>Horario: <?php echo date("d/m/Y - H:i", strtotime($p['hora'])); ?> hs</p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="font-size: 14px; opacity: 0.6; font-style: italic; margin-bottom: 30px;">No registras turnos pendientes por atender.</p>
            <?php endif; ?>

            <div class="section-title" style="margin-top: 40px; border-top: 1px dashed rgba(82,19,30,0.15); padding-top: 25px;">⏳ Historial de Atención</div>
            
            <?php if (count($historial) > 0): ?>
                <?php foreach ($historial as $h): ?>
                    <div class="cita-box" style="border-left-color: #2E7D32;">
                        <span class="badge-status" style="color: #2E7D32; border-color: #2E7D32; background-color: #EDF7ED;"><?php echo htmlspecialchars($h['estado']); ?></span>
                        <h4><?php echo htmlspecialchars($h['servicio_nombre']); ?></h4>
                        <p>Atendido el <?php echo date("d/m/Y", strtotime($h['hora'])); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="font-size: 14px; opacity: 0.6; font-style: italic;">Aún no posees asistencias registradas.</p>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>