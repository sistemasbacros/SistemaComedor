<?php
// ----------------------- SISTEMA DE VALIDACIÓN DE COMEDOR - VERSIÓN FINAL CON TOOLTIP MEJORADO ---------------------------
header('Content-Type: text/html; charset=UTF-8');
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

setlocale(LC_ALL, 'es_ES.UTF-8', 'spanish');
date_default_timezone_set('America/Mexico_City');

// Incluir PHPMailer
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';
require __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Configuración de correos
define('ADMIN_EMAIL', 'tickets@bacrocorp.com');
define('ADMIN_NAME', 'Administrador TI BacroCorp');
define('COMEDOR_EMAIL', 'comedor@bacrocorp.com');
define('COMEDOR_NAME', 'Coordinación de Comedor');

// Conexión a SQL Server
$serverName = "DESAROLLO-BACRO\\SQLEXPRESS";
$connectionInfo = array(
    "Database" => "Comedor",
    "UID" => "Larome03",
    "PWD" => "Larome03",
    "CharacterSet" => "UTF-8",
    "ReturnDatesAsStrings" => true
);

$conn = sqlsrv_connect($serverName, $connectionInfo);
if (!$conn) {
    die("<div class='alert alert-danger'>Error de conexión a la base de datos</div>");
}

// Obtener filtro de estado desde GET
$filtro_estado = isset($_GET['filtro']) ? $_GET['filtro'] : 'pendientes';
$rol = isset($_GET['newpwd']) ? $_GET['newpwd'] : '';

// Procesar validaciones POST
$mensaje_exito = '';
$tipo_mensaje = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // VALIDACIÓN INDIVIDUAL
    if (isset($_POST['validar_individual'])) {
        $nombre = trim($_POST['nombre'] ?? '');
        $fecha = trim($_POST['fecha'] ?? '');
        $tipo_consumo = trim($_POST['tipo_consumo'] ?? '');
        $accion = $_POST['accion'] ?? 'aprobar';
        $rol_usuario = $_GET['newpwd'] ?? '';
        
        if (!empty($nombre) && !empty($fecha) && !empty($tipo_consumo) && !empty($rol_usuario)) {
            
            if ($rol_usuario == 'Administrador') {
                $sql_update = "UPDATE cancelaciones SET Estatus = ? WHERE NOMBRE = ? AND CONVERT(varchar, FECHA, 120) = ? AND TIPO_CONSUMO = ?";
                $mensaje_exito = ($accion == 'aprobar') ? 
                    "✅ Validación de Administrador completada para: " . $nombre . " (" . $tipo_consumo . ")" :
                    "❌ Rechazo de Administrador registrado para: " . $nombre . " (" . $tipo_consumo . ")";
            } else {
                $sql_update = "UPDATE cancelaciones SET ValJefDirect = ? WHERE NOMBRE = ? AND CONVERT(varchar, FECHA, 120) = ? AND TIPO_CONSUMO = ?";
                $mensaje_exito = ($accion == 'aprobar') ? 
                    "✅ Validación de Coordinador completada para: " . $nombre . " (" . $tipo_consumo . ")" :
                    "❌ Rechazo de Coordinador registrado para: " . $nombre . " (" . $tipo_consumo . ")";
            }
            
            $estado = ($accion == 'aprobar') ? 'APROBADO' : 'RECHAZADO';
            $params = array($estado, $nombre, $fecha, $tipo_consumo);
            
            $stmt_update = sqlsrv_query($conn, $sql_update, $params);
            
            if ($stmt_update !== false) {
                $tipo_mensaje = ($accion == 'aprobar') ? 'success' : 'warning';
                
                // Obtener datos completos
                $sql_datos = "SELECT c.*, co.[Correo Asignado] as Correo 
                             FROM cancelaciones c
                             LEFT JOIN correo2026B co ON c.NOMBRE = co.[Nombre Completo]
                             WHERE c.NOMBRE = ? AND CONVERT(varchar, c.FECHA, 120) = ? AND c.TIPO_CONSUMO = ?";
                $params_datos = array($nombre, $fecha, $tipo_consumo);
                $stmt_datos = sqlsrv_query($conn, $sql_datos, $params_datos);
                
                if ($stmt_datos && $datos_cancelacion = sqlsrv_fetch_array($stmt_datos, SQLSRV_FETCH_ASSOC)) {
                    $datos_cancelacion['Correo'] = trim(str_replace(['>', '<', ' ', '"', "'"], '', $datos_cancelacion['Correo'] ?? ''));
                    
                    if ($accion == 'aprobar') {
                        enviarCorreoAprobacion($datos_cancelacion, $rol_usuario);
                    } else {
                        enviarCorreoRechazo($datos_cancelacion, $rol_usuario);
                    }
                }
                
                $redirect_url = "?filtro=" . urlencode($filtro_estado) . "&mensaje=" . urlencode($mensaje_exito) . "&tipo=" . $tipo_mensaje;
                if (isset($_GET['newpwd'])) {
                    $redirect_url .= "&newpwd=" . urlencode($_GET['newpwd']);
                }
                header("Location: " . $redirect_url);
                exit();
            }
        }
    }
    
    // VALIDACIÓN EN LOTE
    if (isset($_POST['validar_lote'])) {
        $tipo_validacion = $_POST['TIPOVALIDA'] ?? '';
        $departamento = $_POST['DEPARTAMENTO'] ?? '';
        $nombre_lote = $_POST['name123'] ?? '';
        $fecha_lote = $_POST['name1234'] ?? '';
        $tipo_consumo_lote = $_POST['tipo_consumo_lote'] ?? '';
        $accion_lote = $_POST['accion_lote'] ?? 'aprobar';
        $rol_usuario = $_GET['newpwd'] ?? '';
        
        if (!empty($rol_usuario)) {
            
            if ($tipo_validacion == 'UNICA') {
                if (!empty($nombre_lote) && !empty($fecha_lote) && !empty($tipo_consumo_lote)) {
                    if ($rol_usuario == 'Administrador') {
                        $sql_update = "UPDATE cancelaciones SET Estatus = ? WHERE NOMBRE = ? AND CONVERT(varchar, FECHA, 120) = ? AND TIPO_CONSUMO = ?";
                    } else {
                        $sql_update = "UPDATE cancelaciones SET ValJefDirect = ? WHERE NOMBRE = ? AND CONVERT(varchar, FECHA, 120) = ? AND TIPO_CONSUMO = ?";
                    }
                    
                    $estado = ($accion_lote == 'aprobar') ? 'APROBADO' : 'RECHAZADO';
                    $params = array($estado, $nombre_lote, $fecha_lote, $tipo_consumo_lote);
                    $stmt_update = sqlsrv_query($conn, $sql_update, $params);
                    
                    if ($stmt_update !== false) {
                        $sql_datos = "SELECT c.*, co.[Correo Asignado] as Correo 
                                     FROM cancelaciones c
                                     LEFT JOIN correo2026B co ON c.NOMBRE = co.[Nombre Completo]
                                     WHERE c.NOMBRE = ? AND CONVERT(varchar, c.FECHA, 120) = ? AND c.TIPO_CONSUMO = ?";
                        $params_datos = array($nombre_lote, $fecha_lote, $tipo_consumo_lote);
                        $stmt_datos = sqlsrv_query($conn, $sql_datos, $params_datos);
                        
                        if ($stmt_datos && $datos_cancelacion = sqlsrv_fetch_array($stmt_datos, SQLSRV_FETCH_ASSOC)) {
                            $datos_cancelacion['Correo'] = trim(str_replace(['>', '<', ' ', '"', "'"], '', $datos_cancelacion['Correo'] ?? ''));
                            
                            if ($accion_lote == 'aprobar') {
                                enviarCorreoAprobacion($datos_cancelacion, $rol_usuario);
                            } else {
                                enviarCorreoRechazo($datos_cancelacion, $rol_usuario);
                            }
                        }
                        
                        $mensaje_exito = ($accion_lote == 'aprobar') ? 
                            "✅ Validación única completada para: " . $nombre_lote . " (" . $tipo_consumo_lote . ")" :
                            "❌ Rechazo único registrado para: " . $nombre_lote . " (" . $tipo_consumo_lote . ")";
                        $tipo_mensaje = ($accion_lote == 'aprobar') ? 'success' : 'warning';
                    }
                }
            } else {
                if (!empty($departamento)) {
                    if ($rol_usuario == 'Administrador') {
                        $sql_select = "SELECT NOMBRE, FECHA, TIPO_CONSUMO FROM cancelaciones 
                                      WHERE DEPARTAMENTO LIKE ? 
                                      AND (Estatus != 'APROBADO' OR Estatus IS NULL) 
                                      AND Estatus != 'RECHAZADO'";
                    } else {
                        $sql_select = "SELECT NOMBRE, FECHA, TIPO_CONSUMO FROM cancelaciones 
                                      WHERE DEPARTAMENTO LIKE ? 
                                      AND (ValJefDirect != 'APROBADO' OR ValJefDirect IS NULL) 
                                      AND ValJefDirect != 'RECHAZADO'";
                    }
                    
                    $params_select = array('%' . $departamento . '%');
                    $stmt_select = sqlsrv_query($conn, $sql_select, $params_select);
                    $registros_procesar = array();
                    $contador = 0;
                    
                    while ($row = sqlsrv_fetch_array($stmt_select, SQLSRV_FETCH_ASSOC)) {
                        $registros_procesar[] = $row;
                    }
                    
                    foreach ($registros_procesar as $registro) {
                        $nombre = $registro['NOMBRE'];
                        $fecha = $registro['FECHA'];
                        $tipo_consumo = $registro['TIPO_CONSUMO'];
                        $estado = ($accion_lote == 'aprobar') ? 'APROBADO' : 'RECHAZADO';
                        
                        if ($rol_usuario == 'Administrador') {
                            $sql_update = "UPDATE cancelaciones SET Estatus = ? WHERE NOMBRE = ? AND CONVERT(varchar, FECHA, 120) = ? AND TIPO_CONSUMO = ?";
                        } else {
                            $sql_update = "UPDATE cancelaciones SET ValJefDirect = ? WHERE NOMBRE = ? AND CONVERT(varchar, FECHA, 120) = ? AND TIPO_CONSUMO = ?";
                        }
                        
                        $params_update = array($estado, $nombre, $fecha, $tipo_consumo);
                        $stmt_update = sqlsrv_query($conn, $sql_update, $params_update);
                        
                        if ($stmt_update !== false) {
                            $contador++;
                            
                            $sql_datos = "SELECT c.*, co.[Correo Asignado] as Correo 
                                         FROM cancelaciones c
                                         LEFT JOIN correo2026B co ON c.NOMBRE = co.[Nombre Completo]
                                         WHERE c.NOMBRE = ? AND CONVERT(varchar, c.FECHA, 120) = ? AND c.TIPO_CONSUMO = ?";
                            $params_datos = array($nombre, $fecha, $tipo_consumo);
                            $stmt_datos = sqlsrv_query($conn, $sql_datos, $params_datos);
                            
                            if ($stmt_datos && $datos_cancelacion = sqlsrv_fetch_array($stmt_datos, SQLSRV_FETCH_ASSOC)) {
                                $datos_cancelacion['Correo'] = trim(str_replace(['>', '<', ' ', '"', "'"], '', $datos_cancelacion['Correo'] ?? ''));
                                
                                if ($accion_lote == 'aprobar') {
                                    enviarCorreoAprobacion($datos_cancelacion, $rol_usuario);
                                } else {
                                    enviarCorreoRechazo($datos_cancelacion, $rol_usuario);
                                }
                            }
                        }
                    }
                    
                    $mensaje_exito = ($accion_lote == 'aprobar') ? 
                        "✅ Validación múltiple completada para departamento: " . $departamento . " ($contador registros aprobados)" :
                        "❌ Rechazo múltiple registrado para departamento: " . $departamento . " ($contador registros rechazados)";
                    $tipo_mensaje = ($accion_lote == 'aprobar') ? 'success' : 'warning';
                }
            }
            
            if (!empty($mensaje_exito)) {
                $redirect_url = "?filtro=" . urlencode($filtro_estado) . "&mensaje=" . urlencode($mensaje_exito) . "&tipo=" . $tipo_mensaje;
                if (isset($_GET['newpwd'])) {
                    $redirect_url .= "&newpwd=" . urlencode($_GET['newpwd']);
                }
                header("Location: " . $redirect_url);
                exit();
            }
        }
    }
}

