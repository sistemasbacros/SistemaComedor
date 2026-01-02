<?php
// Configuración de conexión a SQL Server
$serverName = "DESAROLLO-BACRO\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "Comedor",
    "Uid" => "Larome03",
    "PWD" => "Larome03",
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true
);

// Inicializar variables
$mensaje = '';
$empleados = array();
$search_term = '';

// Establecer conexión
try {
    $conn = sqlsrv_connect($serverName, $connectionOptions);
    
    if ($conn === false) {
        $errors = sqlsrv_errors();
        $mensaje = '<div class="alert alert-danger">Error de conexión: ' . $errors[0]['message'] . '</div>';
    } else {
        // Procesar búsqueda
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search_term = trim($_GET['search']);
        }

        // Procesar formularios
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['accion'])) {
            switch ($_POST['accion']) {
                case 'insertar_empleado':
                    if (!empty($_POST['id_empleado']) && !empty($_POST['nombre']) && !empty($_POST['area'])) {
                        
                        // Verificar si el ID ya existe
                        $sql_check = "SELECT COUNT(*) as count FROM ConPed WHERE Id_Empleado = ?";
                        $params_check = array($_POST['id_empleado']);
                        $stmt_check = sqlsrv_query($conn, $sql_check, $params_check);
                        
                        if ($stmt_check !== false) {
                            $row = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
                            if ($row['count'] > 0) {
                                $mensaje = '<div class="alert alert-warning">El ID de empleado ya existe</div>';
                                break;
                            }
                        }
                        
                        // Insertar en tabla ConPed
                        $sql_empleados = "INSERT INTO ConPed (Id_Empleado, Nombre, Area, Usuario, Contrasena) VALUES (?, ?, ?, ?, ?)";
                        $params_empleados = array(
                            $_POST['id_empleado'],
                            $_POST['nombre'],
                            $_POST['area'],
                            $_POST['usuario'] ?? null,
                            $_POST['contrasena'] ?? null
                        );
                        
                        $stmt_empleados = sqlsrv_query($conn, $sql_empleados, $params_empleados);
                        
                        if ($stmt_empleados === false) {
                            $errors = sqlsrv_errors();
                            $mensaje = '<div class="alert alert-danger">Error al insertar en ConPed: ' . $errors[0]['message'] . '</div>';
                        } else {
                            // Insertar en tabla Catalogo_EmpArea
                            $sql_catalogo = "INSERT INTO Catalogo_EmpArea (Id_Empleado, Nombre, Area) VALUES (?, ?, ?)";
                            $params_catalogo = array(
                                $_POST['id_empleado'],
                                $_POST['nombre'],
                                $_POST['area']
                            );
                            
                            $stmt_catalogo = sqlsrv_query($conn, $sql_catalogo, $params_catalogo);
                            
                            if ($stmt_catalogo === false) {
                                $errors = sqlsrv_errors();
                                $mensaje = '<div class="alert alert-warning">Empleado creado en ConPed pero error al insertar en Catalogo_EmpArea: ' . $errors[0]['message'] . '</div>';
                            } else {
                                $mensaje = '<div class="alert alert-success">Empleado creado correctamente en ambas tablas</div>';
                            }
                        }
                    } else {
                        $mensaje = '<div class="alert alert-warning">Por favor complete todos los campos obligatorios</div>';
                    }
                    break;
                
                case 'actualizar_empleado':
                    if (!empty($_POST['edit_id_empleado']) && !empty($_POST['edit_nombre']) && !empty($_POST['edit_area'])) {
                        
                        $id_empleado_original = $_POST['edit_id_empleado_original'];
                        $id_empleado_nuevo = $_POST['edit_id_empleado'];
                        
                        // Si el ID cambió, verificar que el nuevo ID no exista
                        if ($id_empleado_original != $id_empleado_nuevo) {
                            $sql_check = "SELECT COUNT(*) as count FROM ConPed WHERE Id_Empleado = ?";
                            $params_check = array($id_empleado_nuevo);
                            $stmt_check = sqlsrv_query($conn, $sql_check, $params_check);
                            
                            if ($stmt_check !== false) {
                                $row = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
                                if ($row['count'] > 0) {
                                    $mensaje = '<div class="alert alert-warning">El nuevo ID de empleado ya existe</div>';
                                    break;
                                }
                            }
                        }
                        
                        // Si no se proporciona nueva contraseña, mantener la actual
                        $nueva_contrasena = $_POST['edit_contrasena'] ?? null;
                        
                        // Actualizar tabla ConPed
                        if (empty($nueva_contrasena)) {
                            // Mantener contraseña actual
                            $sql_empleados = "UPDATE ConPed SET Id_Empleado = ?, Nombre = ?, Area = ?, Usuario = ? WHERE Id_Empleado = ?";
                            $params_empleados = array(
                                $id_empleado_nuevo,
                                $_POST['edit_nombre'],
                                $_POST['edit_area'],
                                $_POST['edit_usuario'] ?? null,
                                $id_empleado_original
                            );
                        } else {
                            // Actualizar con nueva contraseña
                            $sql_empleados = "UPDATE ConPed SET Id_Empleado = ?, Nombre = ?, Area = ?, Usuario = ?, Contrasena = ? WHERE Id_Empleado = ?";
                            $params_empleados = array(
                                $id_empleado_nuevo,
                                $_POST['edit_nombre'],
                                $_POST['edit_area'],
                                $_POST['edit_usuario'] ?? null,
                                $nueva_contrasena,
                                $id_empleado_original
                            );
                        }
                        
                        $stmt_empleados = sqlsrv_query($conn, $sql_empleados, $params_empleados);
                        
                        if ($stmt_empleados === false) {
                            $errors = sqlsrv_errors();
                            $mensaje = '<div class="alert alert-danger">Error al actualizar en ConPed: ' . $errors[0]['message'] . '</div>';
                        } else {
                            // Actualizar tabla Catalogo_EmpArea
                            $sql_catalogo = "UPDATE Catalogo_EmpArea SET Id_Empleado = ?, Nombre = ?, Area = ? WHERE Id_Empleado = ?";
                            $params_catalogo = array(
                                $id_empleado_nuevo,
                                $_POST['edit_nombre'],
                                $_POST['edit_area'],
                                $id_empleado_original
                            );
                            
                            $stmt_catalogo = sqlsrv_query($conn, $sql_catalogo, $params_catalogo);
                            
                            if ($stmt_catalogo === false) {
                                $errors = sqlsrv_errors();
                                $mensaje = '<div class="alert alert-warning">Empleado actualizado en ConPed pero error al actualizar en Catalogo_EmpArea: ' . $errors[0]['message'] . '</div>';
                            } else {
                                $mensaje = '<div class="alert alert-success">Empleado actualizado correctamente en ambas tablas</div>';
                            }
                        }
                    } else {
                        $mensaje = '<div class="alert alert-warning">Por favor complete todos los campos obligatorios</div>';
                    }
                    break;
                
                case 'eliminar_empleado':
                    if (!empty($_POST['delete_id_empleado'])) {
                        $id_empleado = $_POST['delete_id_empleado'];
                        
                        // Eliminar de tabla Catalogo_EmpArea primero
                        $sql_delete_catalogo = "DELETE FROM Catalogo_EmpArea WHERE Id_Empleado = ?";
                        $params_delete_catalogo = array($id_empleado);
                        $stmt_delete_catalogo = sqlsrv_query($conn, $sql_delete_catalogo, $params_delete_catalogo);
                        
                        if ($stmt_delete_catalogo === false) {
                            $errors = sqlsrv_errors();
                            $mensaje = '<div class="alert alert-danger">Error al eliminar de Catalogo_EmpArea: ' . $errors[0]['message'] . '</div>';
                        } else {
                            // Eliminar de tabla ConPed
                            $sql_delete_conped = "DELETE FROM ConPed WHERE Id_Empleado = ?";
                            $params_delete_conped = array($id_empleado);
                            $stmt_delete_conped = sqlsrv_query($conn, $sql_delete_conped, $params_delete_conped);
                            
                            if ($stmt_delete_conped === false) {
                                $errors = sqlsrv_errors();
                                $mensaje = '<div class="alert alert-warning">Empleado eliminado de Catalogo_EmpArea pero error al eliminar de ConPed: ' . $errors[0]['message'] . '</div>';
                            } else {
                                $mensaje = '<div class="alert alert-success">Empleado eliminado correctamente de ambas tablas</div>';
                            }
                        }
                    }
                    break;
            }
        }
        
        // Obtener datos para mostrar con búsqueda
        if (!empty($search_term)) {
            $sql_empleados = "SELECT * FROM ConPed WHERE 
                             Id_Empleado LIKE ? OR 
                             Nombre LIKE ? OR 
                             Area LIKE ? OR 
                             Usuario LIKE ? 
                             ORDER BY Id_Empleado";
            $search_param = "%" . $search_term . "%";
            $params_empleados = array($search_param, $search_param, $search_param, $search_param);
            $stmt_empleados = sqlsrv_query($conn, $sql_empleados, $params_empleados);
        } else {
            $sql_empleados = "SELECT * FROM ConPed ORDER BY Id_Empleado";
            $stmt_empleados = sqlsrv_query($conn, $sql_empleados);
        }
        
        if ($stmt_empleados !== false) {
            while ($row = sqlsrv_fetch_array($stmt_empleados, SQLSRV_FETCH_ASSOC)) {
                $empleados[] = $row;
            }
        }
    }
} catch (Exception $e) {
    $mensaje = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Comedor - Gestión de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-dark: #1a365d;
            --primary-blue: #2d5f9d;
            --accent-blue: #3b82f6;
            --secondary-blue: #60a5fa;
            --light-blue: #dbeafe;
            --white-pearl: #f8fafc;
            --light-gray: #e2e8f0;
            --medium-gray: #94a3b8;
            --dark-gray: #475569;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --card-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            --glass-bg: rgba(255, 255, 255, 0.8);
            --glass-border: rgba(255, 255, 255, 0.5);
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-dark) 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
            color: var(--dark-gray);
            padding: 20px;
        }
        
        .glass-effect {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            box-shadow: var(--card-shadow);
        }
        
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
            background: var(--white-pearl);
        }
        
        .card:hover {
            transform: translateY(-8px);
            box-shadow: var(--hover-shadow);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: white;
            border-radius: 16px 16px 0 0 !important;
            font-weight: 600;
            padding: 18px 25px;
            position: relative;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .card-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, rgba(255,255,255,0.5), transparent);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent-blue), var(--primary-blue));
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #0d966c);
            border: none;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color), #d97706);
            border: none;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
            border: none;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }
        
        .action-btn {
            margin: 3px;
            border-radius: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .action-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .table th {
            background-color: var(--light-blue);
            color: var(--primary-dark);
            font-weight: 600;
            border-top: none;
        }
        
        .table td {
            background-color: var(--white-pearl);
            color: var(--dark-gray);
            border-color: var(--light-gray);
        }
        
        .top-bar {
            background: var(--white-pearl);
            padding: 18px 25px;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid var(--light-gray);
        }
        
        .search-box {
            position: relative;
            width: 320px;
        }
        
        .search-box input {
            padding-left: 45px;
            border-radius: 30px;
            border: 1px solid var(--light-gray);
            background: var(--white-pearl);
            color: var(--dark-gray);
        }
        
        .search-box input::placeholder {
            color: var(--medium-gray);
        }
        
        .search-box i {
            position: absolute;
            left: 18px;
            top: 13px;
            color: var(--medium-gray);
        }
        
        .form-control, .form-select {
            background: var(--white-pearl);
            border: 1px solid var(--light-gray);
            color: var(--dark-gray);
            border-radius: 10px;
        }
        
        .form-control:focus, .form-select:focus {
            background: var(--white-pearl);
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.15);
            color: var(--dark-gray);
        }
        
        .form-control::placeholder {
            color: var(--medium-gray);
        }
        
        .form-label {
            color: var(--primary-dark);
            font-weight: 500;
        }
        
        .alert {
            border-radius: 12px;
            border: 1px solid var(--light-gray);
        }
        
        .modal-content {
            background: var(--white-pearl);
            border-radius: 16px;
            border: 1px solid var(--light-gray);
        }
        
        .modal-header {
            border-bottom: 1px solid var(--light-gray);
        }
        
        .section-title {
            color: var(--white-pearl);
            margin-bottom: 30px;
            padding-bottom: 18px;
            border-bottom: 3px solid var(--accent-blue);
            position: relative;
            text-align: center;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-blue), transparent);
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 25px;
            margin-bottom: 15px;
        }
        
        .user-avatar {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-blue), var(--secondary-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 18px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .user-info h5 {
            margin: 0;
            font-size: 1.1rem;
            color: white;
        }
        
        .user-info p {
            margin: 0;
            font-size: 0.85rem;
            opacity: 0.8;
            color: white;
        }
        
        @media (max-width: 768px) {
            .search-box {
                width: 220px;
            }
            
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-box {
                width: 100%;
                margin-bottom: 15px;
            }
            
            .top-bar-actions {
                width: 100%;
                display: flex;
                justify-content: space-between;
            }
        }
        
        @media (max-width: 576px) {
            body {
                padding: 10px;
            }
            
            .card-body {
                padding: 15px;
            }
        }
    </style>
</head>
<body>


    <!-- Main Content -->
    <div class="container-fluid">
   
        </div>
        
        <!-- Mensajes del sistema -->
        <?php echo $mensaje; ?>
        
        <!-- Gestión de Usuarios Section -->
        <div id="usuarios" class="section">
       
            
            <!-- Formulario para crear usuario -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user-plus me-2"></i>Crear Nuevo Usuario
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="accion" value="insertar_empleado">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">ID Empleado *</label>
                                <input type="number" name="id_empleado" class="form-control" placeholder="ID del empleado" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Nombre Completo *</label>
                                <input type="text" name="nombre" class="form-control" placeholder="Nombre completo" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Área *</label>
                                <input type="text" name="area" class="form-control" placeholder="Área de trabajo" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Usuario</label>
                                <input type="text" name="usuario" class="form-control" placeholder="Nombre de usuario">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contraseña</label>
                                <input type="password" name="contrasena" class="form-control" placeholder="Contraseña">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-user-plus me-2"></i>Crear Usuario
                        </button>
                    </form>
                </div>
            </div>

            <!-- Lista de usuarios -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-users me-2"></i>Lista de Usuarios
                    </div>
                    <div class="d-flex align-items-center">
                        <!-- Formulario de búsqueda -->
                        <form method="GET" action="" class="d-flex me-2" id="search-form">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Buscar usuarios..." value="<?php echo htmlspecialchars($search_term); ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                                <?php if (!empty($search_term)): ?>
                                <a href="?" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Información de búsqueda -->
                    <?php if (!empty($search_term)): ?>
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Mostrando resultados para: "<strong><?php echo htmlspecialchars($search_term); ?></strong>"
                        <a href="?" class="float-end">Ver todos</a>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Tabla de empleados -->
                    <h5 class="mb-3">Usuarios Registrados (<?php echo count($empleados); ?> registros)</h5>
                    <?php if (empty($empleados)): ?>
                        <div class="alert alert-info">
                            <?php if (!empty($search_term)): ?>
                                No se encontraron usuarios que coincidan con la búsqueda.
                            <?php else: ?>
                                No hay usuarios registrados.
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive mb-4">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Área</th>
                                    <th>Usuario</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($empleados as $empleado): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($empleado['Id_Empleado']); ?></td>
                                    <td><?php echo htmlspecialchars($empleado['Nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($empleado['Area']); ?></td>
                                    <td><?php echo htmlspecialchars($empleado['Usuario'] ?? 'No asignado'); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary action-btn edit-user-btn" 
                                                data-id="<?php echo $empleado['Id_Empleado']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($empleado['Nombre']); ?>"
                                                data-area="<?php echo htmlspecialchars($empleado['Area']); ?>"
                                                data-usuario="<?php echo htmlspecialchars($empleado['Usuario'] ?? ''); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger action-btn delete-user-btn" 
                                                data-id="<?php echo $empleado['Id_Empleado']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($empleado['Nombre']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Usuario -->
    <div class="modal fade" id="editarUsuarioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="" id="editarUsuarioForm">
                        <input type="hidden" name="accion" value="actualizar_empleado">
                        <input type="hidden" name="edit_id_empleado_original" id="edit_id_empleado_original">
                        
                        <div class="mb-3">
                            <label class="form-label">ID Empleado *</label>
                            <input type="number" name="edit_id_empleado" id="edit_id_empleado" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nombre Completo *</label>
                            <input type="text" name="edit_nombre" id="edit_nombre" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Área *</label>
                            <input type="text" name="edit_area" id="edit_area" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Usuario</label>
                            <input type="text" name="edit_usuario" id="edit_usuario" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nueva Contraseña</label>
                            <input type="password" name="edit_contrasena" id="edit_contrasena" class="form-control" placeholder="Dejar vacío para mantener la actual">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="editarUsuarioForm" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Eliminar Usuario -->
    <div class="modal fade" id="eliminarUsuarioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Eliminar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de que desea eliminar al usuario <strong id="delete-user-name"></strong>?</p>
                    <p class="text-danger"><small>Esta acción no se puede deshacer.</small></p>
                    <form method="POST" action="" id="eliminarUsuarioForm">
                        <input type="hidden" name="accion" value="eliminar_empleado">
                        <input type="hidden" name="delete_id_empleado" id="delete_id_empleado">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="eliminarUsuarioForm" class="btn btn-danger">Eliminar Usuario</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Modal functionality para editar
        document.addEventListener('click', function(e) {
            if (e.target.closest('.edit-user-btn')) {
                const button = e.target.closest('.edit-user-btn');
                
                const id = button.getAttribute('data-id');
                const nombre = button.getAttribute('data-nombre');
                const area = button.getAttribute('data-area');
                const usuario = button.getAttribute('data-usuario');
                
                console.log('Editando usuario:', {id, nombre, area, usuario});
                
                // Llenar el formulario del modal
                document.getElementById('edit_id_empleado_original').value = id;
                document.getElementById('edit_id_empleado').value = id;
                document.getElementById('edit_nombre').value = nombre;
                document.getElementById('edit_area').value = area;
                document.getElementById('edit_usuario').value = usuario;
                document.getElementById('edit_contrasena').value = '';
                
                // Mostrar el modal
                const modal = new bootstrap.Modal(document.getElementById('editarUsuarioModal'));
                modal.show();
                
                // Enfocar el primer campo después de que el modal se muestre
                setTimeout(() => {
                    document.getElementById('edit_id_empleado').focus();
                }, 500);
            }
            
            // Modal functionality para eliminar
            if (e.target.closest('.delete-user-btn')) {
                const button = e.target.closest('.delete-user-btn');
                
                const id = button.getAttribute('data-id');
                const nombre = button.getAttribute('data-nombre');
                
                // Llenar el formulario del modal
                document.getElementById('delete_id_empleado').value = id;
                document.getElementById('delete-user-name').textContent = nombre;
                
                // Mostrar el modal
                const modal = new bootstrap.Modal(document.getElementById('eliminarUsuarioModal'));
                modal.show();
            }
        });

        // Búsqueda en tiempo real (opcional)
        document.querySelector('.search-box input').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            if (searchTerm.length > 2) {
                // Aquí puedes implementar búsqueda en tiempo real si lo deseas
                console.log('Buscando:', searchTerm);
            }
        });

        // Prevenir reenvío de formulario
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>