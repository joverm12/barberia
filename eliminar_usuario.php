<?php
/**
 * ARCHIVO: eliminar_usuario.php
 * DESCRIPCIÓN: 
 * Este script actúa como el controlador encargado de dar de baja o borrar 
 * de forma permanente un registro de usuario en la base de datos. 
 * Por motivos de seguridad, está restringido exclusivamente al Administrador, 
 * incluye una regla estricta para evitar que el administrador elimine su propia 
 * cuenta por accidente y maneja excepciones relacionales antes de retornar 
 * el control al panel general.
 */

// Iniciamos la sesión para evaluar los privilegios de quien ejecuta la acción
session_start();
// Importamos la configuración y el objeto de conexión PDO
require_once 'conexion.php';

// FILTRO DE SEGURIDAD: Solo el Administrador principal puede invocar este archivo
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'Administrador') {
    header('Location: index.php');
    exit;
}

// Verificamos que se haya enviado un ID válido e identificable a través de la URL (GET)
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_eliminar = $_GET['id'];

    // CONTROL DE PREVENCIÓN CRÍTICO: Evitamos que el Administrador en sesión se elimine a sí mismo
    if ($id_eliminar == $_SESSION['user_id']) {
        die("Error de seguridad: No está permitido remover tu propia cuenta de Administrador mientras está en uso.");
    }

    try {
        // Ejecutamos la sentencia preparada para borrar de forma directa el registro correspondiente
        $stmt = $pdo->prepare("DELETE FROM usuario WHERE id_usuario = ?");
        $stmt->execute([$id_eliminar]);

        // Si la operación es exitosa, redirigimos enviando la bandera 'deleted' para mostrar el toast de confirmación
        header('Location: admin_usuarios.php?msg=deleted');
        exit;
    } catch (PDOException $e) {
        // Si el usuario tiene citas amarradas (restricción de llave foránea), el catch frena el proceso de forma segura
        die("Error crítico al procesar la baja en el sistema: " . $e->getMessage());
    }
} else {
    // Si intentan entrar al archivo sin pasar un ID, los mandamos de regreso al panel
    header('Location: admin_usuarios.php');
    exit;
}
