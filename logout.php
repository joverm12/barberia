<?php
/**
 * ARCHIVO: logout.php
 * DESCRIPCIÓN: 
 * Este script es el encargado de gestionar el cierre de sesión seguro en Barber House. 
 * Su función es destruir de forma absoluta cualquier rastro de la sesión activa, 
 * limpiando las variables globales del servidor, invalidando y borrando la cookie 
 * de rastreo en el navegador del cliente, y eliminando el archivo de sesión. 
 * Finalmente, redirige al usuario de vuelta a la pantalla de acceso.
 */

// Iniciamos o retomamos la sesión actual para poder destruirla formalmente
session_start();

// 1. Vaciamos por completo el array global de sesión, eliminando datos confidenciales (ID, Nombre, Rol)
$_SESSION = array();

// 2. Limpieza de Cookies en el Cliente: Si la sesión utiliza cookies para rastrear el ID (comportamiento por defecto),
// alteramos sus propiedades para que expiren de inmediato en el navegador del usuario.
if (ini_get("session.use_cookies")) {
    // Recuperamos los parámetros de configuración actuales de la cookie (ruta, dominio, seguridad)
    $params = session_get_cookie_params();
    
    // Sobreescribimos la cookie con un valor vacío y una fecha de expiración en el pasado (time() - 42000)
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destruimos de forma definitiva el archivo y el registro de la sesión alojado en el servidor
session_destroy();

// 4. Redireccionamos de inmediato a la interfaz de login para dejar el sistema listo para un nuevo ingreso
header("Location: index.php");
exit;
?>
