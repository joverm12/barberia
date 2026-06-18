<?php
/**
 * ARCHIVO: cambiar_estado.php
 * DESCRIPCIÓN: 
 * Este script actúa como un controlador intermedio para actualizar el flujo de 
 * las citas en la barbería. Maneja la transición de los estados de una cita, 
 * permitiendo pasar a "EN PROCESO" (guardando notas u observaciones del modal) 
 * o a "FINALIZADA" cuando el servicio concluye. Al terminar la actualización, 
 * devuelve al barbero a su panel principal de forma transparente.
 */

// Iniciamos la sesión para comprobar la autenticidad del usuario conectado
session_start();
// Traemos la conexión a la base de datos
require_once 'conexion.php';

// Seguridad básica: Si el usuario no ha iniciado sesión, lo rebotamos al index
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Capturamos y sanitizamos las variables que viajan por la URL (método GET)
// Usamos intval para asegurar que el ID de la cita sea un número entero válido
$id_cita = isset($_GET['id']) ? intval($_GET['get_id'] ?? $_GET['id']) : 0;
$accion  = isset($_GET['nuevo']) ? trim($_GET['nuevo']) : '';

// Capturamos los comentarios, requerimientos especiales o notas reales que el barbero escribe en el modal
$observaciones = isset($_GET['observaciones']) ? trim($_GET['observaciones']) : null;

// Validamos que contemos con una cita concreta y una acción definida para proceder
if ($id_cita > 0 && !empty($accion)) {
    try {
        
        // CASO 1: El barbero inicia la atención del cliente (La cita cambia a 'EN PROCESO')
        if ($accion === 'proceso') {
            $nuevo_estado = 'EN PROCESO';
            
            // Actualizamos el estado y guardamos las observaciones/detalles del corte capturados en el modal
            $sql = "UPDATE cita SET estado = ?, observaciones = ? WHERE id_cita = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nuevo_estado, $observaciones, $id_cita]);
        } 
        
        // CASO 2: El barbero termina el servicio (La cita cambia a 'FINALIZADA')
        elseif ($accion === 'finalizar') {
            $nuevo_estado = 'FINALIZADA';
            
            // Aquí solo modificamos el estado, ya que las notas se guardaron en el paso anterior
            $sql = "UPDATE cita SET estado = ? WHERE id_cita = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nuevo_estado, $id_cita]);
        }

    } catch (PDOException $e) {
        // En caso de fallar la base de datos, detenemos el flujo y exponemos el error para agilizar el desarrollo
        die("Error al actualizar el estado: " . $e->getMessage());
    }
}

// Redireccionamos de inmediato al perfil del barbero para que visualice el cambio de estado en tiempo real
header('Location: perfil_barbero.php');
exit;
?>
