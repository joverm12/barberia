<?php
session_start();
require_once 'conexion.php';

// Definimos la lista de roles autorizados para usar esta agenda de trabajo
$roles_permitidos = ['Barbero', 'Estilista', 'Manicurista', 'Maquillador'];

// SEGURIDAD: Si no está logeado o su rol no pertenece al personal, regresa al login
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_rol'], $roles_permitidos)) {
    header('Location: index.php');
    exit;
}

$id_usuario = $_SESSION['user_id'];
$nombre_trabajador = $_SESSION['user_name'];
$apellido_trabajador = $_SESSION['user_last'];
$rol_trabajador = $_SESSION['user_rol']; // Guarda dinámicamente el rol actual

// 1. Obtener el id_empleado y el nombre de su sucursal
$stmtEmp = $pdo->prepare("
    SELECT e.id_empleado, s.nombre AS nombre_sucursal 
    FROM empleado e 
    JOIN sucursal s ON e.id_sucursal = s.id_sucursal 
    WHERE e.id_usuario = ?
");
$stmtEmp->execute([$id_usuario]);
$empleado = $stmtEmp->fetch();

$id_empleado = $empleado['id_empleado'] ?? 0;
$sucursal_nombre = $empleado['nombre_sucursal'] ?? 'No asignada';

// 2. Traer las citas del trabajador logeado organizadas para el día de hoy
$sqlCitas = "
    SELECT 
        c.id_cita,
        c.hora,
        c.estado,
        c.observaciones,
        u_cli.nombre AS cli_nombre,
        u_cli.apellido AS cli_apellido,
        cl.telefono AS cli_telefono,
        s.nombre AS servicio_nombre
    FROM cita c
    JOIN cliente cl ON c.id_cliente = cl.id_cliente
    JOIN usuario u_cli ON cl.id_usuario = u_cli.id_usuario
    LEFT JOIN servicio s ON c.id_servicio = s.id_servicio
    WHERE c.id_empleado = ?
    ORDER BY c.hora ASC
";
$stmtCitas = $pdo->prepare($sqlCitas);
$stmtCitas->execute([$id_empleado]);
$citas = $stmtCitas->fetchAll();

// Total de citas para el contador dinámico
$total_citas = count($citas);

// Configuración de fecha en español idéntica a tu Figma
setlocale(LC_TIME, 'es_ES.UTF-8', 'esp');
$fecha_actual = strftime("%A, %d  %b  %Y");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barber House - Agenda <?php echo htmlspecialchars($rol_trabajador); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600&family=Sawarabi+Mincho&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #420516; /* Tu Degradado Lineal Oscuro Base de Figma */
            background: linear-gradient(180deg, #420516 0%, #29030E 100%);
            font-family: 'Instrument Sans', sans-serif;
            color: #FFEED5; /* Tu color crema claro de fuentes */
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* --- NAVBAR SUPERIOR --- */
        .navbar {
            background-color: #52131E; /* Tu color vino oficial */
            width: 100%;
            height: 109px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 50px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 10;
        }

        .nav-logo img {
            height: 65px;
            width: auto;
        }

        .nav-links {
            display: flex;
            gap: 40px;
            list-style: none;
        }

        .nav-links a {
            color: #FFEED5;
            text-decoration: none;
            font-size: 18px;
            font-weight: 400;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: #EDC484; /* Tu dorado */
        }

        .nav-user-zone {
            display: flex;
            align-items: center;
            gap: 20px;
            position: relative;
        }

        .user-dropdown-container {
            position: relative;
        }

        .user-dropdown-btn {
            background-color: #231918;
            border: 1px solid #EDC484;
            padding: 10px 18px;
            border-radius: 12px;
            color: #EDC484;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        /* --- MENÚ DESPLEGABLE FLOTANTE --- */
        .dropdown-menu {
            position: absolute;
            top: 120%;
            right: 0;
            background-color: #231918;
            border: 1px solid #EDC484;
            border-radius: 12px;
            width: 180px;
            box-shadow: 0px 8px 24px rgba(0,0,0,0.5);
            display: none; 
            flex-direction: column;
            overflow: hidden;
            z-index: 100;
        }

        .dropdown-menu.show {
            display: flex; 
        }

        .dropdown-menu a {
            color: #FFEED5;
            padding: 14px 16px;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s, color 0.3s;
            text-align: left;
        }

        .dropdown-menu a:hover {
            background-color: #52131E;
            color: #EDC484;
        }

        /* --- CONTENEDOR PRINCIPAL --- */
        .main-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* --- ENCABEZADO DE LA AGENDA --- */
        .agenda-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .barbero-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .avatar-circle {
            width: 65px;
            height: 65px;
            background-color: #52131E;
            border: 2px solid #EDC484;
            color: #EDC484;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            font-family: 'Sawarabi Mincho', serif;
        }

        .barbero-text h1 {
            font-family: 'Sawarabi Mincho', serif;
            font-size: 32px;
            color: #EDC484;
            font-weight: 400;
        }

        .barbero-text p {
            font-size: 16px;
            color: #FFEED5;
            opacity: 0.8;
            margin-top: 4px;
        }

        .barbero-text span {
            color: #EA4335;
            font-size: 18px;
            margin-right: 5px;
        }

        .fecha-card {
            background-color: #231918;
            border: 1px solid rgba(237, 196, 132, 0.15);
            padding: 15px 25px;
            border-radius: 15px;
            text-align: right;
        }

        .fecha-card label {
            font-size: 12px;
            color: #EDC484;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: block;
            margin-bottom: 4px;
        }

        .fecha-card p {
            font-family: 'Sawarabi Mincho', serif;
            font-size: 18px;
        }

        /* --- TABLA DE CITAS --- */
        .agenda-card {
            background-color: #231918;
            border-radius: 25px;
            border: 1px solid rgba(237, 196, 132, 0.1);
            padding: 30px;
            box-shadow: 0px 10px 30px rgba(0,0,0,0.3);
        }

        .card-top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 238, 213, 0.1);
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .card-top-bar h2 {
            font-size: 22px;
            color: #EDC484;
            font-weight: 500;
        }

        .citas-counter {
            font-size: 16px;
            opacity: 0.7;
        }

        .table-agenda {
            width: 100%;
            border-collapse: collapse;
        }

        .table-agenda th {
            color: #EDC484;
            font-size: 14px;
            text-transform: uppercase;
            font-weight: 500;
            padding: 15px 20px;
            text-align: left;
            opacity: 0.8;
            border-bottom: 1px solid rgba(255, 238, 213, 0.1);
        }

        .table-agenda td {
            padding: 22px 20px;
            font-size: 16px;
            border-bottom: 1px solid rgba(255, 238, 213, 0.05);
            vertical-align: middle;
        }

        .time-col {
            font-weight: 600;
            color: #FFEED5;
        }

        .client-info h3 {
            font-size: 16px;
            color: #FFFFFF;
            font-weight: 500;
        }

        .client-info p {
            font-size: 13px;
            color: #EDC484;
            opacity: 0.8;
            margin-top: 4px;
        }

        .badge-status {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-status.finalizada {
            background-color: rgba(46, 125, 50, 0.2);
            color: #81C784;
            border: 1px solid #4CAF50;
        }

        .badge-status.pendiente {
            background-color: rgba(245, 124, 0, 0.15);
            color: #FFB74D;
            border: 1px solid #F57C00;
        }

        .badge-status.en_proceso {
            background-color: rgba(21, 101, 192, 0.2);
            color: #64B5F6;
            border: 1px solid #2196F3;
        }

        /* BOTONES ACCIONES */
        .btn-action {
            padding: 10px 20px;
            border-radius: 12px;
            font-family: 'Instrument Sans', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-start {
            background-color: #52131E;
            color: #EDC484;
            border: 1px solid #EDC484;
        }

        .btn-start:hover {
            background-color: #780524;
        }

        .btn-finish {
            background-color: #2E7D32;
            color: #FFFFFF;
        }

        .btn-finish:hover {
            background-color: #1B5E20;
        }

        .no-actions {
            color: #FFEED5;
            opacity: 0.5;
            font-size: 14px;
        }

        .no-data-msg {
            text-align: center;
            padding: 40px;
            color: #FFEED5;
            opacity: 0.6;
            font-style: italic;
        }

        /* --- VENTANA MODAL (EMERGENTE FIEL A FIGMA) --- */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            display: none; 
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-content {
            background-color: #231918;
            border: 1px solid #EDC484;
            border-radius: 20px;
            width: 450px;
            padding: 25px;
            box-shadow: 0px 15px 35px rgba(0,0,0,0.6);
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 238, 213, 0.1);
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .modal-header h3 {
            color: #EDC484;
            font-family: 'Sawarabi Mincho', serif;
            font-size: 20px;
            font-weight: 400;
        }

        .close-modal-btn {
            background: none;
            border: none;
            color: #FFEED5;
            font-size: 22px;
            cursor: pointer;
            opacity: 0.7;
        }

        .close-modal-btn:hover {
            opacity: 1;
            color: #EA4335;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .info-group {
            margin-bottom: 12px;
        }

        .info-group label {
            font-size: 12px;
            color: #EDC484;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 2px;
        }

        .info-group p {
            font-size: 16px;
            color: #FFFFFF;
        }

        .obs-textarea {
            width: 100%;
            height: 80px;
            background-color: #1A1211;
            border: 1px solid rgba(237, 196, 132, 0.3);
            border-radius: 8px;
            color: #FFEED5;
            padding: 10px;
            font-family: 'Instrument Sans', sans-serif;
            font-size: 14px;
            resize: none;
            margin-top: 5px;
        }

        .obs-textarea:focus {
            outline: none;
            border-color: #EDC484;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .btn-modal {
            padding: 10px 18px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            border: none;
        }

        .btn-modal-cancel {
            background-color: transparent;
            color: #FFEED5;
            border: 1px solid rgba(255, 238, 213, 0.3);
        }

        .btn-modal-cancel:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .btn-modal-confirm {
            background-color: #2E7D32;
            color: white;
        }

        .btn-modal-confirm:hover {
            background-color: #1B5E20;
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-logo">
            <img src="imagenes/logo.png" alt="Barber House">
        </div>
        
        <ul class="nav-links">
            <li style="color: #EDC484; font-weight: 500; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">
                Panel de Control de Operaciones
            </li>
        </ul>
        
        <div class="nav-user-zone">
            <span style="font-size: 22px; cursor: pointer;">🔔</span>
            
            <div class="user-dropdown-container">
                <button class="user-dropdown-btn" id="dropdownBtn">
                    <span>Usuario: <?php echo htmlspecialchars($rol_trabajador); ?></span>
                    <span style="font-size: 10px;">▼</span>
                </button>
                
                <div class="dropdown-menu" id="dropdownMenu">
                    <a href="logout.php" style="color: #EA4335; font-weight: 500;">🚪 Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="main-container">
        
        <div class="agenda-header">
            <div class="barbero-info">
                <div class="avatar-circle"><?php echo substr($nombre_trabajador, 0, 1); ?></div>
                <div class="barbero-text">
                    <h1>Agenda: <?php echo htmlspecialchars($nombre_trabajador . ' ' . $apellido_trabajador); ?></h1>
                    <p><span>●</span> Sucursal <?php echo htmlspecialchars($sucursal_nombre); ?></p>
                </div>
            </div>
            
            <div class="fecha-card">
                <label>Fecha Actual</label>
                <p><?php echo $fecha_actual; ?></p>
            </div>
        </div>

        <div class="agenda-card">
            <div class="card-top-bar">
                <h2>Citas Programadas para Hoy</h2>
                <div class="citas-counter">Total: <?php echo $total_citas; ?> Citas</div>
            </div>

            <?php if ($total_citas > 0): ?>
                <table class="table-agenda">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Horario</th>
                            <th style="width: 30%;">Cliente</th>
                            <th style="width: 25%;">Servicio</th>
                            <th style="width: 15%;">Estado de Cita</th>
                            <th style="width: 15%;">Acciones/Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($citas as $cita): 
                            $status_clean = strtoupper($cita['estado']);
                            $status_class = 'pendiente';
                            
                            if (strpos($status_clean, 'FINALIZADA') !== false) {
                                $status_class = 'finalizada';
                            } elseif (strpos($status_clean, 'PROCESO') !== false) {
                                $status_class = 'en_proceso';
                            }
                        ?>
                            <tr>
                                <td class="time-col">
                                    <?php echo date("H:i", strtotime($cita['hora'])); ?> - 
                                    <?php echo date("H:i", strtotime($cita['hora'] . " + 45 minutes")); ?>
                                </td>
                                <td>
                                    <div class="client-info">
                                        <h3><?php echo htmlspecialchars($cita['cli_nombre'] . ' ' . $cita['cli_apellido']); ?></h3>
                                        <p>📞 <?php echo htmlspecialchars($cita['cli_telefono'] ?? 'Sin teléfono'); ?></p>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($cita['servicio_nombre'] ?? 'Por definir'); ?></td>
                                <td>
                                    <span class="badge-status <?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($cita['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($status_clean === 'PENDIENTE (EN SALA)' || $status_clean === 'PENDIENTE'): ?>
                                        <button type="button" class="btn-action btn-start" 
                                                onclick="openAttentionModal('<?php echo $cita['id_cita']; ?>', '<?php echo htmlspecialchars($cita['cli_nombre'] . ' ' . $cita['cli_apellido']); ?>', '<?php echo htmlspecialchars($cita['servicio_nombre']); ?>')">
                                            Iniciar Atención
                                        </button>
                                    <?php elseif ($status_clean === 'EN PROCESO'): ?>
                                        <a href="cambiar_estado.php?id=<?php echo $cita['id_cita']; ?>&nuevo=finalizar" class="btn-action btn-finish">✓ Finalizar Servicio</a>
                                    <?php else: ?>
                                        <span class="no-actions">Sin acciones</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data-msg">No tienes citas agendadas asignadas para el día de hoy.</p>
            <?php endif; ?>

        </div>
    </div>

    <div class="modal-overlay" id="attentionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmar Inicio de Cita</h3>
                <button class="close-modal-btn" onclick="closeAttentionModal()">&times;</button>
            </div>
            <form action="cambiar_estado.php" method="GET">
                <input type="hidden" name="id" id="modalCitaId">
                <input type="hidden" name="nuevo" value="proceso">
                
                <div class="modal-body">
                    <div class="info-group">
                        <label>Cliente</label>
                        <p id="modalClienteName">Nombre del Cliente</p>
                    </div>
                    <div class="info-group">
                        <label>Servicio Solicitado</label>
                        <p id="modalServicioName">Corte clásico</p>
                    </div>
                    <div class="info-group" style="margin-top: 15px;">
                        <label>Observaciones / Notas iniciales</label>
                        <textarea name="observaciones" class="obs-textarea" placeholder="Ej. El cliente prefiere disminución baja, detalle en la patilla..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal btn-modal-cancel" onclick="closeAttentionModal()">Cancelar</button>
                    <button type="submit" class="btn-modal btn-modal-confirm">Comenzar Servicio</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // === LÓGICA DEL BOTÓN DESPLEGABLE DE USUARIO ===
        const dropdownBtn = document.getElementById('dropdownBtn');
        const dropdownMenu = document.getElementById('dropdownMenu');

        dropdownBtn.addEventListener('click', function(e) {
            e.stopPropagation(); 
            dropdownMenu.classList.toggle('show');
        });

        document.addEventListener('click', function(e) {
            if (!dropdownBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('show');
            }
        });

        // === LÓGICA DE LA VENTANA EMERGENTE (MODAL) ===
        const attentionModal = document.getElementById('attentionModal');
        const modalCitaId = document.getElementById('modalCitaId');
        const modalClienteName = document.getElementById('modalClienteName');
        const modalServicioName = document.getElementById('modalServicioName');

        function openAttentionModal(idCita, nombreCliente, nombreServicio) {
            modalCitaId.value = idCita;
            modalClienteName.innerText = nombreCliente;
            modalServicioName.innerText = nombreServicio;
            attentionModal.classList.add('show');
        }

        function closeAttentionModal() {
            attentionModal.classList.remove('show');
        }

        attentionModal.addEventListener('click', function(e) {
            if (e.target === attentionModal) {
                closeAttentionModal();
            }
        });
    </script>
</body>
</html>