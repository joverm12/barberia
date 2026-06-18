<?php
session_start();
require_once 'conexion.php';

// SEGURIDAD: Control estricto de sesión
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'Administrador') {
    header('Location: index.php');
    exit;
}

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_eliminar = $_GET['id'];

    // Prevención: No auto-eliminarse de la sesión activa
    if ($id_eliminar == $_SESSION['user_id']) {
        die("Error de seguridad: No está permitido remover tu propia cuenta de Administrador mientras está en uso.");
    }

    try {
        // Ejecución relacional del borrado
        $stmt = $pdo->prepare("DELETE FROM usuario WHERE id_usuario = ?");
        $stmt->execute([$id_eliminar]);

        header('Location: admin_usuarios.php?msg=deleted');
        exit;
    } catch (PDOException $e) {
        die("Error crítico al procesar la baja en el sistema: " . $e->getMessage());
    }
} else {
    header('Location: admin_usuarios.php');
    exit;
}