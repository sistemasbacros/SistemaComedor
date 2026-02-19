
<?php
/**
 * @file FormCanAprobUpdate.php
 * @brief Endpoint receptor de array JSON para procesamiento de cancelaciones.
 *
 * @description
 * Este archivo recibe un array de valores serializado como JSON desde el cliente
 * (enviado por POST como "arrayDeValores") y lo procesa en el servidor.
 *
 * Estado actual del módulo:
 * - La funcionalidad ACTIVA decodifica el JSON y lista los elementos recibidos.
 * - La funcionalidad COMENTADA (código legacy) contenía la lógica original para:
 *   * Conectarse a la BD Operaciones1
 *   * Registrar en la tabla Modificar el usuario y acción realizada
 *   * Eliminar el registro correspondiente en CatologodeContratos
 *
 * Este archivo es el receptor de las acciones de aprobación/rechazo de cancelaciones
 * iniciadas desde el panel de administración.
 *
 * @module Módulo de Cancelaciones — Aprobación
 * @access DIRECCIÓN | ADMINISTRADOR
 *
 * @dependencies
 * - PHP: json_decode(), sqlsrv_connect() (en código legacy comentado)
 *
 * @database
 * - Tabla activa: ninguna (código de BD está comentado)
 * - Tablas en código legacy (comentado):
 *   * Modificar (INSERT — auditoría)
 *   * CatologodeContratos (DELETE — eliminar contrato)
 *
 * @inputs
 * - $_POST['arrayDeValores'] : string JSON - Array serializado con datos de la cancelación
 *   Índices documentados en el código legacy:
 *   * [0]  ID del registro a eliminar
 *   * [2]  Descripción del contrato
 *   * [10] Usuario que realiza la acción
 *
 * @outputs
 * - Text: Confirmación con el conteo de elementos recibidos
 * - Text: Lista de valores del array (uno por línea)
 *
 * @note El código legacy de BD está comentado intencionalmente.
 *       Pendiente de refactorización para usar config/database.php.
 *
 * @author Equipo Tecnología BacroCorp
 * @version 1.0
 * @since 2024
 * @updated 2026-02-18
 */

// ---------------------------------------------------------------------------
// SECCIÓN: Recepción y decodificación del payload JSON
// Decodifica el arreglo de valores enviado desde el panel de aprobación.
// Se espera que $_POST['arrayDeValores'] contenga un JSON válido con
// los datos de las cancelaciones seleccionadas para procesar.
// ---------------------------------------------------------------------------
$arrayRecibido=json_decode($_POST["arrayDeValores"], true );

// ---------------------------------------------------------------------------
// SECCIÓN: Salida de depuración — listado de elementos recibidos
// Este bloque es un stub de desarrollo; muestra los datos recibidos para
// verificar que el payload llega correctamente al servidor.
// Debe reemplazarse por la lógica real de aprobación antes de producción.
// ---------------------------------------------------------------------------
echo "Hemos recibido en el PHP un array de ".count($arrayRecibido)." elementos de luis123232";
foreach($arrayRecibido as $valor)
{
	echo "\n- ".$valor;

}



// ---------------------------------------------------------------------------
// SECCIÓN: Código legacy comentado — gestión de contratos (NO del comedor)
// ---------------------------------------------------------------------------
// El siguiente bloque fue heredado de un módulo de gestión de contratos.
// Conectaba a la base de datos "Operaciones1" (no "Comedor") para:
//   1. Registrar una auditoría en la tabla "Modificar" (INSERT)
//   2. Eliminar un registro de "CatologodeContratos" por Id (DELETE)
//
// NO debe reactivarse sin adaptarlo completamente al módulo de cancelaciones.
// Para la lógica de aprobación de cancelaciones usar getComedorConnection()
// y operar sobre la tabla "Cancelaciones" de la base "Comedor".
//
// ⚠️ El bloque también contiene:
//   - Credenciales hardcodeadas (UID/PWD)
//   - SQL construido por concatenación de variables (riesgo de inyección SQL)
//   - Un GET param "newpwd" etiquetado internamente como "folio" (propósito ambiguo)
// ---------------------------------------------------------------------------


// // if( $conn ) {
     // // echo "Conexión establecida.<br />";
// // }else{
     // // echo "Conexión no se pudo establecer.<br />";
     // // die( print_r( sqlsrv_errors(), true));
// // }

 // $fechaActualA = date('Y-m-d');
// $fechaActualA1 = date('H:i:s', time()+3600);

// if (isset($_GET["newpwd"])) {
    // $phpVar1 = $_GET["newpwd"];
// } else {
// }

// $phpVar1 =  str_replace("?","",$phpVar1);
// $name10 = $phpVar1; /// folio

// $sql1 = "Insert into Modificar(Usuario,Tabla,Fecha,Hora) Values('$arrayRecibido[10]','Elimino el contrato'+' '+'$arrayRecibido[2]','$fechaActualA','$fechaActualA1')";
// $stmt1 = sqlsrv_query( $conn, $sql1 );



// $sql = "Delete CatologodeContratos where Id='$arrayRecibido[0]'";
// $stmt = sqlsrv_query( $conn, $sql );
// if( $stmt === false) {
    // die( print_r( sqlsrv_errors(), true) );
// }

// // while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
      // // echo $row['Usuario'].", ".$row['Contrasena']."<br />";
// // }



?>

