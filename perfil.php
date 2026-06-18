<?php
/**
 * ARCHIVO: perfil.php
 * DESCRIPCIÓN: 
 * Este archivo actúa como el núcleo o "Dashboard Polimórfico" de Barber House. 
 * Evalúa el nivel de privilegios (Rol) almacenado en la sesión activa del usuario 
 * para renderizar interfaces totalmente personalizadas:
 * - Clientes: Visualizan el historial de sus próximas citas programadas y precios.
 * - Staff (Barberos/Estilistas): Cargan su agenda laboral enlazada de forma automática.
 * - Administradores: Acceden al centro de control maestro del CRUD de usuarios.
 */

// Iniciamos la sesión para poder verificar quién solicita la página
session_start();
// Importamos la conexión centralizada mediante PDO
require_once 'conexion.php';

// Filtro de control: Si el usuario intenta saltarse el login, es rebotado al index
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Extraemos los datos de contexto del usuario en sesión
$id_usuario = $_SESSION['user_id'];
$nombre     = $_SESSION['user_name'];
$apellido   = $_SESSION['user_last'];
$rol        = $_SESSION['user_rol'];

// Normalizamos el rol para mitigar fallas por mayúsculas o espacios accidentales
$rol_evaluar = strtolower(trim($rol));

// Inicializamos los contenedores de datos según la interfaz que corresponda inyectar
$citas = [];
$usuarios = []; 

