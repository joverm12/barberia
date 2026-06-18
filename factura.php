<?php
/**
 * ARCHIVO: factura.php
 * DESCRIPCIÓN: 
 * Este script genera el comprobante y la vista de la Factura Digital para el cliente. 
 * Extrae los datos del usuario en sesión, simula identificadores de transacciones 
 * únicos y expone una plantilla HTML/CSS optimizada de forma milimétrica. 
 * Su característica principal es la integración de reglas de impresión nativas 
 * que permiten transformar la vista web en un documento PDF limpio y estilizado 
 * al presionar un solo botón.
 */

// Iniciamos la sesión para poder recuperar el nombre del cliente atendido
session_start();
// Importamos la conexión por si se requiere persistencia en un futuro
require_once 'conexion.php';

// Filtro de seguridad: Si intentan entrar de forma anónima, los sacamos al index
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Simulamos y estructuramos los últimos datos clave para la emisión del comprobante
// Usamos un rand() para simular un número de comprobante único e inmediato
$factura_num = "FAC-" . rand(10000, 99999);
$fecha_emision = date("d/m/Y H:i"); // Captura la fecha y hora exacta del servidor en tiempo real
$nombre_cliente = $_SESSION['user_name'] . " " . $_SESSION['user_last'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura Digital - Barber House</title>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600&family=Sawarabi+Mincho&display=swap" rel="stylesheet">
    <style>
        /* Estilos generales del fondo de pantalla en modo web */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #29030E; font-family: 'Instrument Sans', sans-serif; padding: 50px 20px; display: flex; flex-direction: column; align-items: center; }
        
        /* Botón interactivo de descarga */
        .btn-download { background-color: #52131E; border: 1px solid #EDC484; color: #EDC484; padding: 12px 30px; font-size: 16px; font-weight: 600; border-radius: 8px; cursor: pointer; margin-bottom: 30px; display: flex; align-items: center; gap: 10px; }
        
        /* Contenedor Físico e Imprimible del Ticket de Factura */
        .invoice-box { width: 800px; background-color: #FFFFFF; color: #29030E; padding: 50px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .invoice-header { display: flex; justify-content: space-between; border-bottom: 3px solid #52131E; padding-bottom: 20px; margin-bottom: 30px; }
        
        .company-details h2 { font-family: 'Sawarabi Mincho', serif; color: #52131E; font-size: 32px; }
        .invoice-details { text-align: right; font-size: 14px; line-height: 1.5; }
        
        .client-block { margin-bottom: 30px; font-size: 15px; }
        .client-block h4 { color: #52131E; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; margin-bottom: 5px; }
        
        /* Estilos de la tabla de ítems consumidos */
        .invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .invoice-table th { background-color: #52131E; color: #FFEED5; text-transform: uppercase; font-size: 12px; padding: 12px; text-align: left; }
        .invoice-table td { padding: 15px 12px; border-bottom: 1px solid #E0E0E0; font-size: 14px; }
        
        /* Bloque inferior de liquidación e impuestos */
        .totals-block { display: flex; flex-direction: column; align-items: flex-end; font-size: 16px; line-height: 2; }
        .totals-block div span { font-weight: 600; color: #52131E; min-width: 120px; display: inline-block; text-align: right; }

        /* ========================================================
            REGLAS MAESTRAS DE IMPRESIÓN (CSS PARA FORZAR PDF LIMPIO)
           ======================================================== */
        @media print {
            /* Al mandar a imprimir o guardar como PDF, cambiamos el fondo oscuro por un blanco total */
            body { background: #FFFFFF !important; padding: 0 !important; }
            
            /* Escondemos por completo el botón de descarga para que no ensucie la visual del PDF final */
            .btn-download { display: none !important; } 
            
            /* Expandimos la caja de la factura al 100% quitando bordes y sombras innecesarias en papel */
            .invoice-box { width: 100% !important; border: none !important; box-shadow: none !important; padding: 0 !important; }
        }
    </style>
</head>
<body>

    <button class="btn-download" onclick="window.print();">
        📥 Descargar Factura en PDF
    </button>

    <div class="invoice-box">
        <div class="invoice-header">
            <div class="company-details">
                <h2>BARBER HOUSE</h2>
                <p style="font-size: 12px; opacity: 0.7;">RUC: 1391740284001 • Manta, Ecuador</p>
            </div>
            <div class="invoice-details">
                <h3 style="color: #52131E; font-size: 20px;">FACTURA DIGITAL</h3>
                <p><strong>Nº Comprobante:</strong> <?php echo $factura_num; ?></p>
                <p><strong>Fecha Emisión:</strong> <?php echo $fecha_emision; ?></p>
            </div>
        </div>

        <div class="client-block">
            <h4>Cliente Receptor</h4>
            <p style="font-size: 18px; font-weight: 500;"><?php echo htmlspecialchars($nombre_cliente); ?></p>
            <p style="opacity: 0.8;">Consumidor Final</p>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th style="width: 55%;">Descripción del Servicio</th>
                    <th style="width: 15%; text-align: center;">Cant.</th>
                    <th style="width: 15%; text-align: right;">Precio Unit.</th>
                    <th style="width: 15%; text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Perfilado & Ritual de Barba Premium (Incluye toalla caliente)</td>
                    <td style="text-align: center;">1</td>
                    <td style="text-align: right;">$18.00</td>
                    <td style="text-align: right;">$18.00</td>
                </tr>
                <tr>
                    <td>Servicios Complementarios: Mascarilla Facial Hidratante</td>
                    <td style="text-align: center;">1</td>
                    <td style="text-align: right;">$10.00</td>
                    <td style="text-align: right;">$10.00</td>
                </tr>
            </tbody>
        </table>

        <div class="totals-block">
            <div>Subtotal: <span>$25.00</span></div>
            <div>IVA (12%): <span>$3.00</span></div>
            <div style="font-size: 20px; font-weight: 600; border-top: 2px solid #52131E; padding-top: 5px; margin-top: 5px;">
                Total Neto: <span style="color: #52131E;">$28.00</span>
            </div>
        </div>
        
        <p style="text-align: center; font-size: 11px; opacity: 0.5; margin-top: 60px; border-top: 1px dashed #CCC; padding-top: 15px;">
            Gracias por preferir la experiencia Barber House. Este documento es un comprobante digital de atención.
        </p>
    </div>

</body>
</html>
