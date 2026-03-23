<?php
/**
 * Cocina
 *
 * Sub-módulos accesibles como propiedades de CocinaApi:
 *
 *   Api::cocina()->entradas()->listar()
 *   Api::cocina()->entradas()->estadisticas()
 *   Api::cocina()->entradas()->atender(...)
 *
 *   Api::cocina()->complementos()->listar()
 *   Api::cocina()->complementos()->estadisticas()
 *   Api::cocina()->complementos()->atender(...)
 *
 *   Api::cocina()->cancelaciones()->listar()
 *   Api::cocina()->cancelaciones()->asignar(...)
 *   Api::cocina()->cancelaciones()->liberar(...)
 *
 *   Api::cocina()->pedidos()->semana()
 *   Api::cocina()->pedidos()->totalesSemana()
 *   Api::cocina()->pedidos()->detalle()
 *
 *   Api::cocina()->compras()->guardar(...)
 *   Api::cocina()->compras()->listar(...)
 *
 * Frontend: dchef.php              → entradas, complementos, cancelaciones cocina
 *           CocinaTotalPedidos.php → totales y detalle de pedidos semanales
 *           AgendaPedidos.php      → consulta de pedidos por semana/empleado
 *           MenComprasCocina.php   → lista de compras
 */

// ============================================================================
// Sub-módulo: Entradas
// ============================================================================

class CocinaEntradasApi {

    private $http;

    public function __construct(HttpClient $http) {
        $this->http = $http;
    }

    /**
     * GET /api/cocina/entradas
     * Todos los registros de entradas del día con nombre limpio. Público.
     * Frontend: dchef.php
     */
    public function listar() {
        return $this->http->get('api/cocina/entradas');
    }

    /**
     * GET /api/cocina/entradas/estadisticas
     * Total, atendidos y pendientes del día. Para badge en tiempo real. Público.
     * Frontend: dchef.php
     */
    public function estadisticas() {
        return $this->http->get('api/cocina/entradas/estadisticas');
    }

    /**
     * PUT /api/cocina/entradas/atender
     * Marca una entrada como ATENDIDO.
     * Usar los mismos valores de nombre/hora_entrada/fecha_hora que devuelve listar(). Público.
     * Frontend: dchef.php
     *
     * @param string $nombre      Nombre limpio del empleado
     * @param string $horaEntrada Fecha (YYYY-MM-DD)
     * @param string $fechaHora   Hora (HH:MM:SS)
     */
    public function atender($nombre, $horaEntrada, $fechaHora) {
        return $this->http->put('api/cocina/entradas/atender', [
            'nombre'       => $nombre,
            'hora_entrada' => $horaEntrada,
            'fecha_hora'   => $fechaHora,
        ]);
    }
}

// ============================================================================
// Sub-módulo: Complementos
// ============================================================================

class CocinaComplementosApi {

    private $http;

    public function __construct(HttpClient $http) {
        $this->http = $http;
    }

    /**
     * GET /api/cocina/complementos
     * Complementos del día: café/té, tortillas, agua, desechable, comida para llevar. Público.
     * Frontend: dchef.php
     */
    public function listar() {
        return $this->http->get('api/cocina/complementos');
    }

    /**
     * GET /api/cocina/complementos/estadisticas
     * Total, atendidos y pendientes de complementos del día. Público.
     * Frontend: dchef.php
     */
    public function estadisticas() {
        return $this->http->get('api/cocina/complementos/estadisticas');
    }

    /**
     * PUT /api/cocina/complementos/atender
     * Marca un complemento como atendido.
     * Usar los mismos valores que devuelve listar(). Público.
     * Frontend: dchef.php
     *
     * @param string $nombre      Nombre limpio del empleado
     * @param string $complemento Tipo de complemento (CAFÉ O TÉ | TORTILLAS | ...)
     * @param string $fecha       Fecha (DD-MM-YYYY)
     * @param string $hora        Hora (HH:MM:SS)
     */
    public function atender($nombre, $complemento, $fecha, $hora) {
        return $this->http->put('api/cocina/complementos/atender', [
            'nombre'      => $nombre,
            'complemento' => $complemento,
            'fecha'       => $fecha,
            'hora'        => $hora,
        ]);
    }
}

// ============================================================================
// Sub-módulo: Cancelaciones Cocina
// ============================================================================

class CocinaCancelacionesApi {

    private $http;

    public function __construct(HttpClient $http) {
        $this->http = $http;
    }

    /**
     * GET /api/cocina/cancelaciones
     * Cancelaciones aprobadas disponibles para asignar a otras personas. Público.
     * Frontend: dchef.php
     */
    public function listar() {
        return $this->http->get('api/cocina/cancelaciones');
    }