// FUNCIÓN PARA FORMATEAR TIPO DE CONSUMO
function formatearTipoConsumo($tipo_consumo) {
    if (empty($tipo_consumo)) return 'No especificado';
    
    $tipo_upper = strtoupper(trim($tipo_consumo));
    
    if (strpos($tipo_upper, 'DESAYUNO') !== false && strpos($tipo_upper, 'COMIDA') !== false) {
        return '🌅 Desayuno y 🍲 Comida';
    } elseif (strpos($tipo_upper, 'DESAYUNO') !== false) {
        return '🌅 Desayuno';
    } elseif (strpos($tipo_upper, 'COMIDA') !== false) {
        return '🍲 Comida';
    } elseif (strpos($tipo_upper, 'AMBOS') !== false) {
        return '🌅 Desayuno y 🍲 Comida';
    } else {
        return htmlspecialchars($tipo_consumo);
    }
}

// FUNCIÓN PARA FORMATEAR FECHA CAPTURA
function formatearFechaCaptura($fecha_captura) {
    if (empty($fecha_captura)) return 'No disponible';
    
    try {
        $timestamp = strtotime($fecha_captura);
        if ($timestamp === false) return $fecha_captura;
        return date('d/m/Y H:i:s', $timestamp);
    } catch (Exception $e) {
        return $fecha_captura;
    }
}

// FUNCIÓN PARA ENVIAR CORREO DE APROBACIÓN
function enviarCorreoAprobacion($datos, $rol_aprobacion) {
    try {
        $correo_destino = trim($datos['Correo'] ?? '');
        $correo_destino = preg_replace('/[^a-zA-Z0-9@._-]/', '', $correo_destino);
        
        if (empty($correo_destino) || !filter_var($correo_destino, FILTER_VALIDATE_EMAIL)) {
            error_log("Correo inválido para aprobación: " . ($datos['NOMBRE'] ?? 'Desconocido'));
            return false;
        }
        
        $mail = new PHPMailer(true);
        
        $mail->isSMTP();
        $mail->Host = 'smtp.office365.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tickets@bacrocorp.com';
        $mail->Password = 'XTqzA0GkA#';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        
        $mail->setFrom('tickets@bacrocorp.com', 'Sistema de Comedor - BacroCorp');
        $mail->addAddress($correo_destino, $datos['NOMBRE']);
        $mail->addReplyTo('comedor@bacrocorp.com', 'Coordinación de Comedor');
        $mail->addCC('tickets@bacrocorp.com', 'Administración TI');
        
        $mail->isHTML(true);
        $mail->Subject = '✅ Confirmación de Aprobación - Sistema de Comedor BacroCorp';
        $mail->Body = crearPlantillaAprobacion($datos, $rol_aprobacion);
        $mail->AltBody = crearTextoPlanoAprobacion($datos, $rol_aprobacion);
        
        return $mail->send();
        
    } catch (Exception $e) {
        error_log("Error enviando correo de aprobación: " . $e->getMessage());
        return false;
    }
}

// FUNCIÓN PARA ENVIAR CORREO DE RECHAZO
function enviarCorreoRechazo($datos, $rol_rechazo) {
    try {
        $correo_destino = trim($datos['Correo'] ?? '');
        $correo_destino = preg_replace('/[^a-zA-Z0-9@._-]/', '', $correo_destino);
        
        if (empty($correo_destino) || !filter_var($correo_destino, FILTER_VALIDATE_EMAIL)) {
            error_log("Correo inválido para rechazo: " . ($datos['NOMBRE'] ?? 'Desconocido'));
            return false;
        }
        
        $mail = new PHPMailer(true);
        
        $mail->isSMTP();
        $mail->Host = 'smtp.office365.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tickets@bacrocorp.com';
        $mail->Password = 'XTqzA0GkA#';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        
        $mail->setFrom('tickets@bacrocorp.com', 'Sistema de Comedor - BacroCorp');
        $mail->addAddress($correo_destino, $datos['NOMBRE']);
        $mail->addReplyTo('comedor@bacrocorp.com', 'Coordinación de Comedor');
        $mail->addCC('tickets@bacrocorp.com', 'Administración TI');
        
        $mail->isHTML(true);
        $mail->Subject = '❌ Notificación de Rechazo - Sistema de Comedor BacroCorp';
        $mail->Body = crearPlantillaRechazo($datos, $rol_rechazo);
        $mail->AltBody = crearTextoPlanoRechazo($datos, $rol_rechazo);
        
        return $mail->send();
        
    } catch (Exception $e) {
        error_log("Error enviando correo de rechazo: " . $e->getMessage());
        return false;
    }
}

