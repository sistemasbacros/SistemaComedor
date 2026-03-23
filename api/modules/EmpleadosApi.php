<?php
/**
 * Empleados
 *
 * GET    /api/empleados/perfil
 * GET    /api/empleados?search=
 * POST   /api/empleados
 * PUT    /api/empleados/{id}
 * DELETE /api/empleados/{id}
 *
 * Frontend: MenUsuario.php → perfil del usuario autenticado
 *           gestusu.php    → listado y CRUD de empleados (admin)
 *
 * Nota: /api/empleados y /api/admin/comedor/usuarios usan el mismo handler interno.
 */
class EmpleadosApi {

    private $http;

    public function __construct() {
        $this->http = new HttpClient();
    }

    /**
     * GET /api/empleados/perfil
     * Nombre y área del usuario en la cabecera del menú principal.
     * Frontend: MenUsuario.php
     */
    public function perfil() {
        return $this->http->get('api/empleados/perfil');
    }

    /**
     * GET /api/empleados?search=término
     * Lista empleados. Búsqueda por ID, nombre, área o usuario.
     * Frontend: gestusu.php
     */
    public function listar($search = null) {
        $params = $search ? ['search' => $search] : [];
        return $this->http->get('api/empleados', $params);
    }

    /**
     * POST /api/empleados
     * Alta de empleado. usuario y contrasena son opcionales.
     * Frontend: gestusu.php
     *
     * @param array $datos ['id_empleado' => ..., 'nombre' => ..., 'area' => ..., 'usuario' => ..., 'contrasena' => ...]
     */
    public function crear($datos) {
        return $this->http->post('api/empleados', $datos);
    }

    /**
     * PUT /api/empleados/{id}
     * Edición de empleado.
     * contrasena: null → no modifica la contraseña.
     * id_empleado_nuevo → permite cambiar el número de empleado.
     * Frontend: gestusu.php
     */
    public function actualizar($id, $datos) {
        return $this->http->put("api/empleados/{$id}", $datos);
    }

    /**
     * DELETE /api/empleados/{id}
     * Baja de empleado.
     * Frontend: gestusu.php
     */
    public function eliminar($id) {
        return $this->http->delete("api/empleados/{$id}");
    }
}
