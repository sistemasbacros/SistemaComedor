<?php
$pedido = $name = $email = $gender = $comment = $website = "";

// Conexión a la base de datos SQL Server
$serverName = "DESAROLLO-BACRO\SQLEXPRESS"; //serverName\instanceName
$connectionInfo = array("Database"=>"Comedor", "UID"=>"Larome03", "PWD"=>"Larome03","CharacterSet" => "UTF-8");
$conn = sqlsrv_connect($serverName, $connectionInfo);

// Consulta SQL para obtener las cancelaciones
$sql = "SELECT * FROM cancelaciones WHERE convert(date, FECHA, 102) > '2025-10-05' ORDER BY Nombre";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Inicialización de arrays para almacenar los datos
$array_tot1 = [];
$array_tot2 = [];
$array_tot3 = [];
$array_tot4 = [];
$array_tot5 = [];
$array_tot6 = [];
$array_tot7 = [];

// Recorrido de los resultados y llenado de los arrays
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    array_push($array_tot1, $row['NOMBRE']);
    array_push($array_tot2, $row['DEPARTAMENTO']);
    array_push($array_tot3, $row['JEFE']);
    array_push($array_tot4, $row['TIPO_CONSUMO']);
    array_push($array_tot5, $row['FECHA']);
    array_push($array_tot6, $row['CAUSA']);
    array_push($array_tot7, $row['ESTATUS']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cancelaciones Comedor</title>

    <!-- Inclusión de Bootstrap y DataTables CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap.min.css" rel="stylesheet">

    <!-- Script de JQuery y DataTables -->
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>

    <style>
        /* Estilos para la tabla */
        .table td {
            font-size: 12px;
            font-weight: bold;
        }

        .table th {
            font-size: 15px;
            color: white;
            background: #1E4E79;
            font-weight: bold;
        }

        .img-container img {
            width: 7%;
            height: 10%;
        }
    </style>
</head>

<body>

    <!-- Imagen del logo -->
    <div class="img-container">
        <img src="Logo2.png" alt="Logo">
    </div>

    <!-- Enlace al menú principal -->
    <p><a href="http://192.168.100.95/Comedor">Menú principal</a></p>

    <div class="inner1">
        <!-- Tabla para mostrar los datos -->
        <table id="example" class="table table-striped table-bordered" width="100%" style="font-size:100%; background-color:#2D6DA6; color:white; font-weight: bold;"></table>
    </div>

    <script>
        // Pasamos los datos de PHP a JavaScript
        var dataQ1 = <?php echo json_encode($array_tot1); ?>;
        var dataQ2 = <?php echo json_encode($array_tot2); ?>;
        var dataQ3 = <?php echo json_encode($array_tot3); ?>;
        var dataQ4 = <?php echo json_encode($array_tot4); ?>;
        var dataQ5 = <?php echo json_encode($array_tot5); ?>;
        var dataQ6 = <?php echo json_encode($array_tot6); ?>;
        var dataQ7 = <?php echo json_encode($array_tot7); ?>;

        // Generamos el array de datos para la tabla
        var dataSet = [];

        for (let i = 0; i < dataQ1.length; i++) {
            dataSet.push([dataQ1[i], dataQ2[i], dataQ3[i], dataQ4[i], dataQ5[i], dataQ6[i], dataQ7[i]]);
        }

        // Inicializamos la tabla DataTable
        $(document).ready(function () {
            $('#example').DataTable({
                columns: [
                    { title: 'Nombre' },
                    { title: 'Departamento' },
                    { title: 'Jefe inmediato' },
                    { title: 'Tipo de consumo a cancelar' },
                    { title: 'Fecha' },
                    { title: 'Causa' },
                    { title: 'Estatus' }
                ],
                responsive: true,
                data: dataSet,
                paging: false
            });
        });
    </script>

</body>
</html>
