<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🍽️ Sistema de Gestión de Menús</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --navy-blue: #1e3a5f;
            --deep-blue: #2d4a76;
            --light-navy: #3a5a8c;
            --accent-blue: #4a7bbe;
            --pearl-white: #f8f9fa;
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }

        body {
            background: linear-gradient(135deg, var(--navy-blue) 0%, var(--deep-blue) 50%, var(--light-navy) 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            padding: 20px;
            background-attachment: fixed;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            box-shadow: var(--glass-shadow);
            color: white;
        }

        .content-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .header-section {
            background: linear-gradient(135deg, var(--navy-blue), var(--deep-blue));
            color: white;
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="rgba(255,255,255,0.05)" points="0,1000 1000,0 1000,1000"/></svg>');
            background-size: cover;
        }

        .stats-card {
            background: linear-gradient(135deg, var(--accent-blue), var(--light-navy));
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(74, 123, 190, 0.3);
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(74, 123, 190, 0.4);
            background: linear-gradient(135deg, var(--light-navy), var(--accent-blue));
        }

        .menu-item-card {
            background: rgba(248, 249, 250, 0.9);
            border-radius: 12px;
            padding: 1.25rem;
            margin: 0.5rem 0;
            border-left: 4px solid var(--accent-blue);
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }

        .menu-item-card:hover {
            transform: translateX(8px);
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 8px 25px rgba(30, 58, 95, 0.15);
        }

        .btn-navigation {
            background: linear-gradient(135deg, var(--accent-blue), var(--light-navy));
            border: none;
            color: white;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(74, 123, 190, 0.3);
        }

        .btn-navigation:hover {
            background: linear-gradient(135deg, var(--light-navy), var(--accent-blue));
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(74, 123, 190, 0.4);
        }

        .btn-primary-glass {
            background: linear-gradient(135deg, var(--accent-blue), var(--navy-blue));
            border: none;
            color: white;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary-glass:hover {
            background: linear-gradient(135deg, var(--navy-blue), var(--accent-blue));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(30, 58, 95, 0.4);
        }

        .table-glass {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .table-glass thead th {
            background: linear-gradient(135deg, var(--navy-blue), var(--deep-blue));
            color: white;
            border: none;
            padding: 1.25rem;
            font-weight: 600;
        }

        .current-day {
            background: linear-gradient(135deg, rgba(30, 58, 95, 0.1), rgba(45, 74, 118, 0.05)) !important;
            position: relative;
        }

        .current-day::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--accent-blue);
        }

        .modal-glass .modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .form-control-glass {
            background: rgba(248, 249, 250, 0.8);
            border: 2px solid rgba(30, 58, 95, 0.1);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control-glass:focus {
            background: rgba(255, 255, 255, 0.95);
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(74, 123, 190, 0.2);
        }

        .floating-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-blue), var(--navy-blue));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 8px 25px rgba(74, 123, 190, 0.4);
            transition: all 0.3s ease;
            z-index: 1000;
            border: none;
        }

        .floating-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 30px rgba(74, 123, 190, 0.6);
        }

        .badge-glass {
            background: linear-gradient(135deg, var(--accent-blue), var(--light-navy));
            color: white;
            border-radius: 20px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(74, 123, 190, 0.3);
        }

        .btn-action {
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-edit {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: white;
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, #e0a800, #ffc107);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
        }

        .btn-delete {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, #c82333, #dc3545);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        /* Estilos para el botón de upload */
        .btn-upload {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-upload:hover {
            background: linear-gradient(135deg, #20c997, #28a745);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }

        .header-actions {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 10;
        }

        .sync-spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1055;
            min-width: 300px;
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: white;
            padding: 1rem;
            box-shadow: var(--glass-shadow);
        }
    </style>
</head>
<body>
    <!-- Botón Flotante -->
    <button class="floating-btn" data-bs-toggle="modal" data-bs-target="#menuModal" onclick="nuevoMenu()">
        <i class="fas fa-plus"></i>
    </button>

    <div class="content-container">
        <!-- Header -->
        <div class="header-section">
            <!-- Botón de Sincronización -->
            <div class="header-actions">
                <button class="btn btn-upload" onclick="mostrarSincronizacion()">
                    <i class="fas fa-sync-alt me-2"></i>Sincronizar
                </button>
            </div>
            
            <div class="container position-relative">
                <h1 class="display-4 fw-bold mb-3">🍽️ Sistema de Menús</h1>
                <p class="lead mb-4 opacity-90">Gestión profesional de menús semanales</p>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="container mt-4">
            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="stats-card">
                        <i class="fas fa-utensils fa-3x mb-3 opacity-75"></i>
                        <h3 class="fw-bold" id="totalMenus">8</h3>
                        <p class="mb-0 opacity-90">Total Menús</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <i class="fas fa-coffee fa-3x mb-3 opacity-75"></i>
                        <h3 class="fw-bold" id="totalDesayunos">4</h3>
                        <p class="mb-0 opacity-90">Desayunos</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <i class="fas fa-utensil-spoon fa-3x mb-3 opacity-75"></i>
                        <h3 class="fw-bold" id="totalComidas">4</h3>
                        <p class="mb-0 opacity-90">Comidas</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegación Semanal -->
        <div class="container mb-5">
            <div class="glass-card p-4">
                <div class="row align-items-center">
                    <div class="col-md-4 text-center text-md-start">
                        <button class="btn btn-navigation" onclick="cambiarSemana('anterior')">
                            <i class="fas fa-chevron-left me-2"></i>Semana Anterior
                        </button>
                    </div>
                    <div class="col-md-4 text-center">
                        <h4 class="fw-bold mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>
                            <span id="rangoSemana">Semana del 09/12/2024</span>
                        </h4>
                        <p class="mb-0 opacity-90" id="fechaFin">al 13/12/2024</p>
                    </div>
                    <div class="col-md-4 text-center text-md-end">
                        <button class="btn btn-navigation me-2" onclick="cambiarSemana('siguiente')">
                            Semana Siguiente<i class="fas fa-chevron-right ms-2"></i>
                        </button>
                        <button class="btn btn-primary-glass" onclick="irAHoy()">
                            <i class="fas fa-calendar-week me-2"></i>Hoy
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Menús -->
        <div class="container mb-5">
            <div class="table-glass">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 15%">DÍA</th>
                            <th style="width: 35%">DESAYUNO</th>
                            <th style="width: 35%">COMIDA</th>
                            <th style="width: 15%">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody id="tablaMenus">
                        <!-- Los menús se cargan con JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para Menú -->
    <div class="modal fade modal-glass" id="menuModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--navy-blue), var(--deep-blue)); border: none;">
                    <h5 class="modal-title text-white fw-bold" id="modalTitle">
                        <i class="fas fa-plus-circle me-2"></i>Nuevo Menú
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form onsubmit="guardarMenu(event)">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id" id="menuId" value="">
                        <input type="hidden" name="accion" id="accion" value="crear">
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-calendar text-primary me-2"></i>Fecha
                            </label>
                            <input type="date" name="fecha" id="fecha" class="form-control form-control-glass" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-utensils text-primary me-2"></i>Tipo de Comida
                            </label>
                            <select name="tipo" id="tipo" class="form-select form-control-glass" required>
                                <option value="">Seleccionar tipo...</option>
                                <option value="desayuno">🍳 Desayuno</option>
                                <option value="comida">🍽️ Comida</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-pencil-alt text-primary me-2"></i>Descripción
                            </label>
                            <textarea name="descripcion" id="descripcion" class="form-control form-control-glass" rows="4" placeholder="Describe el menú completo..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid rgba(0,0,0,0.1);">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary-glass">
                            <i class="fas fa-save me-2"></i>Guardar Menú
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Sincronización -->
    <div class="modal fade modal-glass" id="syncModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #28a745, #20c997); border: none;">
                    <h5 class="modal-title text-white fw-bold">
                        <i class="fas fa-sync-alt me-2"></i>Sincronizar Menús
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <i class="fas fa-cloud-upload-alt fa-4x text-success mb-3"></i>
                        <h5 class="fw-bold">Sincronización de Menús</h5>
                        <p class="text-muted">Conecta con fuentes externas para actualizar automáticamente</p>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-3"><i class="fas fa-database me-2"></i>Fuentes de Datos</h6>
                        <div class="list-group">
                            <div class="list-group-item list-group-item-action border-0 mb-2 rounded" onclick="seleccionarFuente('excel')" style="cursor: pointer;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-file-excel text-success fa-2x me-3"></i>
                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold mb-1">Archivo Excel</h6>
                                        <p class="mb-0 small text-muted">Sube un archivo Excel con los menús</p>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted"></i>
                                </div>
                            </div>
                            
                            <div class="list-group-item list-group-item-action border-0 rounded" onclick="seleccionarFuente('api')" style="cursor: pointer;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-cloud text-primary fa-2x me-3"></i>
                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold mb-1">API Externa</h6>
                                        <p class="mb-0 small text-muted">Conecta con sistema de proveedores</p>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>¿Cómo funciona?</strong> Selecciona una fuente de datos para importar menús automáticamente.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Excel -->
    <div class="modal fade modal-glass" id="excelModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #28a745, #20c997); border: none;">
                    <h5 class="modal-title text-white fw-bold">
                        <i class="fas fa-file-excel me-2"></i>Subir Excel
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <i class="fas fa-file-upload fa-4x text-success mb-3"></i>
                        <h5 class="fw-bold">Subir Archivo Excel</h5>
                        <p class="text-muted">Carga menús desde un archivo Excel</p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-file me-2"></i>Seleccionar Archivo
                        </label>
                        <div class="input-group">
                            <input type="file" class="form-control form-control-glass" id="archivoExcel" accept=".xlsx,.xls,.csv">
                            <button class="btn btn-upload" onclick="simularCargaExcel()">
                                <i class="fas fa-upload me-2"></i>Subir
                            </button>
                        </div>
                        <div class="form-text">Formatos aceptados: .xlsx, .xls, .csv</div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Formato requerido:</strong> El archivo debe tener columnas: Fecha, Tipo (desayuno/comida), Descripción
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Datos de ejemplo
        let menus = [
            { id: 1, fecha: '2024-12-09', tipo: 'desayuno', descripcion: 'Huevos revueltos, fruta y jugo' },
            { id: 2, fecha: '2024-12-09', tipo: 'comida', descripcion: 'Pollo a la parrilla con ensalada' },
            { id: 3, fecha: '2024-12-10', tipo: 'desayuno', descripcion: 'Cereal con leche y tostadas' },
            { id: 4, fecha: '2024-12-10', tipo: 'comida', descripcion: 'Pasta al pesto con vegetales' },
            { id: 5, fecha: '2024-12-11', tipo: 'desayuno', descripcion: 'Yogur con granola y frutos secos' },
            { id: 6, fecha: '2024-12-11', tipo: 'comida', descripcion: 'Sopa de verduras y pescado' },
            { id: 7, fecha: '2024-12-12', tipo: 'desayuno', descripcion: 'Tortillas de huevo con frijoles' },
            { id: 8, fecha: '2024-12-12', tipo: 'comida', descripcion: 'Carne asada con papas' }
        ];

        let semanaActual = '2024-12-09'; // Lunes de la semana actual

        // Inicializar la aplicación
        document.addEventListener('DOMContentLoaded', function() {
            cargarMenus();
            actualizarEstadisticas();
            actualizarRangoSemana();
        });

        // Cargar menús en la tabla
        function cargarMenus() {
            const tabla = document.getElementById('tablaMenus');
            tabla.innerHTML = '';
            
            const dias = ['LUNES', 'MARTES', 'MIÉRCOLES', 'JUEVES', 'VIERNES'];
            const hoy = new Date().toISOString().split('T')[0];
            
            for (let i = 0; i < 5; i++) {
                const fechaDia = new Date(semanaActual);
                fechaDia.setDate(fechaDia.getDate() + i);
                const fechaStr = fechaDia.toISOString().split('T')[0];
                const esHoy = fechaStr === hoy;
                
                const fila = document.createElement('tr');
                if (esHoy) fila.className = 'current-day';
                
                // Columna Día
                const tdDia = document.createElement('td');
                tdDia.className = 'fw-bold';
                tdDia.innerHTML = `
                    <div class="text-primary">${dias[i]}</div>
                    <div class="text-muted small">${formatearFecha(fechaStr)}</div>
                    ${esHoy ? '<span class="badge-glass mt-1"><i class="fas fa-star me-1"></i>Hoy</span>' : ''}
                `;
                
                // Columna Desayuno
                const tdDesayuno = document.createElement('td');
                const desayuno = menus.find(m => m.fecha === fechaStr && m.tipo === 'desayuno');
                if (desayuno) {
                    tdDesayuno.innerHTML = crearCardMenu(desayuno);
                } else {
                    tdDesayuno.innerHTML = `
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-coffee fa-2x mb-2 opacity-50"></i>
                            <p class="mb-0 small">Sin menú de desayuno</p>
                        </div>
                    `;
                }
                
                // Columna Comida
                const tdComida = document.createElement('td');
                const comida = menus.find(m => m.fecha === fechaStr && m.tipo === 'comida');
                if (comida) {
                    tdComida.innerHTML = crearCardMenu(comida);
                } else {
                    tdComida.innerHTML = `
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-utensils fa-2x mb-2 opacity-50"></i>
                            <p class="mb-0 small">Sin menú de comida</p>
                        </div>
                    `;
                }
                
                // Columna Acciones
                const tdAcciones = document.createElement('td');
                tdAcciones.className = 'text-center';
                tdAcciones.innerHTML = `
                    <button class="btn btn-primary-glass btn-sm w-100" onclick="nuevoMenu('${fechaStr}')">
                        <i class="fas fa-plus me-1"></i>Agregar
                    </button>
                `;
                
                fila.appendChild(tdDia);
                fila.appendChild(tdDesayuno);
                fila.appendChild(tdComida);
                fila.appendChild(tdAcciones);
                
                tabla.appendChild(fila);
            }
        }

        function crearCardMenu(menu) {
            return `
                <div class="menu-item-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-1">
                                <i class="fas ${menu.tipo === 'desayuno' ? 'fa-coffee text-warning' : 'fa-utensils text-success'} me-2"></i>
                                ${menu.descripcion}
                            </h6>
                            <small class="text-muted">${menu.tipo === 'desayuno' ? 'Desayuno' : 'Comida'} registrado</small>
                        </div>
                        <div class="btn-group btn-group-sm ms-2">
                            <button class="btn btn-action btn-edit" title="Editar" onclick="editarMenu(${menu.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-action btn-delete" title="Eliminar" onclick="eliminarMenu(${menu.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        // Funciones de sincronización
        function mostrarSincronizacion() {
            const modal = new bootstrap.Modal(document.getElementById('syncModal'));
            modal.show();
        }

        function seleccionarFuente(tipo) {
            const syncModal = bootstrap.Modal.getInstance(document.getElementById('syncModal'));
            syncModal.hide();
            
            if (tipo === 'excel') {
                const excelModal = new bootstrap.Modal(document.getElementById('excelModal'));
                excelModal.show();
            } else if (tipo === 'api') {
                // Simular conexión a API
                mostrarNotificacion('🔌 Conectando con API...', 'info');
                setTimeout(() => {
                    simularCargaAPI();
                }, 1500);
            }
        }

        function simularCargaExcel() {
            const excelModal = bootstrap.Modal.getInstance(document.getElementById('excelModal'));
            excelModal.hide();
            
            mostrarNotificacion('📊 Procesando archivo Excel...', 'info');
            
            // Simular procesamiento
            setTimeout(() => {
                // Agregar menús de ejemplo del Excel
                const nuevosMenus = [
                    { id: menus.length + 1, fecha: '2024-12-13', tipo: 'desayuno', descripcion: 'Panqueques con miel' },
                    { id: menus.length + 2, fecha: '2024-12-13', tipo: 'comida', descripcion: 'Lasagna de carne' },
                    { id: menus.length + 3, fecha: '2024-12-16', tipo: 'desayuno', descripcion: 'Omelette de jamón y queso' }
                ];
                
                menus.push(...nuevosMenus);
                cargarMenus();
                actualizarEstadisticas();
                
                mostrarNotificacion('✅ Excel procesado correctamente', 'success');
                mostrarNotificacion(`📥 Se agregaron ${nuevosMenus.length} nuevos menús`, 'success');
            }, 2000);
        }

        function simularCargaAPI() {
            mostrarNotificacion('🌐 Conectando con proveedor...', 'info');
            
            setTimeout(() => {
                // Simular datos de API
                const datosAPI = [
                    { id: menus.length + 1, fecha: '2024-12-17', tipo: 'desayuno', descripcion: 'Smoothie de frutas y yogurt' },
                    { id: menus.length + 2, fecha: '2024-12-17', tipo: 'comida', descripcion: 'Arroz con pollo y ensalada' },
                    { id: menus.length + 3, fecha: '2024-12-18', tipo: 'desayuno', descripcion: 'Café y pastelería' },
                    { id: menus.length + 4, fecha: '2024-12-18', tipo: 'comida', descripcion: 'Filete de pescado con vegetales' }
                ];
                
                menus.push(...datosAPI);
                cargarMenus();
                actualizarEstadisticas();
                
                mostrarNotificacion('✅ API sincronizada correctamente', 'success');
                mostrarNotificacion(`📡 Se importaron ${datosAPI.length} menús del proveedor`, 'success');
            }, 2500);
        }

        // Funciones auxiliares
        function formatearFecha(fecha) {
            const d = new Date(fecha);
            return d.toLocaleDateString('es-ES');
        }

        function actualizarEstadisticas() {
            const total = menus.length;
            const desayunos = menus.filter(m => m.tipo === 'desayuno').length;
            const comidas = menus.filter(m => m.tipo === 'comida').length;
            
            document.getElementById('totalMenus').textContent = total;
            document.getElementById('totalDesayunos').textContent = desayunos;
            document.getElementById('totalComidas').textContent = comidas;
        }

        function actualizarRangoSemana() {
            const lunes = new Date(semanaActual);
            const viernes = new Date(lunes);
            viernes.setDate(viernes.getDate() + 4);
            
            document.getElementById('rangoSemana').textContent = `Semana del ${formatearFecha(semanaActual)}`;
            document.getElementById('fechaFin').textContent = `al ${formatearFecha(viernes.toISOString().split('T')[0])}`;
        }

        function cambiarSemana(direccion) {
            const fecha = new Date(semanaActual);
            if (direccion === 'siguiente') {
                fecha.setDate(fecha.getDate() + 7);
            } else {
                fecha.setDate(fecha.getDate() - 7);
            }
            semanaActual = fecha.toISOString().split('T')[0];
            cargarMenus();
            actualizarRangoSemana();
        }

        function irAHoy() {
            const hoy = new Date();
            const lunes = new Date(hoy);
            lunes.setDate(lunes.getDate() - (hoy.getDay() === 0 ? 6 : hoy.getDay() - 1));
            semanaActual = lunes.toISOString().split('T')[0];
            cargarMenus();
            actualizarRangoSemana();
        }

        function nuevoMenu(fecha = '') {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle me-2"></i>Nuevo Menú';
            document.getElementById('accion').value = 'crear';
            document.getElementById('menuId').value = '';
            document.getElementById('fecha').value = fecha || new Date().toISOString().split('T')[0];
            document.getElementById('tipo').value = '';
            document.getElementById('descripcion').value = '';
            
            const modal = new bootstrap.Modal(document.getElementById('menuModal'));
            modal.show();
        }

        function editarMenu(id) {
            const menu = menus.find(m => m.id === id);
            if (!menu) return;
            
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Menú';
            document.getElementById('accion').value = 'editar';
            document.getElementById('menuId').value = menu.id;
            document.getElementById('fecha').value = menu.fecha;
            document.getElementById('tipo').value = menu.tipo;
            document.getElementById('descripcion').value = menu.descripcion;
            
            const modal = new bootstrap.Modal(document.getElementById('menuModal'));
            modal.show();
        }

        function guardarMenu(event) {
            event.preventDefault();
            
            const id = parseInt(document.getElementById('menuId').value) || 0;
            const fecha = document.getElementById('fecha').value;
            const tipo = document.getElementById('tipo').value;
            const descripcion = document.getElementById('descripcion').value;
            const accion = document.getElementById('accion').value;
            
            if (accion === 'crear') {
                const nuevoId = Math.max(...menus.map(m => m.id)) + 1;
                menus.push({ id: nuevoId, fecha, tipo, descripcion });
                mostrarNotificacion('✅ Menú creado exitosamente', 'success');
            } else {
                const index = menus.findIndex(m => m.id === id);
                if (index !== -1) {
                    menus[index] = { id, fecha, tipo, descripcion };
                    mostrarNotificacion('✅ Menú actualizado exitosamente', 'success');
                }
            }
            
            cargarMenus();
            actualizarEstadisticas();
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('menuModal'));
            modal.hide();
        }

        function eliminarMenu(id) {
            if (confirm('¿Está seguro de eliminar este menú?')) {
                menus = menus.filter(m => m.id !== id);
                cargarMenus();
                actualizarEstadisticas();
                mostrarNotificacion('🗑️ Menú eliminado exitosamente', 'success');
            }
        }

        function mostrarNotificacion(mensaje, tipo = 'info') {
            // Eliminar notificación anterior
            const notifAnterior = document.querySelector('.toast-notification');
            if (notifAnterior) notifAnterior.remove();
            
            // Crear nueva notificación
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 fs-4 me-3">
                        ${tipo === 'success' ? '✅' : tipo === 'info' ? 'ℹ️' : '⚠️'}
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">${mensaje}</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-3" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Auto-eliminar después de 5 segundos
            setTimeout(() => {
                if (toast.parentElement) toast.remove();
            }, 5000);
        }
    </script>
</body>
</html>