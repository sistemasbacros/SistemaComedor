<?php
/**
 * ============================================================================
 * CONFIGURACIÓN DE BASES DE DATOS
 * ============================================================================
 *
 * Este archivo centraliza todas las conexiones a bases de datos.
 * Lee las credenciales desde el archivo .env
 *
 * USO:
 *   require_once __DIR__ . '/config/database.php';
 *   $conn = getComedorConnection();
 */

// Cargar variables de entorno desde .env
function loadEnv($path = null) {
    static $loaded = false;

    if ($loaded) {
        return;
    }

    if ($path === null) {
        $path = __DIR__ . '/../.env';
    }

    if (!file_exists($path)) {
        trigger_error(".env file not found at: $path", E_USER_WARNING);
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Ignorar comentarios
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parsear línea KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remover comillas si existen
            $value = trim($value, '"\'');

            // No sobrescribir si ya existe en $_ENV
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }

    $loaded = true;
}

// Cargar automáticamente al incluir este archivo
loadEnv();

/**
 * Obtener valor de variable de entorno
 */
function env($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

/**
 * ============================================================================
 * FUNCIONES DE CONEXIÓN A BASES DE DATOS
 * ============================================================================
 */

/**
 * Obtener conexión a la base de datos COMEDOR (principal)
 *
 * @return resource|false Conexión SQL Server o false en caso de error
 */
function getComedorConnection() {
    $serverName = env('DB_COMEDOR_SERVER', 'DESAROLLO-BACRO\SQLEXPRESS');
    $connectionOptions = array(
        "Database" => env('DB_COMEDOR_DATABASE', 'Comedor'),
        "Uid" => env('DB_COMEDOR_USERNAME', 'Larome03'),
        "PWD" => env('DB_COMEDOR_PASSWORD', 'Larome03'),
        "CharacterSet" => env('DB_COMEDOR_CHARSET', 'UTF-8')
    );

    $conn = sqlsrv_connect($serverName, $connectionOptions);

    if (!$conn) {
        error_log("Error de conexión a Comedor: " . print_r(sqlsrv_errors(), true));
    }

    return $conn;
}

/**
 * Obtener configuración de conexión a COMEDOR (sin conectar)
 *
 * @return array Array con serverName y connectionOptions
 */
function getComedorConfig() {
    return [
        'serverName' => env('DB_COMEDOR_SERVER', 'DESAROLLO-BACRO\SQLEXPRESS'),
        'connectionOptions' => array(
            "Database" => env('DB_COMEDOR_DATABASE', 'Comedor'),
            "Uid" => env('DB_COMEDOR_USERNAME', 'Larome03'),
            "PWD" => env('DB_COMEDOR_PASSWORD', 'Larome03'),
            "CharacterSet" => env('DB_COMEDOR_CHARSET', 'UTF-8')
        )
    ];
}

/**
 * Obtener conexión a la base de datos ALQUIMISTA2024
 *
 * @return resource|false Conexión SQL Server o false en caso de error
 */
function getAlquimistaConnection() {
    $serverName = env('DB_ALQUIMISTA_SERVER', 'WIN-44O80L37Q7M\COMERCIAL');
    $connectionOptions = array(
        "Database" => env('DB_ALQUIMISTA_DATABASE', 'ALQUIMISTA2024'),
        "Uid" => env('DB_ALQUIMISTA_USERNAME', 'sa'),
        "PWD" => env('DB_ALQUIMISTA_PASSWORD', 'Administrador1*'),
        "CharacterSet" => env('DB_ALQUIMISTA_CHARSET', 'UTF-8')
    );

    $conn = sqlsrv_connect($serverName, $connectionOptions);

    if (!$conn) {
        error_log("Error de conexión a Alquimista: " . print_r(sqlsrv_errors(), true));
    }

    return $conn;
}

/**
 * Obtener configuración de conexión a ALQUIMISTA2024 (sin conectar)
 *
 * @return array Array con serverName y connectionOptions
 */
function getAlquimistaConfig() {
    return [
        'serverName' => env('DB_ALQUIMISTA_SERVER', 'WIN-44O80L37Q7M\COMERCIAL'),
        'connectionOptions' => array(
            "Database" => env('DB_ALQUIMISTA_DATABASE', 'ALQUIMISTA2024'),
            "Uid" => env('DB_ALQUIMISTA_USERNAME', 'sa'),
            "PWD" => env('DB_ALQUIMISTA_PASSWORD', 'Administrador1*'),
            "CharacterSet" => env('DB_ALQUIMISTA_CHARSET', 'UTF-8')
        )
    ];
}

/**
 * Obtener conexión a la base de datos BASENUEVA
 *
 * @return resource|false Conexión SQL Server o false en caso de error
 */
function getBaseNuevaConnection() {
    $serverName = env('DB_BASENUEVA_SERVER', 'WIN-44O80L37Q7M\COMERCIAL');
    $connectionOptions = array(
        "Database" => env('DB_BASENUEVA_DATABASE', 'BASENUEVA'),
        "Uid" => env('DB_BASENUEVA_USERNAME', 'sa'),
        "PWD" => env('DB_BASENUEVA_PASSWORD', 'Administrador1*'),
        "CharacterSet" => env('DB_BASENUEVA_CHARSET', 'UTF-8')
    );

    $conn = sqlsrv_connect($serverName, $connectionOptions);

    if (!$conn) {
        error_log("Error de conexión a BaseNueva: " . print_r(sqlsrv_errors(), true));
    }

    return $conn;
}

/**
 * Obtener configuración de conexión a BASENUEVA (sin conectar)
 *
 * @return array Array con serverName y connectionOptions
 */
function getBaseNuevaConfig() {
    return [
        'serverName' => env('DB_BASENUEVA_SERVER', 'WIN-44O80L37Q7M\COMERCIAL'),
        'connectionOptions' => array(
            "Database" => env('DB_BASENUEVA_DATABASE', 'BASENUEVA'),
            "Uid" => env('DB_BASENUEVA_USERNAME', 'sa'),
            "PWD" => env('DB_BASENUEVA_PASSWORD', 'Administrador1*'),
            "CharacterSet" => env('DB_BASENUEVA_CHARSET', 'UTF-8')
        )
    ];
}

/**
 * Obtener conexión a la base de datos KPI
 *
 * @return resource|false Conexión SQL Server o false en caso de error
 */
function getKpiConnection() {
    $serverName = env('DB_KPI_SERVER', 'DESAROLLO-BACRO\SQLEXPRESS');
    $connectionOptions = array(
        "Database" => env('DB_KPI_DATABASE', 'KPI'),
        "Uid" => env('DB_KPI_USERNAME', 'Larome03'),
        "PWD" => env('DB_KPI_PASSWORD', 'Larome03'),
        "CharacterSet" => env('DB_KPI_CHARSET', 'UTF-8')
    );

    $conn = sqlsrv_connect($serverName, $connectionOptions);

    if (!$conn) {
        error_log("Error de conexión a KPI: " . print_r(sqlsrv_errors(), true));
    }

    return $conn;
}

/**
 * Obtener configuración de conexión a KPI (sin conectar)
 *
 * @return array Array con serverName y connectionOptions
 */
function getKpiConfig() {
    return [
        'serverName' => env('DB_KPI_SERVER', 'DESAROLLO-BACRO\SQLEXPRESS'),
        'connectionOptions' => array(
            "Database" => env('DB_KPI_DATABASE', 'KPI'),
            "Uid" => env('DB_KPI_USERNAME', 'Larome03'),
            "PWD" => env('DB_KPI_PASSWORD', 'Larome03'),
            "CharacterSet" => env('DB_KPI_CHARSET', 'UTF-8')
        )
    ];
}

/**
 * Obtener conexión a la base de datos TICKET
 *
 * @return resource|false Conexión SQL Server o false en caso de error
 */
function getTicketConnection() {
    $serverName = env('DB_TICKET_SERVER', 'DESAROLLO-BACRO\SQLEXPRESS');
    $connectionOptions = array(
        "Database" => env('DB_TICKET_DATABASE', 'Ticket'),
        "Uid" => env('DB_TICKET_USERNAME', 'Larome03'),
        "PWD" => env('DB_TICKET_PASSWORD', 'Larome03'),
        "CharacterSet" => env('DB_TICKET_CHARSET', 'UTF-8')
    );

    $conn = sqlsrv_connect($serverName, $connectionOptions);

    if (!$conn) {
        error_log("Error de conexión a Ticket: " . print_r(sqlsrv_errors(), true));
    }

    return $conn;
}

/**
 * Obtener configuración de conexión a TICKET (sin conectar)
 *
 * @return array Array con serverName y connectionOptions
 */
function getTicketConfig() {
    return [
        'serverName' => env('DB_TICKET_SERVER', 'DESAROLLO-BACRO\SQLEXPRESS'),
        'connectionOptions' => array(
            "Database" => env('DB_TICKET_DATABASE', 'Ticket'),
            "Uid" => env('DB_TICKET_USERNAME', 'Larome03'),
            "PWD" => env('DB_TICKET_PASSWORD', 'Larome03'),
            "CharacterSet" => env('DB_TICKET_CHARSET', 'UTF-8')
        )
    ];
}

/**
 * ============================================================================
 * FUNCIONES HELPER PARA COMPATIBILIDAD
 * ============================================================================
 */

/**
 * Cerrar conexión SQL Server de forma segura
 *
 * @param resource $conn Conexión SQL Server
 * @param resource|null $stmt Statement opcional a liberar
 */
function closeConnection($conn, $stmt = null) {
    if ($stmt) {
        sqlsrv_free_stmt($stmt);
    }
    if ($conn) {
        sqlsrv_close($conn);
    }
}

/**
 * Obtener string de conexión para debugging
 *
 * @param string $dbName Nombre de la base de datos (comedor|alquimista|basenueva|kpi|ticket)
 * @return string String de conexión (sin contraseña)
 */
function getConnectionString($dbName = 'comedor') {
    switch (strtolower($dbName)) {
        case 'alquimista':
            return env('DB_ALQUIMISTA_SERVER') . ' -> ' . env('DB_ALQUIMISTA_DATABASE');
        case 'basenueva':
            return env('DB_BASENUEVA_SERVER') . ' -> ' . env('DB_BASENUEVA_DATABASE');
        case 'kpi':
            return env('DB_KPI_SERVER') . ' -> ' . env('DB_KPI_DATABASE');
        case 'ticket':
            return env('DB_TICKET_SERVER') . ' -> ' . env('DB_TICKET_DATABASE');
        case 'comedor':
        default:
            return env('DB_COMEDOR_SERVER') . ' -> ' . env('DB_COMEDOR_DATABASE');
    }
}

?>
