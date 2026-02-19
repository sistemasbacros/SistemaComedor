<!--
===============================================================
DOCUMENTACIÓN: Menu.php
===============================================================
@file       Menu.php
@brief      Visualizador de menús semanales del comedor BacroCorp — Enero 2026

@description
  Página HTML estática que presenta los menús de desayuno y comida
  para las cuatro semanas de enero de 2026 del comedor corporativo
  de BacroCorp. El contenido está organizado en cuatro pestañas (tabs),
  una por semana, cada una con una tabla de lunes a viernes que detalla
  los platillos disponibles por categoría (desayuno / comida).

  La navegación entre semanas se gestiona mediante JavaScript vanilla:
  al hacer clic en un botón de semana, se activa la sección correspondiente
  añadiendo/quitando la clase CSS "active" tanto en el botón como en
  la sección de contenido asociada. Solo una sección es visible a la vez.

  Cada platillo se representa con un elemento <span class="food-item">
  con clases de icono predefinidas (icon-*) que renderizan emojis via
  pseudo-elemento CSS ::before, facilitando la lectura visual del menú.

  Este archivo NO contiene lógica PHP ni interacción con base de datos;
  su propósito es exclusivamente informativo/publicitario del menú
  del comedor para los empleados.

@module     Menú / Comedor — Visualización de menús semanales
@access     TODOS (no requiere sesión ni autenticación)