// FUNCIÓN PARA CREAR PLANTILLA HTML DE APROBACIÓN
function crearPlantillaAprobacion($datos, $rol_aprobacion) {
    $fecha_formateada = date('d/m/Y', strtotime($datos['FECHA']));
    $hoy = date('d/m/Y H:i:s');
    $responsable = ($rol_aprobacion == 'Administrador') ? 'Administración del Comedor' : 'Coordinación del Comedor';
    $tipo_consumo_formateado = formatearTipoConsumo($datos['TIPO_CONSUMO'] ?? '');
    $fecha_captura_formateada = formatearFechaCaptura($datos['FECHA_CAPTURA'] ?? '');
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                color: #333;
                margin: 0;
                padding: 0;
                background-color: #f5f5f5;
            }
            .container {
                max-width: 600px;
                margin: 20px auto;
                background-color: #ffffff;
                border-radius: 20px;
                overflow: hidden;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                border: 1px solid #e0e0e0;
            }
            .header {
                background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
                color: white;
                padding: 30px 20px;
                text-align: center;
                border-bottom: 5px solid #ffb74d;
            }
            .header-icon {
                font-size: 60px;
                margin-bottom: 15px;
            }
            .content {
                padding: 30px;
            }
            .mensaje-principal {
                background: #e8f5e9;
                border-radius: 15px;
                padding: 25px;
                margin-bottom: 25px;
                border-left: 8px solid #2e7d32;
                font-size: 16px;
                line-height: 1.6;
            }
            .mensaje-principal strong {
                color: #1b5e20;
                font-size: 18px;
            }
            .info-card {
                background: #fafafa;
                border-radius: 15px;
                padding: 25px;
                margin: 20px 0;
                border: 1px solid #e0e0e0;
            }
            .info-card h3 {
                margin-top: 0;
                color: #2e7d32;
                font-size: 20px;
                border-bottom: 2px solid #ffb74d;
                padding-bottom: 10px;
                margin-bottom: 20px;
            }
            .info-row {
                display: flex;
                margin-bottom: 15px;
                padding-bottom: 15px;
                border-bottom: 1px dashed #e0e0e0;
            }
            .info-row:last-child {
                border-bottom: none;
                margin-bottom: 0;
                padding-bottom: 0;
            }
            .info-label {
                font-weight: 600;
                min-width: 140px;
                color: #5d4037;
                font-size: 15px;
            }
            .info-value {
                color: #2c3e50;
                font-weight: 500;
                flex: 1;
            }
            .badge-aprobado {
                background: #e8f5e9;
                color: #2e7d32;
                padding: 8px 20px;
                border-radius: 30px;
                font-size: 16px;
                font-weight: 700;
                display: inline-block;
                border: 2px solid #a5d6a7;
                letter-spacing: 1px;
            }
            .motivo-box {
                background: #e8f5e9;
                border: 2px solid #a5d6a7;
                border-radius: 12px;
                padding: 20px;
                margin: 20px 0;
                color: #1b5e20;
            }
            .footer {
                background-color: #f5f5f5;
                padding: 25px;
                text-align: center;
                font-size: 13px;
                color: #757575;
                border-top: 3px solid #ffb74d;
            }
            .btn-contacto {
                display: inline-block;
                padding: 12px 25px;
                background: #2e7d32;
                color: white;
                text-decoration: none;
                border-radius: 50px;
                font-weight: 600;
                margin: 15px 0;
                border: none;
                box-shadow: 0 4px 10px rgba(46, 125, 50, 0.3);
                transition: all 0.3s;
            }
            .btn-contacto:hover {
                background: #1b5e20;
                transform: translateY(-2px);
                box-shadow: 0 6px 15px rgba(27, 94, 32, 0.4);
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="header-icon">✅</div>
                <h1>Sistema de Comedor BacroCorp</h1>
                <p>Gestión de Cancelaciones de Alimentos</p>
            </div>
            
            <div class="content">
                <div class="mensaje-principal">
                    <strong>Estimado/a ' . htmlspecialchars($datos['NOMBRE']) . ',</strong>
                    <p style="margin-top: 15px; font-size: 16px;">
                        Te informamos que tu solicitud de cancelación de servicio de comedor 
                        <strong style="color: #2e7d32;">HA SIDO APROBADA</strong> por ' . $responsable . '.
                    </p>
                </div>
                
                <div class="info-card">
                    <h3>📋 Detalles de tu Solicitud</h3>
                    
                    <div class="info-row">
                        <span class="info-label">👤 Nombre:</span>
                        <span class="info-value"><strong>' . htmlspecialchars($datos['NOMBRE']) . '</strong></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">🏢 Departamento:</span>
                        <span class="info-value">' . htmlspecialchars($datos['DEPARTAMENTO'] ?? 'No especificado') . '</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">👔 Jefe Inmediato:</span>
                        <span class="info-value">' . htmlspecialchars($datos['JEFE'] ?? 'No especificado') . '</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Tipo Consumo:</span>
                        <span class="info-value"><strong>' . $tipo_consumo_formateado . '</strong></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">📅 Fecha solicitud:</span>
                        <span class="info-value"><strong>' . $fecha_formateada . '</strong></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">⏰ Fecha Captura:</span>
                        <span class="info-value"><strong>' . $fecha_captura_formateada . '</strong></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">✅ Estado final:</span>
                        <span class="info-value">
                            <span class="badge-aprobado">APROBADO</span>
                        </span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">👥 Aprobado por:</span>
                        <span class="info-value"><strong>' . $responsable . '</strong></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">📆 Fecha aprobación:</span>
                        <span class="info-value">' . $hoy . '</span>
                    </div>
                </div>
                
                <div class="motivo-box">
                    <p style="margin: 0 0 10px; font-weight: 700; font-size: 18px;">
                        📝 Motivo de la Cancelación:
                    </p>
                    <p style="font-style: italic; font-size: 16px;">
                        "' . htmlspecialchars($datos['CAUSA'] ?? $datos['DESCRIPCION'] ?? 'No especificada') . '"
                    </p>
                </div>
                
                <div style="text-align: center; margin: 30px 0 20px;">
                    <p style="margin-bottom: 20px; color: #5d4037;">
                        <strong>¿Necesitas más información?</strong>
                    </p>
                    <a href="mailto:comedor@bacrocorp.com" class="btn-contacto">
                        <span style="margin-right: 8px;">📧</span> Contactar a Coordinación
                    </a>
                </div>
                
                <p style="text-align: center; color: #9e9e9e; font-size: 13px; margin-top: 30px; font-style: italic;">
                    Este es un mensaje automático del Sistema de Gestión de Comedor.
                </p>
            </div>
            
            <div class="footer">
                <p style="margin: 0 0 5px; font-weight: 600;">BacroCorp - Sistema Integral de Comedor</p>
                <p style="margin: 5px 0;">Alimentando a nuestro mejor recurso: TÚ</p>
                <p style="margin: 10px 0 0; font-size: 11px;">&copy; ' . date('Y') . ' BacroCorp Comedor Industrial</p>
            </div>
        </div>
    </body>
    </html>';
}

