<?php
/**
 * Admin - Comedor
 *
 * GET    /api/admin/comedor/dashboard?fecha_inicio=&fecha_fin=
 * GET    /api/admin/comedor/cancelaciones-pendientes
 * GET    /api/admin/comedor/usuarios?search=
 * POST   /api/admin/comedor/usuarios
 * PUT    /api/admin/comedor/usuarios/{id}
 * DELETE /api/admin/comedor/usuarios/{id}
 * GET    /api/admin/comedor/reporte-principal?fecha_inicio=&fecha_fin=
 * GET    /api/admin/comedor/reporte-cancelaciones?fecha_inicio=&fecha_fin=
 * GET    /api/admin/comedor/reporte-complementos?fecha_inicio=&fecha_fin=
 *
 * Frontend: admicome4.php → dashboard y cancelaciones pendientes
 *           gestusu.php   → gestión de usuarios (alias de /api/empleados)
 *           REPOCOMEDOR.php → reporte principal
 *           EstadisticasCancelaciones.php → reporte de cancelaciones
 */
class AdminApi {

    private $http;

    // Áreas con acceso admin. Agregar aquí si cambia la lógica de roles.
    private const ROLES_ADMIN = ['DIRECCIÓN', 'ADMINISTRADOR', 'SISTEMAS'];

    public function __construct() {
        $this->http = new HttpClient();
    }

    /**
     * Verifica que el usuario en sesión tenga rol de administrador.
     * El backend también valida el JWT, pero esta verificación evita
     * llamadas innecesarias a la API con usuarios sin permiso.
     *
     * @throws RuntimeException si no hay sesión activa o el área no es admin
     */
    private function requireAdmin() {
        if (!TokenManager::isAuthenticated()) {
            header('Location: ' . ApiConfig::getInstance()->getAppUrl('Admiin.php'));
            exit;
        }
        $user = TokenManager::getCurrentUser();
        $area = strtoupper(trim($user['area'] ?? ''));
        if (!in_array($area, self::ROLES_ADMIN, true)) {
            http_response_code(403);
            exit('Acceso denegado: se requiere rol de administrador.');
        }
    }

    /**
     * GET /api/admin/comedor/dashboard?fecha_inicio=&fecha_fin=
     * Dashboard completo: consumos, cancelaciones, montos, gastos y empleados exentos.
     * Frontend: admicome4.php
     * (Para dashboard simplificado usar Estadisticas::dashboard())
     *
     * @param array $params ['fecha_inicio' => 'YYYY-MM-DD', 'fecha_fin' => 'YYYY-MM-DD'] (opcionales)
     */
    public function dashboard($params = []) {
        $this->requireAdmin();
        return $this->http->get('api/admin/comedor/dashboard', $params);
    }

    /**
     * GET /api/admin/comedor/cancelaciones-pendientes
     * Badge con el número de cancelaciones EN PROCESO en todo el sistema.
     * Frontend: admicome4.php
     */
    public function cancelacionesPendientes() {
        $this->requireAdmin();
        return $this->http->get('api/admin/comedor/cancelaciones-pendientes');
    }

    /**
     * GET /api/admin/comedor/usuarios?search=
     * Listado de empleados con soporte de búsqueda por ID, nombre, área o usuario.
     * Frontend: gestusu.php (también accesible vía GET /api/empleados)
     */
    public function listarUsuarios($search = null) {
        $this->requireAdmin();
        $params = $search ? ['search' => $search] : [];
        return $this->http->get('api/admin/comedor/usuarios', $params);
    }

    /**
     * POST /api/admin/comedor/usuarios
     * Alta de nuevo empleado. usuario y contrasena son opcionales.
     * Frontend: gestusu.php (también accesible vía POST /api/empleados)
     */
    public function crearUsuario($datos) {
        $this->requireAdmin();
        return $this->http->post('api/admin/comedor/usuarios', $datos);
    }

    /**
     * PUT /api/admin/comedor/usuarios/{id}
     * Edición de empleado.
     * contrasena: null → no modifica la contraseña.
     * id_empleado_nuevo → permite cambiar el número de empleado.
     * Frontend: gestusu.php (también accesible vía PUT /api/empleados/:id)
     */
    public function actualizarUsuario($id, $datos) {
        $this->requireAdmin();
        return $this->http->put("api/admin/comedor/usuarios/{$id}", $datos);
    }

    /**
     * DELETE /api/admin/comedor/usuarios/{id}
     * Baja de empleado.
     * Frontend: gestusu.php (también accesible vía DELETE /api/empleados/:id)
     */
    public function eliminarUsuario($id) {
        $this->requireAdmin();
        return $this->http->delete("api/admin/comedor/usuarios/{$id}");
    }

    /**
     * GET /api/admin/comedor/reporte-principal?fecha_inicio=&fecha_fin=
     * Reporte consolidado de todos los empleados: asistencias, pedidos,
     * entradas, cancelaciones y balance. Ambas fechas requeridas.
     * Frontend: REPOCOMEDOR.php, admicome4.php
     */
    public function reportePrincipal($fechaInicio, $fechaFin) {
        $this->requireAdmin();
        return $this->http->get('api/admin/comedor/reporte-principal', [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin'    => $fechaFin,
        ]);
    }

    /**
     * GET /api/admin/comedor/reporte-cancelaciones?fecha_inicio=&fecha_fin=
     * Cancelaciones del período con montos calculados, agrupadas por persona.
     * Ambas fechas requeridas.
     * Frontend: EstadisticasCancelaciones.php
     */
    public function reporteCancelaciones($fechaInicio, $fechaFin) {
        $this->requireAdmin();
        return $this->http->get('api/admin/comedor/reporte-cancelaciones', [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin'    => $fechaFin,
        ]);
    }

    /**
     * GET /api/admin/comedor/reporte-complementos?fecha_inicio=&fecha_fin=
     * Complementos consumidos (café/té, tortillas, agua, desechable, comida para llevar).
     * Con totales y montos. Ambas fechas requeridas.
     * Frontend: Admin (reporte de complementos)
     */
    public function reporteComplementos($fechaInicio, $fechaFin) {
        $this->requireAdmin();
        return $this->http->get('api/admin/comedor/reporte-complementos', [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin'    => $fechaFin,
        ]);
    }
}
