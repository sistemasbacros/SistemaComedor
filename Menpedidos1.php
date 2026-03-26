<?php
// ==================================================
// CONFIGURACIÓN Y CONEXIÓN A BD
// ==================================================

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/Comedor/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

// ==================================================
// DEFINICIÓN DE SEMANAS DISPONIBLES - MARZO/ABRIL 2026 (DESDE 23-03-2026)
// ==================================================

$semanasDisponibles = [
    // Semana 4 Marzo: 23-27 Marzo 2026
    [
        'fecha' => '2026-03-23',
        'mostrar' => '23/03/2026 - Semana 4 Mar (23-27 Mar)',
        'num_semana' => 4,
        'mes' => 'marzo',
        'fecha_inicio' => '2026-03-23',
        'fecha_fin' => '2026-03-27'
    ],
    // Semana 1 Abril: 30 Mar - 3 Abril 2026
    [
        'fecha' => '2026-03-30',
        'mostrar' => '30/03/2026 - Semana 1 Abr (30 Mar-3 Abr)',
        'num_semana' => 1,
        'mes' => 'abril',
        'fecha_inicio' => '2026-03-30',
        'fecha_fin' => '2026-04-03'
    ],
    // Semana 2 Abril: 6-10 Abril 2026
    [
        'fecha' => '2026-04-06',
        'mostrar' => '06/04/2026 - Semana 2 Abr (6-10 Abr)',
        'num_semana' => 2,
        'mes' => 'abril',
        'fecha_inicio' => '2026-04-06',
        'fecha_fin' => '2026-04-10'
    ],
    // Semana 3 Abril: 13-17 Abril 2026
    [
        'fecha' => '2026-04-13',
        'mostrar' => '13/04/2026 - Semana 3 Abr (13-17 Abr)',
        'num_semana' => 3,
        'mes' => 'abril',
        'fecha_inicio' => '2026-04-13',
        'fecha_fin' => '2026-04-17'
    ],
    // Semana 4 Abril: 20-24 Abril 2026
    [
        'fecha' => '2026-04-20',
        'mostrar' => '20/04/2026 - Semana 4 Abr (20-24 Abr)',
        'num_semana' => 4,
        'mes' => 'abril',
        'fecha_inicio' => '2026-04-20',
        'fecha_fin' => '2026-04-24'
    ],
    // Semana 5 Abril: 27-30 Abril 2026
    [
        'fecha' => '2026-04-27',
        'mostrar' => '27/04/2026 - Semana 5 Abr (27-30 Abr)',
        'num_semana' => 5,
        'mes' => 'abril',
        'fecha_inicio' => '2026-04-27',
        'fecha_fin' => '2026-04-30'
    ]
];

// ==================================================
// FUNCIÓN PARA OBTENER LA SIGUIENTE SEMANA DESDE HOY (SOLO POSTERIOR)
// ==================================================

function obtenerSiguienteSemana($semanasDisponibles) {
    $hoy = new DateTime();
    $hoy->setTime(0, 0, 0);
    
    foreach ($semanasDisponibles as $semana) {
        $fechaInicio = new DateTime($semana['fecha_inicio']);
        $fechaInicio->setTime(0, 0, 0);
        
        if ($fechaInicio > $hoy) {
            return $semana;
        }
    }
    
    return end($semanasDisponibles);
}

$semanaPorDefecto = obtenerSiguienteSemana($semanasDisponibles);

// ==================================================
// OBTENER PARÁMETROS DEL USUARIO Y DATOS DE BD
// ==================================================

$user_name = $_GET['user_name'] ?? $_SESSION['user_name'] ?? '';
$user_area = $_GET['user_area'] ?? $_SESSION['user_area'] ?? '';

if (!empty($user_name)) {
    $user_name = urldecode($user_name);
    $user_area = urldecode($user_area);
    $_SESSION['user_name'] = $user_name;
    $_SESSION['user_area'] = $user_area;
}

$serverName = "DESAROLLO-BACRO\\SQLEXPRESS";
$connectionInfo = [
    "Database" => "Comedor",
    "UID" => "Larome03",
    "PWD" => "Larome03",
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true,
    "LoginTimeout" => 5
];

$conn = sqlsrv_connect($serverName, $connectionInfo);

$id_empleado = '';
$usuario_bd = '';
$contrasena_bd = '';

if ($conn && !empty($user_name)) {
    $sql = "SELECT Id_Empleado, nombre, area, usuario, Contrasena FROM ConPed WHERE nombre LIKE ?";
    $params = ["%$user_name%"];
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt && sqlsrv_has_rows($stmt)) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $id_empleado = $row['Id_Empleado'] ?? '';
        $usuario_bd = $row['usuario'] ?? '';
        $contrasena_bd = $row['Contrasena'] ?? '';
    }
    
    if ($stmt) sqlsrv_free_stmt($stmt);
}

// ==================================================
// PROCESAMIENTO DEL FORMULARIO
// ==================================================

function test_input($data) {
    if (empty($data)) return '';
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

$notification = ['type' => '', 'message' => ''];
$pedido_exitoso = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lunesd = test_input($_POST["gender1"] ?? '');
    $lunesc = test_input($_POST["gender2"] ?? '');
    $martesd = test_input($_POST["gender3"] ?? '');
    $martesc = test_input($_POST["gender4"] ?? '');
    $miercolesd = test_input($_POST["gender5"] ?? '');
    $miercolesc = test_input($_POST["gender6"] ?? '');
    $juevesd = test_input($_POST["gender7"] ?? '');
    $juevesc = test_input($_POST["gender8"] ?? '');
    $viernesd = test_input($_POST["gender9"] ?? '');
    $viernesc = test_input($_POST["gender10"] ?? '');
    $numemp = test_input($_POST["Nempleado"] ?? '');
    $usua = test_input($_POST["Usuar"] ?? '');
    $cont = test_input($_POST["contrase"] ?? '');
    $fecha = test_input($_POST["Fecha2"] ?? '');

    $fechaSeleccionada = new DateTime($fecha);
    $hoy = new DateTime();
    $hoy->setTime(0, 0, 0);
    $fechaInicioSemana = new DateTime($fecha);
    $fechaInicioSemana->setTime(0, 0, 0);
    
    if ($fechaInicioSemana <= $hoy) {
        $notification = ['type' => 'error', 'message' => '❌ No se pueden realizar pedidos para la semana actual o semanas pasadas. Solo puedes pedir para semanas futuras.'];
    } elseif (empty($numemp) || empty($usua) || empty($cont) || empty($fecha)) {
        $notification = ['type' => 'error', 'message' => 'Todos los campos son obligatorios.'];
    } elseif (!$conn) {
        $notification = ['type' => 'error', 'message' => 'Error de conexión con la base de datos.'];
    } else {
        $sql2 = "SELECT Usuario, Contrasena FROM ConPed WHERE Usuario = ? AND Contrasena = ?";
        $stmt2 = sqlsrv_query($conn, $sql2, [$usua, $cont]);
        $credencial_valida = ($stmt2 && sqlsrv_has_rows($stmt2));

        $sql3 = "SELECT COUNT(*) AS Total FROM PedidosComida WHERE Fecha = ? AND Usuario = ?";
        $stmt3 = sqlsrv_query($conn, $sql3, [$fecha, $usua]);
        $row = $stmt3 ? sqlsrv_fetch_array($stmt3, SQLSRV_FETCH_ASSOC) : null;
        $valor1 = $row['Total'] ?? 0;

        if (!$credencial_valida) {
            $notification = ['type' => 'error', 'message' => 'Usuario o contraseña incorrectos.'];
        } elseif ($valor1 >= 2) {
            $notification = ['type' => 'error', 'message' => 'Ya tienes un pedido registrado para esta fecha.'];
        } else {
            $sql = "INSERT INTO PedidosComida (Id_Empleado, Nom_Pedido, Usuario, Contrasena, Fecha, Lunes, Martes, Miercoles, Jueves, Viernes, Costo) 
                    VALUES (?, '', ?, ?, ?, ?, ?, ?, ?, ?, 30)";
            
            $params1 = [$numemp, $usua, $cont, $fecha, $lunesd, $martesd, $miercolesd, $juevesd, $viernesd];
            $params2 = [$numemp, $usua, $cont, $fecha, $lunesc, $martesc, $miercolesc, $juevesc, $viernesc];

            $stmt = sqlsrv_query($conn, $sql, $params1);
            $stmt1 = sqlsrv_query($conn, $sql, $params2);

            if ($stmt && $stmt1) {
                $notification = ['type' => 'success', 'message' => '✅ ¡Tu pedido se registró con éxito para la semana seleccionada!'];
                $pedido_exitoso = true;
            } else {
                $notification = ['type' => 'error', 'message' => 'Error al registrar el pedido. Inténtalo más tarde.'];
            }

            if ($stmt) sqlsrv_free_stmt($stmt);
            if ($stmt1) sqlsrv_free_stmt($stmt1);
        }

        if ($stmt2) sqlsrv_free_stmt($stmt2);
        if ($stmt3) sqlsrv_free_stmt($stmt3);
    }
}

