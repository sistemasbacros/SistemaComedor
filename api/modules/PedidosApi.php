<?php
/**
 * Pedidos
 *
 * GET  /api/pedidos/semanas-disponibles
 * GET  /api/pedidos/verificar?fecha=
 * POST /api/pedidos
 * POST /api/pedidos/agendar-pedidos
 * GET  /api/pedidos/mis-pedidos
 * GET  /api/pedidos/perfil
 *
 * Frontend: Menpedidos.php / Menpedidos1.php → agendar pedidos
 *           AgendaPedidos.php / AgendaPedidos1.php → consultar pedidos
 */
class PedidosApi {

    private $http;

    public function __construct() {
        $this->http = new HttpClient();
    }

    /**
     * GET /api/pedidos/semanas-disponibles
     * Pobla el selector de semana al abrir la pantalla de pedidos.
     * Frontend: Menpedidos.php, Menpedidos1.php
     */
    public function semanasDisponibles() {
        return $this->http->get('api/pedidos/semanas-disponibles');
    }

    /**
     * GET /api/pedidos/verificar?fecha=YYYY-MM-DD
     * Verifica si el usuario ya tiene pedido para la semana y si puede agregar más.
     * Frontend: Menpedidos.php
     */
    public function verificar($fecha) {
        return $this->http->get('api/pedidos/verificar', ['fecha' => $fecha]);
    }

    /**
     * POST /api/pedidos
     * Ruta principal para crear pedidos.
     * Valores por día: "Desayuno" / "Comida" / "" (vacío = no pide).
     * Frontend: Menpedidos.php, Menpedidos1.php
     *
     * @param string $fechaSemana Fecha del lunes (YYYY-MM-DD)
     * @param array  $desayunos   ['lunes' => 'Desayuno', 'martes' => '', ...]
     * @param array  $comidas     ['lunes' => 'Comida',   'martes' => '', ...]
     */
    public function crear($fechaSemana, $desayunos, $comidas) {
        return $this->http->post('api/pedidos', [
            'fecha_semana' => $fechaSemana,
            'desayunos'    => $desayunos,
            'comidas'      => $comidas,
        ]);
    }

    /**
     * POST /api/pedidos/agendar-pedidos
     * Ruta original del backend. Acepta valores "SI" / "NO" por día.
     * El frontend usa POST /api/pedidos; esta ruta es la interna del backend.
     */
    public function agendar($fechaSemana, $desayunos, $comidas) {
        return $this->http->post('api/pedidos/agendar-pedidos', [
            'fecha_semana' => $fechaSemana,
            'desayunos'    => $desayunos,
            'comidas'      => $comidas,
        ]);
    }

    /**
     * GET /api/pedidos/mis-pedidos
     * Historial de pedidos del usuario autenticado.
     * Frontend: AgendaPedidos.php, AgendaPedidos1.php
     */
    public function misPedidos() {
        return $this->http->get('api/pedidos/mis-pedidos');
    }

    /**
     * GET /api/pedidos/perfil
     * Nombre y área del usuario en la cabecera de la pantalla de pedidos.
     * Frontend: Menpedidos.php
     */
    public function perfil() {
        return $this->http->get('api/pedidos/perfil');
    }
}
