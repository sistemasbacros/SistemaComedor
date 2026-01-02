<?php
// --- Conexión a SQL Server ---
$serverName = "DESAROLLO‑BACRO\\SQLEXPRESS";
$connectionOptions = [
    "Database" => "Comedor",
    "Uid" => "Larome03",
    "PWD" => "Larome03",
    "CharacterSet" => "UTF-8"
];
$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'es', 'spanish');

$hoy = new DateTime();
$anioActual = (int)$hoy->format("Y");
$mesActual = (int)$hoy->format("m");

// --- LÓGICA PARA SEMANAS QUE EMPIEZAN EN EL MES (pueden terminar en otro mes) ---
function obtenerSemanasDelMes($anio, $mes) {
    $semanas = [];
    
    // Primer día del mes
    $primerDia = new DateTime("$anio-$mes-01");
    // Último día del mes
    $ultimoDia = new DateTime("$anio-$mes-" . date('t', strtotime("$anio-$mes-01")));
    
    // Encontrar el primer lunes del mes
    $lunesActual = clone $primerDia;
    while ($lunesActual->format('N') != 1 && $lunesActual <= $ultimoDia) {
        $lunesActual->modify('+1 day');
    }
    
    $numeroSemana = 1;
    
    // Generar semanas que empiezan en este mes (lunes puede estar en este mes)
    while ($lunesActual->format('m') == $mes || $lunesActual <= $ultimoDia) {
        $viernes = clone $lunesActual;
        $viernes->modify('+4 days'); // Viernes
        
        $semanas[$numeroSemana] = [
            'inicio' => clone $lunesActual,
            'fin' => $viernes
        ];
        
        // Siguiente lunes
        $lunesActual->modify('+7 days');
        $numeroSemana++;
        
        // Salir si ya pasamos mucho del mes
        if ($lunesActual->format('m') > $mes && $lunesActual->format('Y') > $anio) {
            break;
        }
    }
    
    return $semanas;
}

function formatoFechaEsp(DateTime $dt) {
    return ucfirst(strftime("%d %B %Y", $dt->getTimestamp()));
}

// ✅ FUNCIÓN CON EMOJIS
function iconoPorPlatillo(string $platillo, string $tipoComida): string {
    $texto = strtolower(trim($platillo));
    $mapaEmojis = [
        'molletes' => '🍞', 'quesadillas' => '🫓', 'enchiladas' => '🌶️', 'chilaquiles' => '🥣',
        'café' => '☕', 'jugo' => '🥤', 'tortas' => '🥪', 'huevo' => '🍳', 'huevos' => '🍳',
        'hot cakes' => '🥞', 'waffles' => '🧇', 'yogurt' => '🥛', 'fruta' => '🍎', 'pan' => '🥖',
        'tamal' => '🫓', 'atole' => '🥣', 'elote' => '🌽', 'tacos' => '🌮', 'flautas' => '🌯',
        'burritos' => '🌯', 'mole' => '🍛', 'pozole' => '🍲', 'birria' => '🥘', 'carnitas' => '🐖',
        'pollo' => '🍗', 'pescado' => '🐟', 'carne' => '🥩', 'arroz' => '🍚', 'frijoles' => '🫘',
        'sopa' => '🍜', 'ensalada' => '🥗', 'agua' => '💧', 'refresco' => '🥤', 'postre' => '🍮'
    ];
    
    $emoji = '🍽️';

    foreach ($mapaEmojis as $clave => $icono) {
        if (strpos($texto, $clave) !== false) {
            $emoji = $icono;
            break;
        }
    }

    return "<span class='icon' title='" . htmlspecialchars($platillo) . "'>$emoji</span> " . htmlspecialchars(trim($platillo));
}

// Consulta SQL - incluir también días del siguiente mes si la semana termina ahí
$mesSiguiente = $mesActual == 12 ? 1 : $mesActual + 1;
$anioSiguiente = $mesActual == 12 ? $anioActual + 1 : $anioActual;

$sql = "SELECT id, fecha, tipo_comida, descripcion FROM menu 
        WHERE (YEAR(fecha) = ? AND MONTH(fecha) = ?) 
        OR (YEAR(fecha) = ? AND MONTH(fecha) = ?)
        ORDER BY fecha, tipo_comida";
