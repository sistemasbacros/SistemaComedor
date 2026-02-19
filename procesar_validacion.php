<?php
// procesar_validacion.php
header('Content-Type: application/json');

// Configuración de la base de datos
require_once __DIR__ . '/config/database.php';
$conn = getComedorConnection();

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

// Obtener datos del POST
$tipoValidacion = $_POST['tipoValidacion'] ?? '';
$departamento = $_POST['departamento'] ?? '';
$userType = $_POST['userType'] ?? '';
$registros = json_decode($_POST['registros'], true);

$registrosActualizados = [];

try {
    // Procesar cada registro
    foreach ($registros as $registro) {
        $nombre = $registro['nombre'];
        $fecha = $registro['fecha'];
        $index = $registro['index'];
        
        // Convertir fecha al formato de la base de datos (YYYY-MM-DD)
        $fechaParts = explode('/', $fecha);
        if (count($fechaParts) === 3) {
            $fechaSql = $fechaParts[2] . '-' . $fechaParts[1] . '-' . $fechaParts[0];
        } else {
            $fechaSql = $fecha;
        }
        
        // Determinar la consulta según el tipo de usuario y validación
        if ($userType == 'Administrador') {
            if ($tipoValidacion == 'ÚNICA') {
                $sql = "UPDATE cancelaciones SET Estatus='APROBADO' WHERE Nombre=? AND CONVERT(VARCHAR, Fecha, 23)=?";
                $params = array($nombre, $fechaSql);
                $nuevoEstatus = 'APROBADO';
                $nuevaValidacionJefe = $registro['validacionJefe'];
            } else {
                $var5 = substr($departamento, 0, 4);
                $sql = "UPDATE cancelaciones SET Estatus='APROBADO' WHERE DEPARTAMENTO LIKE ? AND Nombre=? AND CONVERT(VARCHAR, Fecha, 23)=?";
                $params = array('%' . $var5 . '%', $nombre, $fechaSql);
                $nuevoEstatus = 'APROBADO';
                $nuevaValidacionJefe = $registro['validacionJefe'];
            }
        } elseif ($userType == 'Coordinador') {
            if ($tipoValidacion == 'ÚNICA') {
                $sql = "UPDATE cancelaciones SET ValJefDirect='APROBADO' WHERE Nombre=? AND CONVERT(VARCHAR, Fecha, 23)=?";
                $params = array($nombre, $fechaSql);
                $nuevoEstatus = $registro['estatus'];
                $nuevaValidacionJefe = 'APROBADO';
            } else {
                $var5 = substr($departamento, 0, 4);
                $sql = "UPDATE cancelaciones SET ValJefDirect='APROBADO' WHERE DEPARTAMENTO LIKE ? AND Nombre=? AND CONVERT(VARCHAR, Fecha, 23)=?";
                $params = array('%' . $var5 . '%', $nombre, $fechaSql);
                $nuevoEstatus = $registro['estatus'];
                $nuevaValidacionJefe = 'APROBADO';
            }
        } else {
            continue; // Tipo de usuario no reconocido
        }
        
        // Ejecutar la consulta
        $stmt = sqlsrv_query($conn, $sql, $params);
        
        if ($stmt) {
            $registrosActualizados[] = [
                'index' => $index,
                'nuevoEstatus' => $nuevoEstatus,
                'nuevaValidacionJefe' => $nuevaValidacionJefe
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Validación completada',
        'registrosActualizados' => $registrosActualizados
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

sqlsrv_close($conn);
?>