// =========================================================================
// MÓDULO 1: COMPORTAMIENTO PARA ROLES DE TIPO 'CLIENTE'
// =========================================================================
if ($rol_evaluar === 'cliente') {
    // Primero, buscamos el ID relacional único en la tabla intermedia de clientes
    $stmtCliente = $pdo->prepare("SELECT id_cliente FROM cliente WHERE id_usuario = ?");
    $stmtCliente->execute([$id_usuario]);
    $clienteObj = $stmtCliente->fetch();

    if ($clienteObj) {
        $id_cliente = $clienteObj['id_cliente'];
        // Query multi-join para reconstruir el ticket de la agenda del cliente en orden cronológico
        $sql = "SELECT c.hora, c.estado, s.nombre AS servicio, s.precio AS servicio_precio, suc.nombre AS sucursal, u_emp.nombre AS barbero
                FROM cita c
                LEFT JOIN servicio s ON c.id_servicio = s.id_servicio
                LEFT JOIN sucursal suc ON c.id_sucursal = suc.id_sucursal
                LEFT JOIN empleado e ON c.id_empleado = e.id_empleado
                LEFT JOIN usuario u_emp ON e.id_usuario = u_emp.id_usuario
                WHERE c.id_cliente = ?
                ORDER BY c.hora ASC";
        $stmtCitas = $pdo->prepare($sql);
        $stmtCitas->execute([$id_cliente]);
        $citas = $stmtCitas->fetchAll();
    }

// =========================================================================
// MÓDULO 2: COMPORTAMIENTO PARA EL STAFF OPERATIVO (BARBEROS, ESTILISTAS, ETC.)
// =========================================================================
} elseif ($rol_evaluar === 'barbero' || $rol_evaluar === 'trabajador' || $rol_evaluar === 'estilista') {
    // Buscamos el identificador del profesional enlazado a su cuenta base de usuario
    $stmtEmpleado = $pdo->prepare("SELECT id_empleado FROM empleado WHERE id_usuario = ?");
    $stmtEmpleado->execute([$id_usuario]);
    $empleadoObj = $stmtEmpleado->fetch();

    if ($empleadoObj) {
        $id_empleado = $empleadoObj['id_empleado'];
        // Query estructurada para traerle al barbero los datos de contacto del cliente que reservó
        $sql = "SELECT c.hora, c.estado, s.nombre AS servicio, suc.nombre AS sucursal, u_cli.nombre AS cliente_nombre, u_cli.apellido AS cliente_apellido
                FROM cita c
                LEFT JOIN servicio s ON c.id_servicio = s.id_servicio
                LEFT JOIN sucursal suc ON c.id_sucursal = suc.id_sucursal
                LEFT JOIN cliente cl ON c.id_cliente = cl.id_cliente
                LEFT JOIN usuario u_cli ON cl.id_usuario = u_cli.id_usuario
                WHERE c.id_empleado = ?
                ORDER BY c.hora ASC";
        $stmtCitas = $pdo->prepare($sql);
        $stmtCitas->execute([$id_empleado]);
        $citas = $stmtCitas->fetchAll();
    }

// =========================================================================
// MÓDULO 3: COMPORTAMIENTO PARA ADMINISTRADORES PRINCIPALES
// =========================================================================
} else {
    try {
        // Traemos de forma descendente todas las cuentas globales registradas para el centro de control CRUD
        $stmtAdmin = $pdo->query("SELECT * FROM usuario ORDER BY id_usuario DESC");
        $usuarios = $stmtAdmin->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $usuarios = [];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barber House - Mi Perfil</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600&family=Sawarabi+Mincho&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Instrument Sans', sans-serif; overflow-x: hidden; }

        /* Renderizado condicional del CSS de fondo: Ajusta la paleta de colores según el tipo de panel a pintar */
        <?php if ($rol_evaluar === 'administrador' || $rol_evaluar === 'admin' || count($usuarios) > 0): ?>
            body { background-color: #52131E; color: #FFEED5; }
        <?php else: ?>
            body { background-color: #FCF6ED; color: #231918; padding: 40px 20px; }
        <?php endif; ?>

        /* --- CLASES DE LA INTERFAZ TRADICIONAL (CLIENTES / TRABAJADORES) --- */
        .container { max-width: 1100px; margin: 0 auto; background: #FFFFFF; border-radius: 30px; padding: 40px; box-shadow: 0px 10px 30px rgba(0,0,0,0.03); color: #231918; }
        header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid rgba(82, 19, 30, 0.1); padding-bottom: 20px; margin-bottom: 30px; }
        .welcome-title { font-family: 'Sawarabi Mincho', serif; font-size: 32px; color: #52131E; }
        .role-badge { background-color: #780524; color: #EDC484; padding: 6px 16px; border-radius: 20px; font-size: 14px; font-weight: 500; }
        .btn-logout-trad { background-color: #52131E; color: #EDC484; text-decoration: none; padding: 10px 25px; border-radius: 40px; font-size: 15px; font-weight: 500; transition: 0.3s; }
        .section-title { font-size: 22px; color: #52131E; margin-bottom: 20px; font-weight: 600; }
        .citas-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .citas-table th, .citas-table td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .citas-table th { background-color: rgba(82, 19, 30, 0.05); color: #52131E; font-weight: 600; }
        .status { padding: 4px 10px; border-radius: 12px; font-size: 13px; font-weight: 600; text-transform: uppercase; }
        .status.pendiente { background-color: #FFEAA7; color: #8A6D3B; }
        .status.finalizada, .status.completada { background-color: #D4EDDA; color: #155724; }
        .no-data { text-align: center; padding: 30px; color: #777; font-style: italic; }

        /* --- CLASES DE LA INTERFAZ MAESTRA PREMIUM (ADMINISTRADORES) --- */
        .navbar-admin { width: 100%; height: 109px; background-color: #52131E; display: flex; justify-content: space-between; align-items: center; padding: 0 40px; border-bottom: 1px solid rgba(237, 196, 132, 0.15); }
        .nav-left { display: flex; align-items: center; gap: 20px; }
        .nav-logo img { height: 60px; width: auto; object-fit: contain; }
        .nav-brand-text { font-family: 'Sawarabi Mincho', serif; font-size: 36px; color: #FFFFFF; font-style: italic; }
        .nav-right { display: flex; align-items: center; gap: 25px; }
        .icon-bell { font-size: 22px; color: #FFFFFF; cursor: pointer; }
        .user-menu-container { position: relative; }
        .user-menu-btn { background-color: #1A1211; border: 1px solid #EDC484; padding: 12px 24px; border-radius: 8px; color: #EDC484; font-size: 14px; display: flex; align-items: center; gap: 15px; cursor: pointer; width: 250px; justify-content: space-between; }
        .menu-dropdown-content { position: absolute; top: 110%; right: 0; background-color: #1A1211; border: 1px solid #EDC484; border-radius: 8px; width: 250px; box-shadow: 0px 8px 25px rgba(0,0,0,0.5); display: none; flex-direction: column; z-index: 1000; }
        .menu-dropdown-content.show { display: flex; }
        .menu-dropdown-content a { color: #EDC484; padding: 14px 20px; text-decoration: none; font-size: 14px; transition: background 0.3s; opacity: 0.8; text-align: left; }
        .menu-dropdown-content a:hover { background-color: #52131E; opacity: 1; }
        .container-admin { width: 1400px; max-width: 95%; margin: 30px auto; padding: 0 10px; }
        .header-panel { margin-bottom: 25px; }
        .header-panel h2 { font-family: 'Sawarabi Mincho', serif; font-size: 32px; color: #EDC484; font-weight: 400; }
        .header-panel p { font-size: 14px; color: #EDC484; opacity: 0.6; }
        .search-bar-row { display: flex; gap: 20px; margin-bottom: 25px; }
        .search-input-container { position: relative; flex: 1; }
        .search-input-container::before { content: "🔍"; position: absolute; left: 20px; top: 14px; opacity: 0.4; font-size: 16px; }
        .input-search { width: 100%; height: 50px; background-color: #FCF6ED; border: none; border-radius: 25px; padding: 0 25px 0 55px; font-size: 15px; color: #29030E; outline: none; }
        .select-role-filter { width: 280px; height: 50px; background-color: #FCF6ED; border: none; border-radius: 25px; padding: 0 25px; font-size: 15px; color: #29030E; outline: none; cursor: pointer; }
        .table-wrapper { background-color: #380A14; border: 1px solid rgba(237, 196, 132, 0.15); border-radius: 15px; padding: 25px; box-shadow: 0px 10px 30px rgba(0,0,0,0.3); }
        .main-table { width: 100%; border-collapse: collapse; text-align: left; }
        .main-table th { padding: 15px; font-size: 13px; font-weight: 500; text-transform: uppercase; color: #EDC484; opacity: 0.5; border-bottom: 1px solid rgba(237, 196, 132, 0.15); letter-spacing: 0.5px; }
        .main-table td { padding: 20px 15px; font-size: 15px; border-bottom: 1px solid rgba(237, 196, 132, 0.05); vertical-align: middle; color: #EFE6DC; }
        .user-name-text { font-size: 17px; color: #FFFFFF; font-weight: 600; }
        .sub-contact { font-size: 13px; opacity: 0.5; margin-top: 4px; display: block; }
        .role-tag { padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 500; display: inline-block; text-transform: capitalize; }
        .role-tag.cliente { background-color: #4A1A22; color: #E57373; }
        .role-tag.barbero, .role-tag.trabajador, .role-tag.estilista { background-color: #1A2E40; color: #64B5F6; }
        .role-tag.administrador { background-color: #4A3B22; color: #FFD54F; }
        .status-tag { background-color: rgba(76, 175, 80, 0.1); color: #81C784; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; border: 1px solid rgba(76, 175, 80, 0.2); }
        .btn-action-round { width: 38px; height: 38px; border-radius: 50%; border: 1px solid rgba(237, 196, 132, 0.2); background-color: transparent; color: #EDC484; font-size: 14px; cursor: pointer; display: inline-flex; justify-content: center; align-items: center; text-decoration: none; transition: all 0.3s; margin-right: 5px; }
        .btn-action-round:hover { background-color: #EDC484; color: #52131E; }
        .btn-action-round.delete-btn:hover { background-color: #E57373; color: white; border-color: #E57373; }
    </style>
</head>
<body>

    <?php if ($rol_evaluar !== 'administrador' && $rol_evaluar !== 'admin' && count($usuarios) === 0): ?>
        <div class="container">
            <header>
                <div>
                    <h1 class="welcome-title">Bienvenido, <?php echo htmlspecialchars($nombre); ?></h1>
                    <p style="margin-top: 5px; color: #666;">¡Qué bueno verte de regreso en Barber House!</p>
                </div>
                <div>
                    <span class="role-badge"><?php echo htmlspecialchars($rol); ?></span>
                    <a href="logout.php" class="btn-logout-trad" style="margin-left: 15px;">Salir</a>
                </div>
            </header>

            <?php if ($rol_evaluar === 'cliente'): ?>
                <div class="main-content">
                    <h2 class="section-title">📅 Mis Próximas Citas Agendadas</h2>
                    <?php if (count($citas) > 0): ?>
                        <table class="citas-table">
                            <thead>
                                <tr>
                                    <th>Fecha y Hora</th>
                                    <th>Servicio</th>
                                    <th>Barbero</th>
                                    <th>Sucursal</th>
                                    <th>Precio</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($citas as $cita): ?>
                                    <tr>
                                        <td><strong><?php echo date("d/m/Y - g:i a", strtotime($cita['hora'])); ?> hs</strong></td>
                                        <td><?php echo htmlspecialchars($cita['servicio'] ?? 'Pack Especial'); ?></td>
                                        <td><?php echo htmlspecialchars($cita['barbero'] ?? 'Por asignar'); ?></td>
                                        <td><?php echo htmlspecialchars($cita['sucursal']); ?></td>
                                        <td style="font-weight: 600; color: #52131E;">$<?php echo number_format($cita['servicio_precio'] ?? 0, 2); ?></td>
                                        <td><span class="status <?php echo strtolower($cita['estado']); ?>"><?php echo htmlspecialchars($cita['estado']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="no-data">Aún no registras citas programadas. ¡Agenda tu primer corte!</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <nav class="navbar-admin">
            <div class="nav-left">
                <div class="nav-logo"><img src="imagenes/logo.png" alt="Barber House"></div>
                <div class="nav-brand-text">Administración</div>
            </div>
            <div class="nav-right">
                <span class="icon-bell">🔔</span>
                <div class="user-menu-container">
                    <button class="user-menu-btn" id="menuToggle">
                        <span style="display: flex; flex-direction: column; text-align: left;">
                            <small style="font-size: 10px; opacity: 0.5; color: #FFF;">Usuario: Admin</small>
                            <strong><?php echo htmlspecialchars($nombre); ?></strong>
                        </span>
                        <span>▼</span>
                    </button>
                    <div class="menu-dropdown-content" id="menuDropdown">
                        <a href="perfil.php" class="active-link">Administración</a>
                        <a href="#">Operaciones</a>
                        <a href="servicios.php">Catálogo</a>
                        <a href="#">Marketing y Web</a>
                        <a href="logout.php" style="color: #E57373; border-top: 1px solid rgba(237,196,132,0.1);">Cerrar Sesión</a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container-admin">
            <div class="header-panel">
                <h2>Usuarios y Roles</h2>
                <p>Gestión de Roles</p>
                <a href="crear_usuario.php" style="display: inline-block; margin-top: 15px; background: #EDC484; color: #52131E; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600;">+ Nuevo Usuario</a>
            </div>

            <div class="search-bar-row">
                <div class="search-input-container">
                    <input type="text" class="input-search" id="adminSearch" placeholder="Buscar usuario (Nombre, Cédula, Correo)...">
                </div>
                <select class="select-role-filter" id="adminRoleFilter">
                    <option value="todos">Todos los Roles</option>
                    <option value="Cliente">Cliente</option>
                    <option value="Trabajador">Barbero / Trabajador</option>
                    <option value="Administrador">Administrador</option>
                </select>
            </div>

            <div class="table-wrapper">
                <table class="main-table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">ID / DOC.</th>
                            <th style="width: 25%;">USUARIO</th>
                            <th style="width: 25%;">CONTACTO</th>
                            <th style="width: 15%;">ROL ASIGNADO</th>
                            <th style="width: 10%;">ESTADO</th>
                            <th style="width: 10%;">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody id="adminTableBody">
                        <?php foreach ($usuarios as $u): 
                            // Adaptación dinámica de etiquetas CSS según el rol exacto de la base de datos
                            $rol_actual = htmlspecialchars($u['rol']);
                            $rol_lower = strtolower(trim($rol_actual));
                            $clase_badge = 'cliente';
                            
                            if ($rol_lower === 'administrador' || $rol_lower === 'admin') {
                                $clase_badge = 'administrador';
                            } elseif ($rol_lower === 'barbero' || $rol_lower === 'trabajador' || $rol_lower === 'estilista') {
                                $clase_badge = 'barbero';
                            }
                        ?>
                            <tr class="item-row" data-rol="<?php echo $rol_actual; ?>">
                                <td>
                                    <span style="font-size: 12px; opacity: 0.4; display: block; margin-bottom: 2px;">ID: <?php echo $u['id_usuario']; ?></span>
                                    <strong><?php echo htmlspecialchars($u['cedula'] ?? '1700000000'); ?></strong>
                                </td>
                                <td><span class="user-name-text"><?php echo htmlspecialchars($u['nombre'] . ' ' . $u['apellido']); ?></span></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($u['correo']); ?></strong>
                                    <span class="sub-contact">📞 <?php echo !empty($u['telefono']) ? htmlspecialchars($u['telefono']) : 'S/N'; ?></span>
                                </td>
                                <td><span class="role-tag <?php echo $clase_badge; ?>"><?php echo $rol_actual; ?></span></td>
                                <td><span class="status-tag">ACTIVO</span></td>
                                <td>
                                    <a href="editar_usuario.php?id=<?php echo $u['id_usuario']; ?>" class="btn-action-round" title="Editar">🖋️</a>
                                    <a href="eliminar_usuario.php?id=<?php echo $u['id_usuario']; ?>" class="btn-action-round delete-btn" title="Eliminar" onclick="return confirm('¿Eliminar permanentemente este usuario?');">🗑️</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
            // Lógica del Dropdown del Administrador
            const menuToggle = document.getElementById('menuToggle');
            const menuDropdown = document.getElementById('menuDropdown');
            if (menuToggle && menuDropdown) {
                menuToggle.addEventListener('click', function(e) { 
                    e.stopPropagation(); 
                    menuDropdown.classList.toggle('show'); 
                });
                document.addEventListener('click', function() { 
                    menuDropdown.classList.remove('show'); 
                });
            }

            // Motor de filtrado inmediato de usuarios en el lado del cliente (Front-end)
            const adminSearch = document.getElementById('adminSearch');
            const adminRoleFilter = document.getElementById('adminRoleFilter');
            const itemRows = document.querySelectorAll('.item-row');

            function realizarFiltro() {
                const query = adminSearch.value.toLowerCase();
                const role = adminRoleFilter.value.toLowerCase();
                
                itemRows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const rowRole = row.getAttribute('data-rol').toLowerCase();
                    
                    // Comprobamos correspondencia por texto del buscador
                    const matchesSearch = text.includes(query);
                    // Comprobamos agrupación de roles (Unificamos barberos, estilistas bajo el filtro 'trabajador')
                    const matchesRole = (role === 'todos' || rowRole === role || (role === 'trabajador' && (rowRole === 'barbero' || rowRole === 'estilista' || rowRole === 'trabajador')));
                    
                    // Mostramos u ocultamos la fila según las banderas de coincidencia
                    row.style.display = (matchesSearch && matchesRole) ? '' : 'none';
                });
            }
            
            // Escuchadores de eventos para recalcular la grilla en tiempo real
            adminSearch.addEventListener('input', realizarFiltro);
            adminRoleFilter.addEventListener('change', realizarFiltro);
            
            // Forzamos una ejecución inicial preventiva para consolidar los estados visuales
            realizarFiltro();
        </script>
    <?php endif; ?>

</body>
</html>