<?php
/**
 * ARCHIVO: crear_usuario.php
 * DESCRIPCIÓN:
 * Módulo de registro administrativo para la inserción de nuevos usuarios.
 * Implementa validación proactiva contra registros duplicados mediante el correo electrónico.
 */

session_start();
require_once 'conexion.php';

// SEGURIDAD: Validación de acceso administrativo (Solo administradores autorizados)
if (!isset($_SESSION['user_id']) || strtolower(trim($_SESSION['user_rol'])) !== 'administrador') {
    header('Location: index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $correo   = trim($_POST['correo']);
    $password = trim($_POST['password']);
    $telefono = trim($_POST['telefono']); 
    
    // LÓGICA DE PERSISTENCIA: 
    // Si el usuario elige 'Trabajador' en el primer select, tomamos el valor del segundo select.
    // Si elige 'Cliente' o 'Administrador', tomamos ese valor directamente.
    $rol_seleccionado = $_POST['rol'];
    $rol_final = ($rol_seleccionado === 'Trabajador') ? $_POST['especialidad_trabajador'] : $rol_seleccionado;
    
    $estado   = 'Activo';

    if (!empty($nombre) && !empty($correo) && !empty($password)) {
        try {
            /**
             * VALIDACIÓN DE INTEGRIDAD: 
             * Verificamos la inexistencia previa del correo para evitar conflictos de clave única.
             */
            $check = $pdo->prepare("SELECT id_usuario FROM usuario WHERE correo = ?");
            $check->execute([$correo]);
            
            if ($check->rowCount() > 0) {
                $error = "Error: El correo electrónico ya existe.";
            } else {
                // Insertamos el $rol_final (ej: 'Barbero' o 'Estilista') en la columna 'rol'
                $sql = "INSERT INTO usuario (nombre, apellido, correo, contrasenia, rol, estado, telefono) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nombre, $apellido, $correo, $password, $rol_final, $estado, $telefono]);
                
                // REDIRECCIÓN: Retorno al dashboard administrativo con señal de éxito
                header('Location: perfil.php?msg=created');
                exit;
            }
        } catch (PDOException $e) {
            $error = "Error al persistir: " . $e->getMessage();
        }
    } else {
        $error = "Campos obligatorios incompletos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Barber House - Registrar Usuario</title>
    <style>
        body { background-color: #52131E; color: #FFEED5; font-family: sans-serif; padding: 40px; }
        .form-card { background: #380A14; padding: 30px; border-radius: 15px; max-width: 400px; margin: auto; border: 1px solid #EDC484; }
        input, select { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: none; }
        button { width: 100%; padding: 12px; background: #EDC484; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        #container-especialidad { display: none; }
    </style>
</head>
<body>
    <div class="form-card">
        <h2>Nuevo Usuario</h2>
        <?php if ($error) echo "<p style='color:#FF8A80;'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="nombre" placeholder="Nombre" required>
            <input type="text" name="apellido" placeholder="Apellido" required>
            <input type="email" name="correo" placeholder="Correo" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <input type="text" name="telefono" placeholder="Teléfono">
            
            <select name="rol" id="rolSelect" onchange="toggleEspecialidad()">
                <option value="Cliente">Cliente</option>
                <option value="Trabajador">Trabajador</option>
                <option value="Administrador">Administrador</option>
            </select>

            <div id="container-especialidad">
                <select name="especialidad_trabajador">
                    <?php
                    // Automatización: Consulta los roles de trabajador existentes en la tabla
                    $stmtRoles = $pdo->query("SELECT DISTINCT rol FROM usuario WHERE rol NOT IN ('Cliente', 'Administrador') AND rol IS NOT NULL");
                    while ($row = $stmtRoles->fetch(PDO::FETCH_ASSOC)) {
                        echo "<option value='" . htmlspecialchars($row['rol']) . "'>" . htmlspecialchars($row['rol']) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <button type="submit">Guardar Registro</button>
        </form>
    </div>

    <script>
        function toggleEspecialidad() {
            const rolSelect = document.getElementById("rolSelect").value;
            const container = document.getElementById("container-especialidad");
            container.style.display = (rolSelect === "Trabajador") ? "block" : "none";
        }
    </script>
</body>
</html>