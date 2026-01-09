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
// FUNCIÓN PARA OBTENER TODOS LOS LUNES DEL MES
// ==================================================

function obtenerLunesDelMes($mes = null, $año = null) {
    if ($mes === null) $mes = date('n');
    if ($año === null) $año = date('Y');
    
    $primerDia = new DateTime("$año-$mes-01");
    $ultimoDia = new DateTime("$año-$mes-" . $primerDia->format('t'));
    
    $lunes = [];
    $fecha = clone $primerDia;
    
    // Encontrar el primer lunes del mes
    while ($fecha->format('N') != 1 && $fecha <= $ultimoDia) {
        $fecha->modify('+1 day');
    }
    
    // Recoger todos los lunes del mes
    while ($fecha <= $ultimoDia) {
        $lunes[] = [
            'fecha' => $fecha->format('Y-m-d'),
            'mostrar' => $fecha->format('d/m/Y')
        ];
        $fecha->modify('+1 week');
    }
    
    return $lunes;
}

// ==================================================
// CALCULAR SEMANAS DE ENERO 2026
// ==================================================

// Definir las semanas de enero 2026
$semanasEnero2026 = [
    [
        'fecha' => '2026-01-05',
        'mostrar' => '05/01/2026 - Semana 1 (5-9 Ene)',
        'num_semana' => 1
    ],
    [
        'fecha' => '2026-01-12',
        'mostrar' => '12/01/2026 - Semana 2 (12-16 Ene)',
        'num_semana' => 2
    ],
    [
        'fecha' => '2026-01-19',
        'mostrar' => '19/01/2026 - Semana 3 (19-23 Ene)',
        'num_semana' => 3
    ],
    [
        'fecha' => '2026-01-26',
        'mostrar' => '26/01/2026 - Semana 4 (26-30 Ene)',
        'num_semana' => 4
    ]
];

$todasSemanas = $semanasEnero2026;

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
// DETECTAR SEMANA SELECCIONADA Y OBTENER SU NÚMERO
// ==================================================

// Obtener la semana seleccionada del POST o GET
$semana_seleccionada = $_POST['Fecha2'] ?? $_GET['semana'] ?? '';
$num_semana_seleccionada = 0;

// Buscar el número de semana seleccionada
foreach ($todasSemanas as $semana) {
    if ($semana['fecha'] === $semana_seleccionada) {
        $num_semana_seleccionada = $semana['num_semana'];
        break;
    }
}

// Si no hay semana seleccionada, usar la primera por defecto
if (empty($semana_seleccionada) && !empty($todasSemanas)) {
    $semana_seleccionada = $todasSemanas[0]['fecha'];
    $num_semana_seleccionada = $todasSemanas[0]['num_semana'];
}

// ==================================================
// MENÚS DE ENERO 2026 POR SEMANA
// ==================================================

