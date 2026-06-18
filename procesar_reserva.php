<?php
/**
 * ARCHIVO: procesar_reserva.php
 * DESCRIPCIÓN: 
 * Este script actúa como el controlador encargado de procesar y registrar las nuevas 
 * citas en la base de datos de Barber House. Valida la sesión y el método de envío, 
 * recupera el identificador relacional del cliente, concatena los campos de fecha y hora, 
 * y ejecuta la inserción segura. Al finalizar de forma exitosa, captura el ID generado 
 * en la base de datos para enlazar dinámicamente la descarga de la factura digital.
 */

// Iniciamos la sesión para comprobar la validez del usuario logeado
session_start();
// Importamos la conexión centralizada a la base de datos
require_once 'conexion.php';

// FILTRO DE SEGURIDAD: Bloqueamos el acceso si el usuario no ha iniciado sesión o si intentan entrar sin usar POST
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Recuperamos el ID de usuario base en sesión para encontrar su rol en la tabla intermedia
$id_usuario = $_SESSION['user_id'];

// 1. Buscamos el id_cliente asociado al usuario logeado para mantener la integridad referencial
$stmtCli = $pdo->prepare("SELECT id_cliente FROM cliente WHERE id_usuario = ?");
$stmtCli->execute([$id_usuario]);
$cliente = $stmtCli->fetch();
$id_cliente = $cliente['id_cliente'] ?? 0;

// Capturamos las variables enviadas de forma segura desde el formulario de reserva
$id_sucursal = $_POST['id_sucursal'];
$id_servicio = $_POST['id_servicio'];
$id_empleado = $_POST['id_empleado'];
$fecha       = $_POST['fecha'];
$hora        = $_POST['hora'];

// Concatenamos la fecha y hora para estructurar la cadena compatible con el formato DATETIME de SQL
$fecha_final = $fecha . ' ' . $hora;

// 2. Preparamos e insertamos la nueva cita en el sistema estableciendo el estado inicial por defecto
$sql = "INSERT INTO cita (id_cliente, id_empleado, id_servicio, id_sucursal, hora, estado) VALUES (?, ?, ?, ?, ?, 'Pendiente')";
$stmtInsert = $pdo->prepare($sql);
$stmtInsert->execute([$id_cliente, $id_empleado, $id_servicio, $id_sucursal, $fecha_final]);

// 3. Capturamos el último ID autoincremental generado para pasarlo como parámetro a la factura digital
$id_cita_generada = $pdo->lastInsertId();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reserva Exitosa</title>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600&family=Sawarabi+Mincho&display=swap" rel="stylesheet">
    <style>
        /* Estilos visuales de la tarjeta de confirmación fiel a la paleta de Barber House */
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