$params = [$anioActual, $mesActual, $anioSiguiente, $mesSiguiente];
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Obtener semanas que empiezan en este mes (pueden terminar en otro mes)
$semanasDelMes = obtenerSemanasDelMes($anioActual, $mesActual);

// Procesar menús
$menus = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $fecha = new DateTime($row['fecha']->format('Y-m-d'));
    $tipo = strtolower($row['tipo_comida']);
    $desc = $row['descripcion'];
    
    // Encontrar a qué semana pertenece esta fecha
    foreach ($semanasDelMes as $numeroSemana => $semana) {
        if ($fecha >= $semana['inicio'] && $fecha <= $semana['fin']) {
            $diaSemana = (int)$fecha->format('N'); // 1=lunes, 5=viernes
            
            if (!isset($menus[$numeroSemana][$diaSemana][$tipo])) {
                $menus[$numeroSemana][$diaSemana][$tipo] = [];
            }
            
            $items = array_filter(array_map('trim', explode(',', $desc)));
            foreach ($items as $pl) {
                $menus[$numeroSemana][$diaSemana][$tipo][] = $pl;
            }
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Menú Semanal</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap">
<style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Montserrat', sans-serif;
      margin: 30px auto;
      max-width: 900px;
      color: #2c3e50;
      background: url('https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1920&q=80') no-repeat center center fixed;
      background-size: cover;
      line-height: 1.7;
    }

    body::before {
      content: "";
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(44, 62, 80, 0.6);
      z-index: -1;
    }

    header {
      text-align: center;
      font-size: 2.8rem;
      font-weight: 700;
      margin: 30px 0 40px;
      color: #a8d8f0;
      text-shadow: 0 3px 8px rgba(17, 63, 101, 0.8);
      letter-spacing: 1px;
    }

    .btn-container {
      text-align: center;
      margin-bottom: 40px;
    }

    button {
      background: linear-gradient(145deg, #89abe3, #5778a3);
      border: none;
      color: white;
      padding: 14px 28px;
      margin: 8px;
      border-radius: 15px;
      font-size: 1.1rem;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 6px 10px rgba(87, 120, 163, 0.6);
      transition: all 0.25s ease;
      min-width: 120px;
    }

    button:hover {
      background: linear-gradient(145deg, #aacde5, #7da2cc);
      transform: translateY(-2px);
    }

    button.active {
      background: linear-gradient(145deg, #5d8aa8, #3a5a7a);
      transform: scale(1.05);
    }

    .menu-section {
      display: none;
      padding: 30px;
      border-radius: 16px;
      margin-bottom: 50px;
      background-color: rgba(255, 255, 255, 0.9);
      box-shadow: 0 10px 30px rgba(44, 62, 80, 0.3);
      backdrop-filter: blur(10px);
      animation: fadeIn 0.5s ease-out;
    }

    .menu-section.active {
      display: block;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .menu-section h2 {
      text-align: center;
      font-size: 1.8rem;
      font-weight: 700;
      color: #5d8aa8;
      margin-bottom: 25px;
    }

    table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0 12px;
      font-size: 1rem;
    }

    th, td {
      padding: 14px 16px;
      text-align: center;
      vertical-align: middle;
    }

    th {
      background-color: #d6e4f0;
      color: #2c3e50;
      font-weight: 700;
      font-size: 1.1rem;
      border-radius: 10px;
      padding: 16px;
    }

    tbody tr {
      background-color: #f8fbfe;
      border-radius: 10px;
      box-shadow: 0 3px 10px rgba(44, 62, 80, 0.1);
    }

    td {
      border-radius: 8px;
      background-color: #ffffff;
    }

    td span.icon {
      margin-right: 6px;
      font-size: 1.2rem;
      vertical-align: middle;
    }

    .info-mes {
      text-align: center;
      color: #a8d8f0;
      margin-bottom: 20px;
      font-size: 1.1rem;
    }

    .semana-otro-mes {
      background: linear-gradient(145deg, #ffeb3b, #fbc02d) !important;
      color: #5d4037 !important;
    }
</style>
</head>
<body>

<header>Menú de <?= strftime("%B %Y", $hoy->getTimestamp()) ?></header>

<div class="info-mes">
    <?= count($semanasDelMes) ?> semanas (pueden incluir días del siguiente mes)
</div>

<div class="btn-container" role="tablist">
<?php if (empty($semanasDelMes)): ?>
    <p style="text-align: center; color: white;">No hay semanas este mes</p>
<?php else: ?>
    <?php foreach ($semanasDelMes as $numeroSemana => $semana): ?>
        <button class="<?= $numeroSemana === 1 ? 'active' : '' ?>" data-semana="semana<?= $numeroSemana ?>">
            Semana <?= $numeroSemana ?>
            <?php if ($semana['fin']->format('m') != $mesActual): ?>
                <span style="font-size: 0.8em;">*</span>
            <?php endif; ?>
        </button>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<?php if (!empty($semanasDelMes)): ?>
    <?php foreach ($semanasDelMes as $numeroSemana => $semana): ?>
        <?php
        $activo = ($numeroSemana === 1) ? "active" : "";
        $inicio = $semana['inicio'];
        $fin = $semana['fin'];
        $terminaEnOtroMes = $fin->format('m') != $mesActual;
        ?>
        <section class='menu-section <?= $activo ?>' id='semana<?= $numeroSemana ?>'>
            <h2>
                Semana <?= $numeroSemana ?> 
                (<?= $inicio->format('d/m') ?> - <?= $fin->format('d/m') ?>)
                <?php if ($terminaEnOtroMes): ?>
                    <span style="color: #e67e22; font-size: 0.8em;">*incluye <?= $fin->format('F') ?></span>
                <?php endif; ?>
            </h2>
            <table>
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Lunes</th>
                        <th>Martes</th>
                        <th>Miércoles</th>
                        <th>Jueves</th>
                        <th>Viernes</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Desayuno -->
                    <tr>
                        <td><strong>Desayuno</strong></td>
                        <?php for ($dia = 1; $dia <= 5; $dia++): ?>
                            <?php
                            $fechaDia = clone $inicio;
                            $fechaDia->modify('+' . ($dia - 1) . ' days');
                            $esOtroMes = $fechaDia->format('m') != $mesActual;
                            ?>
                            <td class="<?= $esOtroMes ? 'semana-otro-mes' : '' ?>">
                                <?php if (isset($menus[$numeroSemana][$dia]['desayuno'])): ?>
                                    <?= implode("<br>", array_map(fn($p) => iconoPorPlatillo($p, 'desayuno'), $menus[$numeroSemana][$dia]['desayuno'])) ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                                <?php if ($esOtroMes): ?>
                                    <br><small style="color: #e67e22;"><?= $fechaDia->format('M') ?></small>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                    
                    <!-- Comida -->
                    <tr>
                        <td><strong>Comida</strong></td>
                        <?php for ($dia = 1; $dia <= 5; $dia++): ?>
                            <?php
                            $fechaDia = clone $inicio;
                            $fechaDia->modify('+' . ($dia - 1) . ' days');
                            $esOtroMes = $fechaDia->format('m') != $mesActual;
                            ?>
                            <td class="<?= $esOtroMes ? 'semana-otro-mes' : '' ?>">
                                <?php if (isset($menus[$numeroSemana][$dia]['comida'])): ?>
                                    <?= implode("<br>", array_map(fn($p) => iconoPorPlatillo($p, 'comida'), $menus[$numeroSemana][$dia]['comida'])) ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                                <?php if ($esOtroMes): ?>
                                    <br><small style="color: #e67e22;"><?= $fechaDia->format('M') ?></small>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                </tbody>
            </table>
        </section>
    <?php endforeach; ?>
<?php else: ?>
    <div style="text-align: center; color: white; padding: 40px;">
        No hay semanas este mes
    </div>
<?php endif; ?>

<script>
const botones = document.querySelectorAll(".btn-container button");
const secciones = document.querySelectorAll(".menu-section");

botones.forEach(btn => {
  btn.addEventListener("click", () => {
    botones.forEach(b => b.classList.remove("active"));
    secciones.forEach(s => s.classList.remove("active"));
    btn.classList.add("active");
    const id = btn.getAttribute("data-semana");
    document.getElementById(id).classList.add("active");
  });
});
</script>

</body>
</html>