<?php
// check_pending_cancelations.php

session_start();

// Verificar autenticación
if (!isset($_SESSION['authenticated_from_login']) || $_SESSION['authenticated_from_login'] !== true) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

// Configuración de conexión a la base de datos
$serverName = "DESAROLLO-BACRO\\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "Comedor",
    "Uid" => "Larome03",
    "PWD" => "Larome03",
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true
);

$response = ['success' => false, 'pending_count' => 0, 'details' => []];

try {
    $conn = sqlsrv_connect($serverName, $connectionOptions);
    
    if ($conn !== false) {
        $fecha_actual = date('Y-m-d');
        
        // Consulta para obtener cancelaciones pendientes del día actual
        $sql = "SELECT 
                    nombre as usuario,
                    tipo_consumo,
                    ESTATUS as estatus,
                    convert(varchar, FECHA, 103) as fecha,
                    CONVERT(VARCHAR, HORA, 108) as hora
                FROM cancelaciones 
                WHERE convert(date, FECHA, 102) = ? 
                AND ESTATUS != 'APROBADO'
                ORDER BY HORA DESC";
        
        $params = array($fecha_actual);
        $stmt = sqlsrv_query($conn, $sql, $params);
        
        if ($stmt !== false) {
            $pending_count = 0;
            $details = [];
            
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $pending_count++;
                $details[] = [
                    'usuario' => $row['usuario'] ?? 'N/A',
                    'tipo_consumo' => $row['tipo_consumo'] ?? 'N/A',
                    'estatus' => $row['estatus'] ?? 'PENDIENTE',
                    'fecha' => $row['fecha'] ?? date('d/m/Y'),
                    'hora' => $row['hora'] ?? date('H:i:s')
                ];
            }
            
            $response['success'] = true;
            $response['pending_count'] = $pending_count;
            $response['details'] = $details;
            
        } else {
            $response['error'] = 'Error en la consulta';
        }
        
        sqlsrv_close($conn);
        
    } else {
        $response['error'] = 'Error de conexión';
    }
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

// Devolver respuesta JSON
header('Content-Type: application/json');
echo json_encode($response);
?>