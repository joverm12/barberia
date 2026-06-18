<?php
session_start();
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        
        try {
            // 1. Traer todo el registro para evitar conflictos con el nombre de la columna contraseña
            $stmt = $pdo->prepare('SELECT * FROM usuario WHERE correo = ?');
            $stmt->execute([$email]);
            $userObj = $stmt->fetch();

            if ($userObj) {
                // Verificamos si el usuario está activo
                if (isset($userObj['estado']) && $userObj['estado'] !== 'Activo') {
                    $error = "Tu cuenta se encuentra inactiva. Contacta al administrador.";
                } else {
                    
                    // 2. Buscamos de forma dinámica el campo de la contraseña
                    $db_password = null;
                    foreach ($userObj as $key => $value) {
                        if (strpos($key, 'contra') === 0 || $key === 'password' || $key === 'clave') {
                            $db_password = $value;
                            break;
                        }
                    }

                    // 3. Validamos la contraseña en texto plano
                    if ($db_password !== null && $db_password === $password) {
                        
                        // Guardamos las variables de sesión reales
                        $_SESSION['user_id']   = $userObj['id_usuario'];
                        $_SESSION['user_name'] = $userObj['nombre'];
                        $_SESSION['user_last'] = $userObj['apellido'] ?? '';
                        $_SESSION['user_rol']  = $userObj['rol']; 

                        // 4. Redirección inteligente unificada a los archivos reales
                        // Usamos trim y strtolower para evitar fallos por espacios o mayúsculas en la BD
                        $rol_redir = strtolower(trim($userObj['rol']));

                        switch ($rol_redir) {
                            case 'cliente':
                                header('Location: inicio.php');
                                exit;
                                
                            case 'barbero':
                            case 'estilista':
                            case 'manicurista':
                            case 'maquillador':
                            case 'trabajador':
                                header('Location: perfil_trabajador.php');
                                exit;
                                
                            case 'administrador':
                            case 'admin':
                                // Redirección corregida al archivo único de perfil
                                header('Location: perfil.php');
                                exit;
                                
                            default:
                                header('Location: perfil.php');
                                exit;
                        }

                    } else {
                        $error = "El correo electrónico o la contraseña son incorrectos.";
                    }
                }
            } else {
                $error = "El correo electrónico o la contraseña son incorrectos.";
            }
        } catch (PDOException $e) {
            $error = "Error en la base de datos: " . $e->getMessage();
        }

    } else {
        $error = "Por favor, completa todos los campos obligatorios.";
    }
}
?>