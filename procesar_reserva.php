<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id_usuario = $_SESSION['user_id'];
$stmtCli = $pdo->prepare("SELECT id_cliente FROM cliente WHERE id_usuario = ?");
$stmtCli->execute([$id_usuario]);
$cliente = $stmtCli->fetch();
$id_cliente = $cliente['id_cliente'] ?? 0;

$id_sucursal = $_POST['id_sucursal'];
$id_servicio = $_POST['id_servicio'];
$id_empleado = $_POST['id_empleado'];
$fecha       = $_POST['fecha'];
$hora        = $_POST['hora'];
$fecha_final = $fecha . ' ' . $hora;

$sql = "INSERT INTO cita (id_cliente, id_empleado, id_servicio, id_sucursal, hora, estado) VALUES (?, ?, ?, ?, ?, 'Pendiente')";
$stmtInsert = $pdo->prepare($sql);
$stmtInsert->execute([$id_cliente, $id_empleado, $id_servicio, $id_sucursal, $fecha_final]);
$id_cita_generada = $pdo->lastInsertId();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reserva Exitosa</title>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600&family=Sawarabi+Mincho&display=swap" rel="stylesheet">
    <style>
        body { background-color: #29030E; font-family: 'Instrument Sans', sans-serif; color: #FFEED5; min-height: 100vh; display: flex; justify-content: center; align-items: center; }
        .success-card { background-color: #231918; border: 1px solid #EDC484; border-radius: 20px; padding: 50px; text-align: center; width: 500px; }
        h1 { font-family: 'Sawarabi Mincho', serif; color: #EDC484; margin-bottom: 20px; }
        .btn { display: inline-block; width: 100%; height: 50px; line-height: 50px; border-radius: 8px; text-decoration: none; font-weight: 600; text-align: center; margin-bottom: 10px; }
        .btn-pdf { background-color: #2E7D32; color: white; }
        .btn-profile { border: 1px solid #EDC484; color: #EDC484; }
    </style>
</head>
<body>
    <div class="success-card">
        <h1>¡Cita Agendada!</h1>
        <p style="margin-bottom:30px;">Tu turno se guardó de forma correcta.</p>
        <a href="factura.php?id_cita=<?php echo $id_cita_generada; ?>" target="_blank" class="btn btn-pdf">📥 Descargar Factura Digital</a>
        <a href="perfil_cliente.php" class="btn btn-profile">👤 Ver Mis Citas</a>
    </div>
</body>
</html>