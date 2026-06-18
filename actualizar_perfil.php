<?php
session_start();
require_once 'conexion.php';

// Validar que la petición venga por método POST y con sesión válida
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id_usuario = $_SESSION['user_id'];

// Sanitizar y recibir los valores del formulario
$nombre   = trim($_POST['nombre']);
$apellido = trim($_POST['apellido']);
$telefono = trim($_POST['telefono']);
$correo   = trim($_POST['correo']);

if (!empty($nombre) && !empty($apellido) && !empty($correo)) {
    try {
        // Ejecutar sentencia preparada de actualización SQL
        $sql = "UPDATE usuario SET nombre = ?, apellido = ?, telefono = ?, correo = ? WHERE id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $apellido, $telefono, $correo, $id_usuario]);
        
        // Actualizar variables globales de sesión para mantener sincronía inmediata
        $_SESSION['user_name'] = $nombre;
        $_SESSION['user_last'] = $apellido;

        // Redirigir de vuelta mostrando el mensaje de éxito
        header('Location: perfil_cliente.php?success=1');
        exit;
    } catch (PDOException $e) {
        // En caso de error, puedes imprimirlo para depuración o redirigir
        die("Error al actualizar los datos en el servidor: " . $e->getMessage());
    }
} else {
    header('Location: perfil_cliente.php');
    exit;
}