<?php
/**
 * Menú
 *
 * GET    /api/menu
 * GET    /api/menu/semana/{fecha}
 * POST   /api/menu
 * PUT    /api/menu/{id}
 * DELETE /api/menu/{id}
 *
 * Frontend: Menu.php      → ver menú de la semana
 *           Menpedidos.php → muestra el menú al hacer un pedido
 *           Admin          → crear, editar y eliminar menús
 */
class MenuApi {

    private $http;

    public function __construct() {
        $this->http = new HttpClient();
    }

    /**
     * GET /api/menu
     * Lista todos los menús ordenados por semana descendente.
     * Frontend: Menu.php
     */
    public function listar() {
        return $this->http->get('api/menu');
    }

    /**
     * GET /api/menu/semana/{fecha}
     * Menú específico de una semana (fecha = lunes YYYY-MM-DD).
     * Frontend: Menu.php, Menpedidos.php
     */
    public function semana($fecha) {
        return $this->http->get("api/menu/semana/{$fecha}");
    }

    /**
     * POST /api/menu
     * Registra el menú de una semana. Retorna 409 si ya existe para esa semana.
     * Frontend: Admin (captura de menú)
     *
     * @param array $datos ['semana' => 'YYYY-MM-DD', 'lunes' => ['desayuno' => ..., 'comida' => ...], ...]
     */
    public function crear($datos) {
        return $this->http->post('api/menu', $datos);
    }

    /**
     * PUT /api/menu/{id}
     * Actualiza los días enviados en el body (PATCH-like: los omitidos no se modifican).
     * Frontend: Admin (edición de menú)
     */
    public function actualizar($id, $datos) {
        return $this->http->put("api/menu/{$id}", $datos);
    }

    /**
     * DELETE /api/menu/{id}
     * Elimina el menú indicado.
     * Frontend: Admin (eliminación de menú)
     */
    public function eliminar($id) {
        return $this->http->delete("api/menu/{$id}");
    }
}
