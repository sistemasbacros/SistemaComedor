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
// DEFINICIÓN DE SEMANAS DISPONIBLES
// ==================================================

$semanasDisponibles = [
    // Última semana de Enero 2026 (Semana 4)
    [
        'fecha' => '2026-01-26',
        'mostrar' => '26/01/2026 - Semana 4 Ene (26-30 Ene)',
        'num_semana' => 4,
        'mes' => 'enero',
        'fecha_inicio' => '2026-01-26',
        'fecha_fin' => '2026-01-30'
    ],
    // Semanas de Febrero 2026
    [
        'fecha' => '2026-02-02',
        'mostrar' => '02/02/2026 - Semana 1 Feb (3-6 Feb)',
        'num_semana' => 1,
        'mes' => 'febrero',
        'fecha_inicio' => '2026-02-03',
        'fecha_fin' => '2026-02-06'
    ],
    [
        'fecha' => '2026-02-09',
        'mostrar' => '09/02/2026 - Semana 2 Feb (9-13 Feb)',
        'num_semana' => 2,
        'mes' => 'febrero',
        'fecha_inicio' => '2026-02-09',
        'fecha_fin' => '2026-02-13'
    ],
    [
        'fecha' => '2026-02-16',
        'mostrar' => '16/02/2026 - Semana 3 Feb (16-20 Feb)',
        'num_semana' => 3,
        'mes' => 'febrero',
        'fecha_inicio' => '2026-02-16',
        'fecha_fin' => '2026-02-20'
    ],
    [
        'fecha' => '2026-02-23',
        'mostrar' => '23/02/2026 - Semana 4 Feb (23-27 Feb)',
        'num_semana' => 4,
        'mes' => 'febrero',
        'fecha_inicio' => '2026-02-23',
        'fecha_fin' => '2026-02-27'
    ]
];

// ==================================================
// FUNCIÓN PARA OBTENER LA SIGUIENTE SEMANA DESDE HOY
// ==================================================

function obtenerSiguienteSemana($semanasDisponibles) {
    $hoy = new DateTime();
    
    // Encontrar la próxima semana disponible desde hoy
    foreach ($semanasDisponibles as $semana) {
        $fechaInicio = new DateTime($semana['fecha_inicio']);
        $fechaFin = new DateTime($semana['fecha_fin']);
        
        // Si estamos ANTES de que comience la semana, seleccionarla
        if ($hoy < $fechaInicio) {
            return $semana;
        }
        
        // Si estamos DURANTE la semana, seleccionar la siguiente
        if ($hoy >= $fechaInicio && $hoy <= $fechaFin) {
            // Buscar la próxima semana disponible
            $indiceActual = array_search($semana, $semanasDisponibles);
            if (isset($semanasDisponibles[$indiceActual + 1])) {
                return $semanasDisponibles[$indiceActual + 1];
            }
        }
    }
    
    // Si no hay próximas semanas, devolver la última disponible
    return end($semanasDisponibles);
}

// Obtener la siguiente semana por defecto
$semanaPorDefecto = obtenerSiguienteSemana($semanasDisponibles);

// ==================================================
// OBTENER PARÁMETROS DEL USUARIO Y DATOS DE BD
// ==================================================

// Obtener parámetros del usuario
$user_name = $_GET['user_name'] ?? $_SESSION['user_name'] ?? '';
$user_area = $_GET['user_area'] ?? $_SESSION['user_area'] ?? '';

if (!empty($user_name)) {
    $user_name = urldecode($user_name);
    $user_area = urldecode($user_area);
    $_SESSION['user_name'] = $user_name;
    $_SESSION['user_area'] = $user_area;
}

// Conectar a la base de datos
require_once __DIR__ . '/config/database.php';
$conn = getComedorConnection();

// Variables para los datos del usuario
$id_empleado = '';
$usuario_bd = '';
$contrasena_bd = '';

