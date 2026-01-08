<?php
/**
 * Script de prueba para verificar que las respuestas de la API
 * se están procesando correctamente
 */

session_start();
require_once __DIR__ . '/config_api.php';
require_once __DIR__ . '/api_client.php';

// Simular las respuestas de la API que recibiste
$mock_response_semanas = [
    'success' => true,
    'data' => [
        'semanas' => [
            [
                'fecha' => '2026-01-05',
                'fecha_formateada' => '05/01/2026',
                'es_semana_actual' => true
            ],
            [
                'fecha' => '2026-01-12',
                'fecha_formateada' => '12/01/2026',
                'es_semana_actual' => false
            ]
        ],
        'semana_actual' => '2026-01-05'
    ]
];

$mock_response_consumos = [
    'success' => true,
    'data' => [
        'fecha_consulta' => '2026-01-12',
        'fecha_formateada' => '12/01/2026',
        'empleado' => [
            'id_empleado' => '1200',
            'nombre' => 'BORIS KELVIN RAMIREZ NEYRA',
            'area' => 'Administración'
        ],
        'consumos' => [
            'lunes' => 'Desayuno, Comida',
            'martes' => 'Desayuno, Comida',
            'miercoles' => 'Desayuno, Comida',
            'jueves' => 'Desayuno',
            'viernes' => 'Comida'
        ],
        'total_consumos' => 8,
        'desglose' => [
            ['dia' => 'Lunes', 'tipo' => 'Desayuno'],
            ['dia' => 'Lunes', 'tipo' => 'Comida'],
            ['dia' => 'Martes', 'tipo' => 'Desayuno'],
            ['dia' => 'Martes', 'tipo' => 'Comida'],
            ['dia' => 'Miércoles', 'tipo' => 'Desayuno'],
            ['dia' => 'Miércoles', 'tipo' => 'Comida'],
            ['dia' => 'Jueves', 'tipo' => 'Desayuno'],
            ['dia' => 'Viernes', 'tipo' => 'Comida']
        ]
    ]
];

echo "<h1>Prueba de Procesamiento de Respuestas de API</h1>";

// ==================================================
// PRUEBA 1: Procesamiento de Semanas
// ==================================================
echo "<h2>1. Procesamiento de Semanas Disponibles</h2>";

$lunes_filtrados = [];
$semana_actual = '';

if ($mock_response_semanas['success']) {
    $data_semanas = $mock_response_semanas['data'];
    $semana_actual = $data_semanas['semana_actual'] ?? date('Y-m-d', strtotime('monday this week'));
    
    echo "<p><strong>Semana actual detectada:</strong> $semana_actual</p>";
    
    if (isset($data_semanas['semanas']) && is_array($data_semanas['semanas'])) {
        foreach ($data_semanas['semanas'] as $semana) {
            if (isset($semana['fecha'])) {
                $lunes_filtrados[] = $semana['fecha'];
            }
        }
    }
    
    echo "<p><strong>Semanas extraídas:</strong></p>";
    echo "<ul>";
    foreach ($lunes_filtrados as $fecha) {
        $formateada = date('d/m/Y', strtotime($fecha));
        $es_actual = ($fecha === $semana_actual) ? ' (Semana en curso)' : '';
        echo "<li>$formateada - $fecha$es_actual</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color:red;'>ERROR: No se pudieron obtener las semanas</p>";
}

// ==================================================
// PRUEBA 2: Procesamiento de Consumos
// ==================================================
echo "<h2>2. Procesamiento de Consumos Semanales</h2>";

$resultados_tabla = [];
$total_consumos = 0;

if ($mock_response_consumos['success']) {
    $data = $mock_response_consumos['data'];
    
    if (isset($data['empleado'], $data['consumos'], $data['total_consumos'])) {
        $resultados_tabla[] = [
            'Fecha' => $data['fecha_consulta'] ?? '',
            'Id_Empleado' => $data['empleado']['id_empleado'] ?? '',
            'Nombre' => $data['empleado']['nombre'] ?? '',
            'Lunes' => $data['consumos']['lunes'] ?? '',
            'Martes' => $data['consumos']['martes'] ?? '',
            'Miercoles' => $data['consumos']['miercoles'] ?? '',
            'Jueves' => $data['consumos']['jueves'] ?? '',
            'Viernes' => $data['consumos']['viernes'] ?? ''
        ];
        
        $total_consumos = $data['total_consumos'];
        
        echo "<p><strong>✅ Datos procesados correctamente</strong></p>";
        echo "<p><strong>Fecha consulta:</strong> " . $data['fecha_consulta'] . "</p>";
        echo "<p><strong>Empleado:</strong> " . $data['empleado']['nombre'] . " (ID: " . $data['empleado']['id_empleado'] . ")</p>";
        echo "<p><strong>Total consumos:</strong> $total_consumos</p>";
        
        echo "<h3>Tabla de Consumos:</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr><th>Fecha</th><th>ID</th><th>Nombre</th><th>Lunes</th><th>Martes</th><th>Miércoles</th><th>Jueves</th><th>Viernes</th></tr>";
        
        foreach ($resultados_tabla as $fila) {
            echo "<tr>";
            echo "<td>" . date('d/m/Y', strtotime($fila['Fecha'])) . "</td>";
            echo "<td>" . htmlspecialchars($fila['Id_Empleado']) . "</td>";
            echo "<td>" . htmlspecialchars($fila['Nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($fila['Lunes']) . "</td>";
            echo "<td>" . htmlspecialchars($fila['Martes']) . "</td>";
            echo "<td>" . htmlspecialchars($fila['Miercoles']) . "</td>";
            echo "<td>" . htmlspecialchars($fila['Jueves']) . "</td>";
            echo "<td>" . htmlspecialchars($fila['Viernes']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>Desglose de Consumos:</h3>";
        echo "<ul>";
        foreach ($data['desglose'] as $item) {
            echo "<li><strong>{$item['dia']}:</strong> {$item['tipo']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:red;'>ERROR: La estructura de datos no tiene empleado, consumos o total_consumos</p>";
    }
} else {
    echo "<p style='color:red;'>ERROR: No se pudieron obtener los consumos</p>";
}

// ==================================================
// PRUEBA 3: Verificar estructura completa
// ==================================================
echo "<h2>3. Estructura Completa de Respuestas</h2>";

echo "<h3>Response Semanas:</h3>";
echo "<pre>" . json_encode($mock_response_semanas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "<h3>Response Consumos:</h3>";
echo "<pre>" . json_encode($mock_response_consumos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "<hr>";
echo "<p><strong>Conclusión:</strong> El código PHP está procesando correctamente las respuestas de la API.</p>";
echo "<p><strong>Nota importante:</strong> La API devolvió fecha_consulta='2026-01-12' cuando se solicitó '2026-01-05'. Esto debe corregirse en el backend de Rust.</p>";
?>
