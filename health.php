<?php
/**
 * Health Check Endpoint
 * 
 * Este archivo verifica que PHP-FPM está funcionando correctamente.
 * Usado por Docker para verificar la salud del contenedor.
 */

header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'memory_usage' => memory_get_usage(true),
    'memory_limit' => ini_get('memory_limit')
];

// Verificar extensiones críticas
$requiredExtensions = ['pdo', 'pdo_sqlsrv', 'sqlsrv', 'mbstring', 'gd'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

if (!empty($missingExtensions)) {
    $health['status'] = 'degraded';
    $health['missing_extensions'] = $missingExtensions;
    http_response_code(503);
} else {
    $health['extensions'] = 'all loaded';
    http_response_code(200);
}

echo json_encode($health, JSON_PRETTY_PRINT);