// Ejecutar query para obtener datos del usuario
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger datos del formulario
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

    // Validaciones básicas
    if (empty($numemp) || empty($usua) || empty($cont) || empty($fecha)) {
        $notification = ['type' => 'error', 'message' => 'Todos los campos son obligatorios.'];
    } elseif (!$conn) {
        $notification = ['type' => 'error', 'message' => 'Error de conexión con la base de datos.'];
    } else {
        // Verificar credenciales
        $sql2 = "SELECT Usuario, Contrasena FROM ConPed WHERE Usuario = ? AND Contrasena = ?";
        $stmt2 = sqlsrv_query($conn, $sql2, [$usua, $cont]);
        $credencial_valida = ($stmt2 && sqlsrv_has_rows($stmt2));

        // Verificar pedidos existentes
        $sql3 = "SELECT COUNT(*) AS Total FROM PedidosComida WHERE Fecha = ? AND Usuario = ?";
        $stmt3 = sqlsrv_query($conn, $sql3, [$fecha, $usua]);
        $row = $stmt3 ? sqlsrv_fetch_array($stmt3, SQLSRV_FETCH_ASSOC) : null;
        $valor1 = $row['Total'] ?? 0;

        if (!$credencial_valida) {
            $notification = ['type' => 'error', 'message' => 'Usuario o contraseña incorrectos.'];
        } elseif ($valor1 >= 2) {
            $notification = ['type' => 'error', 'message' => 'Ya tienes un pedido registrado para esta fecha.'];
        } else {
            // Insertar pedidos
            $sql = "INSERT INTO PedidosComida (Id_Empleado, Nom_Pedido, Usuario, Contrasena, Fecha, Lunes, Martes, Miercoles, Jueves, Viernes, Costo) 
                    VALUES (?, '', ?, ?, ?, ?, ?, ?, ?, ?, 30)";
            
            $params1 = [$numemp, $usua, $cont, $fecha, $lunesd, $martesd, $miercolesd, $juevesd, $viernesd];
            $params2 = [$numemp, $usua, $cont, $fecha, $lunesc, $martesc, $miercolesc, $juevesc, $viernesc];

            $stmt = sqlsrv_query($conn, $sql, $params1);
            $stmt1 = sqlsrv_query($conn, $sql, $params2);

            if ($stmt && $stmt1) {
                $notification = ['type' => 'success', 'message' => '¡Tu pedido se registró con éxito!'];
                echo '<script>setTimeout(() => document.getElementById("menuForm").reset(), 1000);</script>';
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

// Obtener la semana seleccionada del POST, GET o usar por defecto
$semana_seleccionada = $_POST['Fecha2'] ?? $_GET['semana'] ?? $semanaPorDefecto['fecha'];
$num_semana_seleccionada = 0;
$mes_seleccionado = '';

// Buscar la semana seleccionada
foreach ($semanasDisponibles as $semana) {
    if ($semana['fecha'] === $semana_seleccionada) {
        $num_semana_seleccionada = $semana['num_semana'];
        $mes_seleccionado = $semana['mes'];
        $fecha_inicio = $semana['fecha_inicio'];
        $fecha_fin = $semana['fecha_fin'];
        break;
    }
}

// ==================================================
// MENÚS COMPLETOS CORREGIDOS (SIN ICONOS DUPLICADOS)
// ==================================================

$menus_completos = [
    // ÚLTIMA SEMANA DE ENERO 2026 (Semana 4: 26-30 Enero)
    'enero_4' => [ 
        'lunes' => [
            'desayuno' => [
                'icono' => '☕',
                'nombre' => 'Café o Té',
                'icono2' => '🍌',
                'descripcion' => 'Plátano con yogurt',
                'icono3' => '🌯',
                'detalle' => 'Burrito norteño'
            ],
            'comida' => [
                'icono' => '🍝',
                'nombre' => 'Espagueti alfredo',
                'icono2' => '🥩',
                'descripcion' => 'Chuleta ahumada con papas al ajillo y frijoles',
                'icono3' => '🍓',
                'postre' => 'Gelatina de frutos rojos',
                'icono4' => '🍍',
                'bebida' => 'Agua de piña'
            ]
        ],
        'martes' => [
            'desayuno' => [
                'icono' => '☕',
                'nombre' => 'Café o Té',
                'icono2' => '🥞',
                'descripcion' => 'Hot cake de amaranto',
                'icono3' => '🍳',
                'detalle' => 'Omelette de espinacas'
            ],
            'comida' => [
                'icono' => '🍜',
                'nombre' => 'Sopa aguada codito',
                'icono2' => '🥘',
                'descripcion' => 'Tortitas de carne en morita con frijoles',
                'icono3' => '🧁',
                'postre' => 'Cup cake fresa',
                'icono4' => '🌰',
                'bebida' => 'Agua de tamarindo'
            ]
        ],
        'miercoles' => [
            'desayuno' => [
                'icono' => '☕',
                'nombre' => 'Café o Té',
                'icono2' => '🥛',
                'descripcion' => 'Yogurt con granola',
                'icono3' => '🍳',
                'detalle' => 'Huevos cocoyoc'
            ],
            'comida' => [
                'icono' => '🍚',
                'nombre' => 'Arroz blanco',
                'icono2' => '🐟',
                'descripcion' => 'Pescado rebosado con ensalada',
                'icono3' => '🍮',
                'postre' => 'Gelatina bicolor agua',
                'icono4' => '🥭',
                'bebida' => 'Agua de frutas tropicales'
            ]
        ],
        'jueves' => [
            'desayuno' => [
                'icono' => '☕',
                'nombre' => 'Café o Té',
                'icono2' => '🍈',
                'descripcion' => 'Papaya',
                'icono3' => '🌯',
                'detalle' => 'Wrap de pollo'
            ],
            'comida' => [
                'icono' => '🍚',
                'nombre' => 'Arroz rojo',
                'icono2' => '🍗',
                'descripcion' => 'Pollo en salsa verde con papas y frijoles',
                'icono3' => '🍊',
                'postre' => 'Panqué de naranja',
                'icono4' => '🥒',
                'bebida' => 'Agua de pepino con limón'
            ]
        ],
        'viernes' => [
            'desayuno' => [
                'icono' => '☕',
                'nombre' => 'Café o Té',
                'icono2' => '🥤',
                'descripcion' => 'Licuado de chocoplátano',
                'icono3' => '🥪',
                'detalle' => 'Sándwich de jamón y panela'
            ],
            'comida' => [
                'icono' => '🍲',
                'nombre' => 'Consome de verduras',
                'icono2' => '🍝',
                'descripcion' => 'Lasaña vegetariana con ensalada',
                'icono3' => '🌈',
                'postre' => 'Gelatina mosaico',
                'icono4' => '🍊',
                'bebida' => 'Agua de naranjada'
            ]
        ]
    ],
    // FEBRERO 2026 - SEMANA 1: 3-6 Febrero (Martes a Viernes)
    'febrero_1' => [
        'lunes' => [
            'desayuno' => [
                'sin_servicio' => true,
                'mensaje' => 'Sin reservación',
                'icono' => '🚫'
            ],
            'comida' => [
                'sin_servicio' => true,
                'mensaje' => 'Sin reservación',
                'icono' => '🚫'
            ]
        ],
        'martes' => [
            'desayuno' => [
                'icono' => '☕',
                'nombre' => 'Café o Té',
                'icono2' => '🍈',
                'descripcion' => 'Papaya con piña',
                'icono3' => '🍳',
                'detalle' => 'Huevo con salchicha'
            ],
            'comida' => [
                'icono' => '🐌',
                'nombre' => 'Sopa de caracol',
                'icono2' => '🍗',
                'descripcion' => 'Pechuga asada con nopal y panela',
                'icono3' => '🫘',
                'detalle2' => 'Con frijoles',
                'icono4' => '🍬',
                'postre' => 'Palanquetas',
                'icono5' => '🌺',
                'bebida' => 'Agua de Jamaica'
            ]
        ],
        'miercoles' => [
            'desayuno' => [
                'icono' => '☕',
                'nombre' => 'Café o Té',
                'icono2' => '🥞',
                'descripcion' => 'Hot cake',
                'icono3' => '🫘',
                'detalle' => 'Enfrijoladas'
            ],
            'comida' => [
                'icono' => '🍝',
                'nombre' => 'Fusilli poblano',
                'icono2' => '🥩',
                'descripcion' => 'Costillas en morita',
                'icono3' => '🫘',
                'detalle2' => 'Con frijoles',
                'icono4' => '🌈',
                'postre' => 'Gelatina mosaico leche',
                'icono5' => '🍓',
                'bebida' => 'Agua de fresa'
            ]
        ],
        'jueves' => [
            'desayuno' => [
                'icono' => '☕',
                'nombre' => 'Café o Té',
                'icono2' => '🍉',
                'descripcion' => 'Sandía con miel y granola',
                'icono3' => '🥪',
                'detalle' => 'Sandwich de queso asado'
            ],
            'comida' => [
                'icono' => '🍚',
                'nombre' => 'Arroz rojo',
                'icono2' => '🐟',
                'descripcion' => 'Filete de pescado a la plancha con verduras',
                'icono3' => '🍫',
                'postre' => 'Helado de chocolate',
                'icono4' => '🥣',
                'bebida' => 'Agua de avena'
            ]
        ],
        'viernes' => [
            'desayuno' => [
                'icono' => '☕',
                'nombre' => 'Café o Té',
                'icono2' => '🥣',
                'descripcion' => 'Atole de chocolate',
                'icono3' => '🥘',
                'detalle' => 'Chilaquiles verdes con huevo'
            ],
            'comida' => [
                'icono' => '🥣',
                'nombre' => 'Sopa de verdura',
                'icono2' => '🌮',
                'descripcion' => 'Tacos dorados de res',
                'icono3' => '3️⃣',
                'detalle2' => '3 piezas',
                'icono4' => '🥛',
                'postre' => 'Gelatina de yogurt',
                'icono5' => '🍹',
                'bebida' => 'Agua de frutas tropicales'
            ]
        ]
    ],
    // FEBRERO 2026 - SEMANA 2: 9-13 Febrero
    'febrero_2' => [
        'lunes' => [
            'desayuno' => [
                'icono' => '☕',
                'nombre' => 'Café o Té',
                'icono2' => '🍈',
                'descripcion' => 'Melón con yogurt',
                'icono3' => '🍳',
                'detalle' => 'Omelette de espinaca'
            ],
            'comida' => [
                'icono' => '⚙️',
                'nombre' => 'Sopa de engrane',
                'icono2' => '🐟',
                'descripcion' => 'Tortitas de atún con ensalada',
                'icono3' => '🍌',
                'postre' => 'Plátanos con crema',
                'icono4' => '🍈',
                'bebida' => 'Agua de papaya'
            ]
        ],
        'martes' => [
            'desayuno' => [
                'icono' => '☕',
                'nombre' => 'Café o Té',
                'icono2' => '🍞',
                'descripcion' => 'Pan tostado con Nutella',
                'icono3' => '🥘',
                'detalle' => 'Chilaquiles de frijol con pollo'
            ],
            'comida' => [
                'icono' => '🍝',
                'nombre' => 'Espagueti rojo',
                'icono2' => '🥩',
                'descripcion' => 'Chuleta ahumada con papas al limón',
                'icono3' => '🍮',
                'postre' => 'Gelatina bicolor leche',
                'icono4' => '🍈',
                'bebida' => 'Agua de melón'
            ]
        ],
        'miercoles' => [
            'desayuno' => [
                'icono' => '☕',
                'nombre' => 'Café o Té',
                'icono2' => '🥣',
                'descripcion' => 'Avena con leche',
                'icono3' => '🥪',
                'detalle' => 'Sandwich de jamón y panela'
            ],
            'comida' => [
                'icono' => '🍚',
                'nombre' => 'Arroz verde',
                'icono2' => '🍗',
                'descripcion' => 'Pollo en mole verde con frijoles',
                'icono3' => '🍎',
                'postre' => 'Ensalada de manzana',
                'icono4' => '🍋',
                'bebida' => 'Agua de limón'
            ]
        ],
        'jueves' => [
            'desayuno' => [
                'icono' => '☕',
                'nombre' => 'Café o Té',
                'icono2' => '🍎',
                'descripcion' => 'Manzana con yogurt',
                'icono3' => '🌶️',
                'detalle' => 'Enchiladas rojas'
            ],
            'comida' => [
                'icono' => '🍲',
                'nombre' => 'Consome de verdura',
                'icono2' => '🥔',
                'descripcion' => 'Tortitas de papa con ensalada',
                'icono3' => '🌈',
                'postre' => 'Gelatina mosaico',
                'icono4' => '🍌',
                'bebida' => 'Agua de plátano'
            ]
        ],
        'viernes' => [
            'desayuno' => [
                'icono' => '☕',
                'nombre' => 'Café o Té',
                'icono2' => '🥤',
                'descripcion' => 'Licuado de granola',
                'icono3' => '🍳',
                'detalle' => 'Huevos divorciados'
            ],
            'comida' => [
                'icono' => '🍚',
                'nombre' => 'Arroz con plátano',
                'icono2' => '🥘',
                'descripcion' => 'Tortitas de carne en salsa verde',
                'icono3' => '🫘',
                'detalle2' => 'Con frijoles',
                'icono4' => '🌰',
                'postre' => 'Alegrías',
                'icono5' => '🍹',
                'bebida' => 'Agua de frutas'
            ]
        ]
    ],
    // FEBRERO 2026 - SEMANA 3: 16-20 Febrero
    'febrero_3' => [
        'lunes' => [
            'desayuno' => [
                'icono' => '☕',
                'nombre' => 'Café o Té',
                'icono2' => '🍌',
                'descripcion' => 'Plátano con yogurt',
                'icono3' => '🍳',
                'detalle' => 'Huevos con tocino'
            ],
            'comida' => [
                'icono' => '🔤',
                'nombre' => 'Sopa de letra',
                'icono2' => '🐟',
                'descripcion' => 'Tostadas de atún a la mexicana',
                'icono3' => '🥜',
                'postre' => 'Pepitorias',
                'icono4' => '🥒',
                'bebida' => 'Agua de pepino con limón'
            ]
        ],
        'martes' => [
            'desayuno' => [
                'icono' => '☕',
                'nombre' => 'Café o Té',
                'icono2' => '🍞',
                'descripcion' => 'Pan tostado con mantequilla',
                'icono3' => '🧀',
                'detalle' => 'Queso a la mexicana'
            ],
            'comida' => [
                'icono' => '🍚',
                'nombre' => 'Arroz rojo',
                'icono2' => '🍡',
                'descripcion' => 'Albóndigas con frijoles',
                'icono3' => '🥛',
                'postre' => 'Arroz con leche',
                'icono4' => '🍍',
                'bebida' => 'Agua de piña'
            ]
        ],
        'miercoles' => [
            'desayuno' => [
                'icono' => '☕',
                'nombre' => 'Café o Té',
                'icono2' => '🍉',
                'descripcion' => 'Sandía con piña',
                'icono3' => '🥐',
                'detalle' => 'Cuernito de jamón y panela'
            ],
            'comida' => [
                'icono' => '🍝',
                'nombre' => 'Espagueti bologñesa',
                'icono2' => '🍗',
                'descripcion' => 'Milanesa de pollo con ensalada rusa',
                'icono3' => '🥜',
                'postre' => 'Gelatina de nuez',
                'icono4' => '🍈',
                'bebida' => 'Agua de guayaba'
            ]
        ],
        'jueves' => [
            'desayuno' => [
                'icono' => '☕',
                'nombre' => 'Café o Té',
                'icono2' => '🥛',
                'descripcion' => 'Yogurt con granola',
                'icono3' => '🥮',
                'detalle' => 'Sopes con nopales'
            ],
            'comida' => [
                'icono' => '🍲',
                'nombre' => 'Consome de pollo',
                'icono2' => '🥗',
                'descripcion' => 'Ensalada mediterránea',
                'icono3' => '💧',
                'postre' => 'Gelatina bicolor agua',
                'icono4' => '🍉',
                'bebida' => 'Agua de sandía'
            ]
        ],
        'viernes' => [
            'desayuno' => [
                'icono' => '☕',
                'nombre' => 'Café o Té',
                'icono2' => '🍈',
                'descripcion' => 'Melón con papaya',
                'icono3' => '🥘',
                'detalle' => 'Chilaquiles en morita con huevo'
            ],
            'comida' => [
                'icono' => '🍚',
                'nombre' => 'Arroz con salchicha',
                'icono2' => '🐷',
                'descripcion' => 'Rollo de cerdo en pasilla con frijoles',
                'icono3' => '🍓',
                'postre' => 'Helado de fresa',
                'icono4' => '🌾',
                'bebida' => 'Agua de horchata'
            ]
        ]
    ],
    // FEBRERO 2026 - SEMANA 4: 23-27 Febrero
    'febrero_4' => [
        'lunes' => [
            'desayuno' => [
                'icono' => '☕',
                'nombre' => 'Café o Té',
                'icono2' => '🍉',
                'descripcion' => 'Sandía con yogurt',
                'icono3' => '🍳',
                'detalle' => 'Omelette de panela'
            ],
            'comida' => [
                'icono' => '⭐',
                'nombre' => 'Sopa de estrella',
                'icono2' => '🐷',
                'descripcion' => 'Milanesa de cerdo con ensalada',
                'icono3' => '🫐',
                'postre' => 'Gelatina frambueza',
                'icono4' => '🍊',
                'bebida' => 'Agua de naranja'
            ]
        ],
        'martes' => [
            'desayuno' => [
                'icono' => '☕',
                'nombre' => 'Café o Té',
                'icono2' => '🥞',
                'descripcion' => 'Hot cake de amaranto',
                'icono3' => '🌶️',
                'detalle' => 'Enchiladas verdes'
            ],
            'comida' => [
                'icono' => '🍲',
                'nombre' => 'Consome de res',
                'icono2' => '🌮',
                'descripcion' => 'Tacos de alambre',
                'icono3' => '3️⃣',
                'detalle2' => '3 piezas',
                'icono4' => '🍋',
                'postre' => 'Helado de limón',
                'icono5' => '🥒',
                'bebida' => 'Agua de pepino'
            ]
        ],
        'miercoles' => [
            'desayuno' => [
                'icono' => '☕',
                'nombre' => 'Café o Té',
                'icono2' => '🍈',
                'descripcion' => 'Papaya con miel y granola',
                'icono3' => '🥪',
                'detalle' => 'Sincronizada Bacros'
            ],
            'comida' => [
                'icono' => '🍝',
                'nombre' => 'Espagueti verde',
                'icono2' => '🥗',
                'descripcion' => 'Ensalada oriental',
                'icono3' => '🍮',
                'postre' => 'Flan napolitano',
                'icono4' => '🧊',
                'bebida' => 'Agua de té helado'
            ]
        ],
        'jueves' => [
            'desayuno' => [
                'icono' => '☕',
                'nombre' => 'Café o Té',
                'icono2' => '🍈',
                'descripcion' => 'Melón con piña',
                'icono3' => '🍳',
                'detalle' => 'Huevo a la mexicana'
            ],
            'comida' => [
                'icono' => '🍚',
                'nombre' => 'Arroz amarillo',
                'icono2' => '🍗',
                'descripcion' => 'Pollo en salsa roja con papas y frijoles',
                'icono3' => '🥥',
                'postre' => 'Cocadas',
                'icono4' => '🌱',
                'bebida' => 'Agua de limón con chía'
            ]
        ],
        'viernes' => [
            'desayuno' => [
                'icono' => '☕',
                'nombre' => 'Café o Té',
                'icono2' => '🍈',
                'descripcion' => 'Papaya con yogurt',
                'icono3' => '🥖',
                'detalle' => 'Molletes con salchicha'
            ],
            'comida' => [
                'icono' => '🍚',
                'nombre' => 'Arroz blanco',
                'icono2' => '🐟',
                'descripcion' => 'Filete de pescado al mojo con ensalada',
                'icono3' => '🥤',
                'postre' => 'Gelatina de refresco',
                'icono4' => '🌰',
                'bebida' => 'Agua de tamarindo'
            ]
        ]
    ]
];

// ==================================================
// SELECCIONAR EL MENÚ CORRECTO
// ==================================================

$menu_a_mostrar = [];
if ($mes_seleccionado === 'enero' && $num_semana_seleccionada === 4) {
    $menu_a_mostrar = $menus_completos['enero_4'];
} elseif ($mes_seleccionado === 'febrero') {
    switch ($num_semana_seleccionada) {
        case 1:
            $menu_a_mostrar = $menus_completos['febrero_1'];
            break;
        case 2:
            $menu_a_mostrar = $menus_completos['febrero_2'];
            break;
        case 3:
            $menu_a_mostrar = $menus_completos['febrero_3'];
            break;
        case 4:
            $menu_a_mostrar = $menus_completos['febrero_4'];
            break;
        default:
            $menu_a_mostrar = $menus_completos['febrero_1'];
    }
} else {
    $menu_a_mostrar = $menus_completos['enero_4'];
}

// ==================================================
// FUNCIÓN PARA MOSTRAR ÍTEMS DE COMIDA CORREGIDA
// ==================================================

function mostrarItemComida($item, $tipo = 'main') {
    $html = '';
    
    // Verificar si es sin servicio
    if (isset($item['sin_servicio']) && $item['sin_servicio']) {
        $html .= '<div class="meal-item sin-servicio">';
        $html .= '<span class="item-icon">' . ($item['icono'] ?? '🚫') . '</span>';
        $html .= '<span>' . ($item['mensaje'] ?? 'Sin servicio') . '</span>';
        $html .= '</div>';
        return $html;
    }
    
    if ($tipo === 'main') {
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
    
    if (!empty($item['detalle2'])) {
        $html .= '<div class="meal-item detail">';
        $html .= '<span class="item-icon">' . ($item['icono3'] ?? ($item['icono4'] ?? '🥗')) . '</span>';
        $html .= '<span>' . $item['detalle2'] . '</span>';
        $html .= '</div>';
    }
    
    if (!empty($item['postre'])) {
        $html .= '<div class="meal-item postre">';
        $html .= '<span class="item-icon">' . ($item['icono4'] ?? ($item['icono5'] ?? '🍰')) . '</span>';
        $html .= '<span>' . $item['postre'] . '</span>';
        $html .= '</div>';
    }
    
    if (!empty($item['bebida'])) {
        $html .= '<div class="meal-item bebida">';
        $html .= '<span class="item-icon">' . ($item['icono5'] ?? ($item['icono6'] ?? '🥤')) . '</span>';
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
    
    if ($mes_seleccionado === 'enero' && $num_semana_seleccionada === 4) {
        $titulo_semana = 'Semana 4: 26-30 Enero 2026';
        $rango_fechas = '26 al 30 de Enero 2026';
    } elseif ($mes_seleccionado === 'febrero') {
        switch ($num_semana_seleccionada) {
            case 1:
                $titulo_semana = 'Semana 1: 3-6 Febrero 2026';
                $rango_fechas = 'Martes 3 al Viernes 6 de Febrero 2026';
                break;
            case 2:
                $titulo_semana = 'Semana 2: 9-13 Febrero 2026';
                $rango_fechas = '9 al 13 de Febrero 2026';
                break;
            case 3:
                $titulo_semana = 'Semana 3: 16-20 Febrero 2026';
                $rango_fechas = '16 al 20 de Febrero 2026';
                break;
            case 4:
                $titulo_semana = 'Semana 4: 23-27 Febrero 2026';
                $rango_fechas = '23 al 27 de Febrero 2026';
                break;
        }
    }
}

// ==================================================
// OBTENER FECHA ACTUAL PARA MOSTRAR
// ==================================================

$fecha_actual = date('d/m/Y H:i');
$hoy_objeto = new DateTime();
$semana_seleccionada_objeto = new DateTime($fecha_inicio);
$diferencia_dias = $hoy_objeto->diff($semana_seleccionada_objeto)->days;

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sistema de Pedidos - Comedor Enero/Febrero 2026</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
      --enero-color: #4a80b5;
      --febrero-1: #10b981;
      --febrero-2: #8b5cf6;
      --febrero-3: #f59e0b;
      --febrero-4: #ec4899;
      --gold-gradient: linear-gradient(135deg, #FFD700 0%, #FFC700 25%, #FFAA00 50%, #FF8C00 75%, #FF6B00 100%);
      --silver-gradient: linear-gradient(135deg, #C0C0C0 0%, #D3D3D3 25%, #E8E8E8 50%, #F0F0F0 75%, #F8F8F8 100%);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, var(--navy-deep) 0%, var(--navy-marine) 50%, var(--navy-medium) 100%);
      color: var(--pearl-white);
      min-height: 100vh;
      padding: 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
      position: relative;
    }

    .logo-corner {
      position: absolute;
      top: 20px;
      left: 20px;
      z-index: 100;
    }

    .logo-corner .logo {
      height: 50px;
      filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
      transition: transform 0.3s ease;
    }

    .logo-corner .logo:hover {
      transform: scale(1.05);
    }

    .user-info-bar {
      background: var(--glass-bg);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid var(--glass-border);
      padding: 20px 30px;
      border-radius: 16px;
      margin-bottom: 25px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 100%;
      max-width: 1000px;
      box-shadow: var(--glass-shadow);
      margin-top: 10px;
    }

    .user-info-bar .user-details {
      display: flex;
      gap: 30px;
    }

    .user-info-bar .user-detail {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 1.05rem;
    }

    .user-info-bar .user-detail i {
      font-size: 1.3rem;
      opacity: 0.9;
      color: var(--navy-accent);
    }

    .session-info {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 0.95rem;
      opacity: 0.8;
    }

    .credentials-panel {
      background: var(--glass-bg);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid var(--glass-border);
      border-radius: 16px;
      padding: 25px;
      margin-bottom: 25px;
      width: 100%;
      max-width: 1000px;
      box-shadow: var(--glass-shadow);
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 30px;
      align-items: start;
    }

    .credentials-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
    }

    .week-selector-container {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid var(--glass-border);
      border-radius: 12px;
      padding: 20px;
      min-width: 250px;
      backdrop-filter: blur(10px);
    }

    .week-selector-container h3 {
      margin-bottom: 15px;
      font-size: 1.1rem;
      font-weight: 600;
      color: var(--navy-accent);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .week-selector-container select {
      width: 100%;
      padding: 12px 15px;
      border: none;
      border-radius: 10px;
      background: rgba(255, 255, 255, 0.15);
      color: var(--pearl-white);
      font-family: 'Inter', sans-serif;
      font-size: 0.95rem;
      backdrop-filter: blur(10px);
      border: 1px solid var(--glass-border);
      transition: all 0.3s ease;
    }

    .week-selector-container select:focus {
      outline: none;
      border-color: var(--navy-accent);
      background: rgba(255, 255, 255, 0.2);
    }

    .week-selector-container select option {
      background: var(--navy-medium);
      color: var(--pearl-white);
      padding: 10px;
    }

    .credential-field {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .credential-field label {
      font-weight: 600;
      font-size: 0.9rem;
      color: var(--navy-accent);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .credential-field input {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid var(--glass-border);
      border-radius: 10px;
      padding: 12px 15px;
      color: var(--pearl-white);
      font-size: 1rem;
      backdrop-filter: blur(10px);
    }

    .credential-field input:focus {
      outline: none;
      border-color: var(--navy-accent);
      background: rgba(255, 255, 255, 0.15);
    }

    .credential-field input.readonly {
      background: rgba(255, 255, 255, 0.05);
      color: #94a3b8;
      cursor: not-allowed;
    }

    .field-note {
      font-size: 0.85rem;
      color: #94a3b8;
      margin-top: 6px;
      display: block;
      font-style: italic;
    }

    form {
      width: 100%;
      max-width: 1000px;
      background: var(--glass-bg);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid var(--glass-border);
      border-radius: 20px;
      padding: 35px;
      box-shadow: var(--glass-shadow);
      transition: transform 0.3s ease;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    form:hover {
      transform: translateY(-5px);
    }

    .week-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
      gap: 20px;
      margin-bottom: 35px;
      width: 100%;
    }

    .day-card {
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid var(--glass-border);
      border-radius: 16px;
      padding: 20px;
      text-align: center;
      transition: all 0.3s ease;
      backdrop-filter: blur(10px);
    }

    .day-card:hover {
      background: rgba(255, 255, 255, 0.12);
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    }

    .day-card h3 {
      margin-bottom: 16px;
      font-size: 1.3rem;
      font-weight: 600;
      color: var(--pearl-white);
      padding-bottom: 10px;
      border-bottom: 1px solid var(--glass-border);
    }

    .meal-option {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      padding: 15px;
      border-radius: 12px;
      margin: 8px 0;
      cursor: pointer;
      transition: all 0.3s ease;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid transparent;
      text-align: left;
      min-height: 100px;
    }

    .meal-option:hover {
      background: rgba(255, 255, 255, 0.1);
      border-color: var(--glass-border);
    }

    .meal-option input {
      width: auto;
      margin: 5px 0 0 0;
      accent-color: var(--navy-accent);
      transform: scale(1.3);
      flex-shrink: 0;
    }

    .meal-option.selected {
      background: rgba(74, 128, 181, 0.2);
      border-color: var(--navy-accent);
      box-shadow: 0 4px 15px rgba(74, 128, 181, 0.3);
    }

    .meal-option.sin-servicio {
      cursor: not-allowed;
      opacity: 0.7;
      background: rgba(148, 163, 184, 0.1);
    }

    .meal-option.sin-servicio .meal-details .meal-type {
      color: var(--sin-servicio-color);
    }

    .meal-option.sin-servicio input {
      display: none;
    }

    .meal-option.sin-servicio:hover {
      background: rgba(148, 163, 184, 0.15);
      transform: none;
    }

    .meal-details {
      flex: 1;
    }

    .meal-type {
      font-weight: 600;
      font-size: 1rem;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .meal-type.desayuno {
      color: var(--desayuno-color);
    }

    .meal-type.comida {
      color: var(--comida-color);
    }

    .meal-items {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .meal-item {
      display: flex;
      align-items: flex-start;
      gap: 8px;
      font-size: 0.9rem;
      line-height: 1.4;
    }

    .meal-item.main {
      font-weight: 500;
    }

    .meal-item.detail {
      font-size: 0.85rem;
      opacity: 0.9;
      margin-left: 5px;
    }

    .meal-item.sin-servicio {
      color: var(--sin-servicio-color);
      font-style: italic;
      align-items: center;
    }

    .meal-item.postre {
      font-size: 0.85rem;
      opacity: 0.8;
      font-style: italic;
      margin-top: 3px;
      color: #d4a5cb;
    }

    .meal-item.bebida {
      font-size: 0.85rem;
      opacity: 0.8;
      font-style: italic;
      margin-top: 3px;
      color: #a5b4cb;
    }

    .item-icon {
      font-size: 1rem;
      min-width: 20px;
      text-align: center;
    }

    .submit-button-container {
      width: 100%;
      display: flex;
      justify-content: center;
      margin-top: 20px;
      position: relative;
    }

    #submitBtn {
      background: var(--gold-gradient);
      color: #000;
      border: none;
      border-radius: 50px;
      padding: 22px 50px;
      font-size: 1.3rem;
      font-weight: 700;
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
      box-shadow: 
        0 10px 30px rgba(255, 215, 0, 0.4),
        0 0 0 2px rgba(255, 255, 255, 0.1),
        inset 0 2px 10px rgba(255, 255, 255, 0.5),
        inset 0 -2px 10px rgba(0, 0, 0, 0.2);
      text-transform: uppercase;
      letter-spacing: 1.5px;
      min-width: 350px;
      text-shadow: 0 1px 1px rgba(255, 255, 255, 0.3);
    }

    #submitBtn::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, 
          rgba(255, 255, 255, 0.2) 0%,
          rgba(255, 255, 255, 0) 50%,
          rgba(255, 255, 255, 0.2) 100%);
      border-radius: 50px;
      z-index: -1;
      opacity: 0;
      transition: opacity 0.4s ease;
    }

    #submitBtn::after {
      content: '';
      position: absolute;
      top: -2px;
      left: -2px;
      right: -2px;
      bottom: -2px;
      background: var(--gold-gradient);
      border-radius: 52px;
      z-index: -2;
      filter: blur(10px);
      opacity: 0;
      transition: opacity 0.4s ease;
    }

    #submitBtn:hover {
      transform: translateY(-5px) scale(1.03);
      box-shadow: 
        0 15px 40px rgba(255, 215, 0, 0.6),
        0 0 0 3px rgba(255, 255, 255, 0.2),
        inset 0 2px 15px rgba(255, 255, 255, 0.6),
        inset 0 -2px 15px rgba(0, 0, 0, 0.3);
      letter-spacing: 2px;
    }

    #submitBtn:hover::before {
      opacity: 1;
    }

    #submitBtn:hover::after {
      opacity: 0.5;
    }

    #submitBtn:active {
      transform: translateY(-2px) scale(0.98);
      transition: all 0.1s ease;
      box-shadow: 
        0 5px 20px rgba(255, 215, 0, 0.3),
        0 0 0 2px rgba(255, 255, 255, 0.1),
        inset 0 2px 5px rgba(255, 255, 255, 0.4),
        inset 0 -2px 5px rgba(0, 0, 0, 0.3);
    }

    #submitBtn:disabled {
      background: var(--silver-gradient);
      transform: none;
      cursor: not-allowed;
      opacity: 0.7;
      box-shadow: 
        0 5px 15px rgba(192, 192, 192, 0.2),
        0 0 0 1px rgba(255, 255, 255, 0.05);
    }

    #submitBtn:disabled:hover {
      transform: none;
      box-shadow: 
        0 5px 15px rgba(192, 192, 192, 0.2),
        0 0 0 1px rgba(255, 255, 255, 0.05);
    }

    #submitBtn i {
      font-size: 1.5rem;
      filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.2));
      transition: transform 0.3s ease;
    }

    #submitBtn:hover i {
      transform: scale(1.2) rotate(5deg);
    }

    #submitBtn:disabled i {
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    .button-shine {
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: linear-gradient(
        45deg,
        transparent 30%,
        rgba(255, 255, 255, 0.3) 50%,
        transparent 70%
      );
      transform: rotate(30deg);
      animation: shine 3s infinite linear;
      z-index: 1;
      pointer-events: none;
    }

    @keyframes shine {
      0% { transform: translateX(-100%) translateY(-100%) rotate(30deg); }
      100% { transform: translateX(100%) translateY(100%) rotate(30deg); }
    }

    .notification {
      padding: 18px 22px;
      border-radius: 12px;
      margin-bottom: 25px;
      display: flex;
      align-items: center;
      gap: 15px;
      font-weight: 500;
      backdrop-filter: blur(20px);
      border: 1px solid;
      width: 100%;
      max-width: 1000px;
    }

    .notification.success {
      background: rgba(16, 185, 129, 0.15);
      color: #10b981;
      border-color: rgba(16, 185, 129, 0.3);
    }

    .notification.error {
      background: rgba(239, 68, 68, 0.15);
      color: #ef4444;
      border-color: rgba(239, 68, 68, 0.3);
    }

    .notification i {
      font-size: 1.4rem;
    }

    .week-indicator {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid var(--glass-border);
      border-radius: 12px;
      padding: 15px 20px;
      margin-bottom: 25px;
      width: 100%;
      max-width: 1000px;
      backdrop-filter: blur(10px);
      text-align: center;
      font-size: 1.1rem;
      font-weight: 600;
    }

    .week-indicator.enero {
      color: var(--enero-color);
      border-color: rgba(74, 128, 181, 0.3);
      background: rgba(74, 128, 181, 0.1);
    }

    .week-indicator.febrero-1 {
      color: var(--febrero-1);
      border-color: rgba(16, 185, 129, 0.3);
      background: rgba(16, 185, 129, 0.1);
    }

    .week-indicator.febrero-2 {
      color: var(--febrero-2);
      border-color: rgba(139, 92, 246, 0.3);
      background: rgba(139, 92, 246, 0.1);
    }

    .week-indicator.febrero-3 {
      color: var(--febrero-3);
      border-color: rgba(245, 158, 11, 0.3);
      background: rgba(245, 158, 11, 0.1);
    }

    .week-indicator.febrero-4 {
      color: var(--febrero-4);
      border-color: rgba(236, 72, 153, 0.3);
      background: rgba(236, 72, 153, 0.1);
    }

    .week-indicator i {
      margin-right: 10px;
    }

    .menu-title {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid var(--glass-border);
      border-radius: 12px;
      padding: 15px 20px;
      margin-bottom: 25px;
      width: 100%;
      max-width: 1000px;
      backdrop-filter: blur(10px);
      text-align: center;
      font-size: 1.2rem;
      font-weight: 700;
      color: var(--navy-accent);
    }

    .menu-title i {
      margin-right: 10px;
      color: var(--desayuno-color);
    }

    .semana-info {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid var(--glass-border);
      border-radius: 12px;
      padding: 15px 20px;
      margin-bottom: 25px;
      width: 100%;
      max-width: 1000px;
      backdrop-filter: blur(10px);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .semana-info .info-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 5px;
    }

    .semana-info .info-label {
      font-size: 0.85rem;
      opacity: 0.8;
    }

    .semana-info .info-value {
      font-size: 1.1rem;
      font-weight: 600;
      color: var(--navy-accent);
    }

    .semana-info .info-value.warning {
      color: var(--warning);
    }

    .semana-info .info-value.success {
      color: var(--success);
    }

    @media (max-width: 768px) {
      .user-info-bar {
        flex-direction: column;
        gap: 15px;
        text-align: center;
      }
      
      .user-info-bar .user-details {
        flex-direction: column;
        gap: 10px;
      }
      
      .credentials-panel {
        grid-template-columns: 1fr;
        gap: 20px;
      }
      
      .credentials-grid {
        grid-template-columns: 1fr;
      }
      
      .week-selector-container {
        min-width: auto;
      }
      
      form {
        padding: 25px;
      }
      
      .week-grid {
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
      }

      #submitBtn {
        min-width: 300px;
        padding: 18px 40px;
        font-size: 1.2rem;
      }

      .logo-corner {
        position: relative;
        top: auto;
        left: auto;
        margin-bottom: 15px;
        text-align: center;
      }

      .logo-corner .logo {
        height: 45px;
      }

      .semana-info {
        flex-direction: column;
        gap: 15px;
        text-align: center;
      }
    }

    @media (max-width: 480px) {
      .week-grid {
        grid-template-columns: 1fr;
      }
      
      body {
        padding: 15px;
      }
      
      .user-info-bar, .credentials-panel, form {
        padding: 20px;
      }
      
      .meal-option {
        padding: 12px;
        min-height: 90px;
      }
      
      .item-icon {
        font-size: 0.9rem;
        min-width: 18px;
      }
      
      .meal-item {
        font-size: 0.85rem;
      }

      #submitBtn {
        min-width: 250px;
        padding: 16px 30px;
        font-size: 1.1rem;
        letter-spacing: 1px;
      }

      #submitBtn i {
        font-size: 1.3rem;
      }
    }
  </style>
