<?php
/**
 * ==========================================
 * BACKEND - LÓGICA DE PEDIDOS COMEDOR
 * ==========================================
 * Archivo para reutilizar la lógica del sistema
 * de pedidos en el backend
 */

// ==========================================
// CLASE: PedidosComedor
// ==========================================

class PedidosComedor {
    
    private $conn;
    
    // ==========================================
    // CONSTRUCTOR
    // ==========================================
    
    public function __construct($customConnection = null) {
        if ($customConnection) {
            $this->conn = $customConnection;
        } else {
            $this->conectarBD();
        }
    }
    
    // ==========================================
    // CONECTAR A BASE DE DATOS
    // ==========================================
    
    private function conectarBD() {
        require_once __DIR__ . '/../config/database.php';
        $this->conn = getComedorConnection();

        if (!$this->conn) {
            throw new Exception("Error de conexión: " . print_r(sqlsrv_errors(), true));
        }
    }
    
    // ==========================================
    // OBTENER DATOS DEL USUARIO POR NOMBRE
    // ==========================================
    
    /**
     * Obtiene los datos del usuario (ID, usuario, contraseña) por nombre
     * 
     * @param string $nombre - Nombre completo o parcial del empleado
     * @return array|false - Array con datos del usuario o false si no existe
     */
    public function obtenerUsuarioPorNombre($nombre) {
        $sql = "SELECT Id_Empleado, nombre, area, usuario, Contrasena 
                FROM ConPed 
                WHERE nombre LIKE ?";
        
        $params = ["%$nombre%"];
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        
        if (!$stmt || !sqlsrv_has_rows($stmt)) {
            return false;
        }
        
        $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        
        return $resultado;
    }
    
    // ==========================================
    // VALIDAR CREDENCIALES
    // ==========================================
    
    /**
     * Valida que las credenciales sean correctas
     * 
     * @param string $usuario - Usuario
     * @param string $contrasena - Contraseña
     * @return bool - true si son válidas, false si no
     */
    public function validarCredenciales($usuario, $contrasena) {
        $sql = "SELECT Usuario, Contrasena 
                FROM ConPed 
                WHERE Usuario = ? AND Contrasena = ?";
        
        $stmt = sqlsrv_query($this->conn, $sql, [$usuario, $contrasena]);
        
        if (!$stmt) {
            return false;
        }
        
        $valido = sqlsrv_has_rows($stmt);
        sqlsrv_free_stmt($stmt);
        
        return $valido;
    }
    
    // ==========================================
    // VERIFICAR PEDIDOS DUPLICADOS
    // ==========================================
    
    /**
     * Verifica si ya existe un pedido para esta fecha/usuario
     * Máximo 2 pedidos permitidos (desayuno + comida)
     * 
     * @param string $fecha - Fecha (lunes de la semana)
     * @param string $usuario - Usuario del empleado
     * @return int - Cantidad de pedidos existentes
     */
    public function contarPedidosExistentes($fecha, $usuario) {
        $sql = "SELECT COUNT(*) AS Total 
                FROM PedidosComida 
                WHERE Fecha = ? AND Usuario = ?";
        
        $stmt = sqlsrv_query($this->conn, $sql, [$fecha, $usuario]);
        
        if (!$stmt) {
            return -1;
        }
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        
        return $row['Total'] ?? 0;
    }
    
    // ==========================================
    // REGISTRAR PEDIDO SEMANAL
    // ==========================================
    
