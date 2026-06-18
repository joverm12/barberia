<?php
/**
 * ARCHIVO: editar_usuario.php
 * DESCRIPCIÓN: 
 * Este script provee la interfaz y la lógica para que el Administrador modifique 
 * los datos personales y altere los privilegios (roles) de cualquier usuario 
 * en el sistema. Cuenta con validaciones estrictas por GET para cargar el perfil 
 * correcto, protege datos clave volviendo la cédula un campo de solo lectura, 
 * y retorna confirmaciones de éxito al panel de control centralizado.
 */

// Iniciamos la sesión para comprobar el acceso del usuario
session_start();
// Importamos el puente de conexión a la base de datos
require_once 'conexion.php';

// FILTRO DE SEGURIDAD: Solo permitimos el acceso si el rol activo es 'Administrador'
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'Administrador') {
    header('Location: index.php');
    exit;
}

// Validación de parámetro: Si no nos mandan un ID válido por la URL, devolvemos al panel principal
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: admin_usuarios.php');
    exit;
}

// Almacenamos el ID recibido para saber qué cuenta vamos a intervenir
$id_editar = $_GET['id'];
$error = null; // Variable bandera para acumular mensajes de error en los formularios

// PROCESAMIENTO DEL FORMULARIO (POST): Se dispara cuando el admin da clic en "Actualizar"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibimos y limpiamos los textos del formulario
    $nombre   = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $correo   = trim($_POST['correo']);
    $telefono = trim($_POST['telefono']);
    $rol      = $_POST['rol']; // Captura el nuevo nivel de acceso seleccionado

    // Verificamos que los campos esenciales no estén en blanco
    if (!empty($nombre) && !empty($apellido) && !empty($correo)) {
        try {
            // Ejecutamos la actualización mediante una consulta segura preparada
            $sql = "UPDATE usuario SET nombre = ?, apellido = ?, correo = ?, telefono = ?, rol = ? WHERE id_usuario = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $apellido, $correo, $telefono, $rol, $id_editar]);
            
            // Si todo sale bien, volvemos al panel mandando una señal de éxito por la URL
            header('Location: admin_usuarios.php?msg=updated');
            exit;
        } catch (PDOException $e) {
            // Capturamos cualquier conflicto con la base de datos (como un correo duplicado)
            $error = "Error al actualizar en la base de datos: " . $e->getMessage();
        }
    } else {
        $error = "Por favor, complete todos los campos obligatorios.";
    }
}

// CARGA INICIAL DE DATOS: Traemos la foto actual del registro para pintar los inputs del formulario
try {
    $stmt = $pdo->prepare("SELECT * FROM usuario WHERE id_usuario = ?");
    $stmt->execute([$id_editar]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si el ID de la URL no coincide con ningún usuario real, lo sacamos de aquí
    if (!$user) {
        header('Location: admin_usuarios.php');
        exit;
    }
} catch (PDOException $e) {
    header('Location: admin_usuarios.php');
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
        button { background: #EDC484; border: none; padding: 10px 20px; cursor: pointer; width: 100%; font-weight: bold; }
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
            <input type="text" name="telefono" value="<?php echo htmlspecialchars($user['telefono']); ?>">
            <select name="rol">
                <option value="Cliente" <?php if($user['rol'] == 'Cliente') echo 'selected'; ?>>Cliente</option>
                <option value="Trabajador" <?php if($user['rol'] == 'Trabajador') echo 'selected'; ?>>Trabajador</option>
                <option value="Administrador" <?php if($user['rol'] == 'Administrador') echo 'selected'; ?>>Administrador</option>
            </select>
            <button type="submit">Actualizar Usuario</button>
        </form>
    </div>
</body>
</html>