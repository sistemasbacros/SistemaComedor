<?php
// ============================================
// TEST DE ENVÍO DE CORREO - SOLO PARA VALIDAR
// ============================================
// Guarda este archivo como "test_email.php" en la misma carpeta

// Mostrar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>🔧 TEST DE ENVÍO DE CORREO</h2>";

// Incluir PHPMailer (AJUSTA LA RUTA SEGÚN TU ESTRUCTURA)
$ruta_phpmailer = __DIR__ . '/PHPMailer/src/';
if (!file_exists($ruta_phpmailer . 'PHPMailer.php')) {
    die("❌ NO ENCUENTRO PHPMailer en: " . $ruta_phpmailer);
}

require $ruta_phpmailer . 'PHPMailer.php';
require $ruta_phpmailer . 'SMTP.php';
require $ruta_phpmailer . 'Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "✅ PHPMailer encontrado<br>";

// ============================================
// CONFIGURACIÓN - CÁMBIALO A TUS DATOS
// ============================================
$correo_destino = 'luis.romero@bacrocorp.com'; // <--- PON AQUÍ TU CORREO
$nombre_destino = 'Usuario Prueba';

// Datos de prueba (simulando una cancelación)
$datos_prueba = [
    'NOMBRE' => 'Juan Pérez Prueba',
    'DEPARTAMENTO' => 'Sistemas',
    'JEFE' => 'Jefe de Prueba',
    'TIPO_CONSUMO' => 'Comida corrida',
    'FECHA' => '2026-02-23',
    'CAUSA' => 'Prueba de envío de correo',
    'DESCRIPCION' => 'Esta es una prueba del sistema de notificaciones'
];

$rol_rechazo = 'Administrador';

// ============================================
// FUNCIÓN DE ENVÍO (IGUAL A LA DEL SISTEMA)
// ============================================
function enviarCorreoPrueba($correo_destino, $nombre_destino, $datos, $rol_rechazo) {
    
    echo "<br>📧 Intentando enviar a: <strong>$correo_destino</strong><br>";
    
    try {
        $mail = new PHPMailer(true);
        
        // Configuración SMTP (IGUAL QUE EN TICKETS)
        $mail->isSMTP();
        $mail->Host = 'smtp.office365.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tickets@bacrocorp.com';
        $mail->Password = 'XTqzA0GkA#'; // VERIFICA ESTA CONTRASEÑA
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        
        // Debug (para ver errores detallados)
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) {
            echo "🔍 DEBUG: $str<br>";
        };
        
        // Remitente
        $mail->setFrom('tickets@bacrocorp.com', 'Sistema de Comedor - BacroCorp');
        $mail->addAddress($correo_destino, $nombre_destino);
        $mail->addReplyTo('comedor@bacrocorp.com', 'Coordinación de Comedor');
        
        // Contenido
        $mail->isHTML(true);
        $mail->Subject = '📝 PRUEBA - Notificación de Rechazo';
        $mail->Body = crearPlantillaPrueba($datos, $rol_rechazo);
        $mail->AltBody = "PRUEBA: Tu solicitud fue rechazada";
        
        if ($mail->send()) {
            echo "<p style='color:green; font-weight:bold;'>✅ CORREO ENVIADO EXITOSAMENTE</p>";
            return true;
        } else {
            echo "<p style='color:red; font-weight:bold;'>❌ ERROR: " . $mail->ErrorInfo . "</p>";
            return false;
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red; font-weight:bold;'>❌ EXCEPCIÓN: " . $e->getMessage() . "</p>";
        return false;
    }
}

// Plantilla simplificada para la prueba
function crearPlantillaPrueba($datos, $rol_rechazo) {
    $responsable = ($rol_rechazo == 'Administrador') ? 'Administración' : 'Coordinación';
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
    </head>
    <body style="font-family: Arial, sans-serif;">
        <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd;">
            <div style="background: #c62828; color: white; padding: 20px; text-align: center;">
                <h1>🔧 PRUEBA DE CORREO</h1>
                <p>Sistema de Comedor - Validación</p>
            </div>
            
            <div style="padding: 20px;">
                <p><strong>Estimado/a ' . $datos['NOMBRE'] . ',</strong></p>
                <p><strong style="color: #c62828;">ESTE ES UN CORREO DE PRUEBA</strong></p>
                
                <div style="background: #f5f5f5; padding: 15px; margin: 20px 0;">
                    <p><strong>Detalles de la prueba:</strong></p>
                    <p>📅 Fecha: ' . $datos['FECHA'] . '</p>
                    <p>🏢 Departamento: ' . $datos['DEPARTAMENTO'] . '</p>
                    <p>👤 Jefe: ' . $datos['JEFE'] . '</p>
                    <p>📝 Causa: ' . $datos['CAUSA'] . '</p>
                    <p>❌ Rechazado por: ' . $responsable . '</p>
                </div>
                
                <p style="color: #666; font-size: 14px;">
                    Si recibes este correo, la configuración SMTP está funcionando correctamente.
                </p>
            </div>
            
            <div style="background: #f1f1f1; padding: 15px; text-align: center; font-size: 12px;">
                <p>© 2026 BacroCorp - PRUEBA DE SISTEMA</p>
            </div>
        </div>
    </body>
    </html>';
}

// ============================================
// EJECUTAR LA PRUEBA
// ============================================
echo "<hr>";
echo "<h3>📋 DATOS DE LA PRUEBA:</h3>";
echo "Correo destino: <strong>" . htmlspecialchars($correo_destino) . "</strong><br>";
echo "Nombre: <strong>" . htmlspecialchars($nombre_destino) . "</strong><br>";
echo "Rol: <strong>" . $rol_rechazo . "</strong><br>";
echo "<hr>";

$resultado = enviarCorreoPrueba($correo_destino, $nombre_destino, $datos_prueba, $rol_rechazo);

echo "<hr>";
if ($resultado) {
    echo "<h3 style='color:green;'>✅ PRUEBA EXITOSA - Revisa tu bandeja de entrada</h3>";
} else {
    echo "<h3 style='color:red;'>❌ PRUEBA FALLÓ - Revisa los errores arriba</h3>";
}
echo "<hr>";

// Información adicional
echo "<h4>🔍 INFORMACIÓN DEL SISTEMA:</h4>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Ruta absoluta: " . __DIR__ . "<br>";
echo "PHPMailer: " . (class_exists('PHPMailer\\PHPMailer\\PHPMailer') ? '✅ Cargado' : '❌ No cargado') . "<br>";

// Probar conexión SMTP básica
echo "<h4>📡 PROBANDO CONEXIÓN SMTP:</h4>";
$host = 'smtp.office365.com';
$port = 587;
$connection = @fsockopen($host, $port, $errno, $errstr, 5);
if ($connection) {
    echo "✅ Conexión exitosa a $host:$port<br>";
    fclose($connection);
} else {
    echo "❌ No se puede conectar: $errstr ($errno)<br>";
}
?>