if ($conn) {
    sqlsrv_close($conn);
}

// ==================================================
// DETECTAR SEMANA SELECCIONADA
// ==================================================

$semana_seleccionada = $_POST['Fecha2'] ?? $_GET['semana'] ?? $semanaPorDefecto['fecha'];
$num_semana_seleccionada = 0;
$mes_seleccionado = '';
$fecha_inicio = '';
$fecha_fin = '';

foreach ($semanasDisponibles as $semana) {
    if ($semana['fecha'] === $semana_seleccionada) {
        $num_semana_seleccionada = $semana['num_semana'];
        $mes_seleccionado = $semana['mes'];
        $fecha_inicio = $semana['fecha_inicio'];
        $fecha_fin = $semana['fecha_fin'];
        break;
    }
}

$fechaSeleccionadaObj = new DateTime($fecha_inicio);
$hoyObj = new DateTime();
$hoyObj->setTime(0, 0, 0);
if ($fechaSeleccionadaObj <= $hoyObj) {
    $semana_seleccionada = $semanaPorDefecto['fecha'];
    foreach ($semanasDisponibles as $semana) {
        if ($semana['fecha'] === $semana_seleccionada) {
            $num_semana_seleccionada = $semana['num_semana'];
            $mes_seleccionado = $semana['mes'];
            $fecha_inicio = $semana['fecha_inicio'];
            $fecha_fin = $semana['fecha_fin'];
            break;
        }
    }
}

// ==================================================
// MENÚS COMPLETOS - MARZO Y ABRIL 2026
// ==================================================

