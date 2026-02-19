<?php
/**
 * config/database.php
 *
 * Conexiones centralizadas a SQL Server usando variables de entorno.
 * NUNCA hardcodear credenciales aquí; todas provienen del .env.
 *
 * Variables requeridas en .env:
 *   DB_COMEDOR_SERVER, DB_COMEDOR_DATABASE, DB_COMEDOR_USERNAME, DB_COMEDOR_PASSWORD
 *   DB_KPI_SERVER,     DB_KPI_DATABASE,     DB_KPI_USERNAME,     DB_KPI_PASSWORD
 *
 * Uso:
 *   require_once __DIR__ . '/config/database.php';
 *   $conn = getComedorConnection();
 *   $conn = getKpiConnection();
 *   closeConnection($conn, $stmt);
 */

/**
 * Retorna una conexión activa a la base de datos Comedor.
 * En caso de fallo retorna false y registra el error en el log.
 *
 * @return resource|false
 */
function getComedorConnection()
{
    $server = getenv('DB_COMEDOR_SERVER');
    $info = [
        'Database'               => getenv('DB_COMEDOR_DATABASE'),
        'UID'                    => getenv('DB_COMEDOR_USERNAME'),
        'PWD'                    => getenv('DB_COMEDOR_PASSWORD'),
        'CharacterSet'           => 'UTF-8',
        'TrustServerCertificate' => true,
    ];
    $conn = sqlsrv_connect($server, $info);
    if ($conn === false) {
        error_log('getComedorConnection error: ' . print_r(sqlsrv_errors(), true));
    }
    return $conn;
}

/**
 * Retorna una conexión activa a la base de datos KPI.
 * En caso de fallo retorna false y registra el error en el log.
 *
 * @return resource|false
 */
function getKpiConnection()
{
    $server = getenv('DB_KPI_SERVER');
    $info = [
        'Database'               => getenv('DB_KPI_DATABASE'),
        'UID'                    => getenv('DB_KPI_USERNAME'),
        'PWD'                    => getenv('DB_KPI_PASSWORD'),
        'CharacterSet'           => 'UTF-8',
        'TrustServerCertificate' => true,
    ];
    $conn = sqlsrv_connect($server, $info);
    if ($conn === false) {
        error_log('getKpiConnection error: ' . print_r(sqlsrv_errors(), true));
    }
    return $conn;
}

/**
 * Retorna una conexión activa a la base de datos Ticket.
 *
 * @return resource|false
 */
function getTicketConnection()
{
    $server = getenv('DB_TICKET_SERVER');
    $info = [
        'Database'               => getenv('DB_TICKET_DATABASE'),
        'UID'                    => getenv('DB_TICKET_USERNAME'),
        'PWD'                    => getenv('DB_TICKET_PASSWORD'),
        'CharacterSet'           => 'UTF-8',
        'TrustServerCertificate' => true,
    ];
    $conn = sqlsrv_connect($server, $info);
    if ($conn === false) {
        error_log('getTicketConnection error: ' . print_r(sqlsrv_errors(), true));
    }
    return $conn;
}

/**
 * Retorna una conexión activa a la base de datos Alquimista2024.
 *
 * @return resource|false
 */
function getAlquimistaConnection()
{
    $server = getenv('DB_ALQUIMISTA_SERVER');
    $info = [
        'Database'               => getenv('DB_ALQUIMISTA_DATABASE'),
        'UID'                    => getenv('DB_ALQUIMISTA_USERNAME'),
        'PWD'                    => getenv('DB_ALQUIMISTA_PASSWORD'),
        'CharacterSet'           => 'UTF-8',
        'TrustServerCertificate' => true,
    ];
    $conn = sqlsrv_connect($server, $info);
    if ($conn === false) {
        error_log('getAlquimistaConnection error: ' . print_r(sqlsrv_errors(), true));
    }
    return $conn;
}

/**
 * Retorna una conexión activa a la base de datos BaseNueva.
 *
 * @return resource|false
 */
function getBaseNuevaConnection()
{
    $server = getenv('DB_BASENUEVA_SERVER');
    $info = [
        'Database'               => getenv('DB_BASENUEVA_DATABASE'),
        'UID'                    => getenv('DB_BASENUEVA_USERNAME'),
        'PWD'                    => getenv('DB_BASENUEVA_PASSWORD'),
        'CharacterSet'           => 'UTF-8',
        'TrustServerCertificate' => true,
    ];
    $conn = sqlsrv_connect($server, $info);
    if ($conn === false) {
        error_log('getBaseNuevaConnection error: ' . print_r(sqlsrv_errors(), true));
    }
    return $conn;
}

/**
 * Libera el statement y cierra la conexión de forma segura.
 *
 * @param resource|false $conn
 * @param resource|false|null $stmt
 */
function closeConnection($conn, $stmt = null): void
{
    if ($stmt !== null && $stmt !== false) {
        sqlsrv_free_stmt($stmt);
    }
    if ($conn !== null && $conn !== false) {
        sqlsrv_close($conn);
    }
}