    /**
     * PUT /api/cocina/cancelaciones/asignar
     * Asigna la cancelación de un empleado a otra persona. Requiere JWT del chef.
     * Frontend: dchef.php
     *
     * @param string $nombreCancelacion Nombre del empleado que canceló
     * @param string $tipoConsumo       DESAYUNO | COMIDA
     * @param string $fechaCancelacion  YYYY-MM-DD
     * @param string $nombrePersona     Nombre de quien recibe la cancelación
     */
    public function asignar($nombreCancelacion, $tipoConsumo, $fechaCancelacion, $nombrePersona) {
        return $this->http->put('api/cocina/cancelaciones/asignar', [
            'nombre_cancelacion' => $nombreCancelacion,
            'tipo_consumo'       => $tipoConsumo,
            'fecha_cancelacion'  => $fechaCancelacion,
            'nombre_persona'     => $nombrePersona,
        ]);
    }

    /**
     * PUT /api/cocina/cancelaciones/liberar
     * Libera una cancelación previamente asignada (devuelve al pool disponible).
     * Requiere JWT del chef.
     * Frontend: dchef.php
     */
    public function liberar($nombreCancelacion, $tipoConsumo, $fechaCancelacion) {
        return $this->http->put('api/cocina/cancelaciones/liberar', [
            'nombre_cancelacion' => $nombreCancelacion,
            'tipo_consumo'       => $tipoConsumo,
            'fecha_cancelacion'  => $fechaCancelacion,
        ]);
    }
}

// ============================================================================
// Sub-módulo: Pedidos Semana
// ============================================================================

class CocinaPedidosApi {

    private $http;

    public function __construct(HttpClient $http) {
        $this->http = $http;
    }

    /**
     * GET /api/cocina/pedidos-semana
     * Pedidos de la semana actual normalizados (CTE + UNPIVOT):
     * una fila por empleado por día y servicio. Público.
     * Frontend: dchef.php
     */
    public function semana() {
        return $this->http->get('api/cocina/pedidos-semana');
    }

    /**
     * GET /api/cocina/totales-semana
     * Totales semanales PIVOT: suma de Comidas/Desayunos para lunes–viernes. Público.
     * Frontend: CocinaTotalPedidos.php, AgendaPedidos.php
     */
    public function totalesSemana() {
        return $this->http->get('api/cocina/totales-semana');
    }

    /**
     * GET /api/cocina/pedidos-detalle
     * Detalle por empleado: elección (Desayuno/Comida/vacío) por día de la semana. Público.
     * Frontend: CocinaTotalPedidos.php, AgendaPedidos.php
     */
    public function detalle() {
        return $this->http->get('api/cocina/pedidos-detalle');
    }
}

// ============================================================================
// Sub-módulo: Compras
// ============================================================================

class CocinaComprasApi {

    private $http;

    public function __construct(HttpClient $http) {
        $this->http = $http;
    }

    /**
     * POST /api/cocina/compras
     * Guarda lista de compras categorizada. Público.
     * Frontend: MenComprasCocina.php
     *
     * @param array $datos [
     *   'fecha'     => 'YYYY-MM-DD',
     *   'carnes'    => ['Pollo 5kg', ...],
     *   'frutas'    => ['Manzanas 2kg', ...],
     *   'verduras'  => [...],
     *   'lacteos'   => [...],
     *   'accesorios'=> [...],
     * ]
     */
    public function guardar($datos) {
        return $this->http->post('api/cocina/compras', $datos);
    }

    /**
     * GET /api/cocina/compras?fecha=YYYY-MM-DD
     * Lista de compras para la fecha indicada. Público.
     * Frontend: MenComprasCocina.php
     */
    public function listar($fecha) {
        return $this->http->get('api/cocina/compras', ['fecha' => $fecha]);
    }
}

// ============================================================================
// Fachada principal del módulo Cocina
// ============================================================================

class CocinaApi {

    private $http;
    private $entradas;
    private $complementos;
    private $cancelaciones;
    private $pedidos;
    private $compras;

    public function __construct() {
        $this->http = new HttpClient();
    }

    public function entradas()      { return $this->entradas      ??= new CocinaEntradasApi($this->http); }
    public function complementos()  { return $this->complementos  ??= new CocinaComplementosApi($this->http); }
    public function cancelaciones() { return $this->cancelaciones ??= new CocinaCancelacionesApi($this->http); }
    public function pedidos()       { return $this->pedidos       ??= new CocinaPedidosApi($this->http); }
    public function compras()       { return $this->compras       ??= new CocinaComprasApi($this->http); }
}
