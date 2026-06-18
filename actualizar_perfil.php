<?php
/**
 * ARCHIVO: actualizar_perfil.php
 * DESCRIPCIÓN: 
 * Este script se encarga de procesar y actualizar los datos personales del cliente 
 * (nombre, apellido, teléfono y correo) desde el formulario de perfil. Cuenta con 
 * filtros de seguridad para asegurar que el usuario esté logueado, valida los campos 
 * obligatorios y actualiza las variables de sesión para que los cambios se visualicen 
 * inmediatamente en toda la plataforma.
 */

// Iniciamos la sesión para poder acceder a los datos del usuario logueado
session_start();
// Importamos la configuración de la base de datos
require_once 'conexion.php';

// Filtro de seguridad: Bloqueamos el acceso si el usuario no ha iniciado sesión o si intentan entrar sin usar POST
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Recuperamos el ID del usuario en sesión para saber a quién vamos a actualizar
$id_usuario = $_SESSION['user_id'];

// Recibimos los datos del formulario y limpiamos los espacios en blanco innecesarios en los extremos
$nombre   = trim($_POST['nombre']);
$apellido = trim($_POST['apellido']);
$telefono = trim($_POST['telefono']);
$correo   = trim($_POST['correo']);

// Validación básica: Nos aseguramos de que los campos obligatorios no vengan vacíos
if (!empty($nombre) && !empty($apellido) && !empty($correo)) {
    try {
        // Preparamos la consulta SQL para evitar inyecciones de código. Actualizamos según el ID del usuario.
        $sql = "UPDATE usuario SET nombre = ?, apellido = ?, telefono = ?, correo = ? WHERE id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $apellido, $telefono, $correo, $id_usuario]);
        
        // Sincronizamos las variables de sesión para que los cambios se reflejen de inmediato en la interfaz (como en el navbar)
        $_SESSION['user_name'] = $nombre;
        $_SESSION['user_last'] = $apellido;

        // Si todo sale bien, lo mandamos de vuelta a su perfil con una señal de éxito en la URL
        header('Location: perfil_cliente.php?success=1');
        exit;
    } catch (PDOException $e) {
        // Si la base de datos falla, frenamos el proceso y mostramos el error (útil para desarrollo)
        die("Error al actualizar los datos en el servidor: " . $e->getMessage());
    }
} else {
    // Si falta algún campo obligatorio, lo regresamos al perfil sin aplicar cambios
    header('Location: perfil_cliente.php');
    exit;
}