// FUNCIÓN PARA CREAR PLANTILLA HTML DE RECHAZO
function crearPlantillaRechazo($datos, $rol_rechazo) {
    $fecha_formateada = date('d/m/Y', strtotime($datos['FECHA']));
    $hoy = date('d/m/Y H:i:s');
    $responsable = ($rol_rechazo == 'Administrador') ? 'Administración del Comedor' : 'Coordinación del Comedor';
    $tipo_consumo_formateado = formatearTipoConsumo($datos['TIPO_CONSUMO'] ?? '');
    $fecha_captura_formateada = formatearFechaCaptura($datos['FECHA_CAPTURA'] ?? '');
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                color: #333;
                margin: 0;
                padding: 0;
                background-color: #f5f5f5;
            }
            .container {
                max-width: 600px;
                margin: 20px auto;
                background-color: #ffffff;
                border-radius: 20px;
                overflow: hidden;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                border: 1px solid #e0e0e0;
            }
            .header {
                background: linear-gradient(135deg, #d32f2f 0%, #b71c1c 100%);
                color: white;
                padding: 30px 20px;
                text-align: center;
                border-bottom: 5px solid #ffb74d;
            }
            .header-icon {
                font-size: 60px;
                margin-bottom: 15px;
            }
            .content {
                padding: 30px;
            }
            .mensaje-principal {
                background: #ffebee;
                border-radius: 15px;
                padding: 25px;
                margin-bottom: 25px;
                border-left: 8px solid #d32f2f;
                font-size: 16px;
                line-height: 1.6;
            }
            .mensaje-principal strong {
                color: #b71c1c;
                font-size: 18px;
            }
            .info-card {
                background: #fafafa;
                border-radius: 15px;
                padding: 25px;
                margin: 20px 0;
                border: 1px solid #e0e0e0;
            }
            .info-card h3 {
                margin-top: 0;
                color: #d32f2f;
                font-size: 20px;
                border-bottom: 2px solid #ffb74d;
                padding-bottom: 10px;
                margin-bottom: 20px;
            }
            .info-row {
                display: flex;
                margin-bottom: 15px;
                padding-bottom: 15px;
                border-bottom: 1px dashed #e0e0e0;
            }
            .info-row:last-child {
                border-bottom: none;
                margin-bottom: 0;
                padding-bottom: 0;
            }
            .info-label {
                font-weight: 600;
                min-width: 140px;
                color: #5d4037;
                font-size: 15px;
            }
            .info-value {
                color: #2c3e50;
                font-weight: 500;
                flex: 1;
            }
            .badge-rechazo {
                background: #ffebee;
                color: #b71c1c;
                padding: 8px 20px;
                border-radius: 30px;
                font-size: 16px;
                font-weight: 700;
                display: inline-block;
                border: 2px solid #ef9a9a;
                letter-spacing: 1px;
            }
            .motivo-box {
                background: #fff3e0;
                border: 2px solid #ffb74d;
                border-radius: 12px;
                padding: 20px;
                margin: 20px 0;
                color: #bf360c;
            }
            .footer {
                background-color: #f5f5f5;
                padding: 25px;
                text-align: center;
                font-size: 13px;
                color: #757575;
                border-top: 3px solid #ffb74d;
            }
            .btn-contacto {
                display: inline-block;
                padding: 12px 25px;
                background: #d32f2f;
                color: white;
                text-decoration: none;
                border-radius: 50px;
                font-weight: 600;
                margin: 15px 0;
                border: none;
                box-shadow: 0 4px 10px rgba(211, 47, 47, 0.3);
                transition: all 0.3s;
            }
            .btn-contacto:hover {
                background: #b71c1c;
                transform: translateY(-2px);
                box-shadow: 0 6px 15px rgba(183, 28, 28, 0.4);
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="header-icon">❌</div>
                <h1>Sistema de Comedor BacroCorp</h1>
                <p>Gestión de Cancelaciones de Alimentos</p>
            </div>
            
            <div class="content">
                <div class="mensaje-principal">
                    <strong>Estimado/a ' . htmlspecialchars($datos['NOMBRE']) . ',</strong>
                    <p style="margin-top: 15px; font-size: 16px;">
                        Lamentamos informarte que tu solicitud de cancelación de servicio de comedor 
                        <strong style="color: #b71c1c;">NO HA SIDO APROBADA</strong> por ' . $responsable . '.
                    </p>
                </div>
                
                <div class="info-card">
                    <h3>📋 Detalles de tu Solicitud</h3>
                    
                    <div class="info-row">
                        <span class="info-label">👤 Nombre:</span>
                        <span class="info-value"><strong>' . htmlspecialchars($datos['NOMBRE']) . '</strong></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">🏢 Departamento:</span>
                        <span class="info-value">' . htmlspecialchars($datos['DEPARTAMENTO'] ?? 'No especificado') . '</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">👔 Jefe Inmediato:</span>
                        <span class="info-value">' . htmlspecialchars($datos['JEFE'] ?? 'No especificado') . '</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Tipo Consumo:</span>
                        <span class="info-value"><strong>' . $tipo_consumo_formateado . '</strong></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">📅 Fecha solicitud:</span>
                        <span class="info-value"><strong>' . $fecha_formateada . '</strong></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">⏰ Fecha Captura:</span>
                        <span class="info-value"><strong>' . $fecha_captura_formateada . '</strong></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">❌ Estado final:</span>
                        <span class="info-value">
                            <span class="badge-rechazo">RECHAZADO</span>
                        </span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">👥 Rechazado por:</span>
                        <span class="info-value"><strong>' . $responsable . '</strong></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">📆 Fecha rechazo:</span>
                        <span class="info-value">' . $hoy . '</span>
                    </div>
                </div>
                
                <div class="motivo-box">
                    <p style="margin: 0 0 10px; font-weight: 700; font-size: 18px;">
                        📝 Motivo de la Cancelación:
                    </p>
                    <p style="font-style: italic; font-size: 16px;">
                        "' . htmlspecialchars($datos['CAUSA'] ?? $datos['DESCRIPCION'] ?? 'No especificada') . '"
                    </p>
                </div>
                
                <div style="text-align: center; margin: 30px 0 20px;">
                    <p style="margin-bottom: 20px; color: #5d4037;">
                        <strong>¿Necesitas ayuda o aclaración?</strong>
                    </p>
                    <a href="mailto:comedor@bacrocorp.com" class="btn-contacto">
                        <span style="margin-right: 8px;">📧</span> Contactar a Coordinación
                    </a>
                </div>
                
                <p style="text-align: center; color: #9e9e9e; font-size: 13px; margin-top: 30px; font-style: italic;">
                    Este es un mensaje automático del Sistema de Gestión de Comedor.
                </p>
            </div>
            
            <div class="footer">
                <p style="margin: 0 0 5px; font-weight: 600;">BacroCorp - Sistema Integral de Comedor</p>
                <p style="margin: 5px 0;">Alimentando a nuestro mejor recurso: TÚ</p>
                <p style="margin: 10px 0 0; font-size: 11px;">&copy; ' . date('Y') . ' BacroCorp Comedor Industrial</p>
            </div>
        </div>
    </body>
    </html>';
}

// FUNCIÓN PARA CREAR VERSIÓN TEXTO PLANO DE APROBACIÓN
function crearTextoPlanoAprobacion($datos, $rol_aprobacion) {
    $fecha_formateada = date('d/m/Y', strtotime($datos['FECHA']));
    $hoy = date('d/m/Y H:i:s');
    $responsable = ($rol_aprobacion == 'Administrador') ? 'Administración del Comedor' : 'Coordinación del Comedor';
    $tipo_consumo = $datos['TIPO_CONSUMO'] ?? 'No especificado';
    $fecha_captura_formateada = formatearFechaCaptura($datos['FECHA_CAPTURA'] ?? '');
    
    return "SISTEMA DE COMEDOR BACROCORP - NOTIFICACIÓN DE APROBACIÓN\n" .
           "============================================================\n\n" .
           "Estimado/a " . $datos['NOMBRE'] . ",\n\n" .
           "Te informamos que tu solicitud de cancelación de servicio de comedor\n" .
           "ha sido APROBADA por " . $responsable . ".\n\n" .
           "DETALLES DE LA SOLICITUD:\n" .
           "----------------------\n" .
           "Nombre: " . $datos['NOMBRE'] . "\n" .
           "Departamento: " . ($datos['DEPARTAMENTO'] ?? 'No especificado') . "\n" .
           "Jefe Inmediato: " . ($datos['JEFE'] ?? 'No especificado') . "\n" .
           "Tipo Consumo: " . $tipo_consumo . "\n" .
           "Fecha solicitada: " . $fecha_formateada . "\n" .
           "Fecha Captura: " . $fecha_captura_formateada . "\n" .
           "Estado: APROBADO\n" .
           "Aprobado por: " . $responsable . "\n" .
           "Fecha aprobación: " . $hoy . "\n\n" .
           "Motivo de la Cancelación:\n" .
           "\"" . ($datos['CAUSA'] ?? $datos['DESCRIPCION'] ?? 'No especificada') . "\"\n\n" .
           "Para cualquier aclaración, contacta a: comedor@bacrocorp.com\n\n" .
           "BacroCorp - Sistema Integral de Comedor";
}

// FUNCIÓN PARA CREAR VERSIÓN TEXTO PLANO DE RECHAZO
function crearTextoPlanoRechazo($datos, $rol_rechazo) {
    $fecha_formateada = date('d/m/Y', strtotime($datos['FECHA']));
    $hoy = date('d/m/Y H:i:s');
    $responsable = ($rol_rechazo == 'Administrador') ? 'Administración del Comedor' : 'Coordinación del Comedor';
    $tipo_consumo = $datos['TIPO_CONSUMO'] ?? 'No especificado';
    $fecha_captura_formateada = formatearFechaCaptura($datos['FECHA_CAPTURA'] ?? '');
    
    return "SISTEMA DE COMEDOR BACROCORP - NOTIFICACIÓN DE RECHAZO\n" .
           "============================================================\n\n" .
           "Estimado/a " . $datos['NOMBRE'] . ",\n\n" .
           "Lamentamos informarte que tu solicitud de cancelación de servicio de comedor\n" .
           "ha sido RECHAZADA por " . $responsable . ".\n\n" .
           "DETALLES DE LA SOLICITUD:\n" .
           "----------------------\n" .
           "Nombre: " . $datos['NOMBRE'] . "\n" .
           "Departamento: " . ($datos['DEPARTAMENTO'] ?? 'No especificado') . "\n" .
           "Jefe Inmediato: " . ($datos['JEFE'] ?? 'No especificado') . "\n" .
           "Tipo Consumo: " . $tipo_consumo . "\n" .
           "Fecha solicitada: " . $fecha_formateada . "\n" .
           "Fecha Captura: " . $fecha_captura_formateada . "\n" .
           "Estado: RECHAZADO\n" .
           "Rechazado por: " . $responsable . "\n" .
           "Fecha rechazo: " . $hoy . "\n\n" .
           "Motivo de la Cancelación:\n" .
           "\"" . ($datos['CAUSA'] ?? $datos['DESCRIPCION'] ?? 'No especificada') . "\"\n\n" .
           "Para cualquier aclaración, contacta a: comedor@bacrocorp.com\n\n" .
           "BacroCorp - Sistema Integral de Comedor";
}

// Mostrar mensaje de éxito si viene por GET
if (isset($_GET['mensaje'])) {
    $mensaje_exito = urldecode($_GET['mensaje']);
    $tipo_mensaje = $_GET['tipo'] ?? 'success';
}

// ============================================
// CONSULTA PRINCIPAL CORREGIDA - SIN DUPLICAR FECHA_CAPTURA
// ============================================
if ($filtro_estado == 'pendientes') {
    if ($rol == 'Administrador') {
        $sql = "SELECT *, DESCRIPCION as DESCRIPCION_DETALLE FROM cancelaciones 
                WHERE (Estatus != 'APROBADO' OR Estatus IS NULL) 
                AND Estatus != 'RECHAZADO'
                AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE()) 
                ORDER BY FECHA_CAPTURA DESC, FECHA DESC";
    } elseif ($rol == 'Coordinador') {
        $sql = "SELECT *, DESCRIPCION as DESCRIPCION_DETALLE FROM cancelaciones 
                WHERE (ValJefDirect != 'APROBADO' OR ValJefDirect IS NULL) 
                AND ValJefDirect != 'RECHAZADO'
                AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE()) 
                ORDER BY FECHA_CAPTURA DESC, FECHA DESC";
    } else {
        $sql = "SELECT *, DESCRIPCION as DESCRIPCION_DETALLE FROM cancelaciones 
                WHERE ((Estatus != 'APROBADO' OR Estatus IS NULL) 
                OR (ValJefDirect != 'APROBADO' OR ValJefDirect IS NULL))
                AND (Estatus != 'RECHAZADO' OR Estatus IS NULL)
                AND (ValJefDirect != 'RECHAZADO' OR ValJefDirect IS NULL)
                AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE()) 
                ORDER BY FECHA_CAPTURA DESC, FECHA DESC";
    }
} else {
    if ($rol == 'Administrador') {
        $sql = "SELECT *, DESCRIPCION as DESCRIPCION_DETALLE FROM cancelaciones 
                WHERE Estatus = 'APROBADO' 
                AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE()) 
                ORDER BY FECHA_CAPTURA DESC, FECHA DESC";
    } elseif ($rol == 'Coordinador') {
        $sql = "SELECT *, DESCRIPCION as DESCRIPCION_DETALLE FROM cancelaciones 
                WHERE ValJefDirect = 'APROBADO' 
                AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE()) 
                ORDER BY FECHA_CAPTURA DESC, FECHA DESC";
    } else {
        $sql = "SELECT *, DESCRIPCION as DESCRIPCION_DETALLE FROM cancelaciones 
                WHERE (Estatus = 'APROBADO' OR ValJefDirect = 'APROBADO')
                AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE()) 
                ORDER BY FECHA_CAPTURA DESC, FECHA DESC";
    }
}

