<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario Premium - Utensilios y Equipo de Comedor 2025</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: rgba(26, 60, 110, 0.85);
            --secondary-color: rgba(201, 169, 110, 0.85);
            --glass-bg: rgba(255, 255, 255, 0.2);
            --glass-border: rgba(255, 255, 255, 0.3);
            --glass-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: rgba(40, 167, 69, 0.85);
            --warning-color: rgba(255, 193, 7, 0.85);
            --danger-color: rgba(220, 53, 69, 0.85);
            --gray-color: #6c757d;
            --border-radius: 16px;
            --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
            --blur-intensity: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Inter', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 50%, #d5dce8 100%);
            color: var(--dark-color);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(201, 169, 110, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 20%, rgba(26, 60, 110, 0.1) 0%, transparent 40%);
            z-index: -1;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header Styles with Glassmorphism */
        header {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(var(--blur-intensity));
            -webkit-backdrop-filter: blur(var(--blur-intensity));
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            box-shadow: var(--glass-shadow);
            padding: 40px;
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }

        header:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        header::before {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 400px;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, transparent 60%);
            z-index: 1;
        }

        .header-content {
            position: relative;
            z-index: 2;
        }

        h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--primary-color), #0d2b52);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: 0.5px;
        }

        .subtitle {
            font-size: 1.3rem;
            opacity: 0.9;
            font-weight: 400;
            margin-bottom: 30px;
            color: var(--primary-color);
        }

        .header-info {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin-top: 35px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255, 255, 255, 0.3);
            padding: 18px 24px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            transition: var(--transition);
        }

        .info-item:hover {
            background: rgba(255, 255, 255, 0.4);
            transform: translateX(5px);
        }

        .info-icon {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: white;
            box-shadow: 0 4px 15px rgba(26, 60, 110, 0.2);
        }

        /* Main Content Styles */
        .dashboard {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 40px;
            margin-bottom: 40px;
        }

        @media (max-width: 1200px) {
            .dashboard {
                grid-template-columns: 1fr;
            }
        }

        /* Summary Cards with Glassmorphism */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .card {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(var(--blur-intensity));
            -webkit-backdrop-filter: blur(var(--blur-intensity));
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            box-shadow: var(--glass-shadow);
            padding: 30px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            background: rgba(255, 255, 255, 0.35);
        }

        .card-icon {
            font-size: 2.2rem;
            margin-bottom: 20px;
            color: var(--primary-color);
            opacity: 0.9;
        }

        .card-title {
            font-size: 0.95rem;
            text-transform: uppercase;
            color: var(--gray-color);
            margin-bottom: 10px;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .card-value {
            font-size: 2.8rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .card-change {
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Inventory Table with Glassmorphism */
        .inventory-section {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(var(--blur-intensity));
            -webkit-backdrop-filter: blur(var(--blur-intensity));
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            box-shadow: var(--glass-shadow);
            padding: 35px;
            margin-bottom: 40px;
            transition: var(--transition);
        }

        .inventory-section:hover {
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.4);
        }

        h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        h2 i {
            color: var(--secondary-color);
        }

        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        thead {
            background: rgba(26, 60, 110, 0.15);
            backdrop-filter: blur(10px);
        }

        th {
            padding: 18px 20px;
            text-align: left;
            font-weight: 600;
            color: var(--primary-color);
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 1px;
            border-bottom: 2px solid rgba(26, 60, 110, 0.2);
        }

        td {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            transition: var(--transition);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.3);
        }

        .quantity {
            text-align: center;
            font-weight: 700;
            font-size: 1.2rem;
            position: relative;
        }

        .quantity-zero {
            color: var(--danger-color);
        }

        .article-cell {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .article-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .observations {
            font-size: 0.95rem;
            color: var(--gray-color);
            max-width: 300px;
            line-height: 1.5;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        .status-available {
            background: rgba(40, 167, 69, 0.2);
            color: var(--success-color);
        }

        .status-low {
            background: rgba(255, 193, 7, 0.2);
            color: var(--warning-color);
        }

        .status-out {
            background: rgba(220, 53, 69, 0.2);
            color: var(--danger-color);
        }

        /* Sidebar with Glassmorphism */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 40px;
        }

        .notes-card, .filter-section {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(var(--blur-intensity));
            -webkit-backdrop-filter: blur(var(--blur-intensity));
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            box-shadow: var(--glass-shadow);
            padding: 35px;
            transition: var(--transition);
        }

        .notes-card:hover, .filter-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }

        h3 {
            color: var(--primary-color);
            margin-bottom: 25px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .notes-content {
            background: rgba(255, 255, 255, 0.2);
            padding: 25px;
            border-radius: 12px;
            border-left: 4px solid var(--secondary-color);
        }

        .notes-list {
            list-style-type: none;
        }

        .notes-list li {
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .notes-list li:last-child {
            border-bottom: none;
        }

        .note-icon {
            color: var(--secondary-color);
            font-size: 1.1rem;
            margin-top: 3px;
            flex-shrink: 0;
        }

        /* Filter Section */
        .filter-group {
            margin-bottom: 25px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--primary-color);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-select, .search-box input {
            width: 100%;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 12px;
            font-size: 1rem;
            color: var(--dark-color);
            transition: var(--transition);
            backdrop-filter: blur(5px);
        }

        .filter-select:focus, .search-box input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.5);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 60, 110, 0.1);
        }

        .search-box {
            position: relative;
            margin-bottom: 25px;
        }

        .search-box input {
            padding-left: 55px;
        }

        .search-box i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        #resetFilters {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
            letter-spacing: 0.5px;
        }

        #resetFilters:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(26, 60, 110, 0.2);
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 40px 0;
            margin-top: 60px;
            color: var(--primary-color);
            font-size: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.4);
        }

        .last-update {
            display: inline-block;
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            padding: 12px 30px;
            border-radius: 50px;
            margin-top: 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        /* Floating Elements */
        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .floating-element {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 15s infinite ease-in-out;
        }

        .floating-element:nth-child(1) {
            width: 300px;
            height: 300px;
            top: 10%;
            left: 5%;
            background: radial-gradient(circle, rgba(201, 169, 110, 0.1) 0%, transparent 70%);
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            width: 200px;
            height: 200px;
            top: 60%;
            right: 10%;
            background: radial-gradient(circle, rgba(26, 60, 110, 0.08) 0%, transparent 70%);
            animation-delay: 5s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            33% { transform: translateY(-30px) rotate(120deg); }
            66% { transform: translateY(20px) rotate(240deg); }
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            h1 {
                font-size: 2.5rem;
            }
            
            .dashboard {
                gap: 30px;
            }
            
            .summary-cards {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .subtitle {
                font-size: 1.1rem;
            }
            
            .header-info {
                flex-direction: column;
                gap: 15px;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
            
            .summary-cards {
                grid-template-columns: 1fr;
            }
            
            .card-value {
                font-size: 2.5rem;
            }
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #0d2b52, #b89447);
        }

        /* Print Styles */
        @media print {
            .filter-section, #resetFilters, .search-box, .floating-elements {
                display: none;
            }
            
            .card, .inventory-section, .notes-card {
                box-shadow: none;
                border: 1px solid #ddd;
                background: white;
            }
        }
    </style>
</head>
<body>
    <div class="floating-elements">
        <div class="floating-element"></div>
        <div class="floating-element"></div>
    </div>
    
    <div class="container">
        <header>
            <div class="header-content">
                <h1><i class="fas fa-clipboard-list"></i> Inventario comedor 2025</h1>
                <p class="subtitle">Sistema de gestión de utensilios y equipo de comedor</p>
                
                <div class="header-info">
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <div>
                            <div class="info-label">Total de artículos registrados</div>
                            <div class="info-value">75 categorías · 142 unidades</div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div>
                            <div class="info-label">Estado del inventario</div>
                            <div class="info-value">98% completo · 3 urgentes</div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div>
                            <div class="info-label">Período de inventario</div>
                            <div class="info-value">Actualizado · Julio 2025</div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="dashboard">
            <main>
                <div class="summary-cards">
                    <div class="card">
                        <div class="card-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="card-title">Artículos en Stock</div>
                        <div class="card-value">142</div>
                        <div class="card-change positive">
                            <i class="fas fa-arrow-up"></i>
                            15 artículos nuevos
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="card-title">Artículos Agotados</div>
                        <div class="card-value">3</div>
                        <div class="card-change negative">
                            <i class="fas fa-clock"></i>
                            Requieren atención inmediata
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="card-title">Artículos Dañados</div>
                        <div class="card-value">4</div>
                        <div class="card-change">
                            <i class="fas fa-wrench"></i>
                            Necesitan reparación
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="card-title">Categorías Activas</div>
                        <div class="card-value">12</div>
                        <div class="card-change">
                            <i class="fas fa-layer-group"></i>
                            Tipos de utensilios
                        </div>
                    </div>
                </div>
                
                <div class="inventory-section">
                    <div class="section-header">
                        <h2><i class="fas fa-list-ol"></i> Lista Completa de Inventario</h2>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Buscar artículo por nombre...">
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <table id="inventoryTable">
                            <thead>
                                <tr>
                                    <th width="120">Cantidad</th>
                                    <th>Artículo</th>
                                    <th width="350">Observaciones</th>
                                    <th width="140">Estado</th>
                                </tr>
                            </thead>
                            <tbody id="inventoryTableBody">
                                <!-- Los datos se cargarán aquí con JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
            
            <aside class="sidebar">
                <div class="notes-card">
                    <h3><i class="fas fa-sticky-note"></i> Notas del Inventario</h3>
                    <div class="notes-content">
                        <ul class="notes-list">
                            <li>
                                <div class="note-icon"><i class="fas fa-info-circle"></i></div>
                                <div><strong>Faltan utensilios:</strong> Los que tiene la Licenciada Hilda y la Licenciada Alejandra no están incluidos en este inventario.</div>
                            </li>
                            <li>
                                <div class="note-icon"><i class="fas fa-exclamation-triangle"></i></div>
                                <div><strong>Atención urgente:</strong> Artículos marcados en rojo tienen cantidad 0 y requieren reabastecimiento inmediato.</div>
                            </li>
                            <li>
                                <div class="note-icon"><i class="fas fa-shopping-cart"></i></div>
                                <div><strong>Compras recientes:</strong> Algunos artículos han sido comprados recientemente por el chef (ver observaciones).</div>
                            </li>
                            <li>
                                <div class="note-icon"><i class="fas fa-tools"></i></div>
                                <div><strong>Daños reportados:</strong> Hay 4 artículos que requieren reparación o están rotos (ver observaciones).</div>
                            </li>
                            <li>
                                <div class="note-icon"><i class="fas fa-archive"></i></div>
                                <div><strong>Nuevos ingresos:</strong> Se registraron 15 tazas para café nuevas en el último inventario.</div>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="filter-section">
                    <h3><i class="fas fa-filter"></i> Filtros Avanzados</h3>
                    
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="sidebarSearch" placeholder="Buscar en todo el inventario...">
                    </div>
                    
                    <div class="filter-group">
                        <label for="statusFilter"><i class="fas fa-chart-bar"></i> Filtrar por estado</label>
                        <select id="statusFilter" class="filter-select">
                            <option value="all">Todos los estados</option>
                            <option value="available">En stock</option>
                            <option value="low">Stock bajo</option>
                            <option value="out">Agotados</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="categoryFilter"><i class="fas fa-tag"></i> Filtrar por categoría</label>
                        <select id="categoryFilter" class="filter-select">
                            <option value="all">Todas las categorías</option>
                            <option value="molde">Moldes</option>
                            <option value="olla">Ollas y Cacerolas</option>
                            <option value="acero">Acero Inoxidable</option>
                            <option value="utensilio">Utensilios de Cocina</option>
                            <option value="servicio">Artículos de Servicio</option>
                            <option value="electronico">Electrodomésticos</option>
                            <option value="almacenaje">Almacenaje</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="quantityFilter"><i class="fas fa-sort-amount-down"></i> Filtrar por cantidad</label>
                        <select id="quantityFilter" class="filter-select">
                            <option value="all">Cualquier cantidad</option>
                            <option value="zero">Cantidad 0 (Urgente)</option>
                            <option value="low">1-2 unidades (Bajo)</option>
                            <option value="medium">3-10 unidades (Normal)</option>
                            <option value="high">Más de 10 unidades (Alto)</option>
                        </select>
                    </div>
                    
                    <button id="resetFilters">
                        <i class="fas fa-redo"></i> Restablecer todos los filtros
                    </button>
                </div>
            </aside>
        </div>
        
        <footer>
            <p><i class="fas fa-copyright"></i> Sistema de Gestión de Inventario - Comedor 2025</p>
            <div class="last-update">
                <i class="fas fa-history"></i> Última actualización completa: Julio 2025 · Versión 2.0
            </div>
        </footer>
    </div>

    <script>
        // Datos del inventario
        const inventoryData = [
            { cantidad: 2, articulo: "MOLDE PARA CUP CAKE", observaciones: "" },
            { cantidad: 1, articulo: "MOLDE PARA ROSCA GRANDE", observaciones: "" },
            { cantidad: 1, articulo: "BATIDORA DE PEDESTAL CON (BATIDOR GLOBO,GANCHO Y MEZCLADOR)", observaciones: "" },
            { cantidad: 5, articulo: "TABLA DE POLIETILENO DE USO CULINARIO (BLANCA,VERDE,ROJA,AMARILLA Y CAFÉ)", observaciones: "" },
            { cantidad: 3, articulo: "COLADOR DE ACERO INOXIDABLE", observaciones: "" },
            { cantidad: 1, articulo: "CANASTILLA COLADOR DE ACERO INOXIDABLE", observaciones: "" },
            { cantidad: 1, articulo: "OLLA EXPRES DE 15 LITROS (3 VALVULAS)", observaciones: "" },
            { cantidad: 1, articulo: "COLADOR CHINO", observaciones: "" },
            { cantidad: 1, articulo: "OLLA DE ALUMINIO DE 50 LITROS", observaciones: "" },
            { cantidad: 1, articulo: "CENTRIFUGADOR DE LECHUGAS DE 5 LITROS", observaciones: "" },
            { cantidad: 1, articulo: "BASCULA DIGITAL CAPACIDAD 40KG (RHINO)", observaciones: "" },
            { cantidad: 6, articulo: "ESPATULA PARA PARRILLA", observaciones: "" },
            { cantidad: 4, articulo: "PINZAS DE ACERO INOXIDABLE CON PUNTA DE SILICON", observaciones: "" },
            { cantidad: 1, articulo: "PINZAS PANERAS DE METAL CHICA", observaciones: "" },
            { cantidad: 2, articulo: "COMAL DE ALUMINIO", observaciones: "" },
            { cantidad: 1, articulo: "COMAL DE ACERO", observaciones: "" },
            { cantidad: 1, articulo: "CHAROLA PARA PANADERIA DE ACERO", observaciones: "" },
            { cantidad: 1, articulo: "CHAROLA GALLETAS DE ALUMINIO", observaciones: "" },
            { cantidad: 1, articulo: "AFILADOR DE CUCHILLOS (TRUPER)", observaciones: "" },
            { cantidad: 2, articulo: "MAMILA DE PLASTICO (500ML)", observaciones: "" },
            { cantidad: 1, articulo: "MAMILA DE PLASTICO (1L)", observaciones: "" },
            { cantidad: 1, articulo: "HIELERA PARA ICE BOWL DE SILICON", observaciones: "" },
            { cantidad: 2, articulo: "OLLA DE ACERO INOXIDABLE (12 LITROS)", observaciones: "" },
            { cantidad: 2, articulo: "OLLA DE ACERO INOXIDABLE (25 LITROS)", observaciones: "" },
            { cantidad: 1, articulo: "OLLA DE ACERO INOXIDABLE DE (30 LITROS)", observaciones: "" },
            { cantidad: 1, articulo: "CACEROLA DE ACERO INOXIDABLE DE 5 LITROS CON TAPA", observaciones: "" },
            { cantidad: 1, articulo: "CACEROLA DE ACERO INOXIDABLE DE 8 LITROS CON TAPA", observaciones: "" },
            { cantidad: 1, articulo: "CACEROLA DE ACERO INOXIDABLE DE 12 LITROS CON TAPA", observaciones: "" },
            { cantidad: 1, articulo: "CACEROLA DE ACERO INOXIDABLE DE 20 LITROS CON TAPA", observaciones: "" },
            { cantidad: 2, articulo: "OLLA COLUDA DE 3 LITROS DE ALUMINIO", observaciones: "" },
            { cantidad: 1, articulo: "SARTEN DE TEFLON 25 CM", observaciones: "ESTA ROTO" },
            { cantidad: 1, articulo: "BATIDOR GLOBO GRANDE", observaciones: "" },
            { cantidad: 2, articulo: "VOLTEADOR DE COCINA RANURADO", observaciones: "" },
            { cantidad: 2, articulo: "CUCHARON DE PLASTICO NEGROS 5 OZ", observaciones: "" },
            { cantidad: 1, articulo: "PELADOR (VITORINOX)", observaciones: "" },
            { cantidad: 0, articulo: "COLADERA DE COCINA 25 CM", observaciones: "1 NUEVA (LA COMPRO EL CHEF)" },
            { cantidad: 0, articulo: "COLADERA DE COCINA 30 CM", observaciones: "1 NUEVA (LA COMPRO EL CHEF)" },
            { cantidad: 1, articulo: "PALA DE MADERA", observaciones: "" },
            { cantidad: 3, articulo: "CUCHARA PARA SERVICIO DE PLASTICO", observaciones: "" },
            { cantidad: 1, articulo: "CUCHARA PARA SERVICIO DE PELTRE", observaciones: "" },
            { cantidad: 3, articulo: "CUCHARA DE COCINA DE ACERO INOXIDABLE", observaciones: "" },
            { cantidad: 2, articulo: "CUCHARON DE ACERO INOXIDABLE DE 6 OZ", observaciones: "" },
            { cantidad: 1, articulo: "ESPUMADERA DE ACERO INOXIDABLE", observaciones: "" },
            { cantidad: 1, articulo: "ESCURRIDOR DE TRASTES", observaciones: "" },
            { cantidad: 5, articulo: "INSERTO ENTERO DE ACERO INOXIDABLE", observaciones: "" },
            { cantidad: 3, articulo: "INSERTO MEDIO DE ACERO INOXIDABLE", observaciones: "" },
            { cantidad: 2, articulo: "INCERTO TERCIO DE ACERO INOXIDABLE", observaciones: "" },
            { cantidad: 1, articulo: "RAYADOR DE 4 LADOS DE ACERO INOXIDABLE", observaciones: "" },
            { cantidad: 1, articulo: "BAÑO MARIA CONICO DE 5 LITROS", observaciones: "" },
            { cantidad: 1, articulo: "CHAROLA DE SERVICIO OVALADA CROMADA", observaciones: "" },
            { cantidad: 1, articulo: "ABRELATAS", observaciones: "" },
            { cantidad: 1, articulo: "EXPRIMIDOR PARA LIMONES", observaciones: "" },
            { cantidad: 0, articulo: "BROCHA DE SILICON", observaciones: "SE ROMPIO" },
            { cantidad: 1, articulo: "BATIDORA DE MANO OSTER", observaciones: "" },
            { cantidad: 2, articulo: "ESCUP PARA HELADO", observaciones: "" },
            { cantidad: 1, articulo: "CUCHILLO DE SIERRA PARA PAN", observaciones: "" },
            { cantidad: 1, articulo: "OLLA EXPRES DE 6 LITROS", observaciones: "" },
            { cantidad: 1, articulo: "TAZA MEDIDORA", observaciones: "" },
            { cantidad: 4, articulo: "CUCHILLO TIPO CHEF", observaciones: "" },
            { cantidad: 1, articulo: "COLADOR DE ACERO INOXIDABLE", observaciones: "" },
            { cantidad: 1, articulo: "EXPRIMIDOR DE NARANJAS COLA DE CHANGO", observaciones: "TIENE UNO QUE NO SIRVE" },
            { cantidad: 1, articulo: "MOTOR DE LICUADORA OSTER", observaciones: "" },
            { cantidad: 2, articulo: "VASO DE LICUADORA DE VIDRIO CON ASPAS OSTER", observaciones: "ROMPIERON UNO" },
            { cantidad: 1, articulo: "VASO DE LICUADORA DE VIDRO SIN ASPAS", observaciones: "" },
            { cantidad: 1, articulo: "JARRA DE PLASTICO DE 2 LITROS", observaciones: "" },
            { cantidad: 2, articulo: "JARRA DE PLASTICO DE 2.5 LITROS", observaciones: "" },
            { cantidad: 1, articulo: "JARRA DE PLASTICO DE 3 LITROS", observaciones: "" },
            { cantidad: 1, articulo: "HIELERA DE UNICEL DE 21 LITROS", observaciones: "" },
            { cantidad: 5, articulo: "BOWL DE ACERO INOXIDABLE DE 24 CM", observaciones: "" },
            { cantidad: 5, articulo: "BOWL DE ACERO INOXIDABLE DE 30 CM", observaciones: "" },
            { cantidad: 2, articulo: "BOWL DE ACERO INOXIDABLE DE 36 CM", observaciones: "" },
            { cantidad: 58, articulo: "TAZA PARA CAFÉ", observaciones: "15 NUEVAS" },
            { cantidad: 12, articulo: "TAZON PARA SOPA DE CERAMICA", observaciones: "" },
            { cantidad: 26, articulo: "TAZON CUADRADO PARA SOPA DE MELAMINA", observaciones: "" },
            { cantidad: 11, articulo: "PLATO TRINCHE DE CERAMICA", observaciones: "" },
            { cantidad: 21, articulo: "PLATO TRINCHE CUADRADOS DE MELAMINA", observaciones: "" },
            { cantidad: 1, articulo: "SALERO", observaciones: "" },
            { cantidad: 1, articulo: "AZUCARERA", observaciones: "" },
            { cantidad: 24, articulo: "CHAROLA DE SERVICIO", observaciones: "" },
            { cantidad: 2, articulo: "CANASTILLA DE FREIDORA INDUSTRIAL", observaciones: "" },
            { cantidad: 7, articulo: "TORTILLERO DE MELAMINA", observaciones: "" },
            { cantidad: 21, articulo: "VASO JAIBOLERO", observaciones: "" },
            { cantidad: 18, articulo: "PLATO PARA POSTRE DE CERAMICA", observaciones: "" },
            { cantidad: 27, articulo: "CAJAS HUACALES DE PLASTICO", observaciones: "" },
            { cantidad: 13, articulo: "TENEDORES", observaciones: "" },
            { cantidad: 15, articulo: "CUCHARA SOPERA", observaciones: "" },
            { cantidad: 11, articulo: "CUCHARA CAFETERA", observaciones: "" },
            { cantidad: 55, articulo: "CUCHILLO DE MESA", observaciones: "" },
            { cantidad: 3, articulo: "CHAROLA NEGRA PARA LAVAR TRASTES", observaciones: "" },
            { cantidad: 1, articulo: "CAFETERA", observaciones: "" },
            { cantidad: 1, articulo: "OLLA DE ALUMINIO DE 80 LITROS", observaciones: "" },
            { cantidad: 1, articulo: "CAJA PANERA DE PLASTICO", observaciones: "" }
        ];

        // Función para determinar el icono según el artículo
        function getIconForArticle(articulo) {
            const lowerArticulo = articulo.toLowerCase();
            
            if (lowerArticulo.includes("molde") || lowerArticulo.includes("cup cake")) return "fas fa-cookie-bite";
            if (lowerArticulo.includes("batidora") || lowerArticulo.includes("batidor")) return "fas fa-blender";
            if (lowerArticulo.includes("olla") || lowerArticulo.includes("cacerola") || lowerArticulo.includes("sartén")) return "fas fa-utensil-spoon";
            if (lowerArticulo.includes("colador") || lowerArticulo.includes("coladera")) return "fas fa-filter";
            if (lowerArticulo.includes("cuchara") || lowerArticulo.includes("cucharón")) return "fas fa-utensil-spoon";
            if (lowerArticulo.includes("cuchillo") || lowerArticulo.includes("afilador")) return "fas fa-utensils";
            if (lowerArticulo.includes("pinza") || lowerArticulo.includes("espátula")) return "fas fa-grip-horizontal";
            if (lowerArticulo.includes("taza") || lowerArticulo.includes("tazón")) return "fas fa-mug-hot";
            if (lowerArticulo.includes("plato") || lowerArticulo.includes("charola")) return "fas fa-concierge-bell";
            if (lowerArticulo.includes("vaso") || lowerArticulo.includes("jarra")) return "fas fa-glass-whiskey";
            if (lowerArticulo.includes("licuadora") || lowerArticulo.includes("motor")) return "fas fa-blender";
            if (lowerArticulo.includes("exprimidor") || lowerArticulo.includes("abrelatas")) return "fas fa-wine-bottle";
            if (lowerArticulo.includes("hielera") || lowerArticulo.includes("centrifugador")) return "fas fa-temperature-low";
            if (lowerArticulo.includes("báscula") || lowerArticulo.includes("peso")) return "fas fa-weight";
            if (lowerArticulo.includes("tenedor") || lowerArticulo.includes("cubierto")) return "fas fa-utensils";
            if (lowerArticulo.includes("cafetera")) return "fas fa-coffee";
            if (lowerArticulo.includes("tabla") || lowerArticulo.includes("poli")) return "fas fa-cut";
            
            return "fas fa-box";
        }

        // Función para determinar el estado según la cantidad
        function getStatus(cantidad, observaciones) {
            if (cantidad === 0) {
                return "out";
            } else if (cantidad < 2) {
                return "low";
            } else {
                return "available";
            }
        }

        // Función para determinar la categoría según el artículo
        function getCategory(articulo) {
            const lowerArticulo = articulo.toLowerCase();
            
            if (lowerArticulo.includes("molde")) return "molde";
            if (lowerArticulo.includes("olla") || lowerArticulo.includes("cacerola") || lowerArticulo.includes("sartén") || lowerArticulo.includes("comal")) return "olla";
            if (lowerArticulo.includes("acero") || lowerArticulo.includes("inoxidable") || lowerArticulo.includes("bowl")) return "acero";
            if (lowerArticulo.includes("cuchara") || lowerArticulo.includes("cucharón") || lowerArticulo.includes("batidor") || lowerArticulo.includes("colador") || lowerArticulo.includes("pinza") || lowerArticulo.includes("espátula") || lowerArticulo.includes("cuchillo") || lowerArticulo.includes("pelador")) return "utensilio";
            if (lowerArticulo.includes("taza") || lowerArticulo.includes("plato") || lowerArticulo.includes("vaso") || lowerArticulo.includes("charola") || lowerArticulo.includes("servicio") || lowerArticulo.includes("tenedor") || lowerArticulo.includes("salero") || lowerArticulo.includes("azucarera")) return "servicio";
            if (lowerArticulo.includes("batidora") || lowerArticulo.includes("licuadora") || lowerArticulo.includes("cafetera") || lowerArticulo.includes("báscula")) return "electronico";
            if (lowerArticulo.includes("caja") || lowerArticulo.includes("hielera") || lowerArticulo.includes("canastilla") || lowerArticulo.includes("huacal")) return "almacenaje";
            
            return "otros";
        }

        // Función para renderizar la tabla
        function renderTable(data) {
            const tbody = document.getElementById("inventoryTableBody");
            tbody.innerHTML = "";
            
            if (data.length === 0) {
                const row = document.createElement("tr");
                row.innerHTML = `
                    <td colspan="4" style="text-align: center; padding: 40px;">
                        <i class="fas fa-search" style="font-size: 2rem; color: var(--gray-color); margin-bottom: 15px; display: block;"></i>
                        <h3 style="color: var(--gray-color); margin-bottom: 10px;">No se encontraron resultados</h3>
                        <p>Intenta con otros términos de búsqueda o ajusta los filtros</p>
                    </td>
                `;
                tbody.appendChild(row);
                return;
            }
            
            data.forEach(item => {
                const status = getStatus(item.cantidad, item.observaciones);
                const category = getCategory(item.articulo);
                const icon = getIconForArticle(item.articulo);
                
                let statusText = "";
                let statusClass = "";
                let statusIcon = "";
                
                switch(status) {
                    case "available":
                        statusText = "En stock";
                        statusClass = "status-available";
                        statusIcon = "fas fa-check-circle";
                        break;
                    case "low":
                        statusText = "Stock bajo";
                        statusClass = "status-low";
                        statusIcon = "fas fa-exclamation-circle";
                        break;
                    case "out":
                        statusText = "Agotado";
                        statusClass = "status-out";
                        statusIcon = "fas fa-times-circle";
                        break;
                }
                
                const row = document.createElement("tr");
                row.dataset.category = category;
                row.dataset.status = status;
                row.dataset.quantity = item.cantidad;
                
                const quantityClass = item.cantidad === 0 ? "quantity-zero" : "";
                
                row.innerHTML = `
                    <td class="quantity ${quantityClass}">
                        <div style="font-size: 1.5rem; font-weight: 800;">${item.cantidad}</div>
                        <div style="font-size: 0.8rem; opacity: 0.7;">unidades</div>
                    </td>
                    <td>
                        <div class="article-cell">
                            <div class="article-icon">
                                <i class="${icon}"></i>
                            </div>
                            <div>
                                <div style="font-weight: 600; margin-bottom: 5px;">${item.articulo}</div>
                                <div style="font-size: 0.85rem; color: var(--gray-color);">
                                    <i class="fas fa-tag"></i> ${category.charAt(0).toUpperCase() + category.slice(1)}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="observations">
                        ${item.observaciones ? `<div style="display: flex; align-items: flex-start; gap: 10px;">
                            <i class="fas fa-comment" style="color: var(--secondary-color); margin-top: 3px;"></i>
                            <div>${item.observaciones}</div>
                        </div>` : '<span style="opacity: 0.5;"><i class="fas fa-minus"></i> Sin observaciones</span>'}
                    </td>
                    <td>
                        <span class="status-badge ${statusClass}">
                            <i class="${statusIcon}"></i> ${statusText}
                        </span>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
        }

        // Función para filtrar la tabla
        function filterTable() {
            const searchTerm = document.getElementById("searchInput").value.toLowerCase();
            const statusFilter = document.getElementById("statusFilter").value;
            const categoryFilter = document.getElementById("categoryFilter").value;
            const quantityFilter = document.getElementById("quantityFilter").value;
            
            const filteredData = inventoryData.filter(item => {
                // Filtro por búsqueda
                const matchesSearch = item.articulo.toLowerCase().includes(searchTerm) || 
                                     item.observaciones.toLowerCase().includes(searchTerm);
                
                // Filtro por estado
                const status = getStatus(item.cantidad, item.observaciones);
                const matchesStatus = statusFilter === "all" || status === statusFilter;
                
                // Filtro por categoría
                const category = getCategory(item.articulo);
                const matchesCategory = categoryFilter === "all" || category === categoryFilter;
                
                // Filtro por cantidad
                let matchesQuantity = true;
                if (quantityFilter !== "all") {
                    if (quantityFilter === "zero" && item.cantidad !== 0) matchesQuantity = false;
                    if (quantityFilter === "low" && (item.cantidad < 1 || item.cantidad > 2)) matchesQuantity = false;
                    if (quantityFilter === "medium" && (item.cantidad < 3 || item.cantidad > 10)) matchesQuantity = false;
                    if (quantityFilter === "high" && item.cantidad <= 10) matchesQuantity = false;
                }
                
                return matchesSearch && matchesStatus && matchesCategory && matchesQuantity;
            });
            
            renderTable(filteredData);
            
            // Actualizar el contador de resultados
            const resultCount = filteredData.length;
            const totalCount = inventoryData.length;
            document.getElementById("searchInput").placeholder = `Buscar artículo... (${resultCount} de ${totalCount} resultados)`;
        }

        // Inicializar la tabla
        document.addEventListener("DOMContentLoaded", () => {
            renderTable(inventoryData);
            
            // Configurar eventos de filtro
            document.getElementById("searchInput").addEventListener("input", filterTable);
            document.getElementById("sidebarSearch").addEventListener("input", function() {
                document.getElementById("searchInput").value = this.value;
                filterTable();
            });
            document.getElementById("statusFilter").addEventListener("change", filterTable);
            document.getElementById("categoryFilter").addEventListener("change", filterTable);
            document.getElementById("quantityFilter").addEventListener("change", filterTable);
            
            document.getElementById("resetFilters").addEventListener("click", function() {
                document.getElementById("searchInput").value = "";
                document.getElementById("sidebarSearch").value = "";
                document.getElementById("statusFilter").value = "all";
                document.getElementById("categoryFilter").value = "all";
                document.getElementById("quantityFilter").value = "all";
                renderTable(inventoryData);
                document.getElementById("searchInput").placeholder = "Buscar artículo por nombre...";
            });
            
            // Sincronizar las dos cajas de búsqueda
            document.getElementById("searchInput").addEventListener("input", function() {
                document.getElementById("sidebarSearch").value = this.value;
            });
            
            // Agregar efecto de clic a las tarjetas
            document.querySelectorAll('.card').forEach(card => {
                card.addEventListener('click', function() {
                    this.style.transform = 'scale(0.98)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });
            
            // Agregar efecto de impresión
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'p') {
                    e.preventDefault();
                    alert('Imprimiendo reporte de inventario...');
                    window.print();
                }
            });
        });
    </script>
</body>
</html>