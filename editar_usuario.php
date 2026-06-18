<?php
session_start();
require_once 'conexion.php';

// SEGURIDAD: Solo administradores
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'Administrador') {
    header('Location: index.php');
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: admin_usuarios.php');
    exit;
}

$id_editar = $_GET['id'];
$error = null;

// Procesar actualización cuando se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $correo   = trim($_POST['correo']);
    $telefono = trim($_POST['telefono']);
    $rol      = $_POST['rol'];

    if (!empty($nombre) && !empty($apellido) && !empty($correo)) {
        try {
            $sql = "UPDATE usuario SET nombre = ?, apellido = ?, correo = ?, telefono = ?, rol = ? WHERE id_usuario = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $apellido, $correo, $telefono, $rol, $id_editar]);
            
            header('Location: admin_usuarios.php?msg=updated');
            exit;
        } catch (PDOException $e) {
            $error = "Error al actualizar en la base de datos: " . $e->getMessage();
        }
    } else {
        $error = "Por favor, complete todos los campos obligatorios.";
    }
}

// Cargar información actual del registro
try {
    $stmt = $pdo->prepare("SELECT * FROM usuario WHERE id_usuario = ?");
    $stmt->execute([$id_editar]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barber House - Editar Privilegios</title>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600&family=Sawarabi+Mincho&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #29030E; font-family: 'Instrument Sans', sans-serif; color: #FFEED5; min-height: 100vh; display: flex; flex-direction: column; }
        
        .navbar { background-color: #420516; width: 100%; height: 109px; display: flex; align-items: center; padding: 0 50px; border-bottom: 1px solid rgba(237, 196, 132, 0.1); }
        .nav-logo img { height: 65px; }

        .edit-card-wrapper { width: 650px; max-width: 90%; margin: auto; background-color: #FCF6ED; border-radius: 20px; padding: 45px; color: #29030E; box-shadow: 0 15px 40px rgba(0,0,0,0.4); }
        h2 { font-family: 'Sawarabi Mincho', serif; font-size: 30px; color: #52131E; margin-bottom: 25px; font-weight: 400; }
        
        label { font-size: 12px; font-weight: 600; display: block; margin-top: 18px; margin-bottom: 6px; color: #52131E; text-transform: uppercase; letter-spacing: 0.5px; }
        input, select { width: 100%; height: 46px; border: 1px solid rgba(82, 19, 30, 0.3); border-radius: 8px; padding: 0 15px; font-size: 15px; color: #29030E; background-color: #FFFFFF; outline: none; }
        input[readonly] { background-color: #F0ECE6; color: #777; cursor: not-allowed; border: 1px solid #D1CAbF; }
        
        .grid-inputs { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        
        .action-row { display: flex; justify-content: flex-end; gap: 15px; margin-top: 35px; }
        .btn-form { padding: 12px 28px; border-radius: 8px; font-weight: 600; font-size: 14px; text-transform: uppercase; cursor: pointer; text-decoration: none; text-align: center; }
        .btn-back { background-color: transparent; color: #52131E; border: 1px solid #52131E; }
        .btn-save-user { background-color: #52131E; color: #FFEED5; border: none; transition: background 0.3s; }
        .btn-save-user:hover { background-color: #29030E; }
        .err-banner { padding: 12px; background-color: #FCE8E6; color: #A82424; border-radius: 6px; font-size: 14px; margin-bottom: 15px; font-weight: 500; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-logo"><img src="imagenes/logo.png" alt="Barber House"></div>
    </nav>

    <div class="edit-card-wrapper">
        <h2>Modificar Privilegios</h2>
        
        <?php if($error): ?>
            <div class="err-banner"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="grid-inputs">
                <div>
                    <label>Nombres *</label>
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($user['nombre']); ?>" required>
                </div>
                <div>
                    <label>Apellidos *</label>
                    <input type="text" name="apellido" value="<?php echo htmlspecialchars($user['apellido']); ?>" required>
                </div>
            </div>

            <div class="grid-inputs">
                <div>
                    <label>Cédula (Fija)</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['cedula']); ?>" readonly>
                </div>
                <div>
                    <label>Teléfono</label>
                    <input type="text" name="telefono" value="<?php echo htmlspecialchars($user['telefono']); ?>">
                </div>
            </div>

            <label>Correo Electrónico *</label>
            <input type="email" name="correo" value="<?php echo htmlspecialchars($user['correo']); ?>" required>

            <label>Rol Asignado en el Sistema *</label>
            <select name="rol" required>
                <option value="Cliente" <?php echo ($user['rol'] === 'Cliente') ? 'selected' : ''; ?>>Cliente</option>
                <option value="Trabajador" <?php echo ($user['rol'] === 'Trabajador' || $user['rol'] === 'Barbero') ? 'selected' : ''; ?>>Trabajador / Barbero</option>
                <option value="Administrador" <?php echo ($user['rol'] === 'Administrador') ? 'selected' : ''; ?>>Administrador</option>
            </select>

            <div class="action-row">
                <a href="admin_usuarios.php" class="btn-form btn-back">Cancelar</a>
                <button type="submit" class="btn-form btn-save-user">Actualizar Usuario</button>
            </div>
        </form>
    </div>

</body>
</html>