<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sistema de Gestión de Comidas - Comedor Empresarial</title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    :root {
      --bg-primary: #0f172a;
      --text-light: #e2e8f0;
      --accent-blue: #3b82f6;
      --accent-purple: #a78bfa;
      --card-bg: rgba(255, 255, 255, 0.12);
      --card-border: rgba(255, 255, 255, 0.2);
      --transition: 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg-primary);
      color: var(--text-light);
      min-height: 100vh;
      overflow-x: hidden;
      position: relative;
    }

    /* Fondo: menos opaco y sin escala excesiva */
    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url('https://images.unsplash.com/photo-1579584425555-c3ce17fd4351?crop=entropy&cs=srgb&fm=jpg&w=1920&fit=max') center center / cover no-repeat;
      filter: blur(6px);
      opacity: 0.6; /* Reducido para que no tape */
      z-index: -2;
    }

    /* Overlay semitransparente (menos oscuro) */
    body::after {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(15, 23, 42, 0.7); /* Menos intenso */
      z-index: -1;
    }

    /* Header con más espacio y mejor posición */
    .header {
      position: relative;
      z-index: 10;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 30px 5% 10px; /* Más espacio arriba */
      max-width: 1400px;
      margin: 0 auto;
    }

    .back-link {
      color: #93c5fd;
      font-weight: 500;
      font-size: 0.95rem;
      display: flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
      padding: 12px 20px;
      border-radius: 16px;
      background: rgba(59, 130, 246, 0.15);
      backdrop-filter: blur(8px);
      border: 1px solid rgba(59, 130, 246, 0.3);
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .back-link:hover {
      background: rgba(59, 130, 246, 0.25);
      transform: translateY(-2px);
    }

    .logo {
      height: 60px; /* Más grande */
      filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.3));
    }

    /* Sección principal con más claridad */
    .hero {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: calc(100vh - 140px);
      padding: 60px 20px 80px;
      text-align: center;
      position: relative;
      z-index: 2;
    }

    .hero h1 {
      font-size: 2.8rem;
      font-weight: 700;
      background: linear-gradient(90deg, #93c5fd, #a78bfa);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 16px;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .hero p {
      font-size: 1.1rem;
      color: #cbd5e1;
      max-width: 700px;
      margin: 0 auto 40px;
      line-height: 1.7;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }

    /* Grid de opciones */
    .options-grid {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 32px;
      max-width: 1100px;
      margin: 0 auto;
    }

    .card {
      width: 280px;
      height: 320px;
      border-radius: 20px;
      overflow: hidden;
      cursor: pointer;
      opacity: 0;
      transform: translateY(40px);
      transition: var(--transition);
    }

    .card.visible {
      opacity: 1;
      transform: translateY(0);
    }

    .glass-content {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: var(--card-bg);
      backdrop-filter: blur(10px);
      border: 1px solid var(--card-border);
      border-radius: 20px;
      padding: 30px 20px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      color: white;
      text-align: center;
      transition: all 0.4s ease;
    }

    .card:hover .glass-content {
      transform: translateY(-10px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2), 0 0 30px rgba(59, 130, 246, 0.1);
    }

    .icon-box {
      font-size: 52px;
      margin-bottom: 20px;
      color: white;
    }

    .glass-content h3 {
      font-size: 1.4rem;
      font-weight: 600;
      margin-bottom: 14px;
      color: white;
    }

    .glass-content p {
      font-size: 0.95rem;
      color: rgba(255, 255, 255, 0.9);
      line-height: 1.6;
      margin-bottom: 20px;
    }

    .card-btn {
      padding: 10px 20px;
      font-size: 0.95rem;
      font-weight: 500;
      color: white;
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.3);
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .card-btn:hover {
      background: rgba(255, 255, 255, 0.2);
      transform: scale(1.08);
    }

    /* Footer */
    .footer {
      position: relative;
      z-index: 2;
      text-align: center;
      padding: 20px;
      font-size: 0.85rem;
      color: #94a3b8;
    }

    /* Ajuste responsive */
    @media (max-width: 768px) {
      .hero h1 {
        font-size: 2.3rem;
      }
      .header {
        padding: 20px 5% 10px;
      }
      .logo {
        height: 50px;
      }
      .back-link {
        padding: 10px 16px;
        font-size: 0.9rem;
      }
    }
  </style>
</head>
<body>

  <!-- Header -->
  <header class="header">
    <a href="Demo_SistemaComedor.html" class="back-link">
      <i class="fas fa-arrow-left"></i> Volver al menú
    </a>
    <img src="Logo2.png" alt="Logo del sistema de comedor" class="logo">
  </header>

  <!-- Hero -->
  <section class="hero">
    <h1>Sistema de Gestión de Comidas</h1>
    <p>
      Plataforma interna para la administración de consumos en el comedor corporativo. 
      Acceso seguro y trazabilidad total de solicitudes.
    </p>

    <div class="options-grid" id="optionsGrid">
      <!-- Tarjeta 1 -->
      <div class="card" data-delay="0">
        <div class="glass-content">
          <div class="icon-box">
            <i class="fas fa-check-circle"></i>
          </div>
          <h3>Validar Órdenes</h3>
          <p>Confirma la recepción y validez de las órdenes de comida registradas por empleados.</p>
          <button class="card-btn" onclick="window.location.href='LoginValidarOrdenes.php'">Ingresar</button>
        </div>
      </div>

      <!-- Tarjeta 2 -->
      <div class="card" data-delay="100">
        <div class="glass-content">
          <div class="icon-box">
            <i class="fas fa-times-circle"></i>
          </div>
          <h3>Solicitar Cancelación</h3>
          <p>Cancela tu consumo programado con hasta 24 horas de anticipación.</p>
          <button class="card-btn" onclick="window.location.href='LoginFormCancel.php'">Solicitar</button>
        </div>
      </div>

      <!-- Tarjeta 3 -->
      <div class="card" data-delay="200">
        <div class="glass-content">
          <div class="icon-box">
            <i class="fas fa-search"></i>
          </div>
          <h3>Consultar Estado</h3>
          <p>Revisa el estado actual de tus solicitudes: pendientes, procesadas o rechazadas.</p>
          <button class="card-btn" onclick="window.location.href='Estformcancel.php'">Ver Estado</button>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <p>Sistema interno © 2025 | Todos los derechos reservados</p>
  </footer>

  <!-- Scripts corregidos y simplificados -->
  <script>
    // Asegurarse de que las tarjetas aparezcan incluso si hay errores
    document.addEventListener('DOMContentLoaded', () => {
      const cards = document.querySelectorAll('.card');
      
      cards.forEach(card => {
        const delay = parseInt(card.getAttribute('data-delay')) || 0;
        
        // Mostrar tarjeta sin depender de animaciones complejas
        setTimeout(() => {
          card.style.opacity = '1';
          card.style.transform = 'translateY(0)';
        }, 300 + delay);
      });
    });

    // Diagnóstico de imagen
    window.addEventListener('load', () => {
      const img = new Image();
      img.src = 'https://images.unsplash.com/photo-1579584425555-c3ce17fd4351?crop=entropy&cs=srgb&fm=jpg&w=1920&fit=max';
      img.onload = () => console.log('✅ Fondo: Imagen cargada');
      img.onerror = () => console.error('❌ Fondo: Error al cargar imagen');
    });
  </script>

</body>
</html>