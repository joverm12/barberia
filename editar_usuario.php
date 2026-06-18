<?php
/**
 * ARCHIVO: editar_usuario.php
 * DESCRIPCIÓN: 
 * Este script provee la interfaz y la lógica para que el Administrador modifique 
 * los datos personales, roles y contraseñas de cualquier usuario.
 */

// Iniciamos la sesión para comprobar el acceso del usuario
session_start();
// Importamos el puente de conexión a la base de datos
require_once 'conexion.php';

// FILTRO DE SEGURIDAD: Solo permitimos el acceso si el rol activo es 'Administrador'
if (!isset($_SESSION['user_id']) || strtolower(trim($_SESSION['user_rol'])) !== 'administrador') {
    header('Location: index.php');
    exit;
}

// Validación de parámetro: Si no nos mandan un ID válido por la URL, devolvemos al panel principal
$id_editar = $_GET['id'] ?? null;
if (!$id_editar) {
    header('Location: perfil.php');
    exit;
}

$error = null; // Variable bandera para acumular mensajes de error

// PROCESAMIENTO DEL FORMULARIO (POST): Se dispara cuando el admin da clic en "Actualizar"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibimos y limpiamos los textos del formulario
    $nombre   = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $correo   = trim($_POST['correo']);
    $telefono = trim($_POST['telefono']);
    $password = trim($_POST['password']); // Captura de nueva contraseña opcional
    
    // Lógica para capturar el rol real (especialidad si es trabajador)
    $rol_seleccionado = $_POST['rol'];
    $rol = ($rol_seleccionado === 'Trabajador') ? $_POST['especialidad_trabajador'] : $rol_seleccionado;

    // Verificamos que los campos esenciales no estén en blanco
    if (!empty($nombre) && !empty($apellido) && !empty($correo)) {
        try {
            // Ejecutamos la actualización mediante una consulta segura preparada
            if (!empty($password)) {
                // Si hay contraseña nueva, la actualizamos
                $sql = "UPDATE usuario SET nombre = ?, apellido = ?, correo = ?, telefono = ?, rol = ?, contrasenia = ? WHERE id_usuario = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nombre, $apellido, $correo, $telefono, $rol, $password, $id_editar]);
            } else {
                // Si no hay contraseña nueva, mantenemos la anterior
                $sql = "UPDATE usuario SET nombre = ?, apellido = ?, correo = ?, telefono = ?, rol = ? WHERE id_usuario = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nombre, $apellido, $correo, $telefono, $rol, $id_editar]);
            }
            
            // Si todo sale bien, volvemos al panel mandando una señal de éxito
            header('Location: perfil.php?msg=updated');
            exit;
        } catch (PDOException $e) {
            $error = "Error al actualizar en la base de datos: " . $e->getMessage();
        }
    } else {
        $error = "Por favor, complete todos los campos obligatorios.";
    }
}

// CARGA INICIAL DE DATOS: Traemos el registro para pintar los inputs del formulario
try {
    $stmt = $pdo->prepare("SELECT * FROM usuario WHERE id_usuario = ?");
    $stmt->execute([$id_editar]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Definimos si es trabajador para mostrar el contenedor de especialidad
    $esTrabajador = ($user['rol'] !== 'Cliente' && $user['rol'] !== 'Administrador');
    
    if (!$user) {
        header('Location: perfil.php');
        exit;
    }
} catch (PDOException $e) {
    header('Location: perfil.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Barber House - Editar Privilegios</title>
    <style>
        body { background-color: #29030E; font-family: sans-serif; color: #FFF; padding: 50px; }
        .form-container { background: #380A14; padding: 30px; border-radius: 15px; max-width: 500px; margin: auto; }
        input, select { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: none; }
        button { background: #EDC484; border: none; padding: 10px 20px; cursor: pointer; width: 100%; font-weight: bold; color: #29030E; }
        #container-especialidad { display: <?php echo $esTrabajador ? 'block' : 'none'; ?>; }
        small { color: #EDC484; display: block; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Editar Usuario: <?php echo htmlspecialchars($user['nombre']); ?></h2>
        <?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($user['nombre']); ?>" required>
            <input type="text" name="apellido" value="<?php echo htmlspecialchars($user['apellido']); ?>" required>
            <input type="email" name="correo" value="<?php echo htmlspecialchars($user['correo']); ?>" required>
            <input type="text" name="telefono" value="<?php echo htmlspecialchars($user['telefono'] ?? ''); ?>" placeholder="Teléfono">
            
            <input type="password" name="password" placeholder="Nueva contraseña (opcional)">
            <small>Dejar vacío para mantener la actual.</small>
            
            <select name="rol" id="rolSelect" onchange="toggleEspecialidad()">
                <option value="Cliente" <?php if($user['rol'] == 'Cliente') echo 'selected'; ?>>Cliente</option>
                <option value="Trabajador" <?php if($esTrabajador) echo 'selected'; ?>>Trabajador</option>
                <option value="Administrador" <?php if($user['rol'] == 'Administrador') echo 'selected'; ?>>Administrador</option>
            </select>

            <div id="container-especialidad">
                <select name="especialidad_trabajador">
                    <?php
                    // Automatización: Trae los roles de trabajador existentes en la BD
                    $stmtRoles = $pdo->query("SELECT DISTINCT rol FROM usuario WHERE rol NOT IN ('Cliente', 'Administrador') AND rol IS NOT NULL");
                    while ($row = $stmtRoles->fetch(PDO::FETCH_ASSOC)) {
                        $selected = ($user['rol'] == $row['rol']) ? 'selected' : '';
                        echo "<option value='".htmlspecialchars($row['rol'])."' $selected>".htmlspecialchars($row['rol'])."</option>";
                    }
                    ?>
                </select>
            </div>

            <button type="submit">Actualizar Usuario</button>
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