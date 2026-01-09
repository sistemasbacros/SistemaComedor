<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Programación vehícular</title>

  <!-- Bootstrap -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />
  <!-- FontAwesome -->
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
    rel="stylesheet"
  />
  <!-- Google Fonts: Montserrat -->
  <link
    href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap"
    rel="stylesheet"
  />

  <style>
    /* Reset & base */
    * {
      box-sizing: border-box;
    }
    body,
    html {
      margin: 0;
      padding: 0;
      height: 100%;
      font-family: 'Montserrat', sans-serif;
      background-color: #f8f9fa;
      color: #222;
    }

    /* Header */
    header {
      background-color: #0d3b66; /* Azul oscuro */
      color: #e6e8ea;
      padding: 20px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: relative;
      box-shadow: 0 4px 8px rgb(13 59 102 / 0.4);
      user-select: none;
    }

    .header-title {
      font-weight: 700;
      font-size: 2.8rem;
      letter-spacing: 1.2px;
      transition: transform 0.5s ease;
      white-space: nowrap;
    }

    /* Sidebar */
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      width: 270px;
      height: 100vh;
      background-color: #e9ecef;
      padding: 30px 20px;
      box-shadow: 3px 0 8px rgb(13 59 102 / 0.15);
      overflow-y: auto;
      transition: transform 0.3s ease;
      z-index: 100;
    }
    .sidebar.hidden {
      transform: translateX(-270px);
    }

    .sidebar img {
      display: block;
      max-width: 200px;
      margin: 0 auto 25px;
      object-fit: contain;
      filter: drop-shadow(0 2px 3px rgb(13 59 102 / 0.15));
    }

    .sidebar a {
      display: flex;
      align-items: center;
      padding: 12px 16px;
      margin-bottom: 12px;
      font-weight: 600;
      color: #0d3b66;
      text-decoration: none;
      border-radius: 8px;
      font-size: 1.05rem;
      transition: background-color 0.3s ease, transform 0.2s ease;
      box-shadow: inset 0 0 0 0 transparent;
    }

    .sidebar a i {
      margin-right: 14px;
      font-size: 1.3rem;
      width: 25px;
      text-align: center;
      color: #0d3b66;
      transition: color 0.3s ease;
    }

    .sidebar a:hover {
      background-color: #0d3b66;
      color: #f1f1f1;
      transform: translateX(6px);
      box-shadow: inset 100px 0 0 0 #205493;
    }
    .sidebar a:hover i {
      color: #f1f1f1;
    }

    /* Toggle button */
    .toggle-btn {
      cursor: pointer;
      padding: 10px 14px;
      font-size: 1.6rem;
      background-color: transparent;
      border: none;
      color: #e6e8ea;
      transition: color 0.3s ease;
      user-select: none;
    }
    .toggle-btn:hover {
      color: #a8c0ff;
    }

    /* Main content */
    .main-content {
      margin-left: 270px;
      padding: 30px 40px;
      min-height: 100vh;
      transition: margin-left 0.3s ease;
    }
    .sidebar.hidden + .main-content {
      margin-left: 0;
      padding: 30px 20px;
    }

    /* Background container */
    .background {
      position: relative;
      min-height: 80vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background: linear-gradient(135deg, #e0e6f7, #c1cae9);
      border-radius: 20px;
      box-shadow: 0 10px 30px rgb(13 59 102 / 0.15);
      padding: 40px 20px;
      flex-wrap: wrap;
      gap: 30px;
    }

    /* Institutional image */
    .institutional-image {
      position: absolute;
      top: 15px;
      right: 15px;
      width: 180px;
      opacity: 0.15;
      pointer-events: none;
      user-select: none;
      filter: drop-shadow(0 2px 3px rgb(13 59 102 / 0.1));
    }

    /* Buttons container */
    .button-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 28px;
      max-width: 1000px;
      z-index: 10;
      width: 100%;
    }

    /* Buttons */
    .button {
      flex: 1 1 230px;
      max-width: 270px;
      background: linear-gradient(145deg, #0d3b66, #1e5799);
      border-radius: 16px;
      color: #f0f4ff;
      text-align: center;
      cursor: pointer;
      padding: 28px 20px;
      box-shadow:
        0 5px 15px rgba(13, 59, 102, 0.6),
        inset 0 -3px 6px rgba(255, 255, 255, 0.2);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      user-select: none;
      text-decoration: none;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 1.2rem;
      letter-spacing: 0.02em;
      position: relative;
      overflow: hidden;
    }

    .button i {
      font-size: 3.8rem;
      margin-bottom: 18px;
      filter: drop-shadow(0 1px 2px rgb(255 255 255 / 0.35));
      transition: filter 0.3s ease;
    }

    .button:hover {
      transform: translateY(-8px) scale(1.05);
      box-shadow:
        0 15px 30px rgba(13, 59, 102, 0.9),
        inset 0 -6px 12px rgba(255, 255, 255, 0.35);
      background: linear-gradient(145deg, #1e5799, #0d3b66);
    }

    .button:hover i {
      filter: drop-shadow(0 3px 8px rgba(255 255 255 / 0.6));
    }

    /* Responsive */
    @media (max-width: 992px) {
      .main-content {
        margin-left: 0;
        padding: 30px 20px;
      }
      .sidebar {
        transform: translateX(-270px);
      }
      .sidebar.hidden {
        transform: translateX(0);
      }
      .button-container {
        justify-content: center;
      }
    }

    @media (max-width: 576px) {
      .button {
        max-width: 100%;
        font-size: 1.1rem;
        padding: 24px 12px;
      }
      .button i {
        font-size: 3rem;
        margin-bottom: 14px;
      }
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <div class="header-title" id="header-title">Programación vehícular</div>
    <button class="toggle-btn" id="sidebar-toggle" aria-label="Toggle sidebar">
      &#9776;
    </button>
  </header>

  <!-- Sidebar -->
  <nav class="sidebar" id="sidebar" aria-label="Sidebar navigation">
    <img src="logo.png" alt="Logo institucional" />
    <a onclick="iniciob()" href="javascript:void(0)"><i class="fas fa-arrow-left"></i>Inicio</a>

  </nav>

  <!-- Main Content -->
  <main class="main-content" role="main">
    <section class="background" aria-label="Opciones de programación vehícular">
      <img
        src="logo.png"
        alt="Logo institucional de fondo"
        class="institutional-image"
        aria-hidden="true"
      />

      <div class="button-container">
        <a onclick="cargainfo()" href="javascript:void(0)" class="button" id="finanzasBtn" role="button" tabindex="0">
          <i class="fas fa-database"></i>
          Carga de programación vehícular
        </a>

        <a onclick="visudata()" href="javascript:void(0)" class="button" id="licitacionesBtn" role="button" tabindex="0">
          <i class="fas fa-magnifying-glass"></i>
          Visualizar programación vehícular
        </a>

        <a onclick="valdigerenci()" href="javascript:void(0)" class="button" id="validarGerencialBtn" role="button" tabindex="0">
          <i class="fas fa-clipboard-check"></i>
          Validar programación vehícular gerencial
        </a>

      </div>
    </section>
  </main>

  <!-- Bootstrap JS bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const headerTitle = document.getElementById('header-title');

    sidebarToggle.addEventListener('click', () => {
      sidebar.classList.toggle('hidden');

      if (sidebar.classList.contains('hidden')) {
        sidebarToggle.innerHTML = '&#9776;'; // hamburger
        headerTitle.style.transform = 'translateX(0)';
      } else {
        sidebarToggle.innerHTML = '&times;'; // close icon
        headerTitle.style.transform = 'translateX(250px)';
      }
    });



  function iniciob() {
      const urlParams = new URLSearchParams(window.location.search);
      let product = urlParams.get('newpwd') || '';
      product = product.replace('?', '');
      location.href = `http://192.168.100.95/SisBacrocorp/Operaciones.php?newpwd=${product}?`;
    }




    // Tus funciones para navegar
    function cargainfo() {
      const urlParams = new URLSearchParams(window.location.search);
      let product = urlParams.get('newpwd') || '';
      product = product.replace('?', '');
      location.href = `http://192.168.100.95/SisBacrocorp/FormProgVehiRes.php?newpwd=${product}?`;
    }
    function visudata() {
      const urlParams = new URLSearchParams(window.location.search);
      let product = urlParams.get('newpwd') || '';
      product = product.replace('?', '');
    
	  
	  
	  
	  if (product=='julieta.iglesias' 
		  || product=='santiago.barrera'
		  || product=='adrian.ibarra'
		  || product=='jaqueline.ventolero'
		  	  || product=='carla.prado'
		  || product=='david.nunez') {
  location.href = `http://192.168.100.95/SisBacrocorp/ProgramaVehiRes1.php?newpwd=${product}O?`;
} else {  location.href = `http://192.168.100.95/SisBacrocorp/ProgramaVehiRes1.php?newpwd=${product}?`;}
	  
	  
    }
    function valdigerenci() {
      const urlParams = new URLSearchParams(window.location.search);
      let product = urlParams.get('newpwd') || '';
      product = product.replace('?', '');
    	  if (product=='ismael.soto' || product=='ana.pineda' || product=='martin.santos') {
  location.href = `http://192.168.100.95/SisBacrocorp/Validarprogavehi.php?newpwd=${product}?`;
} 
   	else   if (product=='julieta.iglesias' 
		  || product=='santiago.barrera'
		  || product=='adrian.ibarra'
		  || product=='jaqueline.ventolero'
		  	  || product=='carla.prado'
		  || product=='david.nunez') {
  location.href = `http://192.168.100.95/SisBacrocorp/Validarprogavehi.php?newpwd=${product}O?`;
}
else  {Swal.fire({
  icon: 'error',
  title: 'Acceso denegado',
  text: 'No tienes permisos para ingresar a este módulo.',
  confirmButtonText: 'Entendido',
  confirmButtonColor: '#d33'
});  }
    }
    function valadmini() {
      const urlParams = new URLSearchParams(window.location.search);
      let product = urlParams.get('newpwd') || '';
      product = product.replace('?', '');
      // location.href = `http://192.168.100.95/SisBacrocorp/valprogvehiadmi.php?newpwd=${product}?`;
	  
	     	  if (product=='ivone.hernandez' || product=='emmanuel.mediana') {
   location.href = `http://192.168.100.95/SisBacrocorp/valprogvehiadmi.php?newpwd=${product}?`;
} else  {Swal.fire({
  icon: 'error',
  title: 'Acceso denegado',
  text: 'No tienes permisos para ingresar a este módulo.',
  confirmButtonText: 'Entendido',
  confirmButtonColor: '#d33'
});  } 
	  
	  
    }
    function valvigilancia() {
      const urlParams = new URLSearchParams(window.location.search);
      let product = urlParams.get('newpwd') || '';
      product = product.replace('?', '');
      // location.href = `http://192.168.100.95/SisBacrocorp/IngresoSalidaVigilante.php?newpwd=${product}?`;
	  
	  
	  
if (product=='ivone.hernandez' || product=='emmanuel.mediana'|| product=='Vig') {
   location.href = `http://192.168.100.95/SisBacrocorp/IngresoSalidaVigilante.php?newpwd=${product}?`;
} else  {Swal.fire({
  icon: 'error',
  title: 'Acceso denegado',
  text: 'No tienes permisos para ingresar a este módulo.',
  confirmButtonText: 'Entendido',
  confirmButtonColor: '#d33'
});  } 
	    
	  
	  
    }
	
	
	
	
	
  </script>
</body>
</html>