// Ejecutar consulta
$stmt = sqlsrv_query($conn, $sql);
$registros = array();

if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $value = trim($value);
                $encoding = mb_detect_encoding($value, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
                if ($encoding != 'UTF-8') {
                    $value = mb_convert_encoding($value, 'UTF-8', $encoding);
                }
                $row[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        }
        $registros[] = $row;
    }
}

// CONTADORES
if ($rol == 'Administrador') {
    $sql_pendientes = "SELECT COUNT(*) as total FROM cancelaciones 
                      WHERE (Estatus != 'APROBADO' OR Estatus IS NULL) 
                      AND Estatus != 'RECHAZADO'
                      AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE())";
} elseif ($rol == 'Coordinador') {
    $sql_pendientes = "SELECT COUNT(*) as total FROM cancelaciones 
                      WHERE (ValJefDirect != 'APROBADO' OR ValJefDirect IS NULL) 
                      AND ValJefDirect != 'RECHAZADO'
                      AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE())";
} else {
    $sql_pendientes = "SELECT COUNT(*) as total FROM cancelaciones 
                      WHERE ((Estatus != 'APROBADO' OR Estatus IS NULL) 
                      OR (ValJefDirect != 'APROBADO' OR ValJefDirect IS NULL))
                      AND (Estatus != 'RECHAZADO' OR Estatus IS NULL)
                      AND (ValJefDirect != 'RECHAZADO' OR ValJefDirect IS NULL)
                      AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE())";
}

if ($rol == 'Administrador') {
    $sql_aprobados = "SELECT COUNT(*) as total FROM cancelaciones 
                     WHERE Estatus = 'APROBADO' 
                     AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE())";
} elseif ($rol == 'Coordinador') {
    $sql_aprobados = "SELECT COUNT(*) as total FROM cancelaciones 
                     WHERE ValJefDirect = 'APROBADO' 
                     AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE())";
} else {
    $sql_aprobados = "SELECT COUNT(*) as total FROM cancelaciones 
                     WHERE (Estatus = 'APROBADO' OR ValJefDirect = 'APROBADO')
                     AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE())";
}

// Ejecutar contadores
$stmt_p = sqlsrv_query($conn, $sql_pendientes);
$stmt_a = sqlsrv_query($conn, $sql_aprobados);

$total_pendientes = 0;
$total_aprobados = 0;

if ($stmt_p && $row = sqlsrv_fetch_array($stmt_p, SQLSRV_FETCH_ASSOC)) {
    $total_pendientes = $row['total'];
}

if ($stmt_a && $row = sqlsrv_fetch_array($stmt_a, SQLSRV_FETCH_ASSOC)) {
    $total_aprobados = $row['total'];
}