</head>
<body>

<!-- Logo en esquina -->
<div class="logo-corner">
  <img src="Logo2.png" alt="Logo" class="logo">
</div>

<!-- Barra de información del usuario -->
<div class="user-info-bar">
  <div class="user-details">
    <div class="user-detail">
      <i class="fas fa-user-circle"></i>
      <span><strong>Usuario:</strong> <?php echo htmlspecialchars($user_name); ?></span>
    </div>
    <div class="user-detail">
      <i class="fas fa-building"></i>
      <span><strong>Área:</strong> <?php echo htmlspecialchars($user_area); ?></span>
    </div>
  </div>
  <div class="session-info">
    <i class="fas fa-clock"></i>
    <span><?php echo $fecha_actual; ?></span>
  </div>
</div>

<!-- Título del menú -->
<div class="menu-title">
  <i class="fas fa-utensils"></i>
  <?php if ($mes_titulo && $num_semana_seleccionada > 0): ?>
    Menú <?php echo $mes_titulo; ?> 2026 - <?php echo $titulo_semana; ?>
  <?php else: ?>
    Sistema de Pedidos - Comedor Enero/Febrero 2026
  <?php endif; ?>
</div>

<!-- Información de la semana -->
<div class="semana-info">
  <div class="info-item">
    <span class="info-label">Fecha actual</span>
    <span class="info-value"><?php echo date('d/m/Y'); ?></span>
  </div>
  <div class="info-item">
    <span class="info-label">Semana seleccionada</span>
    <span class="info-value"><?php echo $num_semana_seleccionada > 0 ? "Semana $num_semana_seleccionada" : 'No seleccionada'; ?></span>
  </div>
  <div class="info-item">
    <span class="info-label">Inicia</span>
    <span class="info-value"><?php echo date('d/m/Y', strtotime($fecha_inicio)); ?></span>
  </div>
  <div class="info-item">
    <span class="info-label">Días restantes</span>
    <span class="info-value <?php echo $diferencia_dias <= 3 ? 'warning' : 'success'; ?>">
      <?php echo $diferencia_dias; ?> días
    </span>
  </div>
