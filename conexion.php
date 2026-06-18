<?php
/**
 * ARCHIVO: conexion.php
 * DESCRIPCIÓN: 
 * Este componente es el corazón de la comunicación con la base de datos de Barber House. 
 * Configura la conexión segura utilizando la interfaz PDO (PHP Data Objects). 
 * Centraliza las credenciales del servidor local (como el puerto específico de MySQL en XAMPP), 
 * define políticas estrictas para el manejo de errores, fuerza el set de caracteres UTF-8 
 * y desactiva la emulación de consultas preparadas para mitigar riesgos de inyección SQL.
 */

// Parámetros de configuración del entorno de base de datos local
$host = "localhost;port=3307"; /* <-- Ajustar si tu instancia de XAMPP / MariaDB corre en otro puerto (ej. 3306) */
$db   = "barber_house";
$user = "root";       
$pass = ""; // Por defecto en entornos locales de XAMPP la contraseña del administrador va vacía
$charset = "utf8mb4"; // Garantiza el soporte correcto para tildes, eñes y caracteres especiales

// Construimos el DSN (Data Source Name) con la estructura que exige PDO para MySQL
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Opciones avanzadas de configuración para el comportamiento de la conexión PDO
$options = [
    // Activamos las excepciones para que cualquier fallo en las queries salte al bloque 'catch' de inmediato
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    
    // Configuramos el modo de mapeo por defecto para recibir los datos como arrays asociativos limpios (llave => valor)
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    
    // Desactivamos la emulación de sentencias preparadas para forzar el uso de consultas preparadas reales del motor de BD
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     // Intentamos instanciar el objeto PDO para abrir el canal de comunicación con la base de datos
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // Si la conexión falla (credenciales erróneas, servidor apagado, etc.), capturamos el error y relanzamos la excepción
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