$menus_enero_2026 = [
    1 => [ // Semana 1: 5-9 Enero 2026
        'lunes' => [
            'desayuno' => [
                'icono' => '☕', // Café
                'icono2' => '🍵', // Té
                'nombre' => 'Café o Té',
                'icono3' => '🍈', // Melón
                'icono4' => '🥣', // Yogurt
                'descripcion' => 'Melón con yogurt',
                'icono5' => '🍳', // Huevo
                'icono6' => '🌭', // Chorizo
                'icono7' => '🫘', // Frijoles
                'detalle' => 'Huevo con chorizo y frijoles'
            ],
            'comida' => [
                'icono' => '🍚', // Arroz
                'nombre' => 'Arroz amarillo',
                'icono2' => '🍗', // Pollo
                'descripcion' => 'Pollo encacahuatado con frijoles',
                'icono3' => '🍮', // Gelatina
                'detalle' => 'Gelatina fresa de leche',
                'icono4' => '🧃', // Agua
                'bebida' => 'Agua de Jamaica'
            ]
        ],
        'martes' => [
            'desayuno' => [
                'icono' => '☕', // Café
                'icono2' => '🍵', // Té
                'nombre' => 'Café o Té',
                'icono3' => '🌮', // Entomatadas
                'descripcion' => 'Entomatadas con huevo'
            ],
            'comida' => [
                'icono' => '🍲', // Consomé
                'nombre' => 'Consome de res',
                'icono2' => '🥩', // Pacholas
                'descripcion' => 'Pacholas con ensalada',
                'icono3' => '🍌', // Plátanos
                'detalle' => 'Plátanos con crema',
                'icono4' => '🧃', // Agua
                'bebida' => 'Agua de limón'
            ]
        ],
        'miercoles' => [
            'desayuno' => [
                'icono' => '☕', // Café
                'icono2' => '🍵', // Té
                'nombre' => 'Café o Té',
                'icono3' => '🍈', // Papaya
                'icono4' => '🍈', // Melón
                'descripcion' => 'Papaya con melón',
                'icono5' => '🫓', // Sincronizadas
                'detalle' => 'Sincronizadas (2 piezas)'
            ],
            'comida' => [
                'icono' => '🍝', // Espagueti
                'nombre' => 'Espagueti a la diabla',
                'icono2' => '🥩', // Costillas
                'descripcion' => 'Costillas BBQ con puré de papa',
                'icono3' => '🍦', // Helado
                'detalle' => 'Helado de fresa',
                'icono4' => '🧃', // Agua
                'bebida' => 'Agua de fresa'
            ]
        ],
        'jueves' => [
            'desayuno' => [
                'icono' => '☕', // Café
                'icono2' => '🍵', // Té
                'nombre' => 'Café o Té',
                'icono3' => '🌶️', // Chilaquiles
                'icono4' => '🍗', // Pollo
                'descripcion' => 'Chilaquiles de morita con pollo'
            ],
            'comida' => [
                'icono' => '🍄', // Hongos
                'nombre' => 'Sopa de hongos',
                'icono2' => '🥗', // Ensalada
                'icono3' => '🐟', // Atún
                'descripcion' => 'Ensalada con pasta y atún',
                'icono4' => '🍮', // Gelatina
                'detalle' => 'Gelatina mosaico',
                'icono5' => '🧃', // Agua
                'bebida' => 'Agua de horchata'
            ]
        ],
        'viernes' => [
            'desayuno' => [
                'icono' => '☕', // Café
                'icono2' => '🍵', // Té
                'nombre' => 'Café o Té',
                'icono3' => '🥛', // Atole
                'descripcion' => 'Atole de cajeta',
                'icono4' => '🌭', // Torta
                'detalle' => 'Torta de salchicha'
            ],
            'comida' => [
                'icono' => '🍜', // Sopa
                'nombre' => 'Sopa de fideo',
                'icono2' => '🥧', // Pastel
                'descripcion' => 'Pastel de verdura con ensalada',
                'icono3' => '🍰', // Choux
                'detalle' => 'Choux',
                'icono4' => '🧃', // Agua
                'bebida' => 'Agua de piña colada'
            ]
        ]
    ],
    2 => [ // Semana 2: 12-16 Enero 2026
        'lunes' => [
            'desayuno' => [
                'icono' => '☕', // Café
                'icono2' => '🍵', // Té
                'nombre' => 'Café o Té',
                'icono3' => '🥞', // Hot cakes
                'descripcion' => 'Hot cake con chocochips',
                'icono4' => '🍳', // Omelette
                'detalle' => 'Omelette de pierna con frijoles'
            ],
            'comida' => [
                'icono' => '🍚', // Arroz
                'nombre' => 'Arroz rojo',
                'icono2' => '🥩', // Bistec
                'descripcion' => 'Bistec a la mexicana con frijoles',
                'icono3' => '🍩', // Buñuelos
                'detalle' => 'Buñuelos',
                'icono4' => '🧃', // Agua
                'bebida' => 'Agua de naranja'
            ]
        ],
        'martes' => [
            'desayuno' => [
                'icono' => '☕', // Café
                'icono2' => '🍵', // Té
                'nombre' => 'Café o Té',
                'icono3' => '🍈', // Papaya
                'icono4' => '🥣', // Yogurt
                'descripcion' => 'Papaya con yogurt',
                'icono5' => '🌶️', // Chilaquiles
                'icono6' => '🍗', // Pollo
                'detalle' => 'Chilaquiles suizos con pollo'
            ],
            'comida' => [
                'icono' => '🍲', // Sopa
                'nombre' => 'Sopa de munición',
                'icono2' => '🥩', // Chuleta
                'descripcion' => 'Chuleta natural con papas al romero',
                'icono3' => '🍮', // Gelatina
                'detalle' => 'Gelatina bicolor',
                'icono4' => '🧃', // Agua
                'bebida' => 'Agua de papaya'
            ]
        ],
        'miercoles' => [
            'desayuno' => [
                'icono' => '☕', // Café
                'icono2' => '🍵', // Té
                'nombre' => 'Café o Té',
                'icono3' => '🍈', // Melón
                'descripcion' => 'Melón',
                'icono4' => '🥪', // Sándwich
                'detalle' => 'Sándwich de pechuga de pollo y manchego'
            ],
            'comida' => [
                'icono' => '🍚', // Arroz
                'nombre' => 'Arroz verde',
                'icono2' => '🍗', // Pollo
                'descripcion' => 'Pollo a la cacerola con frijoles',
                'icono3' => '🥧', // Strudell
                'detalle' => 'Strudell de manzana',
                'icono4' => '🧃', // Agua
                'bebida' => 'Agua de sandía'
            ]
        ],
        'jueves' => [
            'desayuno' => [
                'icono' => '☕', // Café
                'icono2' => '🍵', // Té
                'nombre' => 'Café o Té',
                'icono3' => '🍉', // Sandía
                'icono4' => '🍍', // Piña
                'descripcion' => 'Sandía con piña',
                'icono5' => '🌯', // Burrito
                'detalle' => 'Burrito de pastor'
            ],
            'comida' => [
                'icono' => '🥣', // Arriero
                'nombre' => 'Arriero de garbanzos',
                'icono2' => '🌮', // Tacos
                'descripcion' => 'Tacos dorados de papa (4 piezas)',
                'icono3' => '🍮', // Flan
                'detalle' => 'Flan de vainilla',
                'icono4' => '🧃', // Agua
                'bebida' => 'Agua de pepino'
            ]
        ],
        'viernes' => [
            'desayuno' => [
                'icono' => '☕', // Café
                'icono2' => '🍵', // Té
                'nombre' => 'Café o Té',
                'icono3' => '🌶️', // Enchiladas
                'descripcion' => 'Enchiladas suizas'
            ],
            'comida' => [
                'icono' => '🍝', // Codito
                'nombre' => 'Codito carbonara',
                'icono2' => '🦀', // Surimi
                'descripcion' => 'Tostadas de surimi (3 piezas)',
                'icono3' => '🍰', // Pastel
                'detalle' => 'Pastel imposible',
                'icono4' => '🌾', // Amaranto
                'bebida' => 'Agua de amaranto'
            ]
        ]
    ],
    3 => [ // Semana 3: 19-23 Enero 2026
        'lunes' => [
            'desayuno' => [
                'icono' => '☕', // Café
                'icono2' => '🍵', // Té
                'nombre' => 'Café o Té',
                'icono3' => '🍈', // Melón
                'icono4' => '🍍', // Piña
                'descripcion' => 'Melón con piña',
                'icono5' => '🍳', // Omelette
                'detalle' => 'Omelette de jamón'
            ],
            'comida' => [
                'icono' => '🥣', // Sopa
                'nombre' => 'Sopa de verdura',
                'icono2' => '🍗', // Pollo
                'descripcion' => 'Pollo a las finas hierbas con verduras mantequilla',
                'icono3' => '🍮', // Gelatina
                'detalle' => 'Gelatina bicolor leche',
                'icono4' => '🧃', // Agua
                'bebida' => 'Agua de melón'
            ]
        ],
        'martes' => [
            'desayuno' => [
                'icono' => '☕', // Café
                'icono2' => '🍵', // Té
                'nombre' => 'Café o Té',
                'icono3' => '🌶️', // Enchiladas
                'descripcion' => 'Enchiladas potosinas'
            ],
            'comida' => [
                'icono' => '🍚', // Arroz
                'nombre' => 'Arroz blanco',
                'icono2' => '🐟', // Pescado
                'descripcion' => 'Filete de pescado empanizado con ensalada',
                'icono3' => '🥧', // Panqué
                'detalle' => 'Panqué de nata',
                'icono4' => '🧃', // Agua
                'bebida' => 'Agua de guayaba'
            ]
        ],
        'miercoles' => [
            'desayuno' => [
                'icono' => '☕', // Café
                'icono2' => '🍵', // Té
                'nombre' => 'Café o Té',
                'icono3' => '🍎', // Manzana
                'icono4' => '🥣', // Yogurt
                'descripcion' => 'Manzana con yogurt',
                'icono5' => '🌮', // Quesadillas
                'detalle' => 'Quesadillas de jamón'
            ],
            'comida' => [
                'icono' => '🍲', // Consomé
                'nombre' => 'Consome de pollo',
                'icono2' => '🥦', // Brócoli
                'descripcion' => 'Tortitas de brócoli con ensalada',
                'icono3' => '🍮', // Flan
                'detalle' => 'Flan napolitano',
                'icono4' => '🍵', // Té helado
                'bebida' => 'Agua de té helado'
            ]
        ],
        'jueves' => [
            'desayuno' => [
                'icono' => '☕', // Café
                'icono2' => '🍵', // Té
                'nombre' => 'Café o Té',
                'icono3' => '🥞', // Hot cakes
                'descripcion' => 'Hot cake de avena',
                'icono4' => '🍉', // Sandía
                'detalle' => 'Sandía'
            ],
            'comida' => [
                'icono' => '🍜', // Sopa
                'nombre' => 'Sopa de lengüita',
                'icono2' => '🐷', // Cerdo
                'descripcion' => 'Cerdo en pasilla con papas y frijoles',
                'icono3' => '🍰', // Pastel
                'detalle' => 'Pastel de rompope',
                'icono4' => '🍹', // Mojito
                'bebida' => 'Agua de mojito'
            ]
        ],
        'viernes' => [
            'desayuno' => [
                'icono' => '☕', // Café
                'icono2' => '🍵', // Té
                'nombre' => 'Café o Té',
                'icono3' => '🥛', // Atole
                'descripcion' => 'Atole de fresa',
                'icono4' => '🥪', // Torta
                'detalle' => 'Torta de jamón'
            ],
            'comida' => [
                'icono' => '🍔', // Hamburguesa
                'nombre' => 'Hamburguesa con papas a la francesa',
                'icono2' => '🍦', // Helado
                'descripcion' => 'Helado napolitano',
                'icono3' => '🧃', // Agua
                'bebida' => 'Agua de limonada'
            ]
        ]
    ],
    4 => [ // Semana 4: 26-30 Enero 2026
        'lunes' => [
            'desayuno' => [
                'icono' => '☕', // Café
                'icono2' => '🍵', // Té
                'nombre' => 'Café o Té',
                'icono3' => '🍌', // Plátano
                'icono4' => '🥣', // Yogurt
                'descripcion' => 'Plátano con yogurt',
                'icono5' => '🌯', // Burrito
                'detalle' => 'Burrito norteño'
            ],
            'comida' => [
                'icono' => '🍝', // Espagueti
                'nombre' => 'Espagueti alfredo',
                'icono2' => '🥩', // Chuleta
                'descripcion' => 'Chuleta ahumada con papas al ajillo y frijoles',
                'icono3' => '🍮', // Gelatina
                'detalle' => 'Gelatina de frutos rojos',
                'icono4' => '🧃', // Agua
                'bebida' => 'Agua de piña'
            ]
        ],
        'martes' => [
            'desayuno' => [
                'icono' => '☕', // Café
                'icono2' => '🍵', // Té
                'nombre' => 'Café o Té',
                'icono3' => '🥞', // Hot cakes
                'descripcion' => 'Hot cake de amaranto',
                'icono4' => '🍳', // Omelette
                'detalle' => 'Omelette de espinacas'
            ],
            'comida' => [
                'icono' => '🍜', // Sopa
                'nombre' => 'Sopa aguada codito',
                'icono2' => '🥩', // Tortitas
                'descripcion' => 'Tortitas de carne en morita con frijoles',
                'icono3' => '🧁', // Cup cake
                'detalle' => 'Cup cake fresa',
                'icono4' => '🧃', // Agua
                'bebida' => 'Agua de tamarindo'
            ]
        ],
        'miercoles' => [
            'desayuno' => [
                'icono' => '☕', // Café
                'icono2' => '🍵', // Té
                'nombre' => 'Café o Té',
                'icono3' => '🥣', // Yogurt
                'icono4' => '🥜', // Granola
                'descripcion' => 'Yogurt con granola',
                'icono5' => '🍳', // Huevos
                'detalle' => 'Huevos cocoyoc'
            ],
            'comida' => [
                'icono' => '🍚', // Arroz
                'nombre' => 'Arroz blanco',
                'icono2' => '🐟', // Pescado
                'descripcion' => 'Pescado rebosado con ensalada',
                'icono3' => '🍮', // Gelatina
                'detalle' => 'Gelatina bicolor agua',
                'icono4' => '🧃', // Agua
                'bebida' => 'Agua de frutas tropicales'
            ]
        ],
        'jueves' => [
            'desayuno' => [
                'icono' => '☕', // Café
                'icono2' => '🍵', // Té
                'nombre' => 'Café o Té',
                'icono3' => '🍈', // Papaya
                'descripcion' => 'Papaya',
                'icono4' => '🌯', // Wrap
                'detalle' => 'Wrap de pollo'
            ],
            'comida' => [
                'icono' => '🍚', // Arroz
                'nombre' => 'Arroz rojo',
                'icono2' => '🍗', // Pollo
                'descripcion' => 'Pollo en salsa verde con papas y frijoles',
                'icono3' => '🥧', // Panqué
                'detalle' => 'Panqué de naranja',
                'icono4' => '🧃', // Agua
                'bebida' => 'Agua de pepino con limón'
            ]
        ],
        'viernes' => [
            'desayuno' => [
                'icono' => '☕', // Café
                'icono2' => '🍵', // Té
                'nombre' => 'Café o Té',
                'icono3' => '🥤', // Licuado
                'descripcion' => 'Licuado de chocoplátano',
                'icono4' => '🥪', // Sándwich
                'detalle' => 'Sándwich de jamón y panela'
            ],
            'comida' => [
                'icono' => '🍲', // Consomé
                'nombre' => 'Consome de verduras',
                'icono2' => '🍝', // Lasaña
                'descripcion' => 'Lasaña vegetariana con ensalada',
                'icono3' => '🍮', // Gelatina
                'detalle' => 'Gelatina mosaico',
                'icono4' => '🧃', // Agua
                'bebida' => 'Agua de naranjada'
            ]
        ]
    ]
];

