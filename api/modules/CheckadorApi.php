<?php
/**
 * Checador
 *
 * Sin JWT — el checador es un dispositivo físico en la entrada del comedor.
 *
 * GET  /api/checador/disponibilidad
 * POST /api/checador/registrar
 * GET  /api/checador/desglose?fecha_inicio=&fecha_fin=
 *
 * Frontend: CHECADORF.php        → disponibilidad + registrar entrada
 *           Desglosechecador.php → desglose analítico de entradas
 */
class CheckadorApi {

    private $http;

    public function __construct() {
        $this->http = new HttpClient();
    }

    /**
     * GET /api/checador/disponibilidad
     * Cuántos platillos quedan para el servicio actual.
     * Antes de las 12:40 → Desayuno | Después → Comida. Sin autenticación.
     * Frontend: CHECADORF.php
     */
    public function disponibilidad() {
        return $this->http->get('api/checador/disponibilidad');
    }

    /**
     * POST /api/checador/registrar
     * Registra la entrada de un empleado (string crudo del QR o nombre manual).
     * Si complemento tiene valor válido → inserta en tabla complementos.
     * Si no → inserta en tabla Entradas. Sin autenticación.
     * Frontend: CHECADORF.php
     *
     * @param string      $nombre      String del QR o nombre del empleado
     * @param string|null $complemento CAFÉ O TÉ | TORTILLAS | AGUA | DESECHABLE | COMIDA PARA LLEVAR | null
     */
    public function registrar($nombre, $complemento = null) {
        return $this->http->post('api/checador/registrar', [
            'nombre'      => $nombre,
            'complemento' => $complemento,
        ]);
    }

    /**
     * GET /api/checador/desglose?fecha_inicio=&fecha_fin=
     * Lista registros de entradas con nombres parseados del QR.
     * Filtro por rango de fechas opcional (default: hoy). Sin autenticación.
     * Frontend: Desglosechecador.php
     */
    public function desglose($fechaInicio = null, $fechaFin = null) {
        $params = [];
        if ($fechaInicio) $params['fecha_inicio'] = $fechaInicio;
        if ($fechaFin)    $params['fecha_fin']    = $fechaFin;
        return $this->http->get('api/checador/desglose', $params);
    }
}
