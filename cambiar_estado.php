<?php
session_start();
require_once 'conexion.php';

// Seguridad básica: Verificar que esté logeado
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Capturamos las variables que viajan por el formulario (GET)
$id_cita = isset($_GET['id']) ? intval($_GET['get_id'] ?? $_GET['id']) : 0;
$accion  = isset($_GET['nuevo']) ? trim($_GET['nuevo']) : '';
// Capturamos la observación (la broma o notas reales) si viene del modal
$observaciones = isset($_GET['observaciones']) ? trim($_GET['observaciones']) : null;

if ($id_cita > 0 && !empty($accion)) {
    try {
        
        // 1. Si la acción es iniciar la atención (Pasa a EN PROCESO)
        if ($accion === 'proceso') {
            $nuevo_estado = 'EN PROCESO';
            
            // Actualizamos tanto el estado como las observaciones guardadas en el modal
            $sql = "UPDATE cita SET estado = ?, observaciones = ? WHERE id_cita = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nuevo_estado, $observaciones, $id_cita]);
        } 
        
        // 2. Si la acción es terminar el corte (Pasa a FINALIZADA)
        elseif ($accion === 'finalizar') {
            $nuevo_estado = 'FINALIZADA';
            
            $sql = "UPDATE cita SET estado = ? WHERE id_cita = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nuevo_estado, $id_cita]);
        }

    } catch (PDOException $e) {
        // Si hay un error, puedes manejarlo o imprimirlo para debuguear
        die("Error al actualizar el estado: " . $e->getMessage());
    }
}

// Redireccionamos de inmediato al perfil del barbero para ver el cambio en tiempo real
header('Location: perfil_barbero.php');
exit;
?>