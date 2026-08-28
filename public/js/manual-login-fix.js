(function () {
    'use strict';

    var state = {
        token: localStorage.getItem('sunat_token') || '',
        comprobanteId: null,
        numeroComprobante: null,
        consultaTimer: null,
        preloadCargado: false,
        preloadCargando: false
    };

    function el(id) {
        return document.getElementById(id);
    }

    function pad2(value) {
        value = String(value);
        return value.length < 2 ? '0' + value : value;
    }

    function fechaLocal() {
        var d = new Date();
        return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
    }

    function tipoActual() {
        var input = el('tipoComprobante');
        return input && input.value === 'factura' ? 'factura' : 'boleta';
    }

    function recurso() {
        return tipoActual() === 'factura' ? 'invoices' : 'boletas';
    }

    function simbolo() {
        var moneda = el('moneda');
        return moneda && moneda.value === 'USD' ? '$' : 'S/';
    }

    function mostrarEstado(id, mensaje, ok) {
        var box = el(id);
        if (!box) return;
        box.textContent = mensaje || '';
        box.className = 'estado ' + (ok ? 'ok' : 'error');
    }

    function mostrarEstadoGeneral(mensaje, ok) {
        mostrarEstado('estadoGeneral', mensaje, ok);
    }

    function limpiarEstado() {
        var box = el('estadoGeneral');
        if (!box) return;
        box.textContent = '';
        box.className = 'estado';
    }

    function setLoginStatus(mensaje, ok) {
        mostrarEstado('loginEstado', mensaje, ok);
    }

    function estadoConsulta(mensaje, ok) {
        var box = el('consultaEstado');
        if (!box) return;
        box.textContent = mensaje || '';
        box.className = 'mini' + (ok === true ? ' ok' : (ok === false ? ' error' : ''));
    }

    function readJson(response) {
        return response.text().then(function (text) {
            try {
                return JSON.parse(text);
            } catch (e) {
                return { message: text || 'Respuesta inválida del servidor.' };
            }
        });
    }

    function errorMessage(data) {
        var mensajes = [];
        var key;
        var value;
        var i;

        if (data && data.errors) {
            for (key in data.errors) {
                if (!Object.prototype.hasOwnProperty.call(data.errors, key)) continue;
                value = data.errors[key];
                if (Object.prototype.toString.call(value) === '[object Array]') {
                    for (i = 0; i < value.length; i++) mensajes.push(String(value[i]));
                } else if (value !== undefined && value !== null) {
                    mensajes.push(String(value));
                }
            }
        }

        if (mensajes.length) return mensajes.join('\n');
        if (data && data.message) return String(data.message);
        if (data && data.error) return String(data.error);
        return 'No se pudo completar la operación.';
    }

    function mostrarAplicacion() {
        var loginPanel = el('loginPanel');
        var appPanel = el('appPanel');
        if (loginPanel) loginPanel.classList.add('hidden');
        if (appPanel) appPanel.classList.remove('hidden');
    }

    function obtenerPreloadToken() {
        var queryToken = '';
        var match;

        try {
            queryToken = new URLSearchParams(window.location.search).get('preload') || '';
        } catch (e) {
            queryToken = '';
        }

        if (/^[A-Za-z0-9]{48}$/.test(queryToken)) return queryToken;

        match = window.location.pathname.match(/\/comprobantes\/manual\/([A-Za-z0-9]{48})\/?$/);
        return match ? match[1] : '';
    }

    function resetCreado() {
        var ids = ['btnEnviar', 'btnPdf', 'btnVerPdf', 'btnXml', 'btnCdr'];
        var i;
        var button;
        state.comprobanteId = null;
        state.numeroComprobante = null;
        for (i = 0; i < ids.length; i++) {
            button = el(ids[i]);
            if (button) button.disabled = true;
        }
    }

    function actualizarPlaceholder() {
        var tipo = el('tipoDocumento');
        var numero = el('numeroDocumento');
        if (!tipo || !numero) return;
        numero.placeholder = tipo.value === '6' ? '11 dígitos de RUC' : '8 dígitos de DNI';
    }

    function cambiarComprobante() {
        var factura = tipoActual() === 'factura';
        var tipoDocumento = el('tipoDocumento');
        var badge = el('badge');
        var serie = el('serie');
        var crear = el('btnCrear');

        if (serie) serie.value = factura ? 'F001' : 'B001';
        if (crear) crear.textContent = factura ? 'Crear factura' : 'Crear boleta';
        if (badge) {
            badge.textContent = (factura ? 'FACTURA' : 'BOLETA') + ' · SUNAT BETA';
            if (factura) badge.classList.add('factura');
            else badge.classList.remove('factura');
        }

        if (tipoDocumento) {
            if (factura) {
                tipoDocumento.value = '6';
                tipoDocumento.disabled = true;
            } else {
                tipoDocumento.disabled = false;
            }
        }

        actualizarPlaceholder();
        resetCreado();
    }

    function cambiarDocumento() {
        var nombre = el('razonSocial');
        var direccion = el('direccionCliente');
        actualizarPlaceholder();
        if (nombre) nombre.value = '';
        if (direccion) direccion.value = '';
        resetCreado();
        programarConsulta(true);
    }

    function programarConsulta(inmediata) {
        var tipo = el('tipoDocumento');
        var numero = el('numeroDocumento');
        var limpio;
        var largo;

        if (!tipo || !numero) return;

        clearTimeout(state.consultaTimer);
        limpio = String(numero.value || '').replace(/\D/g, '');
        largo = tipo.value === '6' ? 11 : 8;
        numero.value = limpio;

        if (!limpio) {
            estadoConsulta('', null);
            return;
        }
        if (limpio.length < largo) {
            estadoConsulta('Faltan ' + (largo - limpio.length) + ' dígito(s).', null);
            return;
        }
        if (limpio.length > largo) {
            estadoConsulta('Longitud de documento inválida.', false);
            return;
        }

        state.consultaTimer = setTimeout(function () {
            consultarDocumento();
        }, inmediata ? 0 : 450);
    }

    function consultarDocumento() {
        var tipo = el('tipoDocumento');
        var numero = el('numeroDocumento');
        var nombre = el('razonSocial');
        var direccion = el('direccionCliente');
        var limpio;
        var largo;

        if (!state.token || !tipo || !numero) return Promise.resolve(false);

        limpio = String(numero.value || '').replace(/\D/g, '');
        largo = tipo.value === '6' ? 11 : 8;
        if (limpio.length !== largo) return Promise.resolve(false);

        estadoConsulta(tipo.value === '6' ? 'Consultando RUC...' : 'Consultando DNI...', null);

        return fetch(
            window.location.origin + '/api/v1/documentos/consultar?tipo=' + encodeURIComponent(tipo.value) + '&numero=' + encodeURIComponent(limpio),
            { headers: { 'Authorization': 'Bearer ' + state.token, 'Accept': 'application/json' } }
        ).then(function (response) {
            return readJson(response).then(function (data) {
                if (!response.ok || (data && data.success === false)) throw new Error(errorMessage(data));
                var info = data && data.data ? data.data : {};
                if (nombre) nombre.value = info.razon_social || '';
                if (direccion) direccion.value = info.direccion || '';
                estadoConsulta(tipo.value === '6' ? 'RUC encontrado.' : 'DNI encontrado.', true);
                return true;
            });
        }).catch(function (error) {
            estadoConsulta(error && error.message ? error.message : 'No se pudo consultar el documento.', false);
            return false;
        });
    }

    function escapeAttr(value) {
        return String(value === undefined || value === null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function quitarDetalle(button) {
        var row = button;
        while (row && row.tagName !== 'TR') row = row.parentNode;
        if (row && row.parentNode) row.parentNode.removeChild(row);
        calcularTotales();
        resetCreado();
    }

    function agregarDetalle(codigo, descripcion, unidad, cantidad, precio, afectacion) {
        var body = el('detalleBody');
        var tr;
        var inputs;
        var i;

        if (!body) return;

        codigo = codigo || '';
        descripcion = descripcion || '';
        unidad = unidad === 'NIU' ? 'NIU' : 'ZZ';
        cantidad = Number(cantidad);
        if (!(cantidad > 0)) cantidad = 1;
        precio = Number(precio);
        if (!(precio >= 0)) precio = 0;
        afectacion = String(afectacion || '10');
        if (['10', '20', '30'].indexOf(afectacion) < 0) afectacion = '10';

        tr = document.createElement('tr');
        tr.innerHTML =
            '<td><input class="codigo" value="' + escapeAttr(codigo) + '"></td>' +
            '<td><input class="descripcion desc" value="' + escapeAttr(descripcion) + '"></td>' +
            '<td><select class="unidad notranslate" translate="no">' +
                '<option value="ZZ"' + (unidad === 'ZZ' ? ' selected' : '') + '>Servicio (ZZ)</option>' +
                '<option value="NIU"' + (unidad === 'NIU' ? ' selected' : '') + '>Producto / Unidad (NIU)</option>' +
            '</select></td>' +
            '<td><input class="cantidad" type="number" min="0.001" step="0.001" value="' + cantidad + '"></td>' +
            '<td><input class="precio" type="number" min="0" step="0.01" value="' + precio + '"></td>' +
            '<td><select class="afe-igv">' +
                '<option value="10"' + (afectacion === '10' ? ' selected' : '') + '>Sí · Gravado 18%</option>' +
                '<option value="20"' + (afectacion === '20' ? ' selected' : '') + '>No · Exonerado</option>' +
                '<option value="30"' + (afectacion === '30' ? ' selected' : '') + '>No · Inafecto</option>' +
            '</select></td>' +
            '<td class="base">0.00</td>' +
            '<td class="igv">0.00</td>' +
            '<td class="total">0.00</td>' +
            '<td><button type="button" class="btn danger btn-quitar-detalle">×</button></td>';

        body.appendChild(tr);

        inputs = tr.querySelectorAll('input,select');
        for (i = 0; i < inputs.length; i++) {
            inputs[i].addEventListener('input', function () {
                calcularTotales();
                resetCreado();
            });
            inputs[i].addEventListener('change', function () {
                calcularTotales();
                resetCreado();
            });
        }

        tr.querySelector('.btn-quitar-detalle').addEventListener('click', function () {
            quitarDetalle(this);
        });

        calcularTotales();
    }

    function calcularTotales() {
        var rows = document.querySelectorAll('#detalleBody tr');
        var gravado = 0;
        var exonerado = 0;
        var inafecto = 0;
        var igvTotal = 0;
        var total = 0;
        var i;
        var q;
        var p;
        var afe;
        var tt;
        var base;
        var igv;
        var row;

        for (i = 0; i < rows.length; i++) {
            row = rows[i];
            q = Number(row.querySelector('.cantidad').value) || 0;
            p = Number(row.querySelector('.precio').value) || 0;
            afe = row.querySelector('.afe-igv').value;
            tt = q * p;
            base = tt;
            igv = 0;

            if (afe === '10') {
                base = tt / 1.18;
                igv = tt - base;
                gravado += base;
            } else if (afe === '20') {
                exonerado += base;
            } else {
                inafecto += base;
            }

            row.querySelector('.base').textContent = base.toFixed(2);
            row.querySelector('.igv').textContent = igv.toFixed(2);
            row.querySelector('.total').textContent = tt.toFixed(2);
            igvTotal += igv;
            total += tt;
        }

        if (el('totalBase')) el('totalBase').textContent = simbolo() + ' ' + gravado.toFixed(2);
        if (el('totalExonerado')) el('totalExonerado').textContent = simbolo() + ' ' + exonerado.toFixed(2);
        if (el('totalInafecto')) el('totalInafecto').textContent = simbolo() + ' ' + inafecto.toFixed(2);
        if (el('totalIgv')) el('totalIgv').textContent = simbolo() + ' ' + igvTotal.toFixed(2);
        if (el('totalFinal')) el('totalFinal').textContent = simbolo() + ' ' + total.toFixed(2);
    }

    function aplicarPreload(data) {
        var tipoComprobante = el('tipoComprobante');
        var tipoDocumento = el('tipoDocumento');
        var numeroDocumento = el('numeroDocumento');
        var razonSocial = el('razonSocial');
        var direccionCliente = el('direccionCliente');
        var detalleBody = el('detalleBody');
        var moneda = el('moneda');
        var formaPago = el('formaPago');
        var sourceBox = el('sourceBox');
        var fecha = el('fechaEmision');
        var client = data && data.client ? data.client : {};
        var items = data && Object.prototype.toString.call(data.items) === '[object Array]' ? data.items : [];
        var i;
        var item;
        var afe;
        var origen = [];

        if (tipoComprobante) tipoComprobante.value = data && data.tipo_comprobante === 'factura' ? 'factura' : 'boleta';
        cambiarComprobante();

        if (tipoDocumento) {
            if (tipoActual() === 'factura') {
                tipoDocumento.value = '6';
            } else if (String(client.tipo_documento || '') === '1' || String(client.tipo_documento || '') === '6') {
                tipoDocumento.value = String(client.tipo_documento);
            }
        }

        if (numeroDocumento) numeroDocumento.value = String(client.numero_documento || '').replace(/\D/g, '');
        if (razonSocial) razonSocial.value = client.razon_social || '';
        if (direccionCliente) direccionCliente.value = client.direccion || '';
        if (fecha && !fecha.value) fecha.value = fechaLocal();

        if (detalleBody) detalleBody.innerHTML = '';

        for (i = 0; i < items.length; i++) {
            item = items[i] || {};
            afe = String(item.tip_afe_igv || '');
            if (['10', '20', '30'].indexOf(afe) < 0) afe = item.aplica_igv === false ? '30' : '10';
            agregarDetalle(
                item.codigo || '',
                item.descripcion || item.nombre || '',
                item.unidad === 'NIU' ? 'NIU' : 'ZZ',
                item.cantidad !== undefined && item.cantidad !== null ? item.cantidad : 1,
                item.precio_final !== undefined && item.precio_final !== null ? item.precio_final : (item.precio || 0),
                afe
            );
        }

        if (detalleBody && !detalleBody.children.length) agregarDetalle('', '', 'ZZ', 1, 0, '10');
        if (moneda && data && (data.moneda === 'PEN' || data.moneda === 'USD')) moneda.value = data.moneda;
        if (formaPago && data && (data.forma_pago_tipo === 'Contado' || data.forma_pago_tipo === 'Credito')) formaPago.value = data.forma_pago_tipo;

        calcularTotales();

        if (data && data.origen) origen.push(String(data.origen));
        if (data && data.venta_id) origen.push('Venta #' + String(data.venta_id));
        if (sourceBox && origen.length) {
            sourceBox.textContent = 'Datos cargados desde: ' + origen.join(' · ');
            sourceBox.classList.remove('hidden');
        }

        actualizarPlaceholder();
        if (numeroDocumento && numeroDocumento.value && razonSocial && !razonSocial.value) programarConsulta(true);
    }

    function cargarVentaReal(authToken) {
        var preloadToken;
        if (state.preloadCargado || state.preloadCargando) return Promise.resolve(state.preloadCargado);
        preloadToken = obtenerPreloadToken();
        if (!preloadToken || !authToken) return Promise.resolve(false);

        state.preloadCargando = true;
        mostrarEstadoGeneral('Cargando datos de la venta...', true);

        return fetch(
            window.location.origin + '/api/v1/comprobantes/preload/' + encodeURIComponent(preloadToken),
            { headers: { 'Authorization': 'Bearer ' + authToken, 'Accept': 'application/json' } }
        ).then(function (response) {
            return readJson(response).then(function (data) {
                if (!response.ok || (data && data.success === false)) throw new Error(errorMessage(data));
                aplicarPreload(data && data.data ? data.data : {});
                state.preloadCargado = true;
                mostrarAplicacion();
                mostrarEstadoGeneral('Venta cargada correctamente. Revisa los datos y emite el comprobante.', true);
                agregarBotonVolver();
                return true;
            });
        }).catch(function (error) {
            mostrarEstadoGeneral(error && error.message ? error.message : 'No se pudo cargar la venta.', false);
            return false;
        }).then(function (resultado) {
            state.preloadCargando = false;
            return resultado;
        });
    }

    function validarCliente() {
        var factura = tipoActual() === 'factura';
        var tipo = el('tipoDocumento');
        var numero = el('numeroDocumento');
        var nombre = el('razonSocial');
        var limpio = numero ? String(numero.value || '').replace(/\D/g, '') : '';
        var tipoValor = tipo ? tipo.value : '';

        if (factura && (tipoValor !== '6' || !/^\d{11}$/.test(limpio))) return 'Para emitir factura debes usar un RUC válido de 11 dígitos.';
        if (tipoValor === '1' && !/^\d{8}$/.test(limpio)) return 'El DNI debe tener exactamente 8 dígitos.';
        if (tipoValor === '6' && !/^\d{11}$/.test(limpio)) return 'El RUC debe tener exactamente 11 dígitos.';
        if (!nombre || !String(nombre.value || '').trim()) return 'Completa el nombre o razón social del cliente.';
        return '';
    }

    function detalles() {
        var rows = document.querySelectorAll('#detalleBody tr');
        var out = [];
        var i;
        var row;
        var codigo;
        var descripcion;
        var unidad;
        var cantidad;
        var precio;
        var afe;
        var aplicaIgv;

        if (!rows.length) throw new Error('Agrega al menos un servicio o producto.');

        for (i = 0; i < rows.length; i++) {
            row = rows[i];
            codigo = String(row.querySelector('.codigo').value || '').trim();
            descripcion = String(row.querySelector('.descripcion').value || '').trim();
            unidad = row.querySelector('.unidad').value;
            cantidad = Number(row.querySelector('.cantidad').value);
            precio = Number(row.querySelector('.precio').value);
            afe = row.querySelector('.afe-igv').value;

            if (!codigo || !descripcion || !(cantidad > 0) || !(precio > 0)) {
                throw new Error('Completa correctamente todos los detalles de la venta.');
            }

            aplicaIgv = afe === '10';
            out.push({
                codigo: codigo,
                descripcion: descripcion,
                unidad: unidad,
                cantidad: cantidad,
                mto_valor_unitario: Number((aplicaIgv ? precio / 1.18 : precio).toFixed(6)),
                porcentaje_igv: aplicaIgv ? 18 : 0,
                tip_afe_igv: afe
            });
        }

        return out;
    }

    function crearComprobante() {
        var errorCliente;
        var ds;
        var payload;
        var button = el('btnCrear');
        var tipoDocumento = el('tipoDocumento');
        var numeroDocumento = el('numeroDocumento');
        var razonSocial = el('razonSocial');
        var direccionCliente = el('direccionCliente');

        limpiarEstado();
        errorCliente = validarCliente();
        if (errorCliente) {
            mostrarEstadoGeneral(errorCliente, false);
            return;
        }

        try {
            ds = detalles();
        } catch (e) {
            mostrarEstadoGeneral(e.message, false);
            return;
        }

        payload = {
            company_id: 1,
            branch_id: 1,
            serie: el('serie').value,
            fecha_emision: el('fechaEmision').value,
            ubl_version: '2.1',
            tipo_operacion: '0101',
            moneda: el('moneda').value,
            metodo_envio: 'individual',
            forma_pago_tipo: el('formaPago').value,
            client: {
                tipo_documento: tipoDocumento.value,
                numero_documento: String(numeroDocumento.value || '').replace(/\D/g, ''),
                razon_social: String(razonSocial.value || '').trim(),
                direccion: String(direccionCliente.value || '').trim()
            },
            detalles: ds
        };

        if (button) button.disabled = true;

        fetch(window.location.origin + '/api/v1/' + recurso(), {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + state.token,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        }).then(function (response) {
            return readJson(response).then(function (data) {
                var x;
                var correlativo;
                if (!response.ok) throw new Error(errorMessage(data));
                x = data && data.data ? data.data : {};
                state.comprobanteId = x.id || x.id_factura || x.id_boleta || null;
                correlativo = x.correlativo || '';
                state.numeroComprobante = x.numero_completo || x.numero || ((x.serie || payload.serie) + '-' + String(correlativo).padStart(6, '0'));
                if (!state.comprobanteId) throw new Error('La API no devolvió el ID del comprobante.');
                if (el('btnEnviar')) el('btnEnviar').disabled = false;
                mostrarEstadoGeneral((tipoActual() === 'factura' ? 'Factura' : 'Boleta') + ' creada correctamente:\n' + state.numeroComprobante, true);
            });
        }).catch(function (error) {
            mostrarEstadoGeneral(error && error.message ? error.message : 'No se pudo crear el comprobante.', false);
        }).then(function () {
            if (button) button.disabled = false;
        });
    }

    function enviarSunat() {
        var button = el('btnEnviar');
        if (!state.comprobanteId) return;
        if (button) button.disabled = true;
        mostrarEstadoGeneral('Enviando a SUNAT...', true);

        fetch(window.location.origin + '/api/v1/' + recurso() + '/' + state.comprobanteId + '/send-sunat', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + state.token, 'Accept': 'application/json' }
        }).then(function (response) {
            return readJson(response).then(function (data) {
                var d;
                var estado = 'ACEPTADO';
                if (!response.ok) throw new Error(errorMessage(data));
                d = data && data.data ? data.data : {};
                if (d.estado_sunat) estado = d.estado_sunat;
                else if (d.sunat && d.sunat.estado) estado = d.sunat.estado;
                mostrarEstadoGeneral(state.numeroComprobante + '\nEstado SUNAT: ' + estado, true);
                if (el('btnPdf')) el('btnPdf').disabled = false;
                if (el('btnXml')) el('btnXml').disabled = false;
                if (el('btnCdr')) el('btnCdr').disabled = false;
            });
        }).catch(function (error) {
            mostrarEstadoGeneral(error && error.message ? error.message : 'No se pudo enviar a SUNAT.', false);
            if (button) button.disabled = false;
        });
    }

    function generarPdf() {
        if (!state.comprobanteId) return;
        fetch(window.location.origin + '/api/v1/' + recurso() + '/' + state.comprobanteId + '/generate-pdf', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + state.token, 'Accept': 'application/json' }
        }).then(function (response) {
            return readJson(response).then(function (data) {
                if (!response.ok) throw new Error(errorMessage(data));
                if (el('btnVerPdf')) el('btnVerPdf').disabled = false;
                mostrarEstadoGeneral('PDF generado correctamente.', true);
            });
        }).catch(function (error) {
            mostrarEstadoGeneral(error && error.message ? error.message : 'No se pudo generar el PDF.', false);
        });
    }

    function bajar(sufijo, nombre) {
        if (!state.comprobanteId) return;
        fetch(window.location.origin + '/api/v1/' + recurso() + '/' + state.comprobanteId + '/' + sufijo, {
            headers: { 'Authorization': 'Bearer ' + state.token }
        }).then(function (response) {
            if (!response.ok) {
                return readJson(response).then(function (data) { throw new Error(errorMessage(data)); });
            }
            return response.blob();
        }).then(function (blob) {
            if (!blob) return;
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = nombre;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }).catch(function (error) {
            mostrarEstadoGeneral(error && error.message ? error.message : 'No se pudo descargar el archivo.', false);
        });
    }

    function descargarPdf() {
        bajar('download-pdf', (state.numeroComprobante || 'comprobante') + '.pdf');
    }

    function descargarXml() {
        bajar('download-xml', (state.numeroComprobante || 'comprobante') + '.xml');
    }

    function descargarCdr() {
        bajar('download-cdr', 'R-' + (state.numeroComprobante || 'comprobante') + '.zip');
    }

    function cerrarSesion() {
        localStorage.removeItem('sunat_token');
        state.token = '';
        window.location.reload();
    }

    function robustLogin(event) {
        var emailInput;
        var passwordInput;
        var button;
        var email;
        var password;

        if (event && event.preventDefault) event.preventDefault();
        emailInput = el('loginEmail');
        passwordInput = el('loginPassword');
        button = el('manualLoginButton');

        if (!emailInput || !passwordInput) {
            setLoginStatus('No se encontraron los campos de acceso.', false);
            return false;
        }

        email = String(emailInput.value || '').trim();
        password = passwordInput.value || '';
        if (!email || !password) {
            setLoginStatus('Ingresa correo y contraseña.', false);
            return false;
        }

        if (button) button.disabled = true;
        setLoginStatus('Ingresando...', true);

        fetch(window.location.origin + '/api/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ email: email, password: password })
        }).then(function (response) {
            return readJson(response).then(function (data) {
                if (!response.ok || !data || !data.access_token) throw new Error(errorMessage(data));
                state.token = data.access_token;
                localStorage.setItem('sunat_token', state.token);
                setLoginStatus('Acceso correcto. Cargando venta...', true);
                mostrarAplicacion();
                agregarBotonVolver();
                return cargarVentaReal(state.token);
            });
        }).catch(function (error) {
            setLoginStatus(error && error.message ? error.message : 'No se pudo iniciar sesión.', false);
            if (button) button.disabled = false;
        });

        return false;
    }

    function validarToken() {
        if (!state.token) return Promise.resolve(false);

        return fetch(window.location.origin + '/api/v1/auth/me', {
            headers: { 'Authorization': 'Bearer ' + state.token, 'Accept': 'application/json' }
        }).then(function (response) {
            if (!response.ok) {
                localStorage.removeItem('sunat_token');
                state.token = '';
                return false;
            }
            mostrarAplicacion();
            agregarBotonVolver();
            return cargarVentaReal(state.token).then(function () { return true; });
        }).catch(function () {
            return false;
        });
    }

    function agregarBotonVolver() {
        var topbar;
        var button;
        if (el('btnVolverVentasSunat')) return;
        topbar = document.querySelector('.topbar');
        if (!topbar) return;

        button = document.createElement('button');
        button.id = 'btnVolverVentasSunat';
        button.type = 'button';
        button.textContent = '← Volver a ventas';
        button.style.cssText = 'border:0;border-radius:8px;padding:9px 14px;background:#dc2626;color:#fff;font-weight:700;cursor:pointer;margin-left:12px;';
        button.addEventListener('click', function () {
            try {
                if (document.referrer) {
                    var ref = new URL(document.referrer);
                    if (ref.origin !== window.location.origin || /ventas_(servicios|productos)\.php/i.test(ref.pathname)) {
                        window.location.href = document.referrer;
                        return;
                    }
                }
            } catch (e) {}

            if (window.history.length > 1) window.history.back();
            else window.close();
        });
        topbar.appendChild(button);
    }

    window.login = robustLogin;
    window.fechaLocal = fechaLocal;
    window.tipoActual = tipoActual;
    window.recurso = recurso;
    window.simbolo = simbolo;
    window.resetCreado = resetCreado;
    window.cambiarComprobante = cambiarComprobante;
    window.cambiarDocumento = cambiarDocumento;
    window.actualizarPlaceholder = actualizarPlaceholder;
    window.programarConsulta = programarConsulta;
    window.consultarDocumento = consultarDocumento;
    window.estadoConsulta = estadoConsulta;
    window.agregarDetalle = agregarDetalle;
    window.calcularTotales = calcularTotales;
    window.aplicarPreload = aplicarPreload;
    window.crearComprobante = crearComprobante;
    window.enviarSunat = enviarSunat;
    window.generarPdf = generarPdf;
    window.descargarPdf = descargarPdf;
    window.descargarXml = descargarXml;
    window.descargarCdr = descargarCdr;
    window.cerrarSesion = cerrarSesion;
    window.mostrarEstadoGeneral = mostrarEstadoGeneral;

    document.addEventListener('DOMContentLoaded', function () {
        var fecha = el('fechaEmision');
        var loginPanel = el('loginPanel');
        var loginButton = loginPanel ? loginPanel.querySelector('button.btn.primary') : null;
        var passwordInput = el('loginPassword');
        var numeroDocumento = el('numeroDocumento');
        var detalleBody = el('detalleBody');

        if (fecha && !fecha.value) fecha.value = fechaLocal();
        cambiarComprobante();
        actualizarPlaceholder();
        agregarBotonVolver();

        if (loginButton) {
            loginButton.id = 'manualLoginButton';
            loginButton.type = 'button';
            loginButton.onclick = robustLogin;
        }

        if (passwordInput) {
            passwordInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') robustLogin(event);
            });
        }

        if (numeroDocumento) {
            numeroDocumento.addEventListener('input', function () { programarConsulta(false); });
            numeroDocumento.addEventListener('blur', function () { programarConsulta(true); });
        }

        if (detalleBody && !detalleBody.children.length && !obtenerPreloadToken()) {
            agregarDetalle('', '', 'ZZ', 1, 0, '10');
        }

        validarToken();
    });
})();
