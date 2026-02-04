/**
 * Backend API para Sistema de Comedor
 * Escucha en: http://localhost:3000/api/pedidos/agendar-pedidos
 * 
 * Instalar dependencias:
 * npm install express body-parser cors mssql dotenv
 */

const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');
const sql = require('mssql');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

// Configuración de SQL Server
const dbConfig = {
    server: process.env.DB_SERVER || 'DESAROLLO-BACRO\\SQLEXPRESS',
    database: process.env.DB_NAME || 'Comedor',
    authentication: {
        type: 'default',
        options: {
            userName: process.env.DB_USER || 'Larome03',
            password: process.env.DB_PASSWORD || 'Larome03'
        }
    },
    options: {
        encrypt: process.env.DB_ENCRYPT === 'true',
        trustServerCertificate: true,
        enableKeepAlive: true,
        connectionTimeout: 30000,
        requestTimeout: 30000,
    }
};

let pool;

// Conectar a la base de datos
async function connectDB() {
    try {
        pool = new sql.ConnectionPool(dbConfig);
        await pool.connect();
        console.log('✅ Conectado a SQL Server');
    } catch (err) {
        console.error('❌ Error al conectar a BD:', err);
        setTimeout(connectDB, 5000); // Reintentar cada 5 segundos
    }
}

// ============================================================
// ENDPOINTS
// ============================================================

/**
 * GET /api/status
 * Verifica el estado de la API
 */
app.get('/api/status', (req, res) => {
    res.json({
        success: true,
        message: 'API disponible',
        timestamp: new Date().toISOString(),
        database: pool ? (pool.connected ? 'Conectado' : 'Desconectado') : 'No inicializado'
    });
});

/**
 * POST /api/pedidos/agendar-pedidos
 * Registra los pedidos de desayuno y comida para una semana
 */
app.post('/api/pedidos/agendar-pedidos', async (req, res) => {
    try {
        const { fecha_semana, usuario, contrasena, id_empleado, desayunos, comidas } = req.body;

        console.log('📨 Solicitud recibida:', {
            fecha_semana,
            usuario,
            id_empleado,
            desayunos_count: Object.values(desayunos).filter(v => v === 'Desayuno').length,
            comidas_count: Object.values(comidas).filter(v => v === 'Comida').length
        });

        // ========== VALIDACIONES ==========

        // 1. Validar estructura del payload
        if (!fecha_semana) {
            return res.status(400).json({
                success: false,
                error: 'fecha_semana es requerida',
                timestamp: new Date().toISOString()
            });
        }

        if (!desayunos || typeof desayunos !== 'object') {
            return res.status(400).json({
                success: false,
                error: 'desayunos debe ser un objeto válido',
                timestamp: new Date().toISOString()
            });
        }

        if (!comidas || typeof comidas !== 'object') {
            return res.status(400).json({
                success: false,
                error: 'comidas debe ser un objeto válido',
                timestamp: new Date().toISOString()
            });
        }

        // 2. Validar que la fecha sea un lunes
        const fecha = new Date(fecha_semana);
        if (isNaN(fecha.getTime())) {
            return res.status(400).json({
                success: false,
                error: 'fecha_semana debe ser una fecha válida (YYYY-MM-DD)',
                timestamp: new Date().toISOString()
            });
        }

        if (fecha.getDay() !== 1) { // 1 = Lunes
            return res.status(400).json({
                success: false,
                error: 'fecha_semana debe ser un lunes',
                timestamp: new Date().toISOString()
            });
        }

        // 3. Validar que exista al menos una selección
        const diasDesayuno = Object.values(desayunos).filter(v => v === 'Desayuno').length;
        const diasComida = Object.values(comidas).filter(v => v === 'Comida').length;

        if (diasDesayuno === 0 && diasComida === 0) {
            return res.status(400).json({
                success: false,
                error: 'Debes seleccionar al menos un desayuno o comida',
                timestamp: new Date().toISOString()
            });
        }

        // ========== CONECTAR A BD ==========

        if (!pool) {
            return res.status(503).json({
                success: false,
                error: 'Base de datos no disponible',
                timestamp: new Date().toISOString()
            });
        }

        // ========== VERIFICAR DUPLICADOS ==========

        const checkQuery = `
            SELECT COUNT(*) as total 
            FROM PedidosComida 
            WHERE Fecha = CAST(? AS DATE) AND Usuario = ?
        `;

        const checkRequest = pool.request();
        checkRequest.input('fecha', sql.Date, fecha_semana);
        checkRequest.input('usuario', sql.VarChar, usuario);

        const checkResult = await checkRequest.query(checkQuery);

        if (checkResult.recordset[0].total >= 2) {
            return res.status(409).json({
                success: false,
                error: 'Ya existe un pedido registrado para esta fecha. Máximo 2 pedidos por usuario/semana.',
                timestamp: new Date().toISOString()
            });
        }

        // ========== INSERTAR PEDIDOS ==========

        const insertDesayuno = `
            INSERT INTO PedidosComida 
            (Id_Empleado, Nom_Pedido, Usuario, Contrasena, Fecha, Lunes, Martes, Miercoles, Jueves, Viernes, Costo) 
            VALUES (?, ?, ?, ?, CAST(? AS DATE), ?, ?, ?, ?, ?, 30)
        `;

        const insertComida = `
            INSERT INTO PedidosComida 
            (Id_Empleado, Nom_Pedido, Usuario, Contrasena, Fecha, Lunes, Martes, Miercoles, Jueves, Viernes, Costo) 
            VALUES (?, ?, ?, ?, CAST(? AS DATE), ?, ?, ?, ?, ?, 30)
        `;

        // Insertar desayuno
        const reqDesayuno = pool.request();
        reqDesayuno.input('id_empleado', sql.Int, id_empleado || 0);
        reqDesayuno.input('nom_pedido', sql.VarChar, 'Desayuno');
        reqDesayuno.input('usuario', sql.VarChar, usuario);
        reqDesayuno.input('contrasena', sql.VarChar, contrasena);
        reqDesayuno.input('fecha', sql.Date, fecha_semana);
        reqDesayuno.input('lunes', sql.VarChar, desayunos.lunes || null);
        reqDesayuno.input('martes', sql.VarChar, desayunos.martes || null);
        reqDesayuno.input('miercoles', sql.VarChar, desayunos.miercoles || null);
        reqDesayuno.input('jueves', sql.VarChar, desayunos.jueves || null);
        reqDesayuno.input('viernes', sql.VarChar, desayunos.viernes || null);

        await reqDesayuno.query(insertDesayuno);
        console.log('✅ Desayuno insertado');

        // Insertar comida
        const reqComida = pool.request();
        reqComida.input('id_empleado', sql.Int, id_empleado || 0);
        reqComida.input('nom_pedido', sql.VarChar, 'Comida');
        reqComida.input('usuario', sql.VarChar, usuario);
        reqComida.input('contrasena', sql.VarChar, contrasena);
        reqComida.input('fecha', sql.Date, fecha_semana);
        reqComida.input('lunes', sql.VarChar, comidas.lunes || null);
        reqComida.input('martes', sql.VarChar, comidas.martes || null);
        reqComida.input('miercoles', sql.VarChar, comidas.miercoles || null);
        reqComida.input('jueves', sql.VarChar, comidas.jueves || null);
        reqComida.input('viernes', sql.VarChar, comidas.viernes || null);

        await reqComida.query(insertComida);
        console.log('✅ Comida insertada');

        // ========== RESPUESTA DE ÉXITO ==========

        return res.status(200).json({
            success: true,
            message: `Pedidos creados exitosamente para la semana del ${fecha_semana}`,
            data: {
                fecha_semana,
                usuario,
                desayunos_registrados: diasDesayuno,
                comidas_registradas: diasComida,
                total_items: diasDesayuno + diasComida
            },
            timestamp: new Date().toISOString()
        });

    } catch (err) {
        console.error('❌ Error en agendar-pedidos:', err);

        return res.status(500).json({
            success: false,
            error: 'Error al procesar la solicitud: ' + err.message,
            timestamp: new Date().toISOString()
        });
    }
});

