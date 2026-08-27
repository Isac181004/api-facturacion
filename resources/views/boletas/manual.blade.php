<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Emitir Boleta - Salón & Spa</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f4f6f9;
            color: #1f2937;
            font-family: Arial, Helvetica, sans-serif;
        }

        .topbar {
            background: #111827;
            color: white;
            padding: 18px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar h1 {
            margin: 0;
            font-size: 21px;
        }

        .topbar small {
            color: #cbd5e1;
        }

        .container {
            max-width: 1350px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .grid-main {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 22px;
        }

        .card {
            background: white;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 4px 18px rgba(0,0,0,.06);
            margin-bottom: 20px;
        }

        .card h2 {
            margin-top: 0;
            font-size: 18px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 12px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 13px;
            font-weight: bold;
            color: #374151;
        }

        input,
        select {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 11px 12px;
            outline: none;
            background: white;
        }

        input:focus,
        select:focus {
            border-color: #111827;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f3f4f6;
            padding: 10px 8px;
            font-size: 12px;
            text-align: left;
        }

        td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        td input {
            min-width: 80px;
        }

        .descripcion {
            min-width: 220px;
        }

        .btn {
            border: 0;
            border-radius: 8px;
            padding: 11px 17px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-primary {
            background: #111827;
            color: white;
        }

        .btn-success {
            background: #059669;
            color: white;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #111827;
        }

        .btn-danger {
            background: #fee2e2;
            color: #991b1b;
            padding: 8px 11px;
        }

        .btn-blue {
            background: #2563eb;
            color: white;
        }

        .btn:disabled {
            opacity: .45;
            cursor: not-allowed;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .totales {
            font-size: 14px;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            padding: 9px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .total-final {
            font-size: 24px;
            font-weight: bold;
            border: none;
            padding-top: 18px;
        }

        .estado {
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 15px;
            display: none;
            white-space: pre-line;
        }

        .estado.ok {
            display: block;
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .estado.error {
            display: block;
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .login-box {
            max-width: 520px;
            margin: 70px auto;
        }

        .hidden {
            display: none !important;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #e0f2fe;
            color: #075985;
            font-size: 12px;
            font-weight: bold;
        }

        .small-note {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
        }

        @media (max-width: 900px) {
            .grid-main {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .table-wrap {
                overflow-x: auto;
            }
        }
    </style>
</head>

<body>

<div class="topbar">
    <div>
        <h1>Salón & Spa · Facturación</h1>
        <small>Emisión manual de comprobantes</small>
    </div>

    <div>
        <span class="badge">SUNAT BETA</span>
    </div>
</div>


<!-- LOGIN -->
<div id="loginPanel" class="container login-box">

    <div class="card">

        <h2>Ingresar al sistema</h2>

        <div id="loginEstado" class="estado"></div>

        <div class="form-group">
            <label>Correo</label>
            <input
                id="loginEmail"
                type="email"
                value="admin@local.test"
            >
        </div>

        <br>

        <div class="form-group">
            <label>Contraseña</label>
            <input
                id="loginPassword"
                type="password"
                placeholder="Contraseña del administrador"
            >
        </div>

        <br>

        <button
            class="btn btn-primary"
            onclick="login()"
        >
            Ingresar
        </button>

    </div>

</div>


<!-- APLICACIÓN -->
<div id="appPanel" class="container hidden">

    <div id="estadoGeneral" class="estado"></div>

    <div class="grid-main">

        <!-- COLUMNA PRINCIPAL -->
        <div>

            <div class="card">

                <h2>Datos de la boleta</h2>

                <div class="form-grid">

                    <div class="form-group">
                        <label>Serie</label>
                        <input id="serie" value="B001" readonly>
                    </div>

                    <div class="form-group">
                        <label>Fecha de emisión</label>
                        <input id="fechaEmision" type="date">
                    </div>

                    <div class="form-group">
                        <label>Forma de pago</label>
                        <select id="formaPago">
                            <option value="Contado">Contado</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Moneda</label>
                        <select id="moneda">
                            <option value="PEN">Soles</option>
                        </select>
                    </div>

                </div>

            </div>


            <div class="card">

                <h2>Cliente</h2>

                <div class="form-grid">

                    <div class="form-group">

                        <label>Tipo de documento</label>

                        <select id="tipoDocumento">
                            <option value="1">DNI</option>
                            <option value="6">RUC</option>
                        </select>

                    </div>

                    <div class="form-group">

                        <label>Número de documento</label>

                        <input
                            id="numeroDocumento"
                            placeholder="Ej. 12345678"
                        >

                    </div>

                    <div class="form-group full">

                        <label>Nombre / Razón social</label>

                        <input
                            id="razonSocial"
                            placeholder="Nombre completo del cliente"
                        >

                    </div>

                    <div class="form-group full">

                        <label>Dirección</label>

                        <input
                            id="direccionCliente"
                            placeholder="Opcional"
                        >

                    </div>

                </div>

            </div>


            <div class="card">

                <div style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    gap:15px;
                ">

                    <h2 style="border:0;margin:0;">
                        Servicios
                    </h2>

                    <button
                        class="btn btn-secondary"
                        onclick="agregarServicio()"
                    >
                        + Agregar servicio
                    </button>

                </div>

                <div class="table-wrap">

                    <table>

                        <thead>
                        <tr>
                            <th>Código</th>
                            <th>Servicio</th>
                            <th>Cant.</th>
                            <th>Precio final</th>
                            <th>Base</th>
                            <th>IGV</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                        </thead>

                        <tbody id="serviciosBody"></tbody>

                    </table>

                </div>

                <div class="small-note">
                    El precio ingresado es el precio final al cliente,
                    con IGV incluido.
                </div>

            </div>

        </div>


        <!-- RESUMEN -->
        <div>

            <div class="card">

                <h2>Resumen</h2>

                <div class="totales">

                    <div class="total-line">
                        <span>Base gravada</span>
                        <strong id="totalBase">S/ 0.00</strong>
                    </div>

                    <div class="total-line">
                        <span>IGV 18%</span>
                        <strong id="totalIgv">S/ 0.00</strong>
                    </div>

                    <div class="total-line total-final">
                        <span>Total</span>
                        <strong id="totalFinal">S/ 0.00</strong>
                    </div>

                </div>

                <div class="actions">

                    <button
                        id="btnCrear"
                        class="btn btn-primary"
                        onclick="crearBoleta()"
                    >
                        Crear boleta
                    </button>

                    <button
                        id="btnEnviar"
                        class="btn btn-success"
                        onclick="enviarSunat()"
                        disabled
                    >
                        Enviar a SUNAT
                    </button>

                </div>

                <div class="actions">

                    <button
                        id="btnPdf"
                        class="btn btn-blue"
                        onclick="generarPdf()"
                        disabled
                    >
                        Generar PDF
                    </button>

                    <button
                        id="btnVerPdf"
                        class="btn btn-secondary"
                        onclick="descargarPdf()"
                        disabled
                    >
                        Descargar PDF
                    </button>

                </div>

                <div class="actions">

                    <button
                        id="btnXml"
                        class="btn btn-secondary"
                        onclick="descargarXml()"
                        disabled
                    >
                        XML
                    </button>

                    <button
                        id="btnCdr"
                        class="btn btn-secondary"
                        onclick="descargarCdr()"
                        disabled
                    >
                        CDR
                    </button>

                </div>

                <hr style="border:0;border-top:1px solid #e5e7eb;margin:20px 0;">

                <button
                    class="btn btn-secondary"
                    onclick="cerrarSesion()"
                >
                    Cerrar sesión
                </button>

            </div>

        </div>

    </div>

</div>


<script>

const API = window.location.origin;

let token = localStorage.getItem('sunat_token') || '';

let boletaId = null;
let numeroBoleta = null;


/* =========================================================
   FECHA
========================================================= */

function fechaLocal() {

    const d = new Date();

    const year = d.getFullYear();

    const month =
        String(d.getMonth() + 1).padStart(2, '0');

    const day =
        String(d.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

document.getElementById('fechaEmision').value =
    fechaLocal();


/* =========================================================
   LOGIN
========================================================= */

async function login() {

    const email =
        document.getElementById('loginEmail').value.trim();

    const password =
        document.getElementById('loginPassword').value;

    try {

        const response = await fetch(
            API + '/api/auth/login',
            {
                method: 'POST',

                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },

                body: JSON.stringify({
                    email,
                    password
                })
            }
        );

        const data = await response.json();

        if (!response.ok) {

            throw new Error(
                data.message ||
                'No se pudo iniciar sesión'
            );
        }

        token = data.access_token;

        localStorage.setItem(
            'sunat_token',
            token
        );

        mostrarAplicacion();

    }
    catch (error) {

        mostrarEstado(
            'loginEstado',
            error.message,
            false
        );
    }
}


async function validarToken() {

    if (!token) {
        return;
    }

    try {

        const response = await fetch(
            API + '/api/v1/auth/me',
            {
                headers: {
                    'Authorization':
                        'Bearer ' + token,

                    'Accept':
                        'application/json'
                }
            }
        );

        if (response.ok) {

            mostrarAplicacion();

        }
        else {

            localStorage.removeItem(
                'sunat_token'
            );

            token = '';
        }

    }
    catch (error) {

        console.error(error);
    }
}


function mostrarAplicacion() {

    document
        .getElementById('loginPanel')
        .classList.add('hidden');

    document
        .getElementById('appPanel')
        .classList.remove('hidden');
}


function cerrarSesion() {

    localStorage.removeItem(
        'sunat_token'
    );

    location.reload();
}


/* =========================================================
   SERVICIOS
========================================================= */

function agregarServicio(
    codigo = '',
    descripcion = '',
    cantidad = 1,
    precio = 0
) {

    const tbody =
        document.getElementById(
            'serviciosBody'
        );

    const tr =
        document.createElement('tr');

    tr.innerHTML = `

        <td>
            <input
                class="codigo"
                value="${codigo}"
                placeholder="SERV001"
            >
        </td>

        <td>
            <input
                class="descripcion"
                value="${descripcion}"
                placeholder="Ej. Corte de cabello"
            >
        </td>

        <td>
            <input
                class="cantidad"
                type="number"
                min="0.01"
                step="0.01"
                value="${cantidad}"
                oninput="calcularTotales()"
            >
        </td>

        <td>
            <input
                class="precio"
                type="number"
                min="0"
                step="0.01"
                value="${precio}"
                oninput="calcularTotales()"
            >
        </td>

        <td class="base">
            0.00
        </td>

        <td class="igv">
            0.00
        </td>

        <td class="total">
            0.00
        </td>

        <td>
            <button
                class="btn btn-danger"
                onclick="eliminarServicio(this)"
            >
                ×
            </button>
        </td>
    `;

    tbody.appendChild(tr);

    calcularTotales();
}


function eliminarServicio(button) {

    button
        .closest('tr')
        .remove();

    calcularTotales();
}


function calcularTotales() {

    let baseTotal = 0;
    let igvTotal = 0;
    let totalFinal = 0;

    document
        .querySelectorAll('#serviciosBody tr')
        .forEach(row => {

            const cantidad =
                Number(
                    row
                        .querySelector('.cantidad')
                        .value
                ) || 0;

            const precioFinal =
                Number(
                    row
                        .querySelector('.precio')
                        .value
                ) || 0;

            const total =
                cantidad * precioFinal;

            const base =
                total / 1.18;

            const igv =
                total - base;

            row
                .querySelector('.base')
                .textContent =
                    base.toFixed(2);

            row
                .querySelector('.igv')
                .textContent =
                    igv.toFixed(2);

            row
                .querySelector('.total')
                .textContent =
                    total.toFixed(2);

            baseTotal += base;

            igvTotal += igv;

            totalFinal += total;
        });

    document
        .getElementById('totalBase')
        .textContent =
            'S/ ' + baseTotal.toFixed(2);

    document
        .getElementById('totalIgv')
        .textContent =
            'S/ ' + igvTotal.toFixed(2);

    document
        .getElementById('totalFinal')
        .textContent =
            'S/ ' + totalFinal.toFixed(2);
}


/* =========================================================
   CREAR BOLETA
========================================================= */

async function crearBoleta() {

    limpiarEstado();

    const detalles = [];

    const filas =
        document.querySelectorAll(
            '#serviciosBody tr'
        );

    if (!filas.length) {

        mostrarEstadoGeneral(
            'Agrega al menos un servicio.',
            false
        );

        return;
    }

    for (const row of filas) {

        const codigo =
            row
                .querySelector('.codigo')
                .value
                .trim();

        const descripcion =
            row
                .querySelector('.descripcion')
                .value
                .trim();

        const cantidad =
            Number(
                row
                    .querySelector('.cantidad')
                    .value
            );

        const precioFinal =
            Number(
                row
                    .querySelector('.precio')
                    .value
            );

        if (
            !codigo ||
            !descripcion ||
            cantidad <= 0 ||
            precioFinal <= 0
        ) {

            mostrarEstadoGeneral(
                'Completa correctamente todos los servicios.',
                false
            );

            return;
        }

        /*
         * La API actual espera valor unitario SIN IGV.
         * El usuario escribe el precio FINAL.
         */

        const valorUnitario =
            precioFinal / 1.18;

        detalles.push({

            codigo: codigo,

            descripcion: descripcion,

            unidad: 'ZZ',

            cantidad: cantidad,

            mto_valor_unitario:
                Number(
                    valorUnitario.toFixed(6)
                ),

            porcentaje_igv: 18,

            tip_afe_igv: '10'
        });
    }


    const numeroDocumento =
        document
            .getElementById('numeroDocumento')
            .value
            .trim();

    const razonSocial =
        document
            .getElementById('razonSocial')
            .value
            .trim();

    if (
        !numeroDocumento ||
        !razonSocial
    ) {

        mostrarEstadoGeneral(
            'Completa el documento y nombre del cliente.',
            false
        );

        return;
    }


    const payload = {

        company_id: 1,

        branch_id: 1,

        serie:
            document
                .getElementById('serie')
                .value,

        fecha_emision:
            document
                .getElementById('fechaEmision')
                .value,

        ubl_version: '2.1',

        tipo_operacion: '0101',

        moneda: 'PEN',

        metodo_envio: 'individual',

        forma_pago_tipo:
            document
                .getElementById('formaPago')
                .value,

        client: {

            tipo_documento:
                document
                    .getElementById('tipoDocumento')
                    .value,

            numero_documento:
                numeroDocumento,

            razon_social:
                razonSocial,

            direccion:
                document
                    .getElementById('direccionCliente')
                    .value
                    .trim()
        },

        detalles: detalles
    };


    try {

        deshabilitarCrear(true);

        const response = await fetch(
            API + '/api/v1/boletas',
            {
                method: 'POST',

                headers: {
                    'Authorization':
                        'Bearer ' + token,

                    'Content-Type':
                        'application/json',

                    'Accept':
                        'application/json'
                },

                body:
                    JSON.stringify(payload)
            }
        );

        const data =
            await leerJson(response);

        if (!response.ok) {

            throw new Error(
                obtenerMensajeError(data)
            );
        }


        boletaId =
            data.data.id;

        numeroBoleta =
            data.data.numero_completo;


        document
            .getElementById('btnEnviar')
            .disabled = false;


        mostrarEstadoGeneral(
            'Boleta creada correctamente:\n' +
            numeroBoleta,
            true
        );

    }
    catch (error) {

        mostrarEstadoGeneral(
            error.message,
            false
        );

    }
    finally {

        deshabilitarCrear(false);
    }
}


/* =========================================================
   ENVIAR SUNAT
========================================================= */

async function enviarSunat() {

    if (!boletaId) {
        return;
    }

    try {

        document
            .getElementById('btnEnviar')
            .disabled = true;

        mostrarEstadoGeneral(
            'Enviando boleta a SUNAT...',
            true
        );


        const response = await fetch(

            API +
            '/api/v1/boletas/' +
            boletaId +
            '/send-sunat',

            {
                method: 'POST',

                headers: {

                    'Authorization':
                        'Bearer ' + token,

                    'Accept':
                        'application/json'
                }
            }
        );


        const data =
            await leerJson(response);


        if (!response.ok) {

            throw new Error(
                obtenerMensajeError(data)
            );
        }


        const estado =
            data.data.estado_sunat || 'ACEPTADO';


        mostrarEstadoGeneral(
            numeroBoleta +
            '\nEstado SUNAT: ' +
            estado,
            true
        );


        document
            .getElementById('btnPdf')
            .disabled = false;

        document
            .getElementById('btnXml')
            .disabled = false;

        document
            .getElementById('btnCdr')
            .disabled = false;

    }
    catch (error) {

        mostrarEstadoGeneral(
            error.message,
            false
        );

        document
            .getElementById('btnEnviar')
            .disabled = false;
    }
}


/* =========================================================
   PDF
========================================================= */

async function generarPdf() {

    try {

        const response = await fetch(

            API +
            '/api/v1/boletas/' +
            boletaId +
            '/generate-pdf',

            {
                method: 'POST',

                headers: {
                    'Authorization':
                        'Bearer ' + token,

                    'Accept':
                        'application/json'
                }
            }
        );


        const data =
            await leerJson(response);


        if (!response.ok) {

            throw new Error(
                obtenerMensajeError(data)
            );
        }


        document
            .getElementById('btnVerPdf')
            .disabled = false;


        mostrarEstadoGeneral(
            'PDF generado correctamente.',
            true
        );

    }
    catch (error) {

        mostrarEstadoGeneral(
            error.message,
            false
        );
    }
}


/* =========================================================
   DESCARGAS
========================================================= */

async function descargarPdf() {

    await descargarArchivo(

        '/api/v1/boletas/' +
        boletaId +
        '/download-pdf',

        numeroBoleta + '.pdf'
    );
}


async function descargarXml() {

    await descargarArchivo(

        '/api/v1/boletas/' +
        boletaId +
        '/download-xml',

        numeroBoleta + '.xml'
    );
}


async function descargarCdr() {

    await descargarArchivo(

        '/api/v1/boletas/' +
        boletaId +
        '/download-cdr',

        'R-' +
        numeroBoleta +
        '.zip'
    );
}


async function descargarArchivo(
    url,
    nombre
) {

    try {

        const response = await fetch(
            API + url,
            {
                headers: {
                    'Authorization':
                        'Bearer ' + token
                }
            }
        );


        if (!response.ok) {

            const data =
                await leerJson(response);

            throw new Error(
                obtenerMensajeError(data)
            );
        }


        const blob =
            await response.blob();


        const objectUrl =
            URL.createObjectURL(blob);


        const a =
            document.createElement('a');


        a.href =
            objectUrl;

        a.download =
            nombre;


        document.body.appendChild(a);

        a.click();

        a.remove();


        URL.revokeObjectURL(
            objectUrl
        );

    }
    catch (error) {

        mostrarEstadoGeneral(
            error.message,
            false
        );
    }
}


/* =========================================================
   UTILIDADES
========================================================= */

async function leerJson(response) {

    const text =
        await response.text();

    try {

        return JSON.parse(text);

    }
    catch {

        return {
            message:
                text ||
                'Respuesta inválida del servidor.'
        };
    }
}


function obtenerMensajeError(data) {

    if (data.errors) {

        return Object
            .values(data.errors)
            .flat()
            .join('\n');
    }

    return (
        data.message ||
        data.error ||
        'Ocurrió un error.'
    );
}


function mostrarEstadoGeneral(
    mensaje,
    correcto
) {

    mostrarEstado(
        'estadoGeneral',
        mensaje,
        correcto
    );
}


function mostrarEstado(
    id,
    mensaje,
    correcto
) {

    const el =
        document.getElementById(id);

    el.textContent =
        mensaje;

    el.className =
        'estado ' +
        (correcto ? 'ok' : 'error');
}


function limpiarEstado() {

    const el =
        document.getElementById(
            'estadoGeneral'
        );

    el.className =
        'estado';

    el.textContent =
        '';
}


function deshabilitarCrear(valor) {

    document
        .getElementById('btnCrear')
        .disabled = valor;
}


/* =========================================================
   INICIAL
========================================================= */

validarToken();

</script>

</body>
</html>