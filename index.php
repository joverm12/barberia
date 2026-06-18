<?php include 'login.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barber House - Iniciar Sesión</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600&family=Sawarabi+Mincho&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <div class="login-container">
        
        <div class="form-section">
            <div class="form-content">
                
                <img src="imagenes/logo.png" alt="Barber House" class="logo">
                
                <h1 class="main-title">Iniciar Sesión</h1>
                
                <?php if (isset($error)): ?>
                    <p style="color: #EA4335; margin-bottom: 20px; font-weight: 500; font-size: 16px; text-align: center; width: 100%; font-family: 'Instrument Sans', sans-serif;">
                        <?php echo $error; ?>
                    </p>
                <?php endif; ?>
                
                <form action="index.php" method="POST">
                    <div class="input-group">
                        <label for="email">Correo electrónico*</label>
                        <input type="email" id="email" name="email" placeholder="ejemplo@correo.com" required>
                    </div>
                    
                    <div class="input-group">
                        <label for="password">Contraseña*</label>
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                    </div>
                    
                    <div class="forgot-container">
                        <a href="#" class="forgot-link">¿Olvidaste tu contraseña?</a>
                    </div>
                    
                    <button type="submit" class="btn-submit">INGRESAR</button>
                </form>
                
                <div class="divider">
                    <span>ACCEDER CON</span>
                </div>
                
                <button class="btn-google">
                    <img src="imagenes/google-icon.svg" alt="Google" class="google-icon">
                    CONTINUAR CON GOOGLE
                </button>
                
                <p class="terms-text">
                    Al continuar, aceptas los términos y condiciones, y la política de privacidad.
                </p>
            </div>
        </div>

        <div class="image-section">
            <div class="linear-overlay"></div>
        </div>

    </div>

</body>
</html>