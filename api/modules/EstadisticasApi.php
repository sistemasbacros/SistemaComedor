<?php
/**
 * Estadísticas
 *
 * GET /api/estadisticas/mis-consumos
 * GET /api/estadisticas/cancelaciones
 * GET /api/estadisticas/consumo
 * GET /api/estadisticas/dashboard
 *
 * Frontend: KPI_anacomp.php              → dashboard y mis consumos
 *           EstadisticasCancelaciones.php → gráfica de cancelaciones por causa/tipo
 *           EstadCancelaciones.php        → consumo por día de semana
 */
class EstadisticasApi {

    private $http;

    public function __construct() {
        $this->http = new HttpClient();
    }

    /**
     * GET /api/estadisticas/mis-consumos
     * Stats personales: total desayunos, comidas, monto y distribución por día.
     * Frontend: KPI_anacomp.php, EstadisticasCancelaciones.php
     */
    public function misConsumos() {
        return $this->http->get('api/estadisticas/mis-consumos');
    }

    /**
     * GET /api/estadisticas/cancelaciones
     * Cancelaciones del usuario agrupadas por causa y tipo de consumo.
     * Frontend: EstadisticasCancelaciones.php
     */
    public function cancelaciones() {
        return $this->http->get('api/estadisticas/cancelaciones');
    }

    /**
     * GET /api/estadisticas/consumo
     * Distribución de consumos por día de la semana, total y promedio diario.
     * Frontend: EstadCancelaciones.php
     */
    public function consumo() {
        return $this->http->get('api/estadisticas/consumo');
    }

    /**
     * GET /api/estadisticas/dashboard
     * Conteos globales: pedidos, consumos, cancelaciones y monto total.
     * Frontend: KPI_anacomp.php
     * (Para dashboard admin completo con montos/gastos usar Admin::dashboard())
     */
    public function dashboard() {
        return $this->http->get('api/estadisticas/dashboard');
    }
}