@dependencies
  - Google Fonts: Montserrat (CDN — requiere conexión a internet)
  - JavaScript vanilla (sin librerías externas)
  - Imágenes: Logo2.png (asset local del proyecto)
  - Fondo: Unsplash CDN (https://images.unsplash.com/...)

@database
  - Sin conexión a base de datos (contenido estático hardcodeado en HTML)

@session
  - No utiliza variables de sesión

@inputs
  - Ninguno. Página completamente estática.
  - Interacción: clic en botones de pestañas (manejado por JS del lado cliente)

@outputs
  - HTML renderizado al navegador con los cuatro menús semanales de Enero 2026
  - No genera JSON ni redirecciones

@security
  - Sin requerimientos de seguridad (contenido público y estático)
  - No procesa datos de usuario

@author   Equipo Tecnología BacroCorp
@version  1.0
@since    2026-01
@updated  2026-02-18
===============================================================
-->
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Menús semanales Enero 2026 - Desayunos y comidas saludables y deliciosas para cada día de la semana." />
  <title>Menús Semanales Enero 2026</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    /* Reset y variables CSS */
    :root {
      --primary-color: #5d8aa8;
      --primary-dark: #3a5a7a;
      --primary-light: #89abe3;
      --accent-color: #a8d8f0;
      --text-dark: #2c3e50;
      --text-light: #f8fbfe;
      --glass-bg: rgba(255, 255, 255, 0.25);
      --glass-border: rgba(255, 255, 255, 0.18);
      --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
    }
    
    * { 
      margin: 0; 
      padding: 0; 
      box-sizing: border-box; 
    }

    body {
      font-family: 'Montserrat', sans-serif;
      margin: 15px auto;
      max-width: 1100px;
      color: var(--text-dark);
      background: url('https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1920&q=80') no-repeat center center fixed;
      background-size: cover;
      line-height: 1.6;
      padding: 0 15px;
    }

    body::before {
      content: "";
      position: fixed;
      top:0; left:0; right:0; bottom:0;
      background: rgba(44,62,80,0.6);
      z-index: -1;
    }

    @keyframes bgMove {
      0%,100% { background-position: center 40%; }
      50% { background-position: center 50%; }
    }
    body { animation: bgMove 15s ease-in-out infinite; }

    /* Header compacto en esquina */
    .header-container {
      position: absolute;
      top: 15px;
      right: 15px;
      background: var(--glass-bg);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border-radius: 10px;
      border: 1px solid var(--glass-border);
      box-shadow: var(--glass-shadow);
      padding: 8px 12px;
      z-index: 10;
    }
    
    .logo-container {
      display: flex;
      align-items: center;
    }
    
    .logo-container img {
      width: 35px;
      height: auto;
      margin-right: 8px;
    }
    
    .nav-link {
      color: var(--accent-color);
      text-decoration: none;
      font-weight: 500;
      font-size: 0.85rem;
      padding: 5px 10px;
      border-radius: 6px;
      transition: all 0.3s ease;
      background: rgba(168, 216, 240, 0.1);
    }
    
    .nav-link:hover {
      background: rgba(168, 216, 240, 0.3);
      transform: translateY(-2px);
    }

    /* Título principal más compacto */
    header#main-header {
      text-align: center;
      font-size: 1.6rem;
      font-weight: 700;
      margin: 5px 0 15px;
      color: var(--accent-color);
      text-shadow: 0 3px 8px rgba(17,63,101,0.8);
      letter-spacing: 0.5px;
      padding-top: 10px;
    }

    /* Botones ultra compactos */
    .btn-container { 
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 6px;
      margin-bottom: 15px; 
    }
    
    button {
      background: linear-gradient(145deg, var(--primary-light), var(--primary-color));
      border: none;
      color: white;
      padding: 8px 12px;
      border-radius: 8px;
      font-size: 0.8rem;
      font-weight: 600;
      cursor: pointer;
      box-shadow: 0 3px 6px rgba(87,120,163,0.4), 
                  inset 0 -2px 4px rgba(137,171,227,0.6);
      transition: all 0.3s ease;
      min-width: 100px;
      position: relative;
      overflow: hidden;
      backdrop-filter: blur(5px);
    }
    
    button::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(255, 255, 255, 0.1);
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    button:hover {
      background: linear-gradient(145deg, #aacde5, #7da2cc);
      box-shadow: 0 4px 8px rgba(130,165,212,0.6), 
                  inset 0 -2px 4px rgba(160,190,230,0.8);
      transform: translateY(-2px);
    }
    
    button:hover::before {
      opacity: 1;
    }
    
    button:active {
      transform: translateY(1px);
      box-shadow: inset 0 2px 4px rgba(87,120,163,0.9);
    }
    
    button.active { 
      background: linear-gradient(145deg, var(--primary-dark), var(--primary-color)); 
      transform: scale(1.02);
      box-shadow: 0 0 12px rgba(93, 138, 168, 0.6);
    }
    
    button small {
      display: block;
      font-size: 0.65rem;
      margin-top: 2px;
      opacity: 0.9;
    }

    /* Secciones de menú compactas */
    .menu-section {
      display: none;
      padding: 15px;
      border-radius: 12px;
      margin-bottom: 20px;
      background: var(--glass-bg);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid var(--glass-border);
      box-shadow: var(--glass-shadow);
      animation: fadeIn 0.5s ease-out;
    }
    
    .menu-section.active { display: block; }
    
    @keyframes fadeIn { 
      from { opacity: 0; transform: translateY(10px); } 
      to { opacity: 1; transform: translateY(0); } 
    }

    .menu-section h2 {
      text-align: center;
      font-size: 1.2rem;
      font-weight: 700;
      color: var(--primary-color);
      margin-bottom: 12px;
      cursor: default;
      text-shadow: 0 1.5px 4px rgba(29,49,66,0.6);
    }

    /* Tablas con texto de alimentos más grande */
    .table-container {
      overflow-x: auto;
      border-radius: 8px;
    }
    
    table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0 6px;
      font-size: 0.9rem;
      min-width: 650px;
    }
    
    th, td { 
      padding: 12px 14px; 
      text-align: center; 
      vertical-align: middle; 
    }
    
    th {
      background-color: rgba(214, 228, 240, 0.8);
      color: var(--text-dark);
      font-weight: 700;
      font-size: 0.95rem;
      border-radius: 6px;
      padding: 14px;
      backdrop-filter: blur(5px);
    }
    
    tbody tr {
      background-color: rgba(248, 251, 254, 0.7);
      border-radius: 6px;
      box-shadow: 0 2px 6px rgba(44,62,80,0.1);
      transition: all 0.3s ease;
      backdrop-filter: blur(5px);
    }
    
    tbody tr:hover { 
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(44,62,80,0.15);
    }
    
    td { 
      border-radius: 5px; 
      background-color: rgba(255, 255, 255, 0.6);
    }

    /* Sistema de iconos expandido y específico */
    .icon-desayuno::before { content: "🥐 "; }
    .icon-comida::before { content: "🍽️ "; }
    .icon-fruta::before { content: "🍎 "; }
    .icon-yogurt::before { content: "🥣 "; }
    .icon-bebida::before { content: "🥤 "; }
    .icon-postre::before { content: "🍮 "; }
    .icon-pan::before { content: "🍞 "; }
    .icon-huevo::before { content: "🍳 "; }
    .icon-taco::before { content: "🌮 "; }
    .icon-chile::before { content: "🌶️ "; }
    .icon-pollo::before { content: "🍗 "; }
    .icon-carne::before { content: "🥩 "; }
    .icon-pescado::before { content: "🐟 "; }
    .icon-verdura::before { content: "🥗 "; }
    .icon-sopa::before { content: "🍲 "; }
    .icon-arroz::before { content: "🍚 "; }
    .icon-frijol::before { content: "🫘 "; }
    .icon-pasta::before { content: "🍝 "; }
    .icon-queso::before { content: "🧀 "; }
    .icon-jamon::before { content: "🐖 "; }
    .icon-salchicha::before { content: "🌭 "; }
    .icon-papa::before { content: "🥔 "; }
    .icon-ensalada::before { content: "🥬 "; }
    .icon-aguacate::before { content: "🥑 "; }
    .icon-maiz::before { content: "🌽 "; }
    .icon-mole::before { content: "🥘 "; }
    .icon-tortilla::before { content: "🫓 "; }
    .icon-helado::before { content: "🍦 "; }
    .icon-pastel::before { content: "🍰 "; }
    .icon-galleta::before { content: "🍪 "; }
    .icon-cafe::before { content: "☕ "; }
    .icon-te::before { content: "🍵 "; }
    .icon-jugo::before { content: "🧃 "; }
    .icon-leche::before { content: "🥛 "; }
    .icon-licuado::before { content: "🥤 "; }
    .icon-ponche::before { content: "🍹 "; }
    .icon-granola::before { content: "🥜 "; }
    .icon-miel::before { content: "🍯 "; }
    .icon-avena::before { content: "🥣 "; }
    .icon-atun::before { content: "🐟 "; }
    .icon-ejotes::before { content: "🫛 "; }
    .icon-tocino::before { content: "🥓 "; }
    .icon-champinones::before { content: "🍄 "; }
    .icon-lenteja::before { content: "🥣 "; }
    .icon-ciruela::before { content: "🟣 "; }
    .icon-chorizo::before { content: "🌭 "; }
    .icon-consome::before { content: "🍲 "; }
    .icon-chocolate::before { content: "🍫 "; }
    .icon-hongos::before { content: "🍄 "; }
    .icon-platano::before { content: "🍌 "; }
    .icon-espinacas::before { content: "🥬 "; }
    .icon-pastor::before { content: "🥘 "; }
    .icon-surimi::before { content: "🦀 "; }
    .icon-amaranto::before { content: "🌾 "; }
    .icon-romero::before { content: "🌿 "; }
    .icon-brocoli::before { content: "🥦 "; }
    .icon-pasilla::before { content: "🌶️ "; }
    .icon-nata::before { content: "🥛 "; }
    .icon-rompope::before { content: "🍮 "; }
    .icon-ajillo::before { content: "🧄 "; }

    td span.icon { 
      margin-right: 5px; 
      font-size: 1rem; 
      vertical-align: middle; 
    }
    
    .food-item {
      display: block;
      margin-bottom: 4px;
      font-size: 0.9rem;
      font-weight: 500;
    }
    
    .spicy::before {
      content: "🌶️ ";
    }

    /* Días de la semana más destacados */
    .dia-header {
      font-weight: 700;
      font-size: 0.95rem;
      color: var(--primary-dark);
    }

    /* Responsive mejorado */
    @media (max-width: 1024px) {
      body { max-width: 95%; }
      header#main-header { font-size: 1.4rem; }
    }
    
    @media (max-width: 768px) {
      body { margin: 10px; padding: 0 10px; }
      
      .header-container {
        position: relative;
        top: auto;
        right: auto;
        margin-bottom: 10px;
        padding: 6px 10px;
      }
      
      .logo-container {
        justify-content: center;
      }
      
      header#main-header { 
        font-size: 1.3rem; 
        margin: 5px 0 10px;
      }
      
      button { 
        padding: 7px 10px; 
        font-size: 0.75rem; 
        min-width: 85px;
      }
      
      .menu-section { 
        padding: 12px; 
        margin-bottom: 15px;
      }
      
      table { font-size: 0.85rem; }
      th, td { padding: 10px 12px; }
      
      .food-item {
        font-size: 0.85rem;
      }
    }
    
    @media (max-width: 480px) {
      body { margin: 8px; }
      
      header#main-header { 
        font-size: 1.2rem; 
      }
      
      button { 
        padding: 6px 8px; 
        font-size: 0.7rem; 
        min-width: 75px;
      }
      
      .btn-container { gap: 4px; }
      
      .menu-section { 
        padding: 10px; 
      }
      
      table { font-size: 0.8rem; }
      th, td { padding: 8px 10px; }
      
      .menu-section h2 {
        font-size: 1.1rem;
      }
      
      .food-item {
        font-size: 0.8rem;
      }
    }
  </style>
