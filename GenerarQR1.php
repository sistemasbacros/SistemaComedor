<?php
// ==================================================
// PROTECCIÓN DE SEGURIDAD MEJORADA
// ==================================================

// Configuración de sesión
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/Comedor/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

// Verificar si las variables vienen por GET o usar las de sesión
$user_name = isset($_GET['user']) ? $_GET['user'] : (isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Usuario');
$user_area = isset($_GET['area']) ? $_GET['area'] : (isset($_SESSION['user_area']) ? $_SESSION['user_area'] : 'Sistema de Comedor');

// Determinar el tipo de comida según la hora actual
$hora_actual = date('H');
$tipo_comida = ($hora_actual < 12) ? 'Desayuno' : 'Comida';
$fecha_actual = date('d/m/Y');

// Texto para el QR
$texto_qr = $user_name . " se encuentra registrado para el " . $tipo_comida . " con fecha de " . $fecha_actual;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de QR - <?php echo htmlspecialchars($user_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/qrious@4.0.2/dist/qrious.js"></script>
    <style>
        :root {
            --primary-dark: #1a365d;
            --primary-blue: #2d5f9d;
            --accent-blue: #3b82f6;
            --secondary-blue: #60a5fa;
            --light-blue: #dbeafe;
            --white-pearl: #f8fafc;
            --success-color: #10b981;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-dark) 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            color: #333;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            max-width: 600px;
        }
        
        .qr-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            text-align: center;
        }
        
        .header-gradient {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-dark));
            color: white;
            padding: 25px;
            border-radius: 15px 15px 0 0;
            margin: -40px -40px 30px -40px;
        }
        
        .user-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            border-radius: 50px;
            display: inline-block;
            margin-top: 10px;
            font-weight: 600;
        }
        
        .qr-code-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            display: inline-block;
            margin: 20px 0;
            border: 3px solid var(--accent-blue);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .info-text {
            background: var(--light-blue);
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid var(--accent-blue);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #059669);
            border: none;
            padding: 15px 40px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }
        
        .comida-badge {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
            margin: 10px 0;
        }
        
        .fecha-text {
            color: #6b7280;
            font-size: 1rem;
            margin: 10px 0;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .qr-card {
                padding: 30px 20px;
            }
            
            .header-gradient {
                margin: -30px -20px 20px -20px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="qr-card">
            <!-- Header -->
            <div class="header-gradient">
                <h1><i class="fas fa-qrcode me-2"></i>Tu Código QR</h1>
                <div class="user-badge">
                    <i class="fas fa-user me-2"></i>
                    <?php echo htmlspecialchars($user_name); ?>
                </div>
            </div>

            <!-- Información del pedido -->
            <div class="info-text">
                <i class="fas fa-info-circle me-2"></i>
                <strong><?php echo htmlspecialchars($user_name); ?></strong> se encuentra registrado para el 
                <span class="comida-badge"><?php echo $tipo_comida; ?></span>
                con fecha de <strong><?php echo $fecha_actual; ?></strong>
            </div>

            <!-- Código QR -->
            <div class="qr-code-container">
                <canvas id="codigo"></canvas>
            </div>

            <!-- Botón de descarga -->
            <button class="btn btn-success" id="btnDescargar">
                <i class="fas fa-download me-2"></i>Descargar QR
            </button>

            <!-- Información adicional -->
            <div class="fecha-text">
                <i class="fas fa-clock me-1"></i>
                Generado el <?php echo date('d/m/Y H:i:s'); ?>
            </div>
        </div>
    </div>

    <script>
        // Variables desde PHP
        const user_name = "<?php echo htmlspecialchars($user_name); ?>";
        const texto_qr = "<?php echo $texto_qr; ?>";
        const tipo_comida = "<?php echo $tipo_comida; ?>";
        const fecha_actual = "<?php echo $fecha_actual; ?>";

        // Generar QR automáticamente al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Generando QR para:', user_name);
            
            const $imagen = document.querySelector("#codigo");
            const $boton = document.querySelector("#btnDescargar");
            
            // Generar QR
            new QRious({
                element: $imagen,
                value: texto_qr,
                size: 250,
                backgroundAlpha: 0,
                foreground: "#1a365d",
                level: "H",
            });

            // Configurar descarga
            $boton.onclick = () => {
                const enlace = document.createElement("a");
                enlace.href = $imagen.toDataURL("image/png");
                enlace.download = "QR_" + user_name + "_" + tipo_comida + "_" + "<?php echo date('Y-m-d'); ?>" + ".png";
                enlace.click();
            };
            
            // Mostrar mensaje de confirmación
            console.log('QR generado exitosamente:', texto_qr);
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>