<?php
/**
 * Consumos
 *
 * GET /api/consumos/mis-consumos?fecha=
 * GET /api/consumos/reporte
 *
 * Frontend: aparta_consumo_modificado.php / Descrip_Consumo.php → consumos personales
 *           REPOCOMEDOR.php / admicome4.php                      → reporte admin
 */
class ConsumosApi {

    private $http;

    public function __construct() {
        $this->http = new HttpClient();
    }

    /**
     * GET /api/consumos/mis-consumos?fecha=YYYY-MM-DD
     * Consumos del empleado para la semana que contiene la fecha indicada.
     * Frontend: aparta_consumo_modificado.php, Descrip_Consumo.php
     */
    public function misConsumos($fecha = null) {
        $params = $fecha ? ['fecha' => $fecha] : [];
        return $this->http->get('api/consumos/mis-consumos', $params);
    }

    /**
     * GET /api/consumos/reporte
     * Reporte completo de consumos para administradores.
     * Frontend: REPOCOMEDOR.php, admicome4.php
     */
    public function reporte() {
        return $this->http->get('api/consumos/reporte');
    }
}