</head>
<body>
  <div class="header-container">
    <div class="logo-container">
      <img src="Logo2.png" alt="Logo Comedor">
      <a href="." class="nav-link">Menú principal</a>
    </div>
  </div>

  <header id="main-header">
    <h2>Menú Enero 2026</h2>
  </header>

  <div class="btn-container">
    <button class="tab-button active" data-target="semana1">Semana 1<small>5–9 Ene</small></button>
    <button class="tab-button" data-target="semana2">Semana 2<small>12–16 Ene</small></button>
    <button class="tab-button" data-target="semana3">Semana 3<small>19–23 Ene</small></button>
    <button class="tab-button" data-target="semana4">Semana 4<small>26–30 Ene</small></button>
  </div>

  <!-- SEMANA 1 - 5 al 9 de Enero 2026 -->
  <section id="semana1" class="menu-section active">
    <h2>Menú Semanal del 5 al 9 de Enero de 2026</h2>
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Día</th>
            <th class="dia-header">Lunes</th>
            <th class="dia-header">Martes</th>
            <th class="dia-header">Miércoles</th>
            <th class="dia-header">Jueves</th>
            <th class="dia-header">Viernes</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="icon-desayuno">Desayuno</td>
            <td>
              <span class="food-item icon-cafe icon-te">Café o té</span>
              <span class="food-item icon-fruta icon-yogurt">Melón con yogurt</span>
              <span class="food-item icon-huevo icon-chorizo">Huevo con chorizo y frijoles</span>
            </td>
            <td>
              <span class="food-item icon-cafe icon-te">Café o té</span>
              <span class="food-item icon-tortilla icon-huevo">Entomatadas con huevo</span>
            </td>
            <td>
              <span class="food-item icon-cafe icon-te">Café o té</span>
              <span class="food-item icon-fruta">Papaya con melón</span>
              <span class="food-item icon-tortilla">Sincronizadas (2 piezas)</span>
            </td>
            <td>
              <span class="food-item icon-cafe icon-te">Café o té</span>
              <span class="food-item icon-chile icon-pollo">Chilaquiles de morita con pollo</span>
            </td>
            <td>
              <span class="food-item icon-cafe icon-te">Café o té</span>
              <span class="food-item icon-leche icon-chocolate">Atole de cajeta</span>
              <span class="food-item icon-pan icon-salchicha">Torta de salchicha</span>
            </td>
          </tr>
          <tr>
            <td class="icon-comida">Comida</td>
            <td>
              <span class="food-item icon-arroz">Arroz amarillo</span>
              <span class="food-item icon-pollo">Pollo encacahuatado con frijoles</span>
              <span class="food-item icon-postre">Gelatina fresa de leche</span>
              <span class="food-item icon-bebida">Agua de Jamaica</span>
            </td>
            <td>
              <span class="food-item icon-consome">Consome de res</span>
              <span class="food-item icon-carne">Pacholas con ensalada</span>
              <span class="food-item icon-platano">Plátanos con crema</span>
              <span class="food-item icon-bebida">Agua de limón</span>
            </td>
            <td>
              <span class="food-item icon-pasta">Espagueti a la diabla</span>
              <span class="food-item icon-carne">Costillas BBQ con puré de papa</span>
              <span class="food-item icon-helado">Helado de fresa</span>
              <span class="food-item icon-bebida">Agua de fresa</span>
            </td>
            <td>
              <span class="food-item icon-sopa icon-hongos">Sopa de hongos</span>
              <span class="food-item icon-ensalada icon-atun">Ensalada con pasta y atún</span>
              <span class="food-item icon-postre">Gelatina mosaico</span>
              <span class="food-item icon-bebida">Agua de horchata</span>
            </td>
            <td>
              <span class="food-item icon-sopa">Sopa de fideo</span>
              <span class="food-item icon-verdura">Pastel de verdura con ensalada</span>
              <span class="food-item icon-postre">Choux</span>
              <span class="food-item icon-bebida">Agua de piña colada</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>

  <!-- SEMANA 2 - 12 al 16 de Enero 2026 -->
  <section id="semana2" class="menu-section">
    <h2>Menú Semanal del 12 al 16 de Enero de 2026</h2>
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Día</th>
            <th class="dia-header">Lunes</th>
            <th class="dia-header">Martes</th>
            <th class="dia-header">Miércoles</th>
            <th class="dia-header">Jueves</th>
            <th class="dia-header">Viernes</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="icon-desayuno">Desayuno</td>
            <td>
              <span class="food-item icon-cafe icon-te">Café o té</span>
              <span class="food-item icon-pan icon-chocolate">Hot cake con chocochips</span>
              <span class="food-item icon-huevo">Omelette de pierna con frijoles</span>
            </td>
            <td>
              <span class="food-item icon-cafe icon-te">Café o té</span>
              <span class="food-item icon-fruta icon-yogurt">Papaya con yogurt</span>
              <span class="food-item icon-chile icon-pollo">Chilaquiles suizos con pollo</span>
            </td>
            <td>
              <span class="food-item icon-cafe icon-te">Café o té</span>
              <span class="food-item icon-fruta">Melón</span>
              <span class="food-item icon-pan icon-pollo">Sándwich de pechuga de pollo y manchego</span>
            </td>
            <td>
              <span class="food-item icon-cafe icon-te">Café o té</span>
              <span class="food-item icon-fruta">Sandía con piña</span>
              <span class="food-item icon-tortilla icon-pastor">Burrito de pastor</span>
            </td>
            <td>
              <span class="food-item icon-cafe icon-te">Café o té</span>
              <span class="food-item icon-chile">Enchiladas suizas</span>
            </td>
          </tr>
          <tr>
            <td class="icon-comida">Comida</td>
            <td>
              <span class="food-item icon-arroz">Arroz rojo</span>
              <span class="food-item icon-carne">Bistec a la mexicana con frijoles</span>
              <span class="food-item icon-postre">Buñuelos</span>
              <span class="food-item icon-bebida">Agua de naranja</span>
            </td>
            <td>
              <span class="food-item icon-sopa">Sopa de munición</span>
              <span class="food-item icon-carne">Chuleta natural con papas al romero</span>
              <span class="food-item icon-postre">Gelatina bicolor</span>
              <span class="food-item icon-bebida">Agua de papaya</span>
            </td>
            <td>
              <span class="food-item icon-arroz">Arroz verde</span>
              <span class="food-item icon-pollo">Pollo a la cacerola con frijoles</span>
              <span class="food-item icon-postre">Strudell de manzana</span>
              <span class="food-item icon-bebida">Agua de sandía</span>
            </td>
            <td>
              <span class="food-item icon-lenteja">Arriero de garbanzos</span>
              <span class="food-item icon-taco">Tacos dorados de papa (4 piezas)</span>
              <span class="food-item icon-postre">Flan de vainilla</span>
              <span class="food-item icon-bebida">Agua de pepino</span>
            </td>
            <td>
              <span class="food-item icon-pasta">Codito carbonara</span>
              <span class="food-item icon-tortilla icon-surimi">Tostadas de surimi (3 piezas)</span>
              <span class="food-item icon-postre">Pastel imposible</span>
              <span class="food-item icon-bebida icon-amaranto">Agua de amaranto</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>

  <!-- SEMANA 3 - 19 al 23 de Enero 2026 -->
  <section id="semana3" class="menu-section">
    <h2>Menú Semanal del 19 al 23 de Enero de 2026</h2>
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Día</th>
            <th class="dia-header">Lunes</th>
            <th class="dia-header">Martes</th>
            <th class="dia-header">Miércoles</th>
            <th class="dia-header">Jueves</th>
            <th class="dia-header">Viernes</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="icon-desayuno">Desayuno</td>
            <td>
              <span class="food-item icon-cafe icon-te">Café o té</span>
              <span class="food-item icon-fruta">Melón con piña</span>
              <span class="food-item icon-huevo icon-jamon">Omelette de jamón</span>
            </td>
            <td>
              <span class="food-item icon-cafe icon-te">Café o té</span>
              <span class="food-item icon-chile">Enchiladas potosinas</span>
            </td>
            <td>
              <span class="food-item icon-cafe icon-te">Café o té</span>
              <span class="food-item icon-fruta icon-yogurt">Manzana con yogurt</span>
              <span class="food-item icon-tortilla icon-jamon">Quesadillas de jamón</span>
            </td>
            <td>
              <span class="food-item icon-cafe icon-te">Café o té</span>
              <span class="food-item icon-pan icon-avena">Hot cake de avena</span>
              <span class="food-item icon-fruta">Sandía</span>
            </td>
            <td>
              <span class="food-item icon-cafe icon-te">Café o té</span>
              <span class="food-item icon-leche icon-fruta">Atole de fresa</span>
              <span class="food-item icon-pan icon-jamon">Torta de jamón</span>
            </td>
          </tr>
          <tr>
            <td class="icon-comida">Comida</td>
            <td>
              <span class="food-item icon-sopa icon-verdura">Sopa de verdura</span>
              <span class="food-item icon-pollo">Pollo a las finas hierbas con verduras mantequilla</span>
              <span class="food-item icon-postre">Gelatina bicolor</span>
              <span class="food-item icon-bebida">Agua de melón</span>
            </td>
            <td>
              <span class="food-item icon-arroz">Arroz blanco</span>
              <span class="food-item icon-pescado">Filete de pescado empanizado con ensalada</span>
              <span class="food-item icon-postre icon-nata">Panqué de nata</span>
              <span class="food-item icon-bebida">Agua de guayaba</span>
            </td>
            <td>
              <span class="food-item icon-consome">Consome de pollo</span>
              <span class="food-item icon-brocoli">Tortitas de brócoli con ensalada</span>
              <span class="food-item icon-postre">Flan napolitano</span>
              <span class="food-item icon-bebida icon-te">Agua de té helado</span>
            </td>
            <td>
              <span class="food-item icon-sopa">Sopa de lengüita</span>
              <span class="food-item icon-carne icon-pasilla">Cerdo en pasilla con papas y frijoles</span>
              <span class="food-item icon-postre icon-rompope">Pastel de rompope</span>
              <span class="food-item icon-bebida">Agua de mojito</span>
            </td>
            <td>
              <span class="food-item">Hamburguesa con papas a la francesa</span>
              <span class="food-item icon-helado">Helado napolitano</span>
              <span class="food-item icon-bebida">Agua de limonada</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>

  <!-- SEMANA 4 - 26 al 30 de Enero 2026 -->
  <section id="semana4" class="menu-section">
    <h2>Menú Semanal del 26 al 30 de Enero de 2026</h2>
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Día</th>
            <th class="dia-header">Lunes</th>
            <th class="dia-header">Martes</th>
            <th class="dia-header">Miércoles</th>
            <th class="dia-header">Jueves</th>
            <th class="dia-header">Viernes</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="icon-desayuno">Desayuno</td>
            <td>
              <span class="food-item icon-cafe icon-te">Café o té</span>
              <span class="food-item icon-platano icon-yogurt">Plátano con yogurt</span>
              <span class="food-item icon-tortilla">Burrito norteño</span>
            </td>
            <td>
              <span class="food-item icon-cafe icon-te">Café o té</span>
              <span class="food-item icon-pan icon-amaranto">Hot cake de amaranto</span>
              <span class="food-item icon-huevo icon-espinacas">Omelette de espinacas</span>
            </td>
            <td>
              <span class="food-item icon-cafe icon-te">Café o té</span>
              <span class="food-item icon-yogurt icon-granola">Yogurt con granola</span>
              <span class="food-item icon-huevo">Huevos cocoyoc</span>
            </td>
            <td>
              <span class="food-item icon-cafe icon-te">Café o té</span>
              <span class="food-item icon-fruta">Papaya</span>
              <span class="food-item icon-tortilla icon-pollo">Wrap de pollo</span>
            </td>
            <td>
              <span class="food-item icon-cafe icon-te">Café o té</span>
              <span class="food-item icon-licuado icon-chocolate">Licuado de chocoplátano</span>
              <span class="food-item icon-pan icon-jamon">Sándwich de jamón y panela</span>
            </td>
          </tr>
          <tr>
            <td class="icon-comida">Comida</td>
            <td>
              <span class="food-item icon-pasta">Espagueti alfredo</span>
              <span class="food-item icon-carne">Chuleta ahumada con papas al ajillo y frijoles</span>
              <span class="food-item icon-postre">Gelatina de frutos rojos</span>
              <span class="food-item icon-bebida">Agua de piña</span>
            </td>
            <td>
              <span class="food-item icon-sopa">Sopa aguada codito</span>
              <span class="food-item icon-carne">Tortitas de carne en morita con frijoles</span>
              <span class="food-item icon-postre">Cup cake fresa</span>
              <span class="food-item icon-bebida">Agua de tamarindo</span>
            </td>
            <td>
              <span class="food-item icon-arroz">Arroz blanco</span>
              <span class="food-item icon-pescado">Pescado rebosado con ensalada</span>
              <span class="food-item icon-postre">Gelatina bicolor</span>
              <span class="food-item icon-bebida">Agua de frutas tropicales</span>
            </td>
            <td>
              <span class="food-item icon-arroz">Arroz rojo</span>
              <span class="food-item icon-pollo">Pollo en salsa verde con papas y frijoles</span>
              <span class="food-item icon-postre">Panqué de naranja</span>
              <span class="food-item icon-bebida">Agua de pepino con limón</span>
            </td>
            <td>
              <span class="food-item icon-consome icon-verdura">Consome de verduras</span>
              <span class="food-item icon-pasta">Lasaña vegetariana con ensalada</span>
              <span class="food-item icon-postre">Gelatina mosaico</span>
              <span class="food-item icon-bebida">Agua de naranjada</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>

  <!--
  =========================================================
  SECCIÓN: JavaScript — Navegación por pestañas (tabs)
  =========================================================
  Gestiona la visibilidad de las secciones de menú semanal.
  Lógica:
    1. Selecciona todos los botones .tab-button y todas las
       secciones .menu-section del documento.
    2. Al hacer clic en cualquier botón:
       a. Quita la clase "active" de TODOS los botones y secciones.
       b. Agrega la clase "active" al botón clickeado.
       c. Agrega la clase "active" a la sección cuyo id coincide
          con el atributo data-target del botón clickeado.
  La visibilidad CSS se controla con: .menu-section { display: none }
  y .menu-section.active { display: block }.
  -->
  <script>
    const buttons = document.querySelectorAll('.tab-button');
    const sections = document.querySelectorAll('.menu-section');

    buttons.forEach(btn => {
      btn.addEventListener('click', () => {
        buttons.forEach(b => b.classList.remove('active'));
        sections.forEach(s => s.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById(btn.dataset.target).classList.add('active');
      });
    });
  </script>
</body>
</html>