/**
 * GET /api/pedidos/usuario/:id
 * Obtiene todos los pedidos de un usuario
 */
app.get('/api/pedidos/usuario/:id', async (req, res) => {
    try {
        const usuarioId = req.params.id;

        if (!pool) {
            return res.status(503).json({ success: false, error: 'BD no disponible' });
        }

        const query = `
            SELECT Id_Empleado, Nom_Pedido, Usuario, Fecha, Lunes, Martes, Miercoles, Jueves, Viernes, Costo
            FROM PedidosComida 
            WHERE Usuario = ?
            ORDER BY Fecha DESC
        `;

        const request = pool.request();
        request.input('usuario', sql.VarChar, usuarioId);

        const result = await request.query(query);

        return res.json({
            success: true,
            data: result.recordset
        });

    } catch (err) {
        console.error('❌ Error en GET pedidos:', err);
        return res.status(500).json({
            success: false,
            error: err.message
        });
    }
});

/**
 * GET /api/semanas
 * Obtiene todas las semanas disponibles
 */
app.get('/api/semanas', (req, res) => {
    const semanas = [
        { numero_semana: 1, fecha_lunes: '2026-01-05', fecha_viernes: '2026-01-09' },
        { numero_semana: 2, fecha_lunes: '2026-01-12', fecha_viernes: '2026-01-16' },
        { numero_semana: 3, fecha_lunes: '2026-01-19', fecha_viernes: '2026-01-23' },
        { numero_semana: 4, fecha_lunes: '2026-01-26', fecha_viernes: '2026-01-30' }
    ];

    res.json({
        success: true,
        data: semanas
    });
});

// ============================================================
// MANEJO DE ERRORES
// ============================================================

app.use((err, req, res, next) => {
    console.error('❌ Error no manejado:', err);
    res.status(500).json({
        success: false,
        error: 'Error interno del servidor',
        message: err.message
    });
});

app.use((req, res) => {
    res.status(404).json({
        success: false,
        error: 'Endpoint no encontrado'
    });
});

// ============================================================
// INICIAR SERVIDOR
// ============================================================

async function start() {
    await connectDB();

    app.listen(PORT, () => {
        console.log(`\n${'='.repeat(50)}`);
        console.log(`🚀 API Comedor ejecutándose en http://localhost:${PORT}`);
        console.log(`${'='.repeat(50)}`);
        console.log('\n📍 Endpoints disponibles:');
        console.log(`  GET  /api/status`);
        console.log(`  POST /api/pedidos/agendar-pedidos`);
        console.log(`  GET  /api/pedidos/usuario/:id`);
        console.log(`  GET  /api/semanas\n`);
    });
}

start();

// Manejar cierre
process.on('SIGINT', async () => {
    console.log('\n👋 Cerrando aplicación...');
    if (pool) await pool.close();
    process.exit(0);
});
