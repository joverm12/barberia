<?php
session_start();

// 1. Destruimos todas las variables de sesión activas (ID, Nombre, Rol)
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 2. Destruimos la sesión por completo en el servidor
session_destroy();

// 3. Redireccionamos al login para que el usuario pueda volver a ingresar
header("Location: index.php");
exit;
?>