</div>

<!-- Indicador de semana -->
<?php if ($mes_seleccionado && $num_semana_seleccionada > 0): ?>
<div class="week-indicator <?php echo $mes_seleccionado; ?><?php echo ($mes_seleccionado === 'febrero') ? '-' . $num_semana_seleccionada : ''; ?>">
  <i class="fas fa-calendar-week"></i>
  <?php if ($mes_seleccionado === 'enero'): ?>
    Última semana de Enero: <?php echo $rango_fechas; ?>
  <?php elseif ($mes_seleccionado === 'febrero' && $num_semana_seleccionada === 1): ?>
    Semana del <?php echo $rango_fechas; ?> (Lunes sin servicio)
  <?php else: ?>
    Semana del <?php echo $rango_fechas; ?>
  <?php endif; ?>
  <?php if ($diferencia_dias <= 2): ?>
    <br><small style="font-size: 0.9rem; opacity: 0.8;"><i class="fas fa-exclamation-triangle"></i> ¡Sólo quedan <?php echo $diferencia_dias; ?> días para hacer tu pedido!</small>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Notificación -->
<?php if (!empty($notification['message'])): ?>
<div class="notification <?php echo $notification['type']; ?>">
  <i class="fas fa-<?php echo $notification['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
  <span><?php echo htmlspecialchars($notification['message']); ?></span>