// Seleccionar el menú a mostrar
$menu_a_mostrar = isset($menus_enero_2026[$num_semana_seleccionada]) 
    ? $menus_enero_2026[$num_semana_seleccionada] 
    : $menus_enero_2026[1]; // Por defecto semana 1

// ==================================================
// FUNCIÓN PARA MOSTRAR ÍTEMS DE COMIDA CON ICONOS
// ==================================================

function mostrarItemComida($item, $tipo = 'main') {
    $html = '';
    
    if ($tipo === 'main') {
        $html .= '<div class="meal-item main">';
        $html .= '<span class="item-icon">' . ($item['icono'] ?? '🍽️') . '</span>';
        if (isset($item['icono2'])) {
            $html .= '<span class="item-icon">' . $item['icono2'] . '</span>';
        }
        $html .= '<span>' . ($item['nombre'] ?? 'Menú del día') . '</span>';
        $html .= '</div>';
    }
    
    if (!empty($item['descripcion'])) {
        $html .= '<div class="meal-item detail">';
        $html .= '<span class="item-icon">' . ($item['icono2'] ?? ($item['icono3'] ?? '🍽️')) . '</span>';
        $html .= '<span>' . $item['descripcion'] . '</span>';
        $html .= '</div>';
    }
    
    if (!empty($item['detalle'])) {
        $html .= '<div class="meal-item detail">';
        $html .= '<span class="item-icon">' . ($item['icono3'] ?? ($item['icono4'] ?? '🥗')) . '</span>';
        $html .= '<span>' . $item['detalle'] . '</span>';
        $html .= '</div>';
    }
    
    if (!empty($item['bebida'])) {
        $html .= '<div class="meal-item bebida">';
        $html .= '<span class="item-icon">' . ($item['icono4'] ?? ($item['icono5'] ?? '🥤')) . '</span>';
        $html .= '<span>' . $item['bebida'] . '</span>';
        $html .= '</div>';
    }
    
    return $html;
}