    /**
     * Registra un pedido semanal completo (Desayuno + Comida)
     * 
     * @param array $datos - Array con los datos del pedido:
     *   - id_empleado: ID del empleado
     *   - usuario: Usuario del empleado
     *   - contrasena: Contraseña del empleado
     *   - fecha: Fecha (lunes de la semana)
     *   - desayuno: Array con valores por día [lunes, martes, miercoles, jueves, viernes]
     *   - comida: Array con valores por día [lunes, martes, miercoles, jueves, viernes]
     * @return array - ['success' => bool, 'message' => string]
     */
    public function registrarPedido($datos) {
        
        // Validar datos requeridos
        $camposRequeridos = ['id_empleado', 'usuario', 'contrasena', 'fecha', 'desayuno', 'comida'];
        foreach ($camposRequeridos as $campo) {
            if (!isset($datos[$campo]) || empty($datos[$campo])) {
                return [
                    'success' => false,
                    'message' => "Campo requerido faltante: $campo"
                ];
            }
        }
        
        $id_empleado = $datos['id_empleado'];
        $usuario = $datos['usuario'];
        $contrasena = $datos['contrasena'];
        $fecha = $datos['fecha'];
        $desayuno = $datos['desayuno'];  // Array [lunes, martes, miercoles, jueves, viernes]
        $comida = $datos['comida'];      // Array [lunes, martes, miercoles, jueves, viernes]
        
        // Validar credenciales
        if (!$this->validarCredenciales($usuario, $contrasena)) {
            return [
                'success' => false,
                'message' => 'Usuario o contraseña incorrectos.'
            ];
        }
        
        // Verificar pedidos duplicados
        $pedidosExistentes = $this->contarPedidosExistentes($fecha, $usuario);
        if ($pedidosExistentes >= 2) {
            return [
                'success' => false,
                'message' => 'Ya tienes un pedido registrado para esta fecha.'
            ];
        }
        
        // Insertar pedido de desayuno
        $sql = "INSERT INTO PedidosComida 
                (Id_Empleado, Nom_Pedido, Usuario, Contrasena, Fecha, Lunes, Martes, Miercoles, Jueves, Viernes, Costo) 
                VALUES (?, '', ?, ?, ?, ?, ?, ?, ?, ?, 30)";
        
        $desayuno_params = [
            $id_empleado,
            $usuario,
            $contrasena,
            $fecha,
            $desayuno[0] ?? null,  // Lunes
            $desayuno[1] ?? null,  // Martes
            $desayuno[2] ?? null,  // Miércoles
            $desayuno[3] ?? null,  // Jueves
            $desayuno[4] ?? null   // Viernes
        ];
        
        $stmt1 = sqlsrv_query($this->conn, $sql, $desayuno_params);
        
        if (!$stmt1) {
            return [
                'success' => false,
                'message' => 'Error al registrar el pedido de desayuno.'
            ];
        }
        
        // Insertar pedido de comida
        $comida_params = [
            $id_empleado,
            $usuario,
            $contrasena,
            $fecha,
            $comida[0] ?? null,   // Lunes
            $comida[1] ?? null,   // Martes
            $comida[2] ?? null,   // Miércoles
            $comida[3] ?? null,   // Jueves
            $comida[4] ?? null    // Viernes
        ];
        
        $stmt2 = sqlsrv_query($this->conn, $sql, $comida_params);
        
        if (!$stmt2) {
            sqlsrv_free_stmt($stmt1);
            return [
                'success' => false,
                'message' => 'Error al registrar el pedido de comida.'
            ];
        }
        
        sqlsrv_free_stmt($stmt1);
        sqlsrv_free_stmt($stmt2);
        
        return [
            'success' => true,
            'message' => '¡Tu pedido se registró con éxito!'
        ];
    }
    
    // ==========================================
    // OBTENER SEMANAS DE ENERO 2026
    // ==========================================
    
    /**
     * Retorna todas las semanas de enero 2026
     * 
     * @return array - Array de semanas con fecha y descripción
     */
    public static function obtenerSemanasEnero2026() {
        return [
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
    }
    
    // ==========================================
    // SANITIZAR ENTRADA
    // ==========================================
    
    /**
     * Sanitiza datos de entrada para evitar inyecciones
     * 
     * @param string $data - Dato a sanitizar
     * @return string - Dato sanitizado
     */
    public static function sanitizarEntrada($data) {
        if (empty($data)) return '';
        
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        
        return $data;
    }
    
    // ==========================================
    // CERRAR CONEXIÓN
    // ==========================================
    
    public function cerrar() {
        if ($this->conn) {
            sqlsrv_close($this->conn);
        }
    }
    
    // ==========================================
    // DESTRUCTOR
    // ==========================================
    
    public function __destruct() {
        $this->cerrar();
    }
}

// ==========================================
// EJEMPLO DE USO
// ==========================================

/*
// Crear instancia
$pedidos = new PedidosComedor();

try {
    // 1. Obtener datos del usuario
    $usuarioData = $pedidos->obtenerUsuarioPorNombre("Juan Pérez");
    if ($usuarioData) {
        echo "Usuario encontrado: " . $usuarioData['nombre'];
    }
    
    // 2. Registrar pedido semanal
    $resultado = $pedidos->registrarPedido([
        'id_empleado' => 123,
        'usuario' => 'juan.perez',
        'contrasena' => 'password123',
        'fecha' => '2026-01-05',  // Lunes de la semana 1
        'desayuno' => [
            'Desayuno',  // Lunes
            'Desayuno',  // Martes
            null,        // Miércoles
            null,        // Jueves
            'Desayuno'   // Viernes
        ],
        'comida' => [
            'Comida',    // Lunes
            'Comida',    // Martes
            'Comida',    // Miércoles
            'Comida',    // Jueves
            'Comida'     // Viernes
        ]
    ]);
    
    if ($resultado['success']) {
        echo "✓ " . $resultado['message'];
    } else {
        echo "✗ " . $resultado['message'];
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
*/

?>