</div>
<?php endif; ?>

<!-- Panel de credenciales con selector de semana -->
<div class="credentials-panel">
  <div class="credentials-grid">
    <div class="credential-field">
      <label><i class="fas fa-id-card"></i> ID Empleado</label>
      <input type="text" value="<?php echo htmlspecialchars($id_empleado); ?>" readonly class="readonly">
      <span class="field-note">Precargado desde el sistema</span>
    </div>
    <div class="credential-field">
      <label><i class="fas fa-user"></i> Usuario</label>
      <input type="text" value="<?php echo htmlspecialchars($usuario_bd); ?>" readonly class="readonly">
      <span class="field-note">Precargado desde el sistema</span>
    </div>
    <div class="credential-field">
      <label><i class="fas fa-lock"></i> Contraseña</label>
      <input type="password" value="<?php echo htmlspecialchars($contrasena_bd); ?>" readonly class="readonly">
      <span class="field-note">Precargada desde el sistema</span>
    </div>
  </div>
  
  <div class="week-selector-container">
    <h3><i class="far fa-calendar-alt"></i> Semana de Pedido</h3>
    <select name="Fecha2" id="Fecha2" required>
      <option value="">Selecciona la semana</option>
      <?php 
      foreach ($semanasDisponibles as $semana): 
        $selected = ($semana['fecha'] == $semana_seleccionada) ? 'selected' : '';
        $fechaInicioObj = new DateTime($semana['fecha_inicio']);
        $hoyObj = new DateTime();
        $diasParaSemana = $hoyObj->diff($fechaInicioObj)->days;
        
        // Agregar indicador de próxima semana
        $indicador = '';
        if ($semana['fecha'] == $semanaPorDefecto['fecha']) {
          $indicador = ' ⭐ (Próxima semana)';
        }
      ?>
        <option value="<?php echo $semana['fecha']; ?>" <?php echo $selected; ?>>
          <?php echo $semana['mostrar']; ?><?php echo $indicador; ?>
        </option>
      <?php endforeach; ?>
    </select>
    <span class="field-note">Selecciona una semana de enero/febrero 2026</span>
  </div>
