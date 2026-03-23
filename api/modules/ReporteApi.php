<?php
/**
 * Reporte
 *
 * GET /reporte/reporte-detallado?fecha_inicio=&fecha_fin=
 * GET /api/reporte/reporte-detallado?fecha_inicio=&fecha_fin=  (alias)
 *
 * Frontend: REPOCOMEDOR.php (usa la ruta sin prefijo /api)
 *
 * Nota: ambas rutas están disponibles en el backend con el mismo comportamiento.
 */
class ReporteApi {

    private $http;

    public function __construct() {
        $this->http = new HttpClient();
    }

    /**
     * GET /reporte/reporte-detallado?fecha_inicio=&fecha_fin=
     * Desglose día a día del empleado autenticado con costos calculados.
     * Frontend: REPOCOMEDOR.php (usa esta ruta sin /api)
     *
     * @param array $params ['fecha_inicio' => 'YYYY-MM-DD', 'fecha_fin' => 'YYYY-MM-DD'] (opcionales)
     */
    public function detallado($params = []) {
        return $this->http->get('reporte/reporte-detallado', $params);
    }
}