sqlsrv_close($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Validación - Comedor BacroCorp</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 15px;
        }
        
        .header-card {
            background: linear-gradient(135deg, #1e88e5, #1565c0);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .filter-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .filter-btn {
            text-decoration: none;
            color: #333;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px 20px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .filter-btn.active {
            background: #1e88e5;
            color: white;
            border-color: #1e88e5;
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .badge-estado {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-aprobado {
            background-color: #e8f5e9;
            color: #43a047;
            border: 1px solid #c8e6c9;
        }
        
        .badge-pendiente {
            background-color: #fff3e0;
            color: #ef6c00;
            border: 1px solid #ffe0b2;
        }
        
        .btn-validate {
            background: #43a047;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 12px;
            transition: all 0.3s;
            cursor: pointer;
            margin-right: 5px;
        }
        
        .btn-validate:hover {
            background: #2e7d32;
            transform: translateY(-1px);
        }
        
        .btn-reject {
            background: #d32f2f;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 12px;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .btn-reject:hover {
            background: #b71c1c;
            transform: translateY(-1px);
        }
        
        .btn-disabled {
            background: #e0e0e0;
            color: #9e9e9e;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 12px;
            cursor: not-allowed;
        }
        
        .selected-row {
            background-color: rgba(33, 150, 243, 0.1) !important;
            border-left: 3px solid #1e88e5 !important;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .action-toggle {
            display: flex;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .action-toggle-btn {
            flex: 1;
            padding: 8px 15px;
            border: none;
            background: white;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }
        
        .action-toggle-btn.active {
            background: #1e88e5;
            color: white;
        }
        
        .action-toggle-btn:first-child {
            border-right: 1px solid #dee2e6;
        }
        
        .action-toggle-btn.aprobar.active {
            background: #43a047;
        }
        
        .action-toggle-btn.rechazar.active {
            background: #d32f2f;
        }
        
        /* ===== TOOLTIP MEJORADO - BASADO EN TU CÓDIGO ORIGINAL ===== */
        .causa-cell {
            cursor: pointer;
            position: relative;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding: 8px;
        }
        
        .causa-cell:hover {
            background-color: rgba(33, 150, 243, 0.05);
        }
        
        .tooltip-descripcion {
            display: none;
            position: fixed;
            background: #2c3e50;
            color: white;
            padding: 16px 20px;
            border-radius: 12px;
            max-width: 380px;
            width: auto;
            min-width: 300px;
            z-index: 10000;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            font-size: 14px;
            line-height: 1.5;
            word-wrap: break-word;
            white-space: normal;
            pointer-events: none;
            border-left: 5px solid #3498db;
            animation: fadeIn 0.2s ease;
            transition: opacity 0.2s;
            backdrop-filter: blur(2px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .tooltip-descripcion.show {
            display: block;
        }
        
        .tooltip-header {
            font-weight: 700;
            color: #3498db;
            margin-bottom: 12px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .tooltip-header i {
            color: #3498db;
            font-size: 18px;
        }
        
        .tooltip-content {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .tooltip-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .tooltip-item-label {
            font-weight: 600;
            color: #bdc3c7;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .tooltip-item-label i {
            color: #3498db;
            width: 16px;
            font-size: 12px;
        }
        
        .tooltip-item-value {
            color: #ecf0f1;
            font-weight: 400;
            word-break: break-word;
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 14px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .tooltip-item-value.nombre {
            font-weight: 600;
            color: #3498db;
            background: rgba(52, 152, 219, 0.15);
        }
        
        .tooltip-item-value.descripcion {
            max-height: 150px;
            overflow-y: auto;
            line-height: 1.5;
            white-space: pre-wrap;
            word-break: break-word;
        }
        
        .tooltip-item-value.descripcion::-webkit-scrollbar {
            width: 6px;
        }
        
        .tooltip-item-value.descripcion::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        
        .tooltip-item-value.descripcion::-webkit-scrollbar-thumb {
            background: #3498db;
            border-radius: 10px;
        }
        
        .tooltip-arrow {
            position: absolute;
            width: 14px;
            height: 14px;
            background: #2c3e50;
            transform: rotate(45deg);
            bottom: -7px;
            left: 50%;
            margin-left: -7px;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }
        
        .text-truncate {
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* ===== TOOLTIP RESPONSIVE ===== */
        @media (max-width: 768px) {
            .tooltip-descripcion {
                max-width: 90vw;
                min-width: 250px;
                left: 5vw !important;
                right: 5vw;
                padding: 14px 16px;
            }
            
            .tooltip-item-value.descripcion {
                max-height: 120px;
            }
        }
        
        /* ===== FIN TOOLTIP ===== */
        
        .causa-cell:hover::after {
            content: "Ver detalles";
            position: absolute;
            top: -28px;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            white-space: nowrap;
            z-index: 100;
            pointer-events: none;
            animation: fadeIn 0.2s ease;
            font-weight: 500;
        }
        
        .causa-cell:hover::before {
            content: "";
            position: absolute;
            top: -8px;
            left: 50%;
            transform: translateX(-50%);
            border: 4px solid transparent;
            border-top-color: #333;
            z-index: 100;
            pointer-events: none;
        }
        
        .fecha-captura-badge {
            background-color: #e3f2fd;
            color: #0d47a1;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border: 1px solid #90caf9;
            white-space: nowrap;
        }
        
        .fecha-captura-badge i {
            font-size: 10px;
        }
    </style>
</head>
<body>
    <?php if (!empty($mensaje_exito)): ?>
    <div class="alert alert-<?php echo $tipo_mensaje == 'success' ? 'success' : ($tipo_mensaje == 'warning' ? 'warning' : 'danger'); ?> alert-dismissible fade show" role="alert">
        <?php if ($tipo_mensaje == 'success'): ?>
            <i class="fas fa-check-circle me-2"></i>
        <?php elseif ($tipo_mensaje == 'warning'): ?>
            <i class="fas fa-exclamation-triangle me-2"></i>
        <?php else: ?>
            <i class="fas fa-exclamation-circle me-2"></i>
        <?php endif; ?>
        <?php echo $mensaje_exito; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <div class="container-fluid">
        <!-- Header -->
        <div class="header-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="h4 mb-2">
                        <i class="fas fa-utensils me-2"></i>Validación de Cancelaciones - Comedor BacroCorp
                    </h1>
                    <p class="mb-0 opacity-75">
                        Sistema de Gestión de Alimentos
                        <?php if($rol): ?>
                        <br><small><i class="fas fa-user-tag me-1"></i> Rol: <?php echo htmlspecialchars($rol); ?></small>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="http://192.168.100.95/Comedor" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver al Menú
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="filter-card">
            <h5 class="mb-3 text-dark"><i class="fas fa-filter me-2"></i>Filtrar por Estado</h5>
            <div class="d-flex gap-3 flex-wrap">
                <a href="?filtro=pendientes<?php echo $rol ? '&newpwd=' . htmlspecialchars($rol) : ''; ?>" 
                   class="filter-btn <?php echo $filtro_estado == 'pendientes' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i>
                    Pendientes
                    <span class="badge bg-warning ms-2"><?php echo $total_pendientes; ?></span>
                </a>
                
                <a href="?filtro=aprobados<?php echo $rol ? '&newpwd=' . htmlspecialchars($rol) : ''; ?>" 
                   class="filter-btn <?php echo $filtro_estado == 'aprobados' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i>
                    Aprobados
                    <span class="badge bg-success ms-2"><?php echo $total_aprobados; ?></span>
                </a>
            </div>
        </div>
        
        <!-- Formulario de validación en lote -->
        <div class="filter-card">
            <h5 class="mb-3 text-dark"><i class="fas fa-sliders-h me-2"></i>Validación Rápida</h5>
            <form method="post" action="" id="validationForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Tipo de Validación</label>
                        <select id="TIPOVALIDA" name="TIPOVALIDA" class="form-select form-select-sm" required>
                            <option value="MULTIPLE">Validación Múltiple (Todo el departamento)</option>
                            <option value="UNICA">Validación Única (Un solo registro)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Departamento</label>
                        <select id="DEPARTAMENTO" name="DEPARTAMENTO" class="form-select form-select-sm">
                            <option value="">Seleccionar</option>
                            <option>Operaciones</option>
                            <option>Talento Humano</option>
                            <option>Finanzas</option>
                            <option>Administración</option>
                            <option>Auditoría</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Nombre</label>
                        <input type="text" class="form-control form-control-sm" id="name123" name="name123" 
                               placeholder="Selecciona de la tabla" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Fecha</label>
                        <input type="text" class="form-control form-control-sm" id="name1234" name="name1234" 
                               placeholder="Selecciona de la tabla" readonly>
                    </div>
                    
                    <input type="hidden" id="tipo_consumo_lote" name="tipo_consumo_lote" value="">
                    
                    <!-- Selector de acción -->
                    <div class="col-12">
                        <div class="action-toggle">
                            <button type="button" class="action-toggle-btn aprobar active" data-action="aprobar">
                                <i class="fas fa-check me-1"></i> Aprobar
                            </button>
                            <button type="button" class="action-toggle-btn rechazar" data-action="rechazar">
                                <i class="fas fa-times me-1"></i> Rechazar
                            </button>
                        </div>
                        <input type="hidden" name="accion_lote" id="accionLote" value="aprobar">
                    </div>
                    
                    <div class="col-12">
                        <button type="submit" name="validar_lote" class="btn btn-primary w-100 btn-sm">
                            <i class="fas fa-play-circle me-2"></i> Ejecutar Validación
                        </button>
                    </div>
                </div>
                <div class="mt-2 text-muted small">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Importante:</strong> Cada registro se procesa individualmente con su tipo de consumo específico.
                </div>
            </form>
        </div>
        
        <!-- Tabla de registros -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="text-dark mb-0">
                    <i class="fas fa-utensils me-2"></i>Registros de Cancelaciones
                </h5>
                <button class="btn btn-outline-primary btn-sm" onclick="window.location.reload()">
                    <i class="fas fa-redo"></i> Actualizar
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="tablaRegistros">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Departamento</th>
                            <th>Jefe Inmediato</th>
                            <th>Tipo Consumo</th>
                            <th>Fecha</th>
                            <th>Fecha Captura</th>
                            <th>Causa</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($registros)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="fas fa-utensils fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No hay registros encontrados</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($registros as $index => $registro): ?>
                        <?php 
                            $estatus = strtoupper(trim($registro['Estatus'] ?? ''));
                            $valJef = strtoupper(trim($registro['ValJefDirect'] ?? ''));
                            $tipo_consumo_mostrar = formatearTipoConsumo($registro['TIPO_CONSUMO'] ?? '');
                            $fecha_captura_mostrar = formatearFechaCaptura($registro['FECHA_CAPTURA'] ?? '');
                            
                            if ($filtro_estado == 'aprobados') {
                                $badge_class = 'badge-aprobado';
                                $badge_text = 'APROBADO';
                                $badge_icon = 'fa-check-circle';
                            } else {
                                if ($estatus == 'APROBADO' || $valJef == 'APROBADO') {
                                    $badge_class = 'badge-aprobado';
                                    $badge_text = 'APROBADO';
                                    $badge_icon = 'fa-check-circle';
                                } else {
                                    $badge_class = 'badge-pendiente';
                                    $badge_text = 'PENDIENTE';
                                    $badge_icon = 'fa-clock';
                                }
                            }
                            
                            $puede_aprobar = false;
                            $puede_rechazar = false;
                            
                            if ($filtro_estado == 'pendientes') {
                                if ($rol == 'Administrador' && $estatus != 'APROBADO' && $estatus != 'RECHAZADO') {
                                    $puede_aprobar = true;
                                    $puede_rechazar = true;
                                } elseif ($rol == 'Coordinador' && $valJef != 'APROBADO' && $valJef != 'RECHAZADO') {
                                    $puede_aprobar = true;
                                    $puede_rechazar = true;
                                }
                            }
                            
                            $form_aprobar_id = 'formAprobar_' . $index;
                            $form_rechazar_id = 'formRechazar_' . $index;
                            
                            // Obtener la descripción completa
                            $descripcion_completa = $registro['DESCRIPCION_DETALLE'] ?? $registro['DESCRIPCION'] ?? '';
                            $causa_mostrar = $registro['CAUSA'] ?? '';
                        ?>
                        <tr class="row-selectable"
                            data-nombre="<?php echo htmlspecialchars($registro['NOMBRE'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-fecha="<?php echo htmlspecialchars($registro['FECHA'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-departamento="<?php echo htmlspecialchars($registro['DEPARTAMENTO'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-tipo-consumo="<?php echo htmlspecialchars($registro['TIPO_CONSUMO'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            
                            <td><?php echo $registro['NOMBRE'] ?? ''; ?></td>
                            <td><?php echo $registro['DEPARTAMENTO'] ?? ''; ?></td>
                            <td><?php echo $registro['JEFE'] ?? ''; ?></td>
                            <td><?php echo $tipo_consumo_mostrar; ?></td>
                            <td><?php echo $registro['FECHA'] ?? ''; ?></td>
                            <td>
                                <span class="fecha-captura-badge">
                                    <i class="far fa-clock"></i>
                                    <?php echo $fecha_captura_mostrar; ?>
                                </span>
                            </td>
                            <td class="causa-cell" 
                                data-nombre="<?php echo htmlspecialchars($registro['NOMBRE'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                data-fecha="<?php echo htmlspecialchars($registro['FECHA'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                data-descripcion="<?php echo htmlspecialchars($descripcion_completa, ENT_QUOTES, 'UTF-8'); ?>"
                                data-causa="<?php echo htmlspecialchars($causa_mostrar, ENT_QUOTES, 'UTF-8'); ?>"
                                onmouseenter="mostrarTooltipCompleto(event, this)"
                                onmouseleave="ocultarTooltipDescripcion()">
                                <div class="text-truncate" style="max-width: 200px;">
                                    <?php 
                                    echo !empty($causa_mostrar) ? $causa_mostrar : '<span class="text-muted fst-italic">Sin causa especificada</span>';
                                    ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge-estado <?php echo $badge_class; ?>">
                                    <i class="fas <?php echo $badge_icon; ?> me-1"></i>
                                    <?php echo $badge_text; ?>
                                </span>
                                <br>
                                <small class="text-muted" style="font-size: 10px;">
                                    <?php 
                                    if ($estatus == 'APROBADO') echo '✓ Admin'; 
                                    elseif ($estatus == 'RECHAZADO') echo '✗ Admin';
                                    
                                    if ($valJef == 'APROBADO') echo ' ✓ Coord';
                                    elseif ($valJef == 'RECHAZADO') echo ' ✗ Coord';
                                    ?>
                                </small>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($puede_aprobar): ?>
                                    <form method="post" action="" id="<?php echo $form_aprobar_id; ?>" style="display: inline;">
                                        <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($registro['NOMBRE'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="fecha" value="<?php echo htmlspecialchars($registro['FECHA'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="tipo_consumo" value="<?php echo htmlspecialchars($registro['TIPO_CONSUMO'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="departamento" value="<?php echo htmlspecialchars($registro['DEPARTAMENTO'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="accion" value="aprobar">
                                        <input type="hidden" name="validar_individual" value="1">
                                        <button type="button" class="btn-validate" onclick="mostrarConfirmacion('<?php echo $form_aprobar_id; ?>', 'aprobar', '<?php echo htmlspecialchars($registro['NOMBRE'] ?? '', ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($registro['FECHA'] ?? '', ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($registro['TIPO_CONSUMO'] ?? '', ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($registro['DEPARTAMENTO'] ?? '', ENT_QUOTES, 'UTF-8'); ?>')">
                                            <i class="fas fa-check me-1"></i> Aprobar
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($puede_rechazar): ?>
                                    <form method="post" action="" id="<?php echo $form_rechazar_id; ?>" style="display: inline;">
                                        <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($registro['NOMBRE'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="fecha" value="<?php echo htmlspecialchars($registro['FECHA'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="tipo_consumo" value="<?php echo htmlspecialchars($registro['TIPO_CONSUMO'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="departamento" value="<?php echo htmlspecialchars($registro['DEPARTAMENTO'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="accion" value="rechazar">
                                        <input type="hidden" name="validar_individual" value="1">
                                        <button type="button" class="btn-reject" onclick="mostrarConfirmacion('<?php echo $form_rechazar_id; ?>', 'rechazar', '<?php echo htmlspecialchars($registro['NOMBRE'] ?? '', ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($registro['FECHA'] ?? '', ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($registro['TIPO_CONSUMO'] ?? '', ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($registro['DEPARTAMENTO'] ?? '', ENT_QUOTES, 'UTF-8'); ?>')">
                                            <i class="fas fa-times me-1"></i> Rechazar
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <?php if (!$puede_aprobar && !$puede_rechazar): ?>
                                    <button type="button" class="btn-disabled" disabled>
                                        <?php if ($filtro_estado == 'aprobados'): ?>
                                            <i class="fas fa-check me-1"></i> Ya Aprobado
                                        <?php else: ?>
                                            <i class="fas fa-lock me-1"></i> No Disponible
                                        <?php endif; ?>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Tooltip mejorado -->
    <div class="tooltip-descripcion" id="tooltipDescripcion">
        <div class="tooltip-header">
            <i class="fas fa-info-circle"></i>
            <span>Información del Registro</span>
        </div>
        <div class="tooltip-content" id="tooltipContent"></div>
        <div class="tooltip-arrow"></div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        let currentFormId = '';
        let tooltipTimeout = null;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle de acción
            const actionToggleButtons = document.querySelectorAll('.action-toggle-btn');
            if (actionToggleButtons.length > 0) {
                actionToggleButtons.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const action = this.dataset.action;
                        
                        document.querySelectorAll('.action-toggle-btn').forEach(b => {
                            b.classList.remove('active', 'aprobar', 'rechazar');
                            if (b.dataset.action === action) {
                                b.classList.add('active');
                                if (action === 'aprobar') {
                                    b.classList.add('aprobar');
                                } else {
                                    b.classList.add('rechazar');
                                }
                            }
                        });
                        
                        const accionLoteInput = document.getElementById('accionLote');
                        if (accionLoteInput) {
                            accionLoteInput.value = action;
                        }
                        
                        const submitBtn = document.querySelector('button[name="validar_lote"]');
                        if (submitBtn) {
                            if (action === 'aprobar') {
                                submitBtn.className = 'btn btn-success w-100 btn-sm';
                                submitBtn.innerHTML = '<i class="fas fa-check-circle me-2"></i> Ejecutar Aprobación';
                            } else {
                                submitBtn.className = 'btn btn-danger w-100 btn-sm';
                                submitBtn.innerHTML = '<i class="fas fa-times-circle me-2"></i> Ejecutar Rechazo';
                            }
                        }
                    });
                });
            }
            
            // Selección de filas
            const selectableRows = document.querySelectorAll('.row-selectable');
            if (selectableRows.length > 0) {
                selectableRows.forEach(row => {
                    row.addEventListener('click', function() {
                        document.querySelectorAll('.row-selectable').forEach(r => {
                            r.classList.remove('selected-row');
                        });
                        
                        this.classList.add('selected-row');
                        
                        const nombre = this.dataset.nombre;
                        const fecha = this.dataset.fecha;
                        const departamento = this.dataset.departamento;
                        const tipoConsumo = this.dataset.tipoConsumo;
                        
                        const name123Input = document.getElementById('name123');
                        const name1234Input = document.getElementById('name1234');
                        const departamentoSelect = document.getElementById('DEPARTAMENTO');
                        const tipoConsumoInput = document.getElementById('tipo_consumo_lote');
                        
                        if (name123Input) name123Input.value = nombre || '';
                        if (name1234Input) name1234Input.value = fecha || '';
                        if (departamentoSelect) departamentoSelect.value = departamento || '';
                        if (tipoConsumoInput) tipoConsumoInput.value = tipoConsumo || '';
                    });
                });
            }
            
            // Tipo de validación
            const tipoValidaSelect = document.getElementById('TIPOVALIDA');
            if (tipoValidaSelect) {
                tipoValidaSelect.addEventListener('change', function() {
                    const departamentoSelect = document.getElementById('DEPARTAMENTO');
                    const name123Input = document.getElementById('name123');
                    const name1234Input = document.getElementById('name1234');
                    const tipoConsumoInput = document.getElementById('tipo_consumo_lote');
                    
                    if (this.value === 'UNICA') {
                        if (departamentoSelect) departamentoSelect.value = '';
                        if (departamentoSelect) departamentoSelect.disabled = true;
                        if (name123Input) name123Input.disabled = false;
                        if (name1234Input) name1234Input.disabled = false;
                    } else {
                        if (name123Input) {
                            name123Input.value = '';
                            name123Input.disabled = true;
                        }
                        if (name1234Input) {
                            name1234Input.value = '';
                            name1234Input.disabled = true;
                        }
                        if (tipoConsumoInput) tipoConsumoInput.value = '';
                        if (departamentoSelect) departamentoSelect.disabled = false;
                    }
                });
            }
            
            window.addEventListener('scroll', function() {
                ocultarTooltipDescripcion();
            });
            
            // Auto-cerrar alertas
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(alert => {
                    try {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    } catch (e) {
                        alert.style.display = 'none';
                    }
                });
            }, 5000);
        });
        
        function mostrarTooltipCompleto(event, element) {
            if (tooltipTimeout) {
                clearTimeout(tooltipTimeout);
            }
            
            tooltipTimeout = setTimeout(function() {
                const cell = element || event.currentTarget;
                
                if (!cell || !cell.dataset) {
                    ocultarTooltipDescripcion();
                    return;
                }
                
                const nombre = cell.dataset.nombre || 'No disponible';
                const fecha = cell.dataset.fecha || 'No disponible';
                const descripcion = cell.dataset.descripcion || 'Sin descripción disponible';
                const causa = cell.dataset.causa || '';
                
                const tooltip = document.getElementById('tooltipDescripcion');
                const tooltipContent = document.getElementById('tooltipContent');
                
                if (!tooltip || !tooltipContent) return;
                
                // Usar descripción si está disponible, si no, usar causa
                const descripcionMostrar = descripcion || causa || 'Sin descripción disponible';
                
                tooltipContent.innerHTML = `
                    <div class="tooltip-item">
                        <span class="tooltip-item-label"><i class="fas fa-user"></i> Nombre</span>
                        <span class="tooltip-item-value nombre">${nombre}</span>
                    </div>
                    <div class="tooltip-item">
                        <span class="tooltip-item-label"><i class="fas fa-calendar"></i> Fecha</span>
                        <span class="tooltip-item-value">${fecha}</span>
                    </div>
                    <div class="tooltip-item">
                        <span class="tooltip-item-label"><i class="fas fa-align-left"></i> Descripción</span>
                        <span class="tooltip-item-value descripcion">${descripcionMostrar}</span>
                    </div>
                `;
                
                // Posicionar el tooltip
                const rect = cell.getBoundingClientRect();
                
                tooltip.style.visibility = 'hidden';
                tooltip.style.display = 'block';
                
                const tooltipWidth = tooltip.offsetWidth;
                const tooltipHeight = tooltip.offsetHeight;
                const windowWidth = window.innerWidth;
                const windowHeight = window.innerHeight;
                
                let left = rect.left + window.scrollX + (rect.width / 2) - (tooltipWidth / 2);
                let top = rect.top + window.scrollY - tooltipHeight - 10;
                
                // Ajustar si se sale de la pantalla
                if (left < 10) left = 10;
                if (left + tooltipWidth > windowWidth - 10) left = windowWidth - tooltipWidth - 10;
                if (top < 10) top = rect.bottom + window.scrollY + 10;
                if (top + tooltipHeight > windowHeight - 10) top = windowHeight - tooltipHeight - 10;
                
                tooltip.style.left = left + 'px';
                tooltip.style.top = top + 'px';
                tooltip.style.visibility = 'visible';
                tooltip.classList.add('show');
                
            }, 200);
        }
        
        function ocultarTooltipDescripcion() {
            if (tooltipTimeout) {
                clearTimeout(tooltipTimeout);
                tooltipTimeout = null;
            }
            
            const tooltip = document.getElementById('tooltipDescripcion');
            if (tooltip) {
                tooltip.classList.remove('show');
                tooltip.style.display = 'none';
            }
        }
        
        function mostrarConfirmacion(formId, accion, nombre, fecha, tipoConsumo, departamento) {
            currentFormId = formId;
            
            let accionTexto = accion === 'aprobar' ? 'APROBAR' : 'RECHAZAR';
            let color = accion === 'aprobar' ? '#43a047' : '#d32f2f';
            let icono = accion === 'aprobar' ? '✅' : '❌';
            
            let tipoConsumoMostrar = tipoConsumo;
            if (tipoConsumo.toUpperCase().includes('DESAYUNO') && tipoConsumo.toUpperCase().includes('COMIDA')) {
                tipoConsumoMostrar = '🌅 Desayuno y 🍲 Comida';
            } else if (tipoConsumo.toUpperCase().includes('DESAYUNO')) {
                tipoConsumoMostrar = '🌅 Desayuno';
            } else if (tipoConsumo.toUpperCase().includes('COMIDA')) {
                tipoConsumoMostrar = '🍲 Comida';
            } else if (tipoConsumo.toUpperCase().includes('AMBOS')) {
                tipoConsumoMostrar = '🌅 Desayuno y 🍲 Comida';
            }
            
            Swal.fire({
                title: 'Confirmar ' + accionTexto,
                html: `
                    <div style="text-align: left;">
                        <p><strong>Nombre:</strong> ${nombre}</p>
                        <p><strong>Fecha:</strong> ${fecha}</p>
                        <p><strong>Tipo Consumo:</strong> ${tipoConsumoMostrar}</p>
                        <p><strong>Departamento:</strong> ${departamento}</p>
                        <p><strong>Acción:</strong> <span style="color: ${color}; font-weight: bold;">${accionTexto}</span></p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: color,
                cancelButtonColor: '#6c757d',
                confirmButtonText: `${icono} Sí, ${accionTexto}`,
                cancelButtonText: 'Cancelar',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Enviando notificación',
                        html: `
                            <div style="text-align: center;">
                                <i class="fas fa-envelope fa-3x" style="color: #1e88e5; margin-bottom: 15px;"></i>
                                <p>Enviando correo electrónico de notificación...</p>
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Enviando...</span>
                                </div>
                            </div>
                        `,
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        didOpen: () => {
                            setTimeout(() => {
                                const formElement = document.getElementById(currentFormId);
                                if (formElement) {
                                    formElement.submit();
                                }
                            }, 500);
                        }
                    });
                }
            });
        }
        
        // Validación de formulario de lote
        document.getElementById('validationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const tipo = document.getElementById('TIPOVALIDA').value;
            const accion = document.getElementById('accionLote').value;
            
            if (tipo === 'UNICA') {
                const nombre = document.getElementById('name123').value;
                const fecha = document.getElementById('name1234').value;
                const tipoConsumo = document.getElementById('tipo_consumo_lote').value;
                
                if (!nombre || !fecha || !tipoConsumo) {
                    Swal.fire('Atención', 'Selecciona un registro de la tabla', 'warning');
                    return;
                }
                
                mostrarConfirmacionLote(nombre, fecha, tipoConsumo, '', accion);
            } else {
                const depto = document.getElementById('DEPARTAMENTO').value;
                if (!depto) {
                    Swal.fire('Atención', 'Selecciona un departamento', 'warning');
                    return;
                }
                
                mostrarConfirmacionLote('', '', '', depto, accion);
            }
        });
        
        function mostrarConfirmacionLote(nombre, fecha, tipoConsumo, departamento, accion) {
            let html = '';
            if (departamento) {
                html = `<p><strong>Departamento:</strong> ${departamento}</p><p><strong>Tipo:</strong> Validación múltiple</p>`;
            } else {
                html = `<p><strong>Nombre:</strong> ${nombre}</p><p><strong>Fecha:</strong> ${fecha}</p><p><strong>Tipo:</strong> ${tipoConsumo}</p>`;
            }
            
            Swal.fire({
                title: accion === 'aprobar' ? 'Confirmar Aprobación' : 'Confirmar Rechazo',
                html: html,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: accion === 'aprobar' ? '#43a047' : '#d32f2f',
                confirmButtonText: accion === 'aprobar' ? '✅ Sí, aprobar' : '❌ Sí, rechazar'
            }).then(result => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Procesando...',
                        html: '<div class="spinner-border text-primary"></div>',
                        showConfirmButton: false
                    });
                    setTimeout(() => {
                        document.getElementById('validationForm').submit();
                    }, 500);
                }
            });
        }
    </script>
</body>
</html>