$menus_completos = [
    'marzo_4' => [
        'lunes' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🍍', 'descripcion' => 'Papaya con piña', 'icono3' => '🧀', 'detalle' => 'Quesadillas de jamón 3 piezas'],
            'comida' => ['icono' => '🍚', 'nombre' => 'Arroz amarillo', 'icono2' => '🥩', 'descripcion' => 'Bistec en salsa verde con papas', 'icono3' => '🍮', 'postre' => 'Natilla de vainilla', 'icono4' => '🍋', 'bebida' => 'Agua de limonada']
        ],
        'martes' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🍎', 'descripcion' => 'Manzana con granola', 'icono3' => '🌶️', 'detalle' => 'Enchiladas suizas'],
            'comida' => ['icono' => '🍝', 'nombre' => 'Espagueti rojo', 'icono2' => '🍖', 'descripcion' => 'Pacholas 2 pz con ensalada', 'icono3' => '🌈', 'postre' => 'Gelatina mosaico L/A', 'icono4' => '🍍', 'bebida' => 'Agua de piña colada']
        ],
        'miercoles' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🍉', 'descripcion' => 'Melón con sandía', 'icono3' => '🥪', 'detalle' => 'Sandwich de jamón y manchego'],
            'comida' => ['icono' => '🥣', 'nombre' => 'Sopa de verduras', 'icono2' => '🥗', 'descripcion' => 'Ensalada con pasta y pollo', 'icono3' => '🍮', 'postre' => 'Flan de cajeta', 'icono4' => '🍋', 'bebida' => 'Agua de limón con chía']
        ],
        'jueves' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🍌', 'descripcion' => 'Plátanos con yogurt', 'icono3' => '🌯', 'detalle' => 'Burrito de pastor'],
            'comida' => ['icono' => '🍝', 'nombre' => 'Pasta pluma al chipotle', 'icono2' => '🌭', 'descripcion' => 'Hot dogs con tocino 2 piezas', 'icono3' => '🍦', 'postre' => 'Gelatina yogurt natural', 'icono4' => '🍊', 'bebida' => 'Agua de naranjada']
        ],
        'viernes' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🥣', 'descripcion' => 'Atole champurrado', 'icono3' => '🌶️', 'detalle' => 'Mollequiles'],
            'comida' => ['icono' => '🍚', 'nombre' => 'Arroz blanco', 'icono2' => '🐟', 'descripcion' => 'Filete rebosado con ensalada', 'icono3' => '🧁', 'postre' => 'Cup cake de limón', 'icono4' => '🍓', 'bebida' => 'Agua de fresa con kiwi']
        ]
    ],
    'abril_1' => [
        'lunes' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🥣', 'descripcion' => 'Avena con plátano', 'icono3' => '🍳', 'detalle' => 'Huevo con jamón'],
            'comida' => ['icono' => '🍚', 'nombre' => 'Arroz amarillo', 'icono2' => '🍗', 'descripcion' => 'Pollo con mole rojo y frijoles', 'icono3' => '🍪', 'postre' => 'Pepitorias', 'icono4' => '🍍', 'bebida' => 'Agua de piña']
        ],
        'martes' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🍈', 'descripcion' => 'Melón con yogurt', 'icono3' => '🌶️', 'detalle' => 'Enchiladas potosinas'],
            'comida' => ['icono' => '🍚', 'nombre' => 'Arroz rojo', 'icono2' => '🍖', 'descripcion' => 'Costillas en morita con frijoles', 'icono3' => '🍮', 'postre' => 'Flan de cajeta', 'icono4' => '🍋', 'bebida' => 'Agua de limón']
        ],
        'miercoles' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🍉', 'descripcion' => 'Sandía con miel y granola', 'icono3' => '🍳', 'detalle' => 'Huevos cocoyoc'],
            'comida' => ['icono' => '🥣', 'nombre' => 'Consome de verduras', 'icono2' => '🌮', 'descripcion' => 'Tacos dorados de res 4 pz', 'icono3' => '🍎', 'postre' => 'Fruta con chile', 'icono4' => '🍈', 'bebida' => 'Agua de papaya']
        ],
        'jueves' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🍈', 'descripcion' => 'Papaya con yogurt', 'icono3' => '🥪', 'detalle' => 'Sincronizadas 2 pz'],
            'comida' => ['icono' => '🥣', 'nombre' => 'Sopa munición', 'icono2' => '🥔', 'descripcion' => 'Papas con chorizo y frijoles', 'icono3' => '🍮', 'postre' => 'Mousse de mango', 'icono4' => '🍉', 'bebida' => 'Agua de sandía']
        ],
        'viernes' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🥛', 'descripcion' => 'Atole de cajeta', 'icono3' => '🥪', 'detalle' => 'Torta de milanesa de pollo'],
            'comida' => ['icono' => '🍚', 'nombre' => 'Arroz blanco', 'icono2' => '🐟', 'descripcion' => 'Pescado a la talla con verduras mantequilla', 'icono3' => '🍇', 'postre' => 'Gelatina de arándano', 'icono4' => '🍈', 'bebida' => 'Agua de guayaba']
        ]
    ],
    'abril_2' => [
        'lunes' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🍉', 'descripcion' => 'Sandía con yogurt', 'icono3' => '🌭', 'detalle' => 'Huevo con chorizo'],
            'comida' => ['icono' => '🍚', 'nombre' => 'Arroz rojo', 'icono2' => '🥩', 'descripcion' => 'Bistec a la mexicana con frijoles', 'icono3' => '🍬', 'postre' => 'Palanquetas', 'icono4' => '🍈', 'bebida' => 'Agua de melón']
        ],
        'martes' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🍈', 'descripcion' => 'Melón', 'icono3' => '🌶️', 'detalle' => 'Chilaquiles verdes con huevo'],
            'comida' => ['icono' => '🥣', 'nombre' => 'Sopa de engrane', 'icono2' => '🍗', 'descripcion' => 'Muslos adobados con verduras al vapor', 'icono3' => '🍮', 'postre' => 'Gelatina de rompope', 'icono4' => '🥭', 'bebida' => 'Agua de mango']
        ],
        'miercoles' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🍈', 'descripcion' => 'Papaya con miel y granola', 'icono3' => '🥪', 'detalle' => 'Sandwich de jamón y panela'],
            'comida' => ['icono' => '🍚', 'nombre' => 'Arroz amarillo', 'icono2' => '🥩', 'descripcion' => 'Albóndigas en salsa verde con frijoles', 'icono3' => '🥕', 'postre' => 'Crudites de zanahoria', 'icono4' => '🌿', 'bebida' => 'Agua de mojito']
        ],
        'jueves' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🥛', 'descripcion' => 'Yogurt con granola', 'icono3' => '🌯', 'detalle' => 'Burrito campechano'],
            'comida' => ['icono' => '🍝', 'nombre' => 'Espagueti al burro', 'icono2' => '🌶️', 'descripcion' => 'Rajas con crema y frijoles', 'icono3' => '🍫', 'postre' => 'Natilla de chocolate', 'icono4' => '🍉', 'bebida' => 'Agua de frutas']
        ],
        'viernes' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🥛', 'descripcion' => 'Atole de fresa', 'icono3' => '🥓', 'detalle' => 'Molletes con tocino'],
            'comida' => ['icono' => '🍝', 'nombre' => 'Pasta pluma alfredo', 'icono2' => '🐟', 'descripcion' => 'Pescado a la plancha con ensalada', 'icono3' => '🍎', 'postre' => 'Ensalada de manzana', 'icono4' => '🌺', 'bebida' => 'Agua de Jamaica']
        ]
    ],
    'abril_3' => [
        'lunes' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🍉', 'descripcion' => 'Sandía con piña', 'icono3' => '🥕', 'detalle' => 'Omelette de zanahoria'],
            'comida' => ['icono' => '🍚', 'nombre' => 'Arroz verde', 'icono2' => '🍗', 'descripcion' => 'Pollo en morita con frijoles', 'icono3' => '🥒', 'postre' => 'Pepinos con chamoy', 'icono4' => '🥒', 'bebida' => 'Agua de pepino']
        ],
        'martes' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🍎', 'descripcion' => 'Melón con manzana', 'icono3' => '🌶️', 'detalle' => 'Enmoladas'],
            'comida' => ['icono' => '🍝', 'nombre' => 'Fusilli poblano', 'icono2' => '🥩', 'descripcion' => 'Chuleta natural con papas al ajillo', 'icono3' => '🍑', 'postre' => 'Gelatina con fruta durazno', 'icono4' => '🍓', 'bebida' => 'Agua de fresa']
        ],
        'miercoles' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🍞', 'descripcion' => 'Pan tostado con mermelada', 'icono3' => '🌯', 'detalle' => 'Burrito de bistec'],
            'comida' => ['icono' => '🥣', 'nombre' => 'Sopa de verdura', 'icono2' => '🌮', 'descripcion' => 'Tostadas de picadillo 3 pz', 'icono3' => '🌽', 'postre' => 'Flan de elote', 'icono4' => '🌰', 'bebida' => 'Agua de tamarindo']
        ],
        'jueves' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🍍', 'descripcion' => 'Papaya con piña', 'icono3' => '🍖', 'detalle' => 'Torta de pierna ahumada'],
            'comida' => ['icono' => '🍚', 'nombre' => 'Arroz con salchicha', 'icono2' => '🥓', 'descripcion' => 'Calabazas con tocino y frijoles', 'icono3' => '🥕', 'postre' => 'Crudites de jícama', 'icono4' => '🍍', 'bebida' => 'Agua de frutas tropicales']
        ],
        'viernes' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🥛', 'descripcion' => 'Licuado de granola', 'icono3' => '🌮', 'detalle' => 'Sopes con longaniza'],
            'comida' => ['icono' => '🥣', 'nombre' => 'Crema de pimiento', 'icono2' => '🐟', 'descripcion' => 'Tostadas de atún a la vizcaína 3 pz', 'icono3' => '🍬', 'postre' => 'Alegrías', 'icono4' => '🥛', 'bebida' => 'Agua de avena']
        ]
    ],
    'abril_4' => [
        'lunes' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🍎', 'descripcion' => 'Manzana con yogurt', 'icono3' => '🍳', 'detalle' => 'Omelette de jamón'],
            'comida' => ['icono' => '🍝', 'nombre' => 'Espagueti carbonara', 'icono2' => '🍗', 'descripcion' => 'Pechuga asada con nopal y panela', 'icono3' => '🌶️', 'postre' => 'Fruta con chile', 'icono4' => '🥒', 'bebida' => 'Agua de limón con pepino']
        ],
        'martes' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🍍', 'descripcion' => 'Melón con piña', 'icono3' => '🌶️', 'detalle' => 'Chilaquiles de morita con huevo'],
            'comida' => ['icono' => '🥣', 'nombre' => 'Sopa marquesa', 'icono2' => '🥗', 'descripcion' => 'Lasagna vegetariana con ensalada', 'icono3' => '🍰', 'postre' => 'Pay de queso con zarzamora', 'icono4' => '🥛', 'bebida' => 'Agua de horchata']
        ],
        'miercoles' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🍉', 'descripcion' => 'Sandía', 'icono3' => '🥪', 'detalle' => 'Sandwich de pechuga asada'],
            'comida' => ['icono' => '🍚', 'nombre' => 'Arroz verde', 'icono2' => '🐷', 'descripcion' => 'Cerdo en salsa verde con frijoles', 'icono3' => '🍫', 'postre' => 'Mousse de chocolate', 'icono4' => '🌰', 'bebida' => 'Agua de tamarindo']
        ],
        'jueves' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🥞', 'descripcion' => 'Hot cake', 'icono3' => '🥒', 'detalle' => 'Calabacitas a la mexicana'],
            'comida' => ['icono' => '🍚', 'nombre' => 'Arroz blanco', 'icono2' => '🐟', 'descripcion' => 'Chile relleno de atún', 'icono3' => '🍬', 'postre' => 'Mazapán', 'icono4' => '🍌', 'bebida' => 'Agua de plátano']
        ],
        'viernes' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🥛', 'descripcion' => 'Licuado chocoplátano', 'icono3' => '🍅', 'detalle' => 'Entomatadas'],
            'comida' => ['icono' => '🥣', 'nombre' => 'Consome de res', 'icono2' => '🍖', 'descripcion' => 'Tortitas de carne en salsa roja con frijoles', 'icono3' => '🥭', 'postre' => 'Chamoyada de mango', 'icono4' => '🌾', 'bebida' => 'Agua de amaranto']
        ]
    ],
    'abril_5' => [
        'lunes' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🍞', 'descripcion' => 'Pan tostado con nutella', 'icono3' => '🥬', 'detalle' => 'Omelette de espinaca'],
            'comida' => ['icono' => '🍚', 'nombre' => 'Arroz con plátano', 'icono2' => '🌮', 'descripcion' => 'Tacos de bistec con papas y nopales', 'icono3' => '🍑', 'postre' => 'Duraznos con crema', 'icono4' => '🍊', 'bebida' => 'Agua de naranja']
        ],
        'martes' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🍌', 'descripcion' => 'Plátano con yogurt', 'icono3' => '🥑', 'detalle' => 'Avocado toast 2 pz'],
            'comida' => ['icono' => '🥣', 'nombre' => 'Sopa verde', 'icono2' => '🍗', 'descripcion' => 'Pechuga cordon bleu con puré de papa', 'icono3' => '🍦', 'postre' => 'Helado de fresa', 'icono4' => '🌺', 'bebida' => 'Agua de Jamaica']
        ],
        'miercoles' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🍈', 'descripcion' => 'Papaya', 'icono3' => '🌶️', 'detalle' => 'Chilaquiles rojos con pollo'],
            'comida' => ['icono' => '🥣', 'nombre' => 'Consome de pollo', 'icono2' => '🌮', 'descripcion' => 'Tacos dorados de frijol 4 pz', 'icono3' => '🍓', 'postre' => 'Coctel de frutas', 'icono4' => '🌿', 'bebida' => 'Agua de mojito']
        ],
        'jueves' => [
            'desayuno' => ['icono' => '☕', 'nombre' => 'Café o Té', 'icono2' => '🥛', 'descripcion' => 'Licuado de fresa', 'icono3' => '🍳', 'detalle' => 'Huevos divorciados'],
            'comida' => ['icono' => '🍝', 'nombre' => 'Codito hawaiano', 'icono2' => '🍔', 'descripcion' => 'Hamburguesa con papas a la francesa', 'icono3' => '🍰', 'postre' => 'Pastel 3 leches', 'icono4' => '🍍', 'bebida' => 'Agua de piña colada']
        ],
        'viernes' => [
            'desayuno' => ['icono' => '🚫', 'nombre' => 'Sin servicio', 'sin_servicio' => true, 'mensaje' => 'Sin servicio el día viernes'],
            'comida' => ['icono' => '🚫', 'nombre' => 'Sin servicio', 'sin_servicio' => true, 'mensaje' => 'Sin servicio el día viernes']
        ]
    ]
];

