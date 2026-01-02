<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Comedor - Registro</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- ZXing para QR -->
    <script type="text/javascript" src="https://unpkg.com/@zxing/library@latest"></script>
    
    <style>
        :root {
            --primary-color: #1e40af;
            --secondary-color: #3b82f6;
            --accent-color: #60a5fa;
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --light-color: #f8fafc;
            --dark-color: #1e293b;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #1e3a8a, #3b82f6, #60a5fa);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: var(--light-color);
            overflow-x: hidden;
        }
        
        .container-fluid {
            padding: 1rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header-section {
            text-align: center;
            padding: 1rem 0 0.5rem;
            margin-bottom: 1rem;
        }
        
        .logo-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .main-logo {
            height: 60px;
            width: auto;
            filter: brightness(0) invert(1);
        }
        
        .title-text {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--light-color);
            margin-bottom: 0.25rem;
        }
        
        .subtitle-text {
            font-size: 0.9rem;
            opacity: 0.8;
            color: var(--light-color);
        }
        
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
            border-radius: 16px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control-custom {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: var(--light-color);
            padding: 0.75rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .form-control-custom:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.4);
            color: var(--light-color);
            box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.25);
        }
        
        .form-control-custom::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .form-label {
            font-weight: 500;
            color: var(--light-color);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .btn-primary-custom {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            color: white;
            padding: 0.875rem 1.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-primary-custom:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .btn-secondary-custom {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            color: var(--light-color);
            padding: 0.75rem 1.25rem;
            font-weight: 500;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-secondary-custom:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .btn-clear-custom {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 8px;
            color: #fecaca;
            padding: 0.75rem;
            font-weight: 500;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 0.5rem;
        }
        
        .btn-clear-custom:hover {
            background: rgba(239, 68, 68, 0.3);
            transform: translateY(-1px);
        }
        
        .service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 0.75rem;
            margin-top: 1rem;
        }
        
        .service-item {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 0.75rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            min-height: 90px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .service-item:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-2px);
        }
        
        .service-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--light-color);
        }
        
        .service-title {
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--light-color);
            line-height: 1.2;
        }
        
        .video-container {
            border-radius: 8px;
            overflow: hidden;
            background: #000;
            margin-bottom: 1rem;
        }
        
        .status-display {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            font-size: 0.9rem;
            margin-top: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .total-display {
            background: var(--success-color);
            border-radius: 8px;
            padding: 0.75rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.9rem;
            margin-top: 1rem;
        }
        
        .alert-custom {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1050;
            background: rgba(30, 58, 138, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            border-left: 4px solid var(--accent-color);
            transform: translateX(120%);
            transition: transform 0.3s ease;
            max-width: 300px;
        }
        
        .alert-custom.show {
            transform: translateX(0);
        }
        
        .grid-system {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        @media (min-width: 768px) {
            .grid-system {
                grid-template-columns: 2fr 1fr;
            }
            
            .service-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (min-width: 1200px) {
            .container-fluid {
                padding: 1.5rem;
            }
            
            .service-grid {
                grid-template-columns: repeat(6, 1fr);
            }
        }
        
        .compact-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        
        @media (min-width: 576px) {
            .compact-row {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--light-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .qr-scanner {
            position: relative;
        }
        
        .qr-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
        }
        
        /* Formas flotantes para el fondo */
        .floating-shapes {
            position: fixed;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        
        .shape {
            position: absolute;
            opacity: 0.1;
            border-radius: 50%;
            background: white;
            animation: float 15s infinite linear;
        }
        
        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-duration: 20s;
        }
        
        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            left: 80%;
            animation-duration: 25s;
        }
        
        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            top: 80%;
            left: 20%;
            animation-duration: 15s;
        }
        
        .shape:nth-child(4) {
            width: 100px;
            height: 100px;
            top: 30%;
            left: 70%;
            animation-duration: 30s;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
            100% {
                transform: translateY(0) rotate(360deg);
            }
        }
        
        .input-group-custom {
            display: flex;
            flex-direction: column;
        }
        
        @media (min-width: 576px) {
            .input-group-custom {
                flex-direction: row;
                gap: 0.5rem;
            }
            
            .input-group-custom .form-control-custom {
                flex: 1;
            }
            
            .input-group-custom .btn-clear-custom {
                margin-top: 0;
                width: auto;
                min-width: 120px;
            }
        }
    </style>
</head>
<body>
    <!-- Formas flotantes de fondo -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <!-- Alert Custom -->
    <div id="customAlert" class="alert-custom">
        <div class="d-flex align-items-center">
            <i class="bi bi-info-circle me-2" style="color: var(--accent-color);"></i>
            <div>
                <div class="fw-bold" id="alertTitle">Notificación</div>
                <div id="alertMessage" class="small">Mensaje del sistema</div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Header -->
        <div class="header-section">
            <div class="logo-container">
                <img src="Logo2.png" class="main-logo" alt="Logo Comedor">
            </div>
            <h1 class="title-text">Sistema de Comedor</h1>
            <p class="subtitle-text">Registro con código QR</p>
        </div>

        <form name="formulario1" method="POST" action="">
            <div class="grid-system">
                <!-- Main Content -->
                <div class="glass-card">
                    <div class="section-title">
                        <i class="bi bi-person-plus"></i>
                        Registro de Usuario
                    </div>
                    
                    <div class="compact-row">
                        <div>
                            <label for="name" class="form-label">
                                <i class="bi bi-person me-1"></i>Usuario
                            </label>
                            <input type="text" name="name" id="name" class="form-control-custom" placeholder="Escanee código QR o ingrese manualmente">
                        </div>
                        <div>
                            <label for="cot" class="form-label">
                                <i class="bi bi-tag me-1"></i>Complemento
                            </label>
                            <div class="input-group-custom">
                                <input type="text" name="cot" id="cot" class="form-control-custom" placeholder="Seleccione un complemento" readonly>
                                <button type="button" id="btnClearComplement" class="btn-clear-custom">
                                    <i class="bi bi-x-circle me-1"></i>Limpiar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="compact-row">
                        <button type="submit" id="submit" name="submit" class="btn-primary-custom">
                            <i class="bi bi-check-circle me-2"></i>Registrarme
                        </button>
                        <button type="button" id="btnReimprimir" class="btn-secondary-custom">
                            <i class="bi bi-printer me-2"></i>Reimprimir Ticket
                        </button>
                    </div>
                    
                    <div class="total-display">
                        <span id="result1" name="result1">Platillos disponibles: Cargando...</span>
                    </div>
                    
                    <div class="status-display">
                        <i class="bi bi-qr-code-scan me-2"></i>
                        <span id="result" name="result">Listo para escanear código QR</span>
                    </div>
                </div>
                
                <!-- QR Scanner -->
                <div class="glass-card">
                    <div class="section-title">
                        <i class="bi bi-camera"></i>
                        Escáner QR
                    </div>
                    <div class="qr-scanner">
                        <div class="video-container">
                            <video id="webcam-preview" width="100%" height="200"></video>
                        </div>
                    </div>
                    <p class="small text-center opacity-75 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Enfoque el código QR en la cámara
                    </p>
                </div>
            </div>
            
            <!-- Services -->
            <div class="glass-card">
                <div class="section-title">
                    <i class="bi bi-grid-3x3-gap"></i>
                    Servicios Disponibles
                </div>
                <div class="service-grid">
                    <div class="service-item" onclick="selectService('CAFÉ O TÉ')">
                        <i class="bi bi-cup-hot service-icon"></i>
                        <div class="service-title">CAFÉ O TÉ</div>
                    </div>
                    <div class="service-item" onclick="selectService('DESECHABLE')">
                        <i class="bi bi-trash service-icon"></i>
                        <div class="service-title">DESECHABLE</div>
                    </div>
                    <div class="service-item" onclick="selectService('COMIDA PARA LLEVAR')">
                        <i class="bi bi-bag service-icon"></i>
                        <div class="service-title">COMIDA LLEVAR</div>
                    </div>
                    <div class="service-item" onclick="selectService('TORTILLAS')">
                        <i class="bi bi-circle service-icon"></i>
                        <div class="service-title">TORTILLAS</div>
                    </div>
                    <div class="service-item" onclick="selectService('AGUA')">
                        <i class="bi bi-droplet service-icon"></i>
                        <div class="service-title">AGUA</div>
                    </div>
                    <div class="service-item" onclick="showInfo()">
                        <i class="bi bi-info-circle service-icon"></i>
                        <div class="service-title">INFORMACIÓN</div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Función para mostrar alertas
        function showAlert(title, message, type = 'info') {
            const alert = document.getElementById('customAlert');
            const alertTitle = document.getElementById('alertTitle');
            const alertMessage = document.getElementById('alertMessage');
            const alertIcon = alert.querySelector('i');
            
            // Configurar según tipo
            let color = 'var(--accent-color)';
            let icon = 'bi-info-circle';
            
            if (type === 'success') {
                color = 'var(--success-color)';
                icon = 'bi-check-circle';
            } else if (type === 'error') {
                color = 'var(--warning-color)';
                icon = 'bi-exclamation-triangle';
            } else if (type === 'warning') {
                color = 'var(--warning-color)';
                icon = 'bi-exclamation-circle';
            }
            
            alert.style.borderLeftColor = color;
            alertIcon.className = `bi ${icon} me-2`;
            alertIcon.style.color = color;
            
            alertTitle.textContent = title;
            alertMessage.textContent = message;
            
            alert.classList.add('show');
            
            setTimeout(() => {
                alert.classList.remove('show');
            }, 4000);
        }
        
        // Función para seleccionar servicio
        function selectService(service) {
            document.getElementById('cot').value = service;
            document.getElementById('name').focus();
            showAlert('Complemento Seleccionado', `Has seleccionado: ${service}`, 'success');
        }
        
        // Función para limpiar complemento
        function clearComplement() {
            document.getElementById('cot').value = '';
            showAlert('Complemento Limpiado', 'El campo de complemento ha sido limpiado', 'info');
            document.getElementById('name').focus();
        }
        
        // Función para mostrar información
        function showInfo() {
            showAlert('Información del Sistema', 'Seleccione un complemento o escanee su código QR para registrarse', 'info');
        }
        
        // Función para guardar ticket
        function guardarTicket(nombre, fecha, hora, tipo) {
            const ticketData = { nombre, fecha, hora, tipo };
            localStorage.setItem('ultimoTicket', JSON.stringify(ticketData));
        }
        
        // Función para reimprimir ticket
        function reimprimirTicket() {
            const ticketData = localStorage.getItem('ultimoTicket');
            
            if (!ticketData) {
                showAlert('Reimprimir', 'No hay tickets guardados', 'warning');
                return;
            }
            
            const ticket = JSON.parse(ticketData);
            const myHTML = generarHTMLTicket(ticket.nombre, ticket.fecha, ticket.hora, ticket.tipo);
            
            const myWin = window.open("about:blank", "_blank");
            myWin.document.write(myHTML);
            myWin.print();
            myWin.close();
            
            showAlert('Reimpresión', 'Ticket reimpreso correctamente', 'success');
        }
        
        // Función para generar HTML del ticket
        function generarHTMLTicket(nombre, fecha, hora, tipo) {
            return `
                <table border="0" width="278px">
                    <tr><td><p align="center"><b><font face="Segoe UI" style="font-size: 16pt">&nbsp;</font></b><img src="/Comedor/Logo2.png" width="116" height="90"></td></tr>
                    <tr><td><p align="center"><b><font face="Segoe UI" style="font-size: 16pt">&nbsp;Ticket de Consumo</font></b></td></tr>
                    <tr><td height="28"><b><font face="Segoe UI" style="font-size: 16pt">&nbsp;Fecha: ${fecha}</font></b></td></tr>
                    <tr><td height="28"><b><font face="Segoe UI" style="font-size: 16pt">&nbsp;Hora: ${hora}</font></b></td></tr>
                    <tr><td><p align="center"><b><font face="Segoe UI" style="font-size: 16pt">&nbsp;Usuario:</font></b></td></tr>
                    <tr><td><p align="center"><b><font face="Segoe UI" style="font-size: 16pt">&nbsp;${nombre}</font></b></td></tr>
                    <tr><td style="vertical-align: top; border-color: #808080; padding: 0.02in" height="90"><b><font face="Segoe UI" style="font-size: 16pt">Consumo: ${tipo}</font></b></td></tr>
                    <tr><td style="vertical-align: top; border-color: #050505; padding: 0.02in">&nbsp;</td></tr>
                </table>
            `;
        }
        
        // Event Listeners
        document.getElementById('btnReimprimir').addEventListener('click', reimprimirTicket);
        document.getElementById('btnClearComplement').addEventListener('click', clearComplement);
        document.getElementById('name').focus();

        // QR Code Reader
        const codeReader = new ZXing.BrowserQRCodeReader();
        
        codeReader.decodeFromVideoDevice(null, 'webcam-preview', (result, err) => {
            if (result) {
                document.getElementById('result').textContent = "✅ QR detectado - Procesando...";
                document.getElementById('name').value = result.text;
                
                setTimeout(() => {
                    document.getElementById('submit').click();
                }, 800);
                
                showAlert('QR Detectado', 'Código QR escaneado correctamente', 'success');
            }
            
            if (err && !(err instanceof ZXing.NotFoundException)) {
                console.log('Error QR:', err);
            }
        });
        
        // Ajustar altura del contenedor para evitar scroll
        function adjustLayout() {
            const container = document.querySelector('.container-fluid');
            const windowHeight = window.innerHeight;
            const containerHeight = container.scrollHeight;
            
            if (containerHeight < windowHeight) {
                container.style.minHeight = '100vh';
            }
        }
        
        // Ejecutar al cargar y al redimensionar
        window.addEventListener('load', adjustLayout);
        window.addEventListener('resize', adjustLayout);
    </script>

    <?php
    // Configuración inicial
    $name = test_input($_POST["name"] ?? '');
    $cot = test_input($_POST["cot"] ?? '');
    $name2 = $fechaActual = $fechaActual1 = $Com = '';
    
    // Configuración de fechas
    $fechaActual123 = date('d-m-Y');
    $fechaTotales = date('Y-m-d');
    $firstday = date('Y-m-d', strtotime("this week"));
    
    // Conexión a base de datos
    $serverName = "DESAROLLO-BACRO\SQLEXPRESS";
    $connectionInfo = array("Database" => "Comedor", "UID" => "Larome03", "PWD" => "Larome03", "CharacterSet" => "UTF-8");
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    
    // Consulta para totales actuales
    $sql250 = "Select Hora_Entrada,Tipo_Comida,Count(*) as Total from (
        Select  *, Tipo_Comida=  case when Fecha > '12:40:00' then 'Comida' else 'Desayuno' end from [dbo].[Entradas]
        where Hora_Entrada like '%$fechaActual123%' and  not Nombre='' and  not Nombre='.') as a
        Group by Hora_Entrada,Tipo_Comida
        ORDER BY Hora_Entrada"; 
    
    $stmt150 = sqlsrv_query($conn,$sql250);
    $arrayT1 = [];
    $fechaActual1 = date('H:i:s', time()+3600);
    
    while($row = sqlsrv_fetch_array($stmt150,SQLSRV_FETCH_NUMERIC)) {
        if (strtotime($fechaActual1) < strtotime('12:40:00') && $row[1]== 'Desayuno') {
            array_push($arrayT1,$row[2]);
        } 
        if (strtotime($fechaActual1) > strtotime('12:40:00') && $row[1]== 'Comida') {
            array_push($arrayT1,$row[2]);
        } 
    }
    
    // Consulta para totales pedidos
    $sql127 = "Select * from (
        Select Fecha ,Count(*) as Total, FechaDia=DATEADD(day,0,cast(Fecha as date)),Tipo='Desayuno' from PedidosComida
        where Fecha like '%$firstday%' and Lunes like '%Desayuno%' Group by Fecha
        union all Select Fecha ,Count(*) as Total, FechaDia=DATEADD(day,0,cast(Fecha as date)) ,Tipo='Comida' from PedidosComida
        where Fecha like '%$firstday%' and Lunes like '%Comida%' Group by Fecha
        Union all Select Fecha ,Count(*) as Total, FechaDia=DATEADD(day,1,cast(Fecha as date)) ,Tipo='Desayuno' from PedidosComida
        where Fecha like '%$firstday%' and Martes like '%Desayuno%' Group by Fecha
        union all Select Fecha ,Count(*) as Total, FechaDia=DATEADD(day,1,cast(Fecha as date)),Tipo='Comida' from PedidosComida
        where Fecha like '%$firstday%' and Martes like '%Comida%' Group by Fecha
        Union all Select Fecha ,Count(*) as Total, FechaDia=DATEADD(day,2,cast(Fecha as date)) ,Tipo='Desayuno' from PedidosComida
        where Fecha like '%$firstday%' and Miercoles like '%Desayuno%' Group by Fecha
        union all Select Fecha ,Count(*) as Total, FechaDia=DATEADD(day,2,cast(Fecha as date)),Tipo='Comida' from PedidosComida
        where Fecha like '%$firstday%' and Miercoles like '%Comida%' Group by Fecha
        Union all Select Fecha ,Count(*) as Total, FechaDia=DATEADD(day,3,cast(Fecha as date)) ,Tipo='Desayuno' from PedidosComida
        where Fecha like '%$firstday%' and Jueves like '%Desayuno%' Group by Fecha
        union all Select Fecha ,Count(*) as Total, FechaDia=DATEADD(day,3,cast(Fecha as date)),Tipo='Comida' from PedidosComida
        where Fecha like '%$firstday%' and Jueves like '%Comida%' Group by Fecha
        Union all Select Fecha ,Count(*) as Total, FechaDia=DATEADD(day,4,cast(Fecha as date)) ,Tipo='Desayuno' from PedidosComida
        where Fecha like '%$firstday%' and Viernes like '%Desayuno%' Group by Fecha
        union all Select Fecha ,Count(*) as Total, FechaDia=DATEADD(day,4,cast(Fecha as date)),Tipo='Comida' from PedidosComida
        where Fecha like '%$firstday%' and Viernes like '%Comida%' Group by Fecha
    ) as a where FechaDia='$fechaTotales'";
    
    $stmt127 = sqlsrv_query($conn,$sql127);
    $arrayT = [];
    $fechaActual1 = date('H:i:s', time()+3600);
    
    while($row = sqlsrv_fetch_array($stmt127,SQLSRV_FETCH_NUMERIC)) {
        if (strtotime($fechaActual1) < strtotime('12:40:00') && $row[3]== 'Desayuno') {
            array_push($arrayT,$row[1]);
        } 
        if (strtotime($fechaActual1) > strtotime('12:40:00') && $row[3]== 'Comida') {
            array_push($arrayT,$row[1]);
        } 
    }
    
    // Procesar formulario
    if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["name"])) {
        $name = test_input($_POST["name"]);
        $name = str_replace('Ñ', ':', str_replace('Ã"', 'Ó', $name));
        
        $fechaActual = date('d-m-Y');
        date_default_timezone_set('America/Mexico_City');
        $fechaActual1 = date('H:i:s');
        $hoy = (strtotime($fechaActual1) > strtotime('13:00:00')) ? 'Comida' : 'Desayuno';
        
        // Validar disponibilidad
        $sql1 = "Select Hora_Entrada,Tipo_Comida,count(*) as Total from (
            Select ltrim(rtrim(Replace(left(substring(Nombre,CHARINDEX(':',Nombre)+1,LEN(Nombre)-CHARINDEX(':',Nombre)+1),charindex(':',substring(Nombre,CHARINDEX(':',Nombre)+1,LEN(Nombre)-CHARINDEX(':',Nombre)+1))),'.E:','')))
            as Nombre,Hora_Entrada,Fecha, Tipo_Comida= case when Fecha > '12:40:00' then 'Comida' else 'Desayuno' end,
            Id_Semana=datepart(week,CONVERT(date,Hora_Entrada,103)) - datepart(week, dateadd(dd,-day(CONVERT(date,Hora_Entrada,103))+1,CONVERT(date,Hora_Entrada,103))) +1 
            from [dbo].[Entradas] where Not Nombre = '') as a
            where Tipo_Comida = '$hoy' and Hora_Entrada = '$fechaActual'
            Group by Hora_Entrada,Tipo_Comida";
        
        $stmt1 = sqlsrv_query($conn, $sql1);
        $TotalPal = 0;
        
        if (sqlsrv_has_rows($stmt1)) {
            while ($row = sqlsrv_fetch_array($stmt1, SQLSRV_FETCH_ASSOC)) {
                $TotalPal = ($row['Total'] < 200) ? 0 : 1;
            }
        }
        
        if ($TotalPal == 0) {
            // Insertar registro
            if (in_array($cot, ['CAFÉ O TÉ', 'TORTILLAS', 'AGUA', 'DESECHABLE', 'COMIDA PARA LLEVAR'])) {
                $sql = "insert into complementos (Id_Empleado,Nombre,Complemento,Fecha,Hora) Values('','$name','$cot','$fechaActual','$fechaActual1')";  
            } else {     
                $sql = "insert into [dbo].[Entradas] (Id_Empleado,Nombre,Area,Hora_Entrada,Fecha) Values('','$name','','$fechaActual','$fechaActual1')"; 
            }	
            
            $stmt = sqlsrv_query($conn, $sql);
            if ($stmt === false) {
                die(print_r(sqlsrv_errors(), true));
            }
            sqlsrv_free_stmt($stmt);
            
            // Procesar nombre
            $name1 = explode(":", $name);
            if ($name1[0] == 'NOMBRE') {
                $name2 = str_replace(" NSS", "", str_replace(" TEL DE EMERGENCIA", "", str_replace("N.E", "", $name1[1])));
            } elseif ($name1[0] == 'ID') {
                $name2 = str_replace("AREA", "", $name1[2]);
            } else {
                $name2 = str_replace("NSS", "", $name1[0]);
            }
            
            // Determinar tipo de consumo
            $Com = (in_array($cot, ['CAFÉ O TÉ', 'TORTILLAS', 'AGUA', 'DESECHABLE', 'COMIDA PARA LLEVAR'])) ? $cot : 
                  (($fechaActual1 > '12:25:00') ? 'Comida' : 'Desayuno');
            
            echo '<div id="demo" style="display:none">' . $name2 . ' ' . $fechaActual . ' ' . $fechaActual1 . ' ' . $Com . '</div>';
            
            // Script para imprimir y guardar
            echo "<script>
                guardarTicket('$name2', '$fechaActual', '$fechaActual1', '$Com');
                const myHTML = generarHTMLTicket('$name2', '$fechaActual', '$fechaActual1', '$Com');
                const myWin = window.open('about:blank', '_blank');
                myWin.document.write(myHTML);
                myWin.print();
                myWin.close();
                showAlert('Registro Exitoso', 'Usuario registrado correctamente', 'success');
                document.getElementById('cot').value = '';
                document.getElementById('name').value = '';
                document.getElementById('name').focus();
            </script>";
        } else {
            echo "<script>showAlert('Sin Disponibilidad', 'No hay lugares disponibles', 'warning');</script>";
        }
    }
    
    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    ?>
    
    <script>
        // Actualizar totales desde PHP
        var dT = <?php echo json_encode($arrayT ?? []); ?>;
        var dT1 = <?php echo json_encode($arrayT1 ?? []); ?>;
        var total = (dT.length > 0 ? dT[0] : 0) - (dT1.length > 0 ? dT1[0] : 0);
        document.getElementById("result1").innerHTML = 'Platillos disponibles: ' + total;
        document.getElementById('cot').value = '';
    </script>
</body>
</html>