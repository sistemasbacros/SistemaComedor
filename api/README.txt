================================================================================
  API CLIENTE UNIFICADO - SISTEMA COMEDOR BACROCORP
  Basado en: BACROS_Comedor.postman_collection.json
================================================================================

ESTRUCTURA DE ARCHIVOS
-----------------------
  api/
  ├── Api.php              <- Punto de entrada unico (require esto en tus paginas)
  ├── ApiConfig.php        <- Deteccion de entorno (local / desarrollo / produccion)
  ├── TokenManager.php     <- JWT en sesion PHP
  ├── HttpClient.php       <- Cliente curl (GET/POST/PUT/DELETE)
  └── modules/
      ├── AuthApi.php          POST /auth/login, validate, refresh, profile
      ├── PedidosApi.php       semanas-disponibles, verificar, crear, mis-pedidos, perfil
      ├── ConsumosApi.php      mis-consumos, reporte
      ├── CancelacionesApi.php validaciones, crear, mis-cancelaciones, pendientes
      ├── EmpleadosApi.php     perfil, listar, crear, actualizar, eliminar
      ├── EstadisticasApi.php  mis-consumos, cancelaciones, consumo, dashboard
      ├── MenuApi.php          listar, semana, crear, actualizar, eliminar
      ├── ReporteApi.php       detallado
      ├── AdminApi.php         dashboard, cancelaciones-pendientes, usuarios CRUD, reportes
      ├── CheckadorApi.php     disponibilidad, registrar, desglose (sin JWT)
      └── CocinaApi.php        entradas / complementos / cancelaciones / pedidos / compras

  /deprecated/   - Archivos obsoletos (no usar)
  /tests/        - Scripts de prueba


================================================================================
  COMO INCLUIR EN CUALQUIER PAGINA
================================================================================

require_once __DIR__ . '/api/Api.php';   // desde la raiz del proyecto


================================================================================
  TIPOS DE PAGINA
================================================================================

--- PAGINA PUBLICA (sin login) ---
  Ejemplo: CHECADORF.php, Desglosechecador.php

    <?php
    header("Cache-Control: no-store");
    require_once __DIR__ . '/api/Api.php';
    // Sin nada mas — llamar endpoints publicos directamente
    $data = Api::checador()->disponibilidad();
    ?>

--- PAGINA CON LOGIN (usuario normal) ---
  Ejemplo: MenUsuario.php, Menpedidos.php, AgendaPedidos.php

    <?php
    header("Cache-Control: no-store");
    require_once __DIR__ . '/api/Api.php';
    Api::requireAuth();                   // redirige a Admiin.php si no hay sesion
    $user = Api::getCurrentUser();        // ['id_empleado', 'nombre', 'area', 'usuario']
    ?>

--- PAGINA SOLO ADMIN ---
  Ejemplo: admicome4.php, gestusu.php

    <?php
    header("Cache-Control: no-store");
    require_once __DIR__ . '/api/Api.php';
    Api::requireAuth();
    $user = Api::getCurrentUser();
    $areasAdmin = ['DIRECCIÓN', 'ADMINISTRADOR', 'SISTEMAS'];
    if (!in_array(strtoupper($user['area']), $areasAdmin, true)) {
        header('Location: MenUsuario.php');
        exit;
    }
    // A partir de aqui: usar Api::admin()->...
    ?>


================================================================================
  MODULOS Y METODOS
================================================================================

------------------------------------------------------------------------
AUTH
  Frontend: Admiin.php
------------------------------------------------------------------------
  Api::auth()->login($usuario, $contrasena)   // guarda JWT en sesion
  Api::auth()->logout()                        // destruye sesion local
  Api::auth()->validarToken()                  // GET /auth/validate
  Api::auth()->refrescarToken()               // POST /auth/refresh
  Api::auth()->perfil()                        // GET /auth/profile

  Atajos directos:
  Api::requireAuth()                           // redirige si no autenticado
  Api::isAuthenticated()                       // bool
  Api::getCurrentUser()                        // array con datos del usuario
  Api::getToken()                              // string JWT actual

------------------------------------------------------------------------
PEDIDOS
  Frontend: Menpedidos.php, Menpedidos1.php, AgendaPedidos.php
------------------------------------------------------------------------
  Api::pedidos()->semanasDisponibles()
  Api::pedidos()->verificar($fecha)            // $fecha = 'YYYY-MM-DD'
  Api::pedidos()->crear($fechaSemana, $desayunos, $comidas)
  Api::pedidos()->agendar($fechaSemana, $desayunos, $comidas)
  Api::pedidos()->misPedidos()
  Api::pedidos()->perfil()

  Ejemplo crear pedido:
    Api::pedidos()->crear(
        '2026-03-17',
        ['lunes' => 'Desayuno', 'martes' => 'Desayuno', 'miercoles' => '',  'jueves' => 'Desayuno', 'viernes' => ''],
        ['lunes' => 'Comida',   'martes' => '',          'miercoles' => 'Comida', 'jueves' => '',  'viernes' => 'Comida']
    );

------------------------------------------------------------------------
CONSUMOS
  Frontend: aparta_consumo_modificado.php, Descrip_Consumo.php, REPOCOMEDOR.php
------------------------------------------------------------------------
  Api::consumos()->misConsumos($fecha)         // $fecha opcional 'YYYY-MM-DD'
  Api::consumos()->reporte()                   // admin: reporte completo

------------------------------------------------------------------------
CANCELACIONES
  Frontend: FormatCancel.php, Formacancel123456.php, EstadCancelaciones.php,
            EstadisticasCancelaciones.php, MenUsuario.php
------------------------------------------------------------------------
  Api::cancelaciones()->validaciones()
  Api::cancelaciones()->crear([
      'jefe'         => 'Nombre del jefe',
      'tipo_consumo' => 'DESAYUNO',   // DESAYUNO | COMIDA | AMBOS
      'fecha'        => 'YYYY-MM-DD',
      'causa'        => 'PERSONAL'    // SALUD | PERSONAL | VACACIONES | COMISIÓN | REUNIÓN
  ])
  Api::cancelaciones()->misCancelaciones()
  Api::cancelaciones()->pendientes()           // badge en MenUsuario.php

------------------------------------------------------------------------
EMPLEADOS
  Frontend: MenUsuario.php (perfil), gestusu.php (CRUD admin)
------------------------------------------------------------------------
  Api::empleados()->perfil()
  Api::empleados()->listar($search)            // $search opcional
  Api::empleados()->crear([
      'id_empleado' => 456,
      'nombre'      => 'Maria Lopez',
      'area'        => 'Contabilidad',
      'usuario'     => 'maria.lopez',   // opcional
      'contrasena'  => 'pass123'        // opcional
  ])
  Api::empleados()->actualizar($id, [
      'id_empleado_nuevo' => 456,       // permite cambiar el numero
      'nombre'            => 'Nuevo nombre',
      'area'              => 'Finanzas',
      'usuario'           => 'maria.lopez',
      'contrasena'        => null       // null = no cambia la contrasena
  ])
  Api::empleados()->eliminar($id)

------------------------------------------------------------------------
ESTADISTICAS
  Frontend: KPI_anacomp.php, EstadisticasCancelaciones.php, EstadCancelaciones.php
------------------------------------------------------------------------
  Api::estadisticas()->misConsumos()           // stats personales por dia
  Api::estadisticas()->cancelaciones()         // agrupadas por causa y tipo
  Api::estadisticas()->consumo()               // distribucion por dia de semana
  Api::estadisticas()->dashboard()             // conteos globales simplificados

------------------------------------------------------------------------
MENU
  Frontend: Menu.php, Menpedidos.php, Admin
------------------------------------------------------------------------
  Api::menu()->listar()
  Api::menu()->semana($fecha)                  // $fecha = lunes 'YYYY-MM-DD'
  Api::menu()->crear([
      'semana'    => 'YYYY-MM-DD',
      'lunes'     => ['desayuno' => 'Chilaquiles', 'comida' => 'Pollo'],
      'martes'    => ['desayuno' => 'Tamales',     'comida' => 'Carne'],
      'miercoles' => [...],
      'jueves'    => [...],
      'viernes'   => [...]
  ])
  Api::menu()->actualizar($id, ['lunes' => ['desayuno' => 'Nuevo platillo', 'comida' => '...']])
  Api::menu()->eliminar($id)

------------------------------------------------------------------------
REPORTE
  Frontend: REPOCOMEDOR.php
------------------------------------------------------------------------
  Api::reporte()->detallado()                  // sin filtro
  Api::reporte()->detallado([
      'fecha_inicio' => '2026-03-01',
      'fecha_fin'    => '2026-03-31'
  ])

------------------------------------------------------------------------
ADMIN  (requiere area DIRECCIÓN / ADMINISTRADOR / SISTEMAS)
  Frontend: admicome4.php, gestusu.php, REPOCOMEDOR.php, EstadisticasCancelaciones.php
------------------------------------------------------------------------
  Api::admin()->dashboard()                    // metricas completas sin filtro
  Api::admin()->dashboard([
      'fecha_inicio' => '2026-03-01',
      'fecha_fin'    => '2026-03-31'
  ])
  Api::admin()->cancelacionesPendientes()      // conteo global para badge

  // Usuarios (mismo CRUD que empleados, rutas /admin/comedor/usuarios)
  Api::admin()->listarUsuarios($search)
  Api::admin()->crearUsuario($datos)
  Api::admin()->actualizarUsuario($id, $datos)
  Api::admin()->eliminarUsuario($id)

  // Reportes admin (ambas fechas requeridas)
  Api::admin()->reportePrincipal('2026-03-01', '2026-03-31')
  Api::admin()->reporteCancelaciones('2026-03-01', '2026-03-31')
  Api::admin()->reporteComplementos('2026-03-01', '2026-03-31')

------------------------------------------------------------------------
CHECADOR  (sin JWT — dispositivo fisico en la entrada)
  Frontend: CHECADORF.php, Desglosechecador.php
------------------------------------------------------------------------
  Api::checador()->disponibilidad()
  Api::checador()->registrar($nombre, $complemento)
    // $nombre      = string del QR "PEREZ GARCIA JUAN|123|SISTEMAS" o nombre manual
    // $complemento = 'CAFÉ O TÉ' | 'TORTILLAS' | 'AGUA' | 'DESECHABLE' | 'COMIDA PARA LLEVAR' | null
  Api::checador()->desglose($fechaInicio, $fechaFin)
    // ambos opcionales, default: hoy

------------------------------------------------------------------------
COCINA  (sub-modulos encadenados)
  Frontend: dchef.php, CocinaTotalPedidos.php, AgendaPedidos.php, MenComprasCocina.php
------------------------------------------------------------------------
  // Entradas
  Api::cocina()->entradas()->listar()
  Api::cocina()->entradas()->estadisticas()
  Api::cocina()->entradas()->atender($nombre, $horaEntrada, $fechaHora)

  // Complementos
  Api::cocina()->complementos()->listar()
  Api::cocina()->complementos()->estadisticas()
  Api::cocina()->complementos()->atender($nombre, $complemento, $fecha, $hora)

  // Cancelaciones de cocina
  Api::cocina()->cancelaciones()->listar()
  Api::cocina()->cancelaciones()->asignar($nombreCancelacion, $tipoConsumo, $fechaCancelacion, $nombrePersona)
  Api::cocina()->cancelaciones()->liberar($nombreCancelacion, $tipoConsumo, $fechaCancelacion)

  // Pedidos semana
  Api::cocina()->pedidos()->semana()           // CTE + UNPIVOT: fila por empleado/dia/servicio
  Api::cocina()->pedidos()->totalesSemana()    // PIVOT: suma lunes-viernes
  Api::cocina()->pedidos()->detalle()          // eleccion por dia por empleado

  // Lista de compras
  Api::cocina()->compras()->guardar([
      'fecha'      => 'YYYY-MM-DD',
      'carnes'     => ['Pollo 5kg', 'Res 3kg'],
      'frutas'     => ['Manzanas 2kg'],
      'verduras'   => ['Tomates 1kg'],
      'lacteos'    => ['Leche 2L'],
      'accesorios' => ['Papel aluminio']
  ])
  Api::cocina()->compras()->listar('YYYY-MM-DD')


================================================================================
  FORMATO DE RESPUESTA
================================================================================

Todas las llamadas retornan:

  EXITO:
    ['success' => true,  'data' => [...], 'http_code' => 200]

  ERROR:
    ['success' => false, 'error' => 'Mensaje', 'data' => null, 'http_code' => 400]

  Patron de uso:
    $result = Api::pedidos()->misPedidos();
    if ($result['success']) {
        $data = $result['data'];
    } else {
        $error = $result['error'];  // string descriptivo
    }


================================================================================
  CONFIGURACION DE ENTORNO (automatica)
================================================================================

  local       - localhost sin Docker     -> http://127.0.0.1:3000
  desarrollo  - hostname con "desarollo" -> http://desarollo-bacros:3000
  produccion  - Docker en produccion     -> http://host.docker.internal:3000

  Para ver el entorno detectado:
    $info = Api::info();
    // ['entorno', 'api_url', 'timeout', 'debug', 'authenticated', 'has_token']

  Para forzar entorno editar ApiConfig.php linea ~30:
    return 'produccion';  // local | desarrollo | produccion


================================================================================
  FUNCIONES LEGACY (compatibilidad con codigo anterior)
================================================================================

  Funcion vieja                ->  Equivalente nuevo
  -----------------------------------------------------------
  getAPIClient()               ->  Api::http()
  getJwtToken()                ->  Api::getToken()
  setJwtToken($token)          ->  TokenManager::save($token)
  clearJwtToken()              ->  TokenManager::clear()
  isUserAuthenticated()        ->  Api::isAuthenticated()
  requireAuthentication()      ->  Api::requireAuth()
  getCurrentUser()             ->  Api::getCurrentUser()
  obtenerPerfilUsuario()       ->  Api::pedidos()->perfil()
  obtenerSemanasDisponibles()  ->  Api::pedidos()->semanasDisponibles()
  obtenerConsumosSemanales($f) ->  Api::consumos()->misConsumos($f)
  crearPedidoSemanal(...)      ->  Api::pedidos()->crear(...)


================================================================================
  DEBUGGING
================================================================================

  error_log(json_encode(Api::info()));
  error_log(json_encode(Api::getCurrentUser()));
  error_log('Token: ' . Api::getToken());

  // Ver respuesta cruda del backend
  $result = Api::pedidos()->misPedidos();
  error_log('HTTP: ' . $result['http_code']);
  error_log('Data: ' . json_encode($result['data']));


================================================================================
  SEGURIDAD
================================================================================

  - JWT almacenado en $_SESSION (nunca en localStorage ni cookies)
  - Todas las peticiones incluyen automaticamente Authorization: Bearer <token>
  - AdminApi verifica el area del usuario ANTES de llamar al backend
  - Los tokens expiran en 24h (86400 segundos)
  - El backend valida el JWT independientemente de la capa PHP

================================================================================