// ==================================================
// SELECCIONAR EL MENÚ CORRECTO
// ==================================================

$menu_a_mostrar = [];
$clave_menu = '';

if ($mes_seleccionado === 'marzo') {
    $clave_menu = "marzo_{$num_semana_seleccionada}";
    $menu_a_mostrar = $menus_completos[$clave_menu] ?? $menus_completos['marzo_4'];
} elseif ($mes_seleccionado === 'abril') {
    $clave_menu = "abril_{$num_semana_seleccionada}";
    $menu_a_mostrar = $menus_completos[$clave_menu] ?? $menus_completos['abril_1'];
} else {
    $menu_a_mostrar = $menus_completos['marzo_4'];
}

// ==================================================
// FUNCIÓN PARA MOSTRAR ÍTEMS DE COMIDA
// ==================================================

function mostrarItemComida($item, $tipo = 'main') {
    $html = '';
    
    if (isset($item['sin_servicio']) && $item['sin_servicio']) {
        $html .= '<div class="meal-item sin-servicio">';
        $html .= '<span class="item-icon">' . ($item['icono'] ?? '🚫') . '</span>';
        $html .= '<span>' . ($item['mensaje'] ?? 'Sin servicio') . '</span>';
        $html .= '</div>';
        return $html;
    }
    
    if ($tipo === 'main' && !empty($item['nombre'])) {
        $html .= '<div class="meal-item main">';
        $html .= '<span class="item-icon">' . ($item['icono'] ?? '🍽️') . '</span>';
        $html .= '<span>' . ($item['nombre'] ?? 'Menú del día') . '</span>';
        $html .= '</div>';
    }
    
    if (!empty($item['descripcion'])) {
        $html .= '<div class="meal-item detail">';
        $html .= '<span class="item-icon">' . ($item['icono2'] ?? '🍽️') . '</span>';
        $html .= '<span>' . $item['descripcion'] . '</span>';
        $html .= '</div>';
    }
    
    if (!empty($item['detalle'])) {
        $html .= '<div class="meal-item detail">';
        $html .= '<span class="item-icon">' . ($item['icono3'] ?? '🥗') . '</span>';
        $html .= '<span>' . $item['detalle'] . '</span>';
        $html .= '</div>';
    }
    
    if (!empty($item['postre'])) {
        $html .= '<div class="meal-item postre">';
        $html .= '<span class="item-icon">' . ($item['icono4'] ?? '🍰') . '</span>';
        $html .= '<span>' . $item['postre'] . '</span>';
        $html .= '</div>';
    }
    
    if (!empty($item['bebida'])) {
        $html .= '<div class="meal-item bebida">';
        $html .= '<span class="item-icon">' . ($item['icono5'] ?? '🥤') . '</span>';
        $html .= '<span>' . $item['bebida'] . '</span>';
        $html .= '</div>';
    }
    
    return $html;
}

// ==================================================
// OBTENER TÍTULOS PARA MOSTRAR
// ==================================================

$titulo_semana = 'Selecciona una semana';
$rango_fechas = '';
$mes_titulo = '';

if ($mes_seleccionado && $num_semana_seleccionada > 0) {
    $mes_titulo = ucfirst($mes_seleccionado);
    
    if ($mes_seleccionado === 'marzo' && $num_semana_seleccionada === 4) {
        $titulo_semana = 'Semana 4: 23-27 Marzo 2026';
        $rango_fechas = '23 al 27 de Marzo 2026';
    } elseif ($mes_seleccionado === 'abril') {
        $semanas_abril = [1 => '30 Mar-3 Abr', 2 => '6-10', 3 => '13-17', 4 => '20-24', 5 => '27-30'];
        $rango = $semanas_abril[$num_semana_seleccionada] ?? '6-10';
        $titulo_semana = "Semana {$num_semana_seleccionada}: {$rango} Abril 2026";
        if ($num_semana_seleccionada == 1) {
            $rango_fechas = "30 de Marzo al 3 de Abril 2026";
        } elseif ($num_semana_seleccionada == 5) {
            $rango_fechas = "27 al 30 de Abril 2026";
        } else {
            $rango_fechas = str_replace('-', ' al ', $rango) . " de Abril 2026";
        }
    }
}

