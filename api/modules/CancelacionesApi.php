<?php
/**
 * Cancelaciones
 *
 * GET  /api/cancelaciones/validaciones
 * POST /api/cancelaciones
 * GET  /api/cancelaciones/mis-cancelaciones
 * GET  /api/cancelaciones/pendientes
 *
 * Frontend: FormatCancel.php / Formacancel123456.php   → crear cancelación
 *           EstadCancelaciones.php / EstadisticasCancelaciones.php → historial
 *           MenUsuario.php → badge de pendientes
 */
class CancelacionesApi {

    private $http;

    public function __construct() {
        $this->http = new HttpClient();
    }

    /**
     * GET /api/cancelaciones/validaciones
     * Causas, tipos y rango de fechas válidas al abrir el formulario.
     * Frontend: FormatCancel.php, Formacancel123456.php
     */
    public function validaciones() {
        return $this->http->get('api/cancelaciones/validaciones');
    }

    /**
     * POST /api/cancelaciones
     * Envía el formulario de cancelación.
     * tipo_consumo: DESAYUNO | COMIDA | AMBOS
     * causa:        SALUD | PERSONAL | VACACIONES | COMISIÓN | REUNIÓN
     * Frontend: FormatCancel.php, Formacancel123456.php
     *
     * @param array $datos ['tipo_consumo' => ..., 'causa' => ..., 'fecha' => ...]
     */
    public function crear($datos) {
        return $this->http->post('api/cancelaciones', $datos);
    }

    /**
     * GET /api/cancelaciones/mis-cancelaciones
     * Historial completo de cancelaciones del usuario autenticado.
     * Frontend: EstadCancelaciones.php, EstadisticasCancelaciones.php
     */
    public function misCancelaciones() {
        return $this->http->get('api/cancelaciones/mis-cancelaciones');
    }

    /**
     * GET /api/cancelaciones/pendientes
     * Badge con las cancelaciones EN PROCESO del usuario en el menú.
     * Frontend: MenUsuario.php
     */
    public function pendientes() {
        return $this->http->get('api/cancelaciones/pendientes');
    }
}