// ==================================================
// OBTENER NOMBRE DE LA SEMANA PARA EL TÍTULO
// ==================================================

$titulo_semana = 'Selecciona una semana';
$rango_fechas = '';
if ($num_semana_seleccionada > 0) {
    switch ($num_semana_seleccionada) {
        case 1:
            $titulo_semana = 'Semana 1: 5-9 Enero 2026';
            $rango_fechas = '5 al 9 de Enero 2026';
            break;
        case 2:
            $titulo_semana = 'Semana 2: 12-16 Enero 2026';
            $rango_fechas = '12 al 16 de Enero 2026';
            break;
        case 3:
            $titulo_semana = 'Semana 3: 19-23 Enero 2026';
            $rango_fechas = '19 al 23 de Enero 2026';
            break;
        case 4:
            $titulo_semana = 'Semana 4: 26-30 Enero 2026';
            $rango_fechas = '26 al 30 de Enero 2026';
            break;
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sistema de Pedidos - Comedor Enero 2026</title>
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
      --desayuno-color: #f59e0b;
      --comida-color: #10b981;
      --semana-1: #4a80b5;
      --semana-2: #10b981;
      --semana-3: #8b5cf6;
      --semana-4: #f59e0b;
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

    /* ==================================================
       ESTILO PREMIUM PARA EL BOTÓN DE CONFIRMACIÓN
       ================================================== */

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

    .week-indicator.semana-1 {
      color: var(--semana-1);
      border-color: rgba(74, 128, 181, 0.3);
      background: rgba(74, 128, 181, 0.1);
    }

    .week-indicator.semana-2 {
      color: var(--semana-2);
      border-color: rgba(16, 185, 129, 0.3);
      background: rgba(16, 185, 129, 0.1);
    }

    .week-indicator.semana-3 {
      color: var(--semana-3);
      border-color: rgba(139, 92, 246, 0.3);
      background: rgba(139, 92, 246, 0.1);
    }

    .week-indicator.semana-4 {
      color: var(--semana-4);
      border-color: rgba(245, 158, 11, 0.3);
      background: rgba(245, 158, 11, 0.1);
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
    <span><?php echo date('d/m/Y H:i'); ?></span>
  </div>
</div>

<!-- Título del menú -->
<div class="menu-title">
  <i class="fas fa-utensils"></i>
  <?php if ($num_semana_seleccionada > 0): ?>
    Menú Enero 2026 - <?php echo $titulo_semana; ?>
  <?php else: ?>
    Menú Enero 2026 - Sistema de Pedidos
  <?php endif; ?>
</div>

<!-- Indicador de semana -->
<?php if ($num_semana_seleccionada > 0): ?>
<div class="week-indicator semana-<?php echo $num_semana_seleccionada; ?>">
  <i class="fas fa-calendar-week"></i>
  Semana del <?php echo $rango_fechas; ?>
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
      foreach ($todasSemanas as $semana): 
        $selected = ($semana['fecha'] == $semana_seleccionada) ? 'selected' : '';
      ?>
        <option value="<?php echo $semana['fecha']; ?>" <?php echo $selected; ?>>
          <?php echo $semana['mostrar']; ?>
        </option>
      <?php endforeach; ?>
    </select>
    <span class="field-note">Selecciona una semana de enero 2026</span>
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
        $menu_dia = $menu_a_mostrar[$clave];
    ?>
    <div class='day-card'>
        <h3><?php echo $dia_nombre; ?></h3>
        
        <!-- Desayuno -->
        <label class='meal-option'>
            <input type='radio' name='gender<?php echo $desayuno_id; ?>' value='Desayuno' class='toggle-radio'>
            <div class='meal-details'>
                <div class='meal-type desayuno'>
                    <i class="fas fa-egg"></i> Desayuno
                </div>
                <div class='meal-items'>
                    <?php echo mostrarItemComida($menu_dia['desayuno']); ?>
                </div>
            </div>
        </label>
        
        <!-- Comida -->
        <label class='meal-option'>
            <input type='radio' name='gender<?php echo $comida_id; ?>' value='Comida' class='toggle-radio'>
            <div class='meal-details'>
                <div class='meal-type comida'>
                    <i class="fas fa-utensils"></i> Comida
                </div>
                <div class='meal-items'>
                    <?php echo mostrarItemComida($menu_dia['comida']); ?>
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
      const form = document.getElementById('menuForm');
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
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
    submitBtn.disabled = true;
    
    // Permitir el envío del formulario
    return true;
  });

  // Auto-seleccionar la primera semana disponible
  window.addEventListener('load', function() {
    if (fechaSelect && fechaSelect.options.length > 1 && !fechaSelect.value) {
      // Seleccionar automáticamente la primera opción (primera semana disponible)
      fechaSelect.selectedIndex = 1;
      // Sincronizar con el campo hidden
      fechaHidden.value = fechaSelect.value;
    }
  });

  // Marcar automáticamente la semana 1 si está disponible
  document.addEventListener('DOMContentLoaded', function() {
    const semana1Option = document.querySelector('option[value="2026-01-05"]');
    if (semana1Option && !fechaSelect.value) {
      // Seleccionar automáticamente la semana 1 si está disponible
      semana1Option.selected = true;
      fechaHidden.value = '2026-01-05';
      
      // Si no hay parámetro de semana en la URL, recargar para mostrar el menú
      const urlParams = new URLSearchParams(window.location.search);
      if (!urlParams.has('semana')) {
        urlParams.set('semana', '2026-01-05');
        window.location.href = window.location.pathname + '?' + urlParams.toString();
      }
    }
  });

  // Efecto de brillo aleatorio en el botón
  const buttonShine = document.querySelector('.button-shine');
  if (buttonShine) {
    setInterval(() => {
      buttonShine.style.animation = 'none';
      setTimeout(() => {
        buttonShine.style.animation = 'shine 3s infinite linear';
      }, 10);
    }, 10000); // Cambia el brillo cada 10 segundos
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
  }, 5000); // Pulsa cada 5 segundos
</script>

</body>
</html>