$fecha_actual = date('d/m/Y H:i');
$hoy_objeto = new DateTime();
$hoy_objeto->setTime(0, 0, 0);
$semana_seleccionada_objeto = new DateTime($fecha_inicio);
$diferencia_dias = $hoy_objeto->diff($semana_seleccionada_objeto)->days;

$esSemanaFutura = $semana_seleccionada_objeto > $hoy_objeto;
$week_class = '';
if ($mes_seleccionado === 'marzo') $week_class = "marzo-{$num_semana_seleccionada}";
elseif ($mes_seleccionado === 'abril') $week_class = "abril-{$num_semana_seleccionada}";

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes" />
  <meta name="theme-color" content="#0a1a2f" />
  <title>Sistema de Pedidos - Comedor Marzo/Abril 2026</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --navy-deep: #0a1a2f;
      --navy-marine: #1a2f4b;
      --navy-medium: #2a4a6b;
      --navy-light: #3a6590;
      --navy-accent: #4a80b5;
      --pearl-white: #f8fafc;
      --pearl-light: #f0f4f8;
      --glass-bg: rgba(255, 255, 255, 0.08);
      --glass-border: rgba(255, 255, 255, 0.12);
      --glass-shadow: 0 8px 32px rgba(10, 26, 47, 0.3);
      --success: #10b981;
      --error: #ef4444;
      --warning: #f59e0b;
      --info: #3b82f6;
      --desayuno-color: #f59e0b;
      --comida-color: #10b981;
      --sin-servicio-color: #94a3b8;
      --marzo-4: #ec4899;
      --abril-1: #06b6d4;
      --abril-2: #84cc16;
      --abril-3: #f97316;
      --abril-4: #d946ef;
      --abril-5: #ef4444;
      --gold-gradient: linear-gradient(135deg, #FFD700 0%, #FFC700 25%, #FFAA00 50%, #FF8C00 75%, #FF6B00 100%);
      --silver-gradient: linear-gradient(135deg, #C0C0C0 0%, #D3D3D3 25%, #E8E8E8 50%, #F0F0F0 75%, #F8F8F8 100%);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, var(--navy-deep) 0%, var(--navy-marine) 50%, var(--navy-medium) 100%);
      color: var(--pearl-white);
      min-height: 100vh;
      padding: 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    html { scroll-behavior: smooth; }

    .header-container {
      width: 100%;
      max-width: 1400px;
      margin-bottom: 25px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 20px;
    }

    .logo-container { flex-shrink: 0; }
    .logo-container .logo { height: 55px; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3)); transition: transform 0.3s ease; }
    .logo-container .logo:hover { transform: scale(1.05); }

    .user-info-bar {
      background: var(--glass-bg);
      backdrop-filter: blur(20px);
      border: 1px solid var(--glass-border);
      padding: 15px 25px;
      border-radius: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 20px;
      box-shadow: var(--glass-shadow);
      flex: 1;
    }

    .user-details { display: flex; gap: 30px; flex-wrap: wrap; }
    .user-detail { display: flex; align-items: center; gap: 12px; font-size: 0.95rem; }
    .user-detail i { font-size: 1.2rem; opacity: 0.9; color: var(--navy-accent); }
    .session-info { display: flex; align-items: center; gap: 10px; font-size: 0.85rem; opacity: 0.8; }

    .container { width: 100%; max-width: 1400px; margin: 0 auto; }

    .credentials-panel {
      background: var(--glass-bg);
      backdrop-filter: blur(20px);
      border: 1px solid var(--glass-border);
      border-radius: 20px;
      padding: 25px;
      margin-bottom: 25px;
      width: 100%;
      box-shadow: var(--glass-shadow);
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 30px;
      align-items: start;
    }

    .credentials-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }

    .week-selector-container {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid var(--glass-border);
      border-radius: 16px;
      padding: 20px;
      min-width: 280px;
      backdrop-filter: blur(10px);
    }

    .week-selector-container h3 { margin-bottom: 15px; font-size: 1.1rem; font-weight: 600; color: var(--navy-accent); display: flex; align-items: center; gap: 10px; }
    .week-selector-container select { width: 100%; padding: 12px 15px; border: none; border-radius: 12px; background: rgba(255, 255, 255, 0.15); color: var(--pearl-white); font-family: 'Inter', sans-serif; font-size: 0.95rem; backdrop-filter: blur(10px); border: 1px solid var(--glass-border); transition: all 0.3s ease; cursor: pointer; }
    .week-selector-container select:focus { outline: none; border-color: var(--navy-accent); background: rgba(255, 255, 255, 0.2); }
    .week-selector-container select option { background: var(--navy-medium); color: var(--pearl-white); padding: 10px; }

    .credential-field { display: flex; flex-direction: column; gap: 8px; }
    .credential-field label { font-weight: 600; font-size: 0.9rem; color: var(--navy-accent); display: flex; align-items: center; gap: 8px; }
    .credential-field input { background: rgba(255, 255, 255, 0.1); border: 1px solid var(--glass-border); border-radius: 12px; padding: 12px 15px; color: var(--pearl-white); font-size: 1rem; backdrop-filter: blur(10px); }
    .credential-field input.readonly { background: rgba(255, 255, 255, 0.05); color: #94a3b8; cursor: not-allowed; }
    .field-note { font-size: 0.75rem; color: #94a3b8; margin-top: 6px; display: block; font-style: italic; }

    form {
      width: 100%;
      background: var(--glass-bg);
      backdrop-filter: blur(20px);
      border: 1px solid var(--glass-border);
      border-radius: 24px;
      padding: 35px;
      box-shadow: var(--glass-shadow);
      transition: transform 0.3s ease;
    }
    form:hover { transform: translateY(-3px); }

    .week-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin-bottom: 40px; }

    .day-card {
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid var(--glass-border);
      border-radius: 20px;
      padding: 20px;
      transition: all 0.3s ease;
      backdrop-filter: blur(10px);
    }
    .day-card:hover { background: rgba(255, 255, 255, 0.12); transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    .day-card h3 { margin-bottom: 18px; font-size: 1.3rem; font-weight: 700; text-align: center; padding-bottom: 12px; border-bottom: 2px solid var(--glass-border); }

    .meal-option {
      display: flex;
      align-items: flex-start;
      gap: 15px;
      padding: 16px;
      border-radius: 16px;
      margin: 12px 0;
      cursor: pointer;
      transition: all 0.3s ease;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid transparent;
    }
    .meal-option:hover { background: rgba(255, 255, 255, 0.1); border-color: var(--glass-border); transform: translateX(5px); }
    .meal-option input { width: auto; margin: 4px 0 0 0; accent-color: var(--navy-accent); transform: scale(1.2); flex-shrink: 0; cursor: pointer; }
    .meal-option.selected { background: rgba(74, 128, 181, 0.25); border-color: var(--navy-accent); box-shadow: 0 4px 15px rgba(74, 128, 181, 0.2); }
    .meal-option.sin-servicio { cursor: not-allowed; opacity: 0.7; background: rgba(148, 163, 184, 0.1); }
    .meal-option.sin-servicio:hover { transform: none; }
    .meal-option.sin-servicio input { display: none; }

    .meal-details { flex: 1; }
    .meal-type { font-weight: 700; font-size: 1rem; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
    .meal-type.desayuno { color: var(--desayuno-color); }
    .meal-type.comida { color: var(--comida-color); }
    .meal-items { display: flex; flex-direction: column; gap: 6px; }
    .meal-item { display: flex; align-items: flex-start; gap: 10px; font-size: 0.85rem; line-height: 1.4; }
    .meal-item.main { font-weight: 600; font-size: 0.9rem; }
    .meal-item.detail { font-size: 0.8rem; opacity: 0.9; margin-left: 5px; }
    .meal-item.sin-servicio { color: var(--sin-servicio-color); font-style: italic; }
    .meal-item.postre { font-size: 0.8rem; opacity: 0.8; font-style: italic; margin-top: 5px; color: #d4a5cb; }
    .meal-item.bebida { font-size: 0.8rem; opacity: 0.8; font-style: italic; margin-top: 3px; color: #a5b4cb; }
    .item-icon { font-size: 0.9rem; min-width: 24px; text-align: center; }

    .submit-button-container { width: 100%; display: flex; justify-content: center; margin-top: 30px; }
    #submitBtn {
      background: var(--gold-gradient);
      color: #000;
      border: none;
      border-radius: 60px;
      padding: 18px 55px;
      font-size: 1.2rem;
      font-weight: 800;
      font-family: 'Inter', sans-serif;
      cursor: pointer;
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 15px;
      position: relative;
      overflow: hidden;
      z-index: 1;
      box-shadow: 0 10px 30px rgba(255, 215, 0, 0.4);
      text-transform: uppercase;
      letter-spacing: 1.5px;
      min-width: 300px;
    }
    #submitBtn::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 50%, rgba(255,255,255,0.2) 100%);
      border-radius: 60px;
      z-index: -1;
      opacity: 0;
      transition: opacity 0.4s ease;
    }
    #submitBtn:hover { transform: translateY(-5px) scale(1.02); box-shadow: 0 15px 40px rgba(255, 215, 0, 0.6); letter-spacing: 2px; }
    #submitBtn:hover::before { opacity: 1; }
    #submitBtn:active { transform: translateY(-2px) scale(0.98); }
    #submitBtn:disabled { background: var(--silver-gradient); transform: none; cursor: not-allowed; opacity: 0.7; }
    .button-shine {
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.3) 50%, transparent 70%);
      transform: rotate(30deg);
      animation: shine 4s infinite linear;
      pointer-events: none;
    }
    @keyframes shine {
      0% { transform: translateX(-100%) translateY(-100%) rotate(30deg); }
      100% { transform: translateX(100%) translateY(100%) rotate(30deg); }
    }

    .notification { padding: 15px 22px; border-radius: 16px; margin-bottom: 25px; display: flex; align-items: center; gap: 15px; font-weight: 500; backdrop-filter: blur(20px); border: 1px solid; width: 100%; }
    .notification.success { background: rgba(16, 185, 129, 0.15); color: #10b981; border-color: rgba(16, 185, 129, 0.3); }
    .notification.error { background: rgba(239, 68, 68, 0.15); color: #ef4444; border-color: rgba(239, 68, 68, 0.3); }
    .notification i { font-size: 1.3rem; }

    .week-indicator { background: rgba(255, 255, 255, 0.1); border: 1px solid var(--glass-border); border-radius: 16px; padding: 12px 25px; margin-bottom: 25px; width: 100%; backdrop-filter: blur(10px); text-align: center; font-size: 1rem; font-weight: 600; }
    .week-indicator.marzo-4 { color: var(--marzo-4); border-color: rgba(236, 72, 153, 0.3); background: rgba(236, 72, 153, 0.1); }
    .week-indicator.abril-1 { color: var(--abril-1); border-color: rgba(6, 182, 212, 0.3); background: rgba(6, 182, 212, 0.1); }
    .week-indicator.abril-2 { color: var(--abril-2); border-color: rgba(132, 204, 22, 0.3); background: rgba(132, 204, 22, 0.1); }
    .week-indicator.abril-3 { color: var(--abril-3); border-color: rgba(249, 115, 22, 0.3); background: rgba(249, 115, 22, 0.1); }
    .week-indicator.abril-4 { color: var(--abril-4); border-color: rgba(217, 70, 239, 0.3); background: rgba(217, 70, 239, 0.1); }
    .week-indicator.abril-5 { color: var(--abril-5); border-color: rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.1); }

    .menu-title { background: rgba(255, 255, 255, 0.1); border: 1px solid var(--glass-border); border-radius: 16px; padding: 12px 25px; margin-bottom: 25px; width: 100%; backdrop-filter: blur(10px); text-align: center; font-size: 1.1rem; font-weight: 700; color: var(--navy-accent); }
    .menu-title i { margin-right: 10px; }

    .semana-info { background: rgba(255, 255, 255, 0.1); border: 1px solid var(--glass-border); border-radius: 16px; padding: 12px 25px; margin-bottom: 25px; width: 100%; backdrop-filter: blur(10px); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
    .info-item { display: flex; flex-direction: column; align-items: center; gap: 5px; }
    .info-label { font-size: 0.7rem; opacity: 0.7; text-transform: uppercase; letter-spacing: 1px; }
    .info-value { font-size: 0.95rem; font-weight: 600; }
    .info-value.warning { color: var(--warning); }
    .info-value.success { color: var(--success); }

    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.7);
      backdrop-filter: blur(8px);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1000;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }
    .modal-overlay.active { opacity: 1; visibility: visible; }
    .modal-content {
      background: linear-gradient(135deg, var(--navy-marine) 0%, var(--navy-deep) 100%);
      border-radius: 28px;
      padding: 35px;
      max-width: 450px;
      width: 90%;
      text-align: center;
      border: 1px solid var(--glass-border);
      box-shadow: 0 25px 50px rgba(0,0,0,0.5);
      transform: scale(0.9);
      transition: transform 0.3s ease;
    }
    .modal-overlay.active .modal-content { transform: scale(1); }
    .modal-icon { font-size: 4rem; margin-bottom: 20px; }
    .modal-icon.success { color: var(--success); }
    .modal-icon.error { color: var(--error); }
    .modal-icon.loading { color: var(--navy-accent); animation: spin 1s linear infinite; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .modal-title { font-size: 1.6rem; font-weight: 800; margin-bottom: 15px; }
    .modal-message { font-size: 1rem; opacity: 0.9; margin-bottom: 25px; line-height: 1.5; }
    .modal-button { background: var(--gold-gradient); color: #000; border: none; border-radius: 40px; padding: 12px 30px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: all 0.3s ease; }
    .modal-button:hover { transform: scale(1.05); box-shadow: 0 5px 20px rgba(255, 215, 0, 0.4); }

    @media (max-width: 768px) {
      body { padding: 15px; }
      .header-container { flex-direction: column; align-items: center; }
      .logo-container { text-align: center; }
      .user-info-bar { width: 100%; flex-direction: column; text-align: center; padding: 12px 20px; }
      .user-details { justify-content: center; }
      .credentials-panel { grid-template-columns: 1fr; gap: 20px; padding: 20px; }
      .week-selector-container { min-width: auto; }
      form { padding: 20px; }
      .week-grid { grid-template-columns: 1fr; gap: 18px; }
      .day-card { padding: 16px; }
      .day-card h3 { font-size: 1.2rem; }
      .meal-option { padding: 12px; }
      #submitBtn { min-width: 260px; padding: 14px 35px; font-size: 1rem; }
      .semana-info { flex-direction: column; text-align: center; }
      .info-item { flex-direction: row; justify-content: space-between; width: 100%; }
      .modal-content { padding: 25px; }
      .modal-title { font-size: 1.3rem; }
    }
    @media (max-width: 480px) {
      .user-detail { font-size: 0.8rem; }
      .meal-type { font-size: 0.9rem; }
      .meal-item { font-size: 0.75rem; }
      #submitBtn { min-width: 220px; padding: 12px 25px; font-size: 0.9rem; }
    }
    .day-card { animation: fadeInUp 0.4s ease forwards; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>
<body>

<div class="container">
  <div class="header-container">
    <div class="logo-container">
      <img src="Logo2.png" alt="Logo" class="logo">
    </div>
    <div class="user-info-bar">
      <div class="user-details">
        <div class="user-detail">
          <i class="fas fa-user-circle"></i>
          <span><strong><?php echo htmlspecialchars($user_name); ?></strong></span>
        </div>
        <div class="user-detail">
          <i class="fas fa-building"></i>
          <span><?php echo htmlspecialchars($user_area); ?></span>
        </div>
      </div>
      <div class="session-info">
        <i class="fas fa-clock"></i>
        <span><?php echo $fecha_actual; ?></span>
      </div>
    </div>
  </div>

  <div class="menu-title">
    <i class="fas fa-utensils"></i>
    <?php if ($mes_titulo && $num_semana_seleccionada > 0): ?>
      Sistema de Pedidos - Menú <?php echo $mes_titulo; ?> 2026
    <?php else: ?>
      Sistema de Pedidos - Comedor Marzo/Abril 2026
    <?php endif; ?>
  </div>

  <div class="semana-info">
    <div class="info-item">
      <span class="info-label">Fecha actual</span>
      <span class="info-value"><?php echo date('d/m/Y'); ?></span>
    </div>
    <div class="info-item">
      <span class="info-label">Semana seleccionada</span>
      <span class="info-value"><?php echo $titulo_semana; ?></span>
    </div>
    <div class="info-item">
      <span class="info-label">Período</span>
      <span class="info-value"><?php echo $rango_fechas ?: 'Selecciona una semana'; ?></span>
    </div>
    <div class="info-item">
      <span class="info-label">Inicio en</span>
      <span class="info-value <?php echo $diferencia_dias <= 5 ? 'warning' : 'success'; ?>">
        <?php echo $diferencia_dias >= 0 ? $diferencia_dias . ' días' : 'Semana en curso'; ?>
      </span>
    </div>
  </div>

  <?php if ($mes_seleccionado && $num_semana_seleccionada > 0): ?>
  <div class="week-indicator <?php echo $week_class; ?>">
    <i class="fas fa-calendar-week"></i>
    Semana del <?php echo $rango_fechas; ?>
    <?php if (!$esSemanaFutura): ?>
      <br><small><i class="fas fa-exclamation-triangle"></i> ⚠️ No se pueden hacer pedidos para esta semana (solo semanas futuras)</small>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if (!empty($notification['message']) && !$pedido_exitoso): ?>
  <div class="notification <?php echo $notification['type']; ?>">
    <i class="fas fa-<?php echo $notification['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
    <span><?php echo htmlspecialchars($notification['message']); ?></span>
  </div>
  <?php endif; ?>

  <div class="credentials-panel">
    <div class="credentials-grid">
      <div class="credential-field">
        <label><i class="fas fa-id-card"></i> ID Empleado</label>
        <input type="text" value="<?php echo htmlspecialchars($id_empleado); ?>" readonly class="readonly">
        <span class="field-note">Precargado del sistema</span>
      </div>
      <div class="credential-field">
        <label><i class="fas fa-user"></i> Usuario</label>
        <input type="text" value="<?php echo htmlspecialchars($usuario_bd); ?>" readonly class="readonly">
        <span class="field-note">Precargado del sistema</span>
      </div>
      <div class="credential-field">
        <label><i class="fas fa-lock"></i> Contraseña</label>
        <input type="password" value="<?php echo htmlspecialchars($contrasena_bd); ?>" readonly class="readonly">
        <span class="field-note">Precargada del sistema</span>
      </div>
    </div>
    
    <div class="week-selector-container">
      <h3><i class="far fa-calendar-alt"></i> Semana de Pedido</h3>
      <select name="Fecha2" id="Fecha2" required>
        <option value="">Selecciona la semana</option>
        <?php 
        foreach ($semanasDisponibles as $semana): 
          $selected = ($semana['fecha'] == $semana_seleccionada) ? 'selected' : '';
          $indicador = '';
          if ($semana['fecha'] == $semanaPorDefecto['fecha']) {
            $indicador = ' ⭐ (Próxima disponible)';
          }
        ?>
          <option value="<?php echo $semana['fecha']; ?>" <?php echo $selected; ?>>
            <?php echo $semana['mostrar']; ?><?php echo $indicador; ?>
          </option>
        <?php endforeach; ?>
      </select>
      <span class="field-note">⚠️ Solo se pueden pedir semanas FUTURAS (después de hoy)</span>
    </div>
  </div>

  <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?user_name=<?php echo urlencode($user_name); ?>&user_area=<?php echo urlencode($user_area); ?>" id="menuForm">

    <input type="hidden" name="Nempleado" value="<?php echo htmlspecialchars($id_empleado); ?>">
    <input type="hidden" name="Usuar" value="<?php echo htmlspecialchars($usuario_bd); ?>">
    <input type="hidden" name="contrase" value="<?php echo htmlspecialchars($contrasena_bd); ?>">
    <input type="hidden" name="Fecha2" id="Fecha2Hidden" value="<?php echo htmlspecialchars($semana_seleccionada); ?>">

    <div class="week-grid">
      <?php
      $dias = [
          'lunes' => ['Lunes', 1, 2],
          'martes' => ['Martes', 3, 4],
          'miercoles' => ['Miércoles', 5, 6],
          'jueves' => ['Jueves', 7, 8],
          'viernes' => ['Viernes', 9, 10]
      ];
      
      foreach ($dias as $clave => $info):
          $dia_nombre = $info[0];
          $desayuno_id = $info[1];
          $comida_id = $info[2];
          $menu_dia = $menu_a_mostrar[$clave] ?? [];
          
          $desayuno_sin_servicio = isset($menu_dia['desayuno']['sin_servicio']) && $menu_dia['desayuno']['sin_servicio'];
          $comida_sin_servicio = isset($menu_dia['comida']['sin_servicio']) && $menu_dia['comida']['sin_servicio'];
      ?>
      <div class='day-card'>
          <h3><?php echo $dia_nombre; ?></h3>
          
          <label class='meal-option <?php echo $desayuno_sin_servicio ? 'sin-servicio' : ''; ?>'>
              <?php if (!$desayuno_sin_servicio): ?>
              <input type='radio' name='gender<?php echo $desayuno_id; ?>' value='Desayuno' class='toggle-radio'>
              <?php endif; ?>
              <div class='meal-details'>
                  <div class='meal-type desayuno'>
                      <i class="fas fa-egg"></i> Desayuno
                  </div>
                  <div class='meal-items'>
                      <?php echo mostrarItemComida($menu_dia['desayuno'] ?? []); ?>
                  </div>
              </div>
          </label>
          
          <label class='meal-option <?php echo $comida_sin_servicio ? 'sin-servicio' : ''; ?>'>
              <?php if (!$comida_sin_servicio): ?>
              <input type='radio' name='gender<?php echo $comida_id; ?>' value='Comida' class='toggle-radio'>
              <?php endif; ?>
              <div class='meal-details'>
                  <div class='meal-type comida'>
                      <i class="fas fa-utensils"></i> Comida
                  </div>
                  <div class='meal-items'>
                      <?php echo mostrarItemComida($menu_dia['comida'] ?? []); ?>
                  </div>
              </div>
          </label>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="submit-button-container">
      <button type="submit" id="submitBtn">
        <i class="fas fa-check-circle"></i> Confirmar Pedido
        <div class="button-shine"></div>
      </button>
    </div>
  </form>
</div>

<div id="modalOverlay" class="modal-overlay">
  <div class="modal-content">
    <div id="modalIcon" class="modal-icon"></div>
    <h2 id="modalTitle" class="modal-title"></h2>
    <p id="modalMessage" class="modal-message"></p>
    <button id="modalButton" class="modal-button">Aceptar</button>
  </div>
</div>

<script>
  let modalCallback = null;
  
  function showModal(type, title, message, callback = null) {
    const modal = document.getElementById('modalOverlay');
    const iconEl = document.getElementById('modalIcon');
    const titleEl = document.getElementById('modalTitle');
    const messageEl = document.getElementById('modalMessage');
    
    modalCallback = callback;
    
    iconEl.className = 'modal-icon';
    if (type === 'success') {
      iconEl.classList.add('success');
      iconEl.innerHTML = '<i class="fas fa-check-circle"></i>';
    } else if (type === 'error') {
      iconEl.classList.add('error');
      iconEl.innerHTML = '<i class="fas fa-times-circle"></i>';
    } else if (type === 'loading') {
      iconEl.classList.add('loading');
      iconEl.innerHTML = '<i class="fas fa-spinner"></i>';
    } else if (type === 'info') {
      iconEl.innerHTML = '<i class="fas fa-info-circle"></i>';
    }
    
    titleEl.textContent = title;
    messageEl.textContent = message;
    modal.classList.add('active');
  }
  
  function closeModal() {
    const modal = document.getElementById('modalOverlay');
    modal.classList.remove('active');
    if (modalCallback) {
      modalCallback();
      modalCallback = null;
    }
  }
  
  // ========== SOLUCIÓN: PERMITIR DESELECCIONAR RADIO BUTTONS ==========
  // La solución es usar checkboxes en lugar de radios, pero mantener comportamiento de radio
  document.querySelectorAll('.toggle-radio').forEach(radio => {
    // Convertir radio a checkbox
    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.className = 'toggle-checkbox';
    checkbox.value = radio.value;
    checkbox.name = radio.name;
    checkbox.style.margin = '4px 0 0 0';
    checkbox.style.accentColor = '#4a80b5';
    checkbox.style.transform = 'scale(1.2)';
    checkbox.style.cursor = 'pointer';
    checkbox.style.flexShrink = '0';
    
    // Si el radio estaba seleccionado, marcar el checkbox
    if (radio.checked) {
      checkbox.checked = true;
      const parentOption = radio.closest('.meal-option');
      if (parentOption) parentOption.classList.add('selected');
    }
    
    // Reemplazar radio por checkbox
    radio.parentNode.replaceChild(checkbox, radio);
    
    // Agregar evento al checkbox
    checkbox.addEventListener('change', function() {
      const groupName = this.name;
      const allCheckboxes = document.querySelectorAll(`.toggle-checkbox[name="${groupName}"]`);
      const parentOption = this.closest('.meal-option');
      
      if (this.checked) {
        // Desmarcar todos los otros del mismo grupo
        allCheckboxes.forEach(cb => {
          if (cb !== this) {
            cb.checked = false;
            const otherParent = cb.closest('.meal-option');
            if (otherParent) otherParent.classList.remove('selected');
          }
        });
        if (parentOption) parentOption.classList.add('selected');
      } else {
        if (parentOption) parentOption.classList.remove('selected');
      }
    });
  });
  
  // Eventos para clic en toda la tarjeta
  document.querySelectorAll('.meal-option:not(.sin-servicio)').forEach(option => {
    option.addEventListener('click', function(e) {
      if (e.target.type === 'checkbox') return;
      
      const checkbox = this.querySelector('.toggle-checkbox');
      if (!checkbox) return;
      
      const groupName = checkbox.name;
      const allCheckboxes = document.querySelectorAll(`.toggle-checkbox[name="${groupName}"]`);
      
      if (checkbox.checked) {
        checkbox.checked = false;
        this.classList.remove('selected');
      } else {
        allCheckboxes.forEach(cb => {
          if (cb !== checkbox) {
            cb.checked = false;
            const otherParent = cb.closest('.meal-option');
            if (otherParent) otherParent.classList.remove('selected');
          }
        });
        checkbox.checked = true;
        this.classList.add('selected');
      }
      
      // Disparar evento change manualmente
      const changeEvent = new Event('change', { bubbles: true });
      checkbox.dispatchEvent(changeEvent);
    });
  });
  // ========== FIN DE LA SOLUCIÓN ==========
  
  const fechaSelect = document.getElementById('Fecha2');
  const fechaHidden = document.getElementById('Fecha2Hidden');
  
  if (fechaSelect) {
    fechaSelect.addEventListener('change', function() {
      if (this.value) {
        const url = new URL(window.location.href);
        url.searchParams.set('semana', this.value);
        window.location.href = url.toString();
      }
    });
  }
  
  document.getElementById('menuForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const selectedDays = document.querySelectorAll('.toggle-checkbox:checked');
    const selectedWeek = fechaSelect?.value;
    
    if (selectedDays.length === 0) {
      showModal('error', 'Selección requerida', 'Por favor, selecciona al menos una opción de menú (desayuno o comida) para algún día de la semana.');
      return false;
    }
    
    if (!selectedWeek) {
      showModal('error', 'Semana requerida', 'Por favor, selecciona una semana para el pedido.');
      return false;
    }
    
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const fechaSemana = new Date(selectedWeek);
    
    if (fechaSemana <= hoy) {
      showModal('error', 'Semana no válida', '❌ No se pueden realizar pedidos para la semana actual o semanas pasadas. Solo puedes pedir para semanas futuras.');
      return false;
    }
    
    if (fechaHidden) fechaHidden.value = selectedWeek;
    
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
      showModal('loading', 'Procesando', 'Guardando tu pedido, por favor espera...');
      submitBtn.disabled = true;
      
      setTimeout(() => {
        this.submit();
      }, 500);
    }
    
    return true;
  });
  
  document.addEventListener('DOMContentLoaded', function() {
    const warningValue = document.querySelector('.info-value.warning');
    if (warningValue) {
      const days = parseInt(warningValue.textContent);
      if (days <= 5 && days > 0) {
        setTimeout(() => {
          showModal('info', '¡Atención!', `La semana seleccionada comienza en ${days} días. ¡No olvides hacer tu pedido a tiempo!`);
        }, 1500);
      }
    }
    
    const opciones = fechaSelect?.querySelectorAll('option');
    opciones?.forEach(opcion => {
      if (opcion.textContent.includes('⭐')) {
        opcion.style.fontWeight = 'bold';
        opcion.style.backgroundColor = 'rgba(255, 215, 0, 0.2)';
      }
    });
    
    <?php if ($pedido_exitoso): ?>
    showModal('success', '¡Pedido Confirmado!', '<?php echo addslashes($notification['message']); ?>', function() {
      window.location.href = window.location.pathname + '?user_name=<?php echo urlencode($user_name); ?>&user_area=<?php echo urlencode($user_area); ?>';
    });
    <?php endif; ?>
    
    <?php if (!empty($notification['message']) && !$pedido_exitoso && $notification['type'] === 'error'): ?>
    showModal('error', 'Error', '<?php echo addslashes($notification['message']); ?>');
    <?php endif; ?>
  });
  
  document.getElementById('modalButton')?.addEventListener('click', function() {
    closeModal();
  });
</script>

</body>
</html>