</div>

<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?user_name=<?php echo urlencode($user_name); ?>&user_area=<?php echo urlencode($user_area); ?>" id="menuForm">

  <!-- Campos ocultos con los datos precargados -->
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
        
        // Determinar si es sin servicio
        $desayuno_sin_servicio = isset($menu_dia['desayuno']['sin_servicio']) && $menu_dia['desayuno']['sin_servicio'];
        $comida_sin_servicio = isset($menu_dia['comida']['sin_servicio']) && $menu_dia['comida']['sin_servicio'];
    ?>
    <div class='day-card'>
        <h3><?php echo $dia_nombre; ?></h3>
        
        <!-- Desayuno -->
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
        
        <!-- Comida -->
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

  <!-- Contenedor del botón premium -->
  <div class="submit-button-container">
    <button type="submit" id="submitBtn">
      <i class="fas fa-check-circle"></i> Confirmar Pedido Semanal
      <div class="button-shine"></div>
    </button>
  </div>
</form>

<script>
  // Función para marcar/desmarcar radios y añadir clase selected
  document.querySelectorAll('.toggle-radio').forEach(radio => {
    radio.addEventListener('change', function() {
      // Remover clase selected de todas las opciones
      document.querySelectorAll('.meal-option').forEach(option => {
        option.classList.remove('selected');
      });
      
      // Añadir clase selected a las opciones seleccionadas
      document.querySelectorAll('.toggle-radio:checked').forEach(checkedRadio => {
        checkedRadio.closest('.meal-option').classList.add('selected');
      });
    });
  });

  // Permitir deseleccionar radios al hacer clic nuevamente
  document.querySelectorAll('.toggle-radio').forEach(radio => {
    radio.addEventListener('click', function(e) {
      const wasChecked = this.dataset.wasChecked === 'true';
      
      if (wasChecked) {
        e.preventDefault();
        this.checked = false;
        this.dataset.wasChecked = 'false';
        this.closest('.meal-option').classList.remove('selected');
        this.dispatchEvent(new Event('change'));
      } else {
        // Marcar otros radios del mismo grupo como no seleccionados
        const groupName = this.name;
        document.querySelectorAll(`input[name="${groupName}"]`).forEach(r => {
          r.dataset.wasChecked = 'false';
        });
        this.dataset.wasChecked = 'true';
      }
    });
  });

  // Sincronizar el selector de semana con el campo hidden del formulario
  const fechaSelect = document.getElementById('Fecha2');
  const fechaHidden = document.getElementById('Fecha2Hidden');
  
  fechaSelect.addEventListener('change', function() {
    fechaHidden.value = this.value;
    // Recargar la página para actualizar el menú
    if (this.value) {
      const url = new URL(window.location.href);
      url.searchParams.set('semana', this.value);
      window.location.href = url.toString();
    }
  });

  // Validación antes de enviar
  document.getElementById('menuForm').addEventListener('submit', function(e) {
    const selectedDays = document.querySelectorAll('.toggle-radio:checked');
    const selectedWeek = fechaSelect.value;
    
    if (selectedDays.length === 0) {
      e.preventDefault();
      alert('⚠️ Por favor, selecciona al menos una opción de menú.');
      return false;
    }
    
    if (!selectedWeek) {
      e.preventDefault();
      alert('⚠️ Por favor, selecciona una semana para el pedido.');
      return false;
    }
    
    // Sincronizar el valor del selector con el campo hidden
    fechaHidden.value = selectedWeek;
    
    // Mostrar loading en el botón
    const submitBtn = document.getElementById('submitBtn');
    const originalHTML = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
    submitBtn.disabled = true;
    
    // Revertir después de 5 segundos si no se envió
    setTimeout(() => {
      if (submitBtn.disabled) {
        submitBtn.innerHTML = originalHTML;
        submitBtn.disabled = false;
      }
    }, 5000);
    
    // Permitir el envío del formulario
    return true;
  });

  // Mostrar alerta si la semana comienza pronto
  document.addEventListener('DOMContentLoaded', function() {
    const diasRestantes = document.querySelector('.info-value.warning');
    if (diasRestantes && parseInt(diasRestantes.textContent) <= 2) {
      setTimeout(() => {
        alert('⚠️ ATENCIÓN: La semana seleccionada comienza pronto. ¡No olvides hacer tu pedido!');
      }, 1000);
    }
    
    // Resaltar la opción por defecto (próxima semana)
    const opciones = fechaSelect.querySelectorAll('option');
    opciones.forEach(opcion => {
      if (opcion.textContent.includes('⭐')) {
        opcion.style.fontWeight = 'bold';
        opcion.style.color = '#FFD700';
      }
    });
  });

  // Efecto de brillo aleatorio en el botón
  const buttonShine = document.querySelector('.button-shine');
  if (buttonShine) {
    setInterval(() => {
      buttonShine.style.animation = 'none';
      setTimeout(() => {
        buttonShine.style.animation = 'shine 3s infinite linear';
      }, 10);
    }, 10000);
  }

  // Efecto de pulsación sutil
  setInterval(() => {
    const submitBtn = document.getElementById('submitBtn');
    if (!submitBtn.disabled) {
      submitBtn.style.transform = 'translateY(-2px) scale(1.01)';
      setTimeout(() => {
        if (!submitBtn.disabled) {
          submitBtn.style.transform = '';
        }
      }, 300);
    }
  }, 5000);
</script>

</body>
</html>