<?php
/**
 * ARCHIVO: login.php
 * DESCRIPCIÓN: 
 * Este script es el motor lógico de autenticación de Barber House. Procesa las 
 * peticiones de inicio de sesión mediante el método POST, valida que las cuentas 
 * estén en estado 'Activo', busca dinámicamente la columna de la contraseña dentro 
 * del registro devuelto y realiza una redirección inteligente unificada (enrutamiento) 
 * hacia las distintas interfaces del sistema basándose en el rol del usuario.
 */

// Iniciamos la sesión para poder almacenar los datos del usuario autenticado
session_start();
// Importamos la conexión centralizada a la base de datos
require_once 'conexion.php';

// Evaluamos si el formulario fue enviado a través del método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizamos los datos de entrada eliminando espacios en blanco accidentales
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validamos que ninguno de los dos campos obligatorios haya llegado vacío
    if (!empty($email) && !empty($password)) {
        
        try {
            // 1. Buscamos el registro del usuario utilizando su correo mediante una consulta preparada
            $stmt = $pdo->prepare('SELECT * FROM usuario WHERE correo = ?');
            $stmt->execute([$email]);
            $userObj = $stmt->fetch();

            // Si encontramos una coincidencia con ese correo en la base de datos, avanzamos
            if ($userObj) {
                // Filtro de seguridad: Verificamos si la cuenta está dada de baja o suspendida
                if (isset($userObj['estado']) && $userObj['estado'] !== 'Activo') {
                    $error = "Tu cuenta se encuentra inactiva. Contacta al administrador.";
                } else {
                    
                    // 2. Mapeo Dinámico: Recorremos las columnas del registro para identificar cuál almacena la contraseña.
                    // Esto evita errores si la base de datos cambia el nombre de la columna a 'password', 'clave' o 'contrasena'.
                    $db_password = null;
                    foreach ($userObj as $key => $value) {
                        if (strpos($key, 'contra') === 0 || $key === 'password' || $key === 'clave') {
                            $db_password = $value;
                            break;
                        }
                    }

                    // 3. Validación de credenciales en texto plano
                    if ($db_password !== null && $db_password === $password) {
                        
                        // Si la clave coincide, inicializamos las variables globales de la sesión real
                        $_SESSION['user_id']   = $userObj['id_usuario'];
                        $_SESSION['user_name'] = $userObj['nombre'];
                        $_SESSION['user_last'] = $userObj['apellido'] ?? '';
                        $_SESSION['user_rol']  = $userObj['rol']; 

                        // 4. Enrutamiento inteligente y unificado según el nivel de privilegios.
                        // Aplicamos trim y strtolower para evitar fallas por mayúsculas o espacios en la BD.
                        $rol_redir = strtolower(trim($userObj['rol']));

                        switch ($rol_redir) {
                            case 'cliente':
                                // Los clientes van directo al catálogo de servicios y bienvenida pública
                                header('Location: inicio.php');
                                exit;
                                
                            case 'barbero':
                            case 'estilista':
                            case 'manicurista':
                            case 'maquillador':
                            case 'trabajador':
                                // El personal del staff va directo a su agenda y panel operativo
                                header('Location: perfil_trabajador.php');
                                exit;
                                
                            case 'administrador':
                            case 'admin':
                            default:
                                // Los administradores o roles no especificados van a la vista general de perfiles
                                header('Location: perfil.php');
                                exit;
                        }

                    } else {
                        // Mensaje genérico de seguridad para no dar pistas a atacantes sobre qué campo falló
                        $error = "El correo electrónico o la contraseña son incorrectos.";
                    }
                }
            } else {
                $error = "El correo electrónico o la contraseña son incorrectos.";
            }
        } catch (PDOException $e) {
            // Capturamos fallos del motor de BD y guardamos el log para depuración técnica
            $error = "Error en la base de datos: " . $e->getMessage();
        }

    } else {
        $error = "Por favor, completa todos los campos obligatorios.";
    }
}
?>
