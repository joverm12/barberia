<?php
/**
 * ARCHIVO: admin_usuarios.php
 * DESCRIPCIÓN: 
 * Panel de administración para la gestión de usuarios y roles dentro del sistema.
 * Este script valida que únicamente los usuarios con rol de 'Administrador' tengan 
 * acceso. Recupera la lista completa de usuarios registrados y expone herramientas 
 * visuales premium para buscar, filtrar por rol, editar o dar de baja registros de forma dinámica.
 */

// Iniciamos la sesión para evaluar quién está intentando ingresar
session_start();
// Conectamos a la base de datos
require_once 'conexion.php';

// Filtro estricto de seguridad: Si no hay sesión o el rol no es Administrador, lo sacamos de aquí
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'Administrador') {
    header('Location: index.php');
    exit;
}

// Tomamos el nombre del administrador para personalizar la bienvenida (por si acaso, dejamos un valor por defecto)
$nombre_admin = $_SESSION['user_name'] ?? 'Administrador';

// Intentamos traer la lista completa de usuarios ordenados desde el más reciente
try {
    $stmt = $pdo->query("SELECT id_usuario, nombre, apellido, cedula, correo, telefono, rol FROM usuario ORDER BY id_usuario DESC");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si algo falla con la base de datos, vaciamos el array para evitar que el HTML rompa al renderizar
    $usuarios = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barber House - Administración de Roles</title>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600&family=Sawarabi+Mincho&display=swap" rel="stylesheet">
    <style>
        /* Reseteo general de márgenes y caja del documento */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #29030E; font-family: 'Instrument Sans', sans-serif; color: #FFEED5; overflow-x: hidden; }

        /* Estilos del Navbar Superior (Basado en los requerimientos de diseño de Figma) */
        .admin-navbar {
            background-color: #420516;
            width: 100%;
            height: 109px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 50px;
            border-bottom: 1px solid rgba(237, 196, 132, 0.1);
        }
        .nav-brand-zone { display: flex; align-items: center; gap: 20px; }
        .nav-logo img { width: 180px; height: auto; object-fit: contain; }
        .nav-title { font-family: 'Sawarabi Mincho', serif; font-size: 32px; color: #FFFFFF; font-style: italic; }

        .nav-user-controls { display: flex; align-items: center; gap: 25px; }
        .notif-bell { font-size: 22px; color: #EDC484; cursor: pointer; }
        
        .admin-dropdown-btn {
            background-color: #1A1211; border: 1px solid #EDC484; padding: 10px 20px; border-radius: 12px;
            color: #EDC484; font-size: 14px; display: flex; align-items: center; gap: 10px; cursor: pointer;
        }

        /* Estructura del contenedor principal */
        .main-admin-wrapper { width: 1400px; max-width: 95%; margin: 40px auto; padding-bottom: 60px; }

        /* Encabezado interno del panel */
        .title-panel { margin-bottom: 30px; }
        .title-panel h2 { font-family: 'Sawarabi Mincho', serif; font-size: 36px; color: #EDC484; font-weight: 400; }
        .title-panel p { font-size: 14px; opacity: 0.6; margin-top: 4px; }

        /* Zona de control para búsquedas y filtrados rápidos */
        .filters-row { display: flex; gap: 20px; margin-bottom: 30px; }
        
        .search-box-wrapper { position: relative; flex: 1; }
        .search-box-wrapper span { position: absolute; left: 20px; top: 15px; color: rgba(82, 19, 30, 0.5); font-size: 18px; }
        
        .input-search-admin { 
            width: 100%; height: 50px; background-color: #FCF6ED; border: none; border-radius: 25px; 
            padding: 0 25px 0 55px; font-size: 15px; color: #29030E; outline: none;
        }
        
        .select-filter-role { 
            width: 280px; height: 50px; background-color: #FCF6ED; border: none; border-radius: 25px; 
            padding: 0 25px; font-size: 15px; color: #29030E; outline: none; cursor: pointer;
        }

        /* Tarjeta contenedora de la grilla de datos */
        .table-container-card {
            background-color: #420516;
            border: 2px solid #52131E;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0px 10px 30px rgba(0,0,0,0.5);
        }

        .admin-table { width: 100%; border-collapse: collapse; text-align: left; }
        .admin-table th { 
            padding: 18px 15px; font-size: 13px; font-weight: 600; text-transform: uppercase; 
            color: rgba(255, 238, 213, 0.4); border-bottom: 1px solid rgba(255, 238, 213, 0.1); letter-spacing: 1px;
        }
        .admin-table td { padding: 20px 15px; font-size: 15px; border-bottom: 1px solid rgba(255, 238, 213, 0.05); vertical-align: middle; }
        .admin-table tr:last-child td { border-bottom: none; }

        .contact-cell div { font-size: 14px; opacity: 0.6; margin-top: 4px; }

        /* Estilos de Badges (Etiquetas de color para identificar roles ágilmente) */
        .role-badge { padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; display: inline-block; }
        .role-badge.cliente { background-color: rgba(234, 67, 53, 0.15); color: #FF8A80; }
        .role-badge.barbero { background-color: rgba(33, 150, 243, 0.15); color: #90CAF9; }
        .role-badge.administrador { background-color: rgba(237, 196, 132, 0.15); color: #EDC484; }

        .status-badge { background-color: rgba(76, 175, 80, 0.15); color: #A5D6A7; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; border: 1px solid rgba(76, 175, 80, 0.3); }

        /* Botones de acción (Editar y Eliminar) */
        .action-button { 
            width: 40px; height: 40px; border-radius: 50%; border: 1px solid rgba(237, 196, 132, 0.3); 
            background-color: transparent; color: #EDC484; font-size: 16px; cursor: pointer; 
            display: inline-flex; justify-content: center; align-items: center; text-decoration: none; transition: all 0.3s; margin-right: 8px;
        }
        .action-button:hover { background-color: #EDC484; color: #29030E; border-color: #EDC484; }
        .action-button.btn-delete-row:hover { background-color: #EA4335; color: white; border-color: #EA4335; }

        /* Alertas flotantes (Toasts de confirmación) */
        .alert-toast { padding: 15px 25px; background-color: #FCF6ED; color: #29030E; border-left: 5px solid #2E7D32; border-radius: 4px; margin-bottom: 25px; font-weight: 500; }
    </style>
</head>
<body>

    <nav class="admin-navbar">
        <div class="nav-brand-zone">
            <div class="nav-logo"><img src="imagenes/logo.png" alt="Barber House"></div>
            <div class="nav-title">Administración</div>
        </div>
        
        <div class="nav-user-controls">
            <span class="notif-bell">🔔</span>
            <div class="admin-dropdown-container">
                <button class="admin-dropdown-btn">
                    <span>Usuario: Admin</span>
                    <span style="font-size: 10px;">▲</span>
                </button>
            </div>
        </div>
    </nav>

    <div class="main-admin-wrapper">
        
        <div class="title-panel">
            <h2>Usuarios y Roles</h2>
            <p>Gestión de Roles y accesos globales al sistema</p>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
            <div class="alert-toast">✓ Los datos y privilegios del usuario han sido actualizados correctamente.</div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div class="alert-toast" style="border-left-color: #EA4335;">✕ El registro del usuario fue removido permanentemente del sistema.</div>
        <?php endif; ?>

        <div class="filters-row">
            <div class="search-box-wrapper">
                <span>🔍</span>
                <input type="text" class="input-search-admin" id="tableSearch" placeholder="Buscar usuario (Nombre, Cédula, Correo)...">
            </div>
            
            <select class="select-filter-role" id="roleFilter">
                <option value="todos">Todos los Roles</option>
                <option value="Cliente">Cliente</option>
                <option value="Trabajador">Trabajador</option>
                <option value="Administrador">Administrador</option>
            </select>
        </div>

        <div class="table-container-card">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">ID / Doc.</th>
                        <th style="width: 25%;">Usuario</th>
                        <th style="width: 25%;">Contacto</th>
                        <th style="width: 15%;">Rol Asignado</th>
                        <th style="width: 10%;">Estado</th>
                        <th style="width: 10%;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <?php if (count($usuarios) > 0): ?>
                        <?php foreach ($usuarios as $u): 
                            // Determinamos visualmente el estilo de la etiqueta basándonos en su rol asignado
                            $rol_actual = htmlspecialchars($u['rol']);
                            $clase_badge = 'cliente';
                            if ($rol_actual === 'Administrador') $clase_badge = 'administrador';
                            if ($rol_actual === 'Trabajador' || $rol_actual === 'Barbero') $clase_badge = 'barbero';
                        ?>
                            <tr class="user-row" data-rol="<?php echo $rol_actual; ?>">
                                <td>
                                    <span style="font-size: 13px; opacity: 0.5; display: block; margin-bottom: 2px;">#<?php echo $u['id_usuario']; ?></span>
                                    <strong><?php echo htmlspecialchars($u['cedula']); ?></strong>
                                </td>
                                
                                <td><strong style="font-size: 16px; color: #FFFFFF;"><?php echo htmlspecialchars($u['nombre'] . ' ' . $u['apellido']); ?></strong></td>
                                
                                <td class="contact-cell">
                                    <strong><?php echo htmlspecialchars($u['correo']); ?></strong>
                                    <div>📱 <?php echo htmlspecialchars($u['telefono'] ?? 'S/N'); ?></div>
                                </td>
                                
                                <td>
                                    <span class="role-badge <?php echo $clase_badge; ?>">
                                        <?php echo $rol_actual; ?>
                                    </span>
                                </td>
                                
                                <td><span class="status-badge">ACTIVO</span></td>
                                
                                <td>
                                    <a href="editar_usuario.php?id=<?php echo $u['id_usuario']; ?>" class="action-button" title="Editar privilegios">✏️</a>
                                    <a href="eliminar_usuario.php?id=<?php echo $u['id_usuario']; ?>" class="action-button btn-delete-row" title="Dar de baja" onclick="return confirm('¿Remover permanentemente a este usuario del sistema?');">🗑️</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; opacity: 0.5; font-style: italic; padding: 40px 0;">No se encontraron cuentas registradas en la base de datos.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <script>
        const tableSearch = document.getElementById('tableSearch');
        const roleFilter = document.getElementById('roleFilter');
        const userRows = document.querySelectorAll('.user-row');

        function filtrarTabla() {
            const searchText = tableSearch.value.toLowerCase();
            const selectedRole = roleFilter.value;

            userRows.forEach(row => {
                const textContent = row.textContent.toLowerCase();
                const rowRole = row.getAttribute('data-rol');

                // Validamos si la fila cumple simultáneamente con el texto buscado y el rol seleccionado
                const matchesSearch = textContent.includes(searchText);
                const matchesRole = (selectedRole === 'todos' || rowRole === selectedRole);

                // Mostramos u ocultamos la fila según corresponda
                if (matchesSearch && matchesRole) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Escuchamos la escritura en el buscador y el cambio de selector para ejecutar el filtro
        tableSearch.addEventListener('input', filtrarTabla);
        roleFilter.addEventListener('change', filtrarTabla);
    </script>
</body>
</html>
