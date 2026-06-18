<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$id_usuario = $_SESSION['user_id'];
$rol_usuario = $_SESSION['user_rol'];

// Consultas a la base de datos
try {
    $sucursales = $pdo->query("SELECT * FROM sucursal")->fetchAll(PDO::FETCH_ASSOC);
    $servicios  = $pdo->query("SELECT * FROM servicio")->fetchAll(PDO::FETCH_ASSOC);
    $empleados  = $pdo->query("SELECT e.id_empleado, u.nombre, u.apellido FROM empleado e JOIN usuario u ON e.id_usuario = u.id_usuario")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $sucursales = [];
    $servicios = [];
    $empleados = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barber House - Reservar Cita</title>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600&family=Sawarabi+Mincho&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #29030E; font-family: 'Instrument Sans', sans-serif; color: #FFEED5; min-height: 100vh; }
        
        .navbar { background-color: #52131E; width: 100%; height: 109px; display: flex; justify-content: space-between; align-items: center; padding: 0 50px; box-shadow: 0px 4px 15px rgba(0,0,0,0.3); }
        .nav-logo img { height: 65px; width: auto; }
        .nav-links { display: flex; gap: 40px; list-style: none; }
        .nav-links a { color: #FFEED5; text-decoration: none; font-size: 18px; }

        .booking-wrapper { width: 1400px; max-width: 95%; margin: 40px auto; background-color: #FCF6ED; border-radius: 25px; display: block; color: #29030E; overflow: hidden; box-shadow: 0 15px 40px rgba(0,0,0,0.4); }
        
        /* Layout estructurado mediante comportamiento de tabla para evitar desalineación */
        .layout-table { display: table; width: 100%; table-layout: fixed; }
        .form-panel { display: table-cell; width: 63%; padding: 45px; vertical-align: top; }
        .summary-panel { display: table-cell; width: 37%; padding: 45px; background-color: #FDFBF7; border-left: 1px solid rgba(82, 19, 30, 0.1); vertical-align: top; }
        
        h2 { font-family: 'Sawarabi Mincho', serif; font-size: 32px; color: #52131E; font-weight: 400; margin-bottom: 30px; }
        h3 { font-family: 'Sawarabi Mincho', serif; font-size: 22px; color: #52131E; font-weight: 400; margin-bottom: 20px; }
        
        label { font-size: 12px; font-weight: 600; text-transform: uppercase; color: #52131E; display: block; margin-top: 20px; margin-bottom: 6px; letter-spacing: 0.5px; }
        select, input, textarea { width: 100%; height: 48px; border: 1px solid rgba(82, 19, 30, 0.3); border-radius: 8px; padding: 0 15px; font-size: 15px; color: #29030E; background-color: #FFFFFF; outline: none; }
        textarea { height: 110px; padding: 15px; resize: none; }
        
        /* Columnas del formulario */
        .row-grid { display: block; margin-top: 10px; }
        .col-50 { display: inline-block; width: 48%; vertical-align: top; }
        .col-50:last-child { margin-left: 3.5%; }
        
        /* --- CORRECCIÓN DEFINITIVA DE SERVICIOS COMPLEMENTARIOS --- */
        .complements-box { 
            margin-top: 20px; 
            background-color: #FFF5F5; 
            padding: 25px; 
            border-radius: 12px; 
            border: 1px dashed rgba(82, 19, 30, 0.15); 
        }
        
        .checkbox-row {
            display: block;
            margin-bottom: 15px;
            cursor: pointer;
        }
        .checkbox-row:last-child {
            margin-bottom: 0;
        }

        /* Alineación milimétrica del cuadro con el texto */
        .checkbox-cell {
            display: inline-block;
            vertical-align: middle;
            width: 24px;
            height: 24px;
        }
        .checkbox-cell input[type="checkbox"] {
            width: 20px !important;
            height: 20px !important;
            cursor: pointer;
            display: block;
            margin: 2px 0 0 0;
        }
        
        .text-cell {
            display: inline-block;
            vertical-align: middle;
            padding-left: 10px;
            font-size: 15px;
            color: #52131E;
            font-weight: 500;
            line-height: 24px;
        }
        
        .btn-confirm { width: 100%; height: 55px; background-color: #52131E; color: #FFEED5; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; margin-top: 30px; transition: background 0.3s; }
        .btn-confirm:hover { background-color: #29030E; }
        
        .summary-list { list-style: none; margin-top: 20px; }
        .summary-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed rgba(82, 19, 30, 0.15); font-size: 15px; }
        .summary-item.total { border-top: 2px solid #52131E; font-size: 32px; font-family: 'Sawarabi Mincho', serif; color: #52131E; margin-top: 40px; padding-top: 15px; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-logo"><img src="imagenes/logo.png" alt="Barber House"></div>
        <ul class="nav-links">
            <li><a href="inicio.php">Inicio</a></li>
            <li><a href="servicios.php">Servicios</a></li>
            <li><a href="perfil_cliente.php">Mi Perfil</a></li>
        </ul>
    </nav>

    <div class="booking-wrapper">
        <div class="layout-table">
            
            <!-- Panel Izquierdo: Formulario -->
            <div class="form-panel">
                <h2>Detalle de la Cita</h2>
                <form action="procesar_reserva.php" method="POST">
                    
                    <label>Sucursal *</label>
                    <select name="id_sucursal" id="selectSucursal" required>
                        <option value="">Selecciona una sucursal</option>
                        <?php foreach ($sucursales as $suc): ?>
                            <option value="<?php echo $suc['id_sucursal']; ?>"><?php echo htmlspecialchars($suc['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <div class="row-grid">
                        <div class="col-50">
                            <label>Servicio Principal *</label>
                            <select name="id_servicio" id="selectServicio" required>
                                <option value="">Selecciona el servicio</option>
                                <?php foreach ($servicios as $ser): ?>
                                    <option value="<?php echo $ser['id_servicio']; ?>" data-precio="<?php echo $ser['precio']; ?>" data-nombre="<?php echo htmlspecialchars($ser['nombre']); ?>">
                                        <?php echo htmlspecialchars($ser['nombre'] . " ($" . number_format($ser['precio'], 2) . ")"); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-50">
                            <label>Profesional Disponible *</label>
                            <select name="id_empleado" required>
                                <option value="">Selecciona al profesional</option>
                                <?php foreach ($empleados as $emp): ?>
                                    <option value="<?php echo $emp['id_empleado']; ?>"><?php echo htmlspecialchars($emp['nombre'] . " " . $emp['apellido']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- SECCIÓN CORREGIDA DE SERVICIOS COMPLEMENTARIOS -->
                    <label>Servicios Complementarios (Opcional)</label>
                    <div class="complements-box">
                        <label class="checkbox-row">
                            <span class="checkbox-cell">
                                <input type="checkbox" name="extras[]" value="5.00" class="extra-check" data-nombre="Lavado Capilar">
                            </span>
                            <span class="text-cell">Lavado Capilar (+$5.00)</span>
                        </label>
                        
                        <label class="checkbox-row">
                            <span class="checkbox-cell">
                                <input type="checkbox" name="extras[]" value="8.00" class="extra-check" data-nombre="Perfilado de Barba">
                            </span>
                            <span class="text-cell">Perfilado de Barba (+$8.00)</span>
                        </label>
                        
                        <label class="checkbox-row">
                            <span class="checkbox-cell">
                                <input type="checkbox" name="extras[]" value="10.00" class="extra-check" data-nombre="Mascarilla Facial">
                            </span>
                            <span class="text-cell">Mascarilla Facial (+$10.00)</span>
                        </label>
                    </div>

                    <div class="row-grid">
                        <div class="col-50">
                            <label>Fecha de la Cita *</label>
                            <input type="date" name="fecha" required>
                        </div>
                        <div class="col-50">
                            <label>Hora de Atención *</label>
                            <input type="time" name="hora" required>
                        </div>
                    </div>

                    <label>Observaciones (Opcional)</label>
                    <textarea name="observaciones" placeholder="¿Tienes alguna preferencia en especial?"></textarea>

                    <button type="submit" class="btn-confirm">Confirmar y Agendar</button>
                </form>
            </div>

            <!-- Panel Derecho: Resumen -->
            <div class="summary-panel">
                <div style="min-height: 250px;">
                    <h3>Resumen del Servicio</h3>
                    <div id="summaryPlaceholder" style="font-size: 14px; opacity: 0.7; line-height: 1.6; margin-top: 15px;">
                        El desglose final e impuestos detallados se consolidarán al emitir tu comprobante digital de atención.
                    </div>
                    <ul class="summary-list" id="summaryList" style="display: none;"></ul>
                </div>
                
                <div style="border-top: 1px dashed rgba(82, 19, 30, 0.2); padding-top: 20px; margin-top: 40px;">
                    <div class="summary-item total">
                        <span style="font-size: 14px; font-weight: 500; text-transform: uppercase; display: block; color: #52131E; margin-bottom: 5px;">Total estimado</span>
                        <div id="totalLabel">$0.00</div>
                    </div>
                    <p style="font-size: 12px; opacity: 0.6; margin-top: 8px;">* Pago a registrarse en la recepción del local.</p>
                </div>
            </div>

        </div>
    </div>

    <script>
        const selectServicio = document.getElementById('selectServicio');
        const extraChecks = document.querySelectorAll('.extra-check');
        const summaryPlaceholder = document.getElementById('summaryPlaceholder');
        const summaryList = document.getElementById('summaryList');
        const totalLabel = document.getElementById('totalLabel');

        function actualizarResumen() {
            let total = 0;
            let html = '';
            const selectedOption = selectServicio.options[selectServicio.selectedIndex];
            
            if (selectedOption && selectedOption.value !== '') {
                summaryPlaceholder.style.display = 'none';
                summaryList.style.display = 'block';
                
                const precioBase = parseFloat(selectedOption.getAttribute('data-precio'));
                const nombreBase = selectedOption.getAttribute('data-nombre');
                total += precioBase;
                html += `<li class="summary-item"><span>${nombreBase}</span><strong>$${precioBase.toFixed(2)}</strong></li>`;
                
                extraChecks.forEach(check => {
                    if (check.checked) {
                        const precioExtra = parseFloat(check.value);
                        total += precioExtra;
                        html += `<li class="summary-item" style="color: #666; font-size: 14px;"><span>+ ${check.getAttribute('data-nombre')}</span><strong>$${precioExtra.toFixed(2)}</strong></li>`;
                    }
                });
                
                summaryList.innerHTML = html;
                totalLabel.innerText = `$${total.toFixed(2)}`;
            } else {
                summaryPlaceholder.style.display = 'block';
                summaryList.style.display = 'none';
                totalLabel.innerText = '$0.00';
            }
        }

        selectServicio.addEventListener('change', actualizarResumen);
        extraChecks.forEach(check => check.addEventListener('change', actualizarResumen));
    </script>
</body>
</html>