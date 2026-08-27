<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Facturación - Salón & Spa</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f4f6f9;color:#1f2937;font-family:Arial,Helvetica,sans-serif}.topbar{background:#111827;color:#fff;padding:18px 30px;display:flex;justify-content:space-between;align-items:center}.topbar h1{margin:0;font-size:21px}.topbar small{color:#cbd5e1}.container{max-width:1450px;margin:30px auto;padding:0 20px}.grid{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:22px}.card{background:#fff;border-radius:14px;padding:22px;box-shadow:0 4px 18px rgba(0,0,0,.06);margin-bottom:20px}.card h2{margin-top:0;font-size:18px;border-bottom:1px solid #e5e7eb;padding-bottom:12px}.form-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:15px}.group{display:flex;flex-direction:column;gap:6px}.full{grid-column:1/-1}label{font-size:13px;font-weight:700}input,select{width:100%;border:1px solid #d1d5db;border-radius:8px;padding:11px 12px;background:#fff}.btn{border:0;border-radius:8px;padding:11px 17px;font-weight:700;cursor:pointer}.primary{background:#111827;color:#fff}.success{background:#059669;color:#fff}.blue{background:#2563eb;color:#fff}.secondary{background:#e5e7eb}.danger{background:#fee2e2;color:#991b1b;padding:8px 11px}.btn:disabled{opacity:.45;cursor:not-allowed}.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}.hidden{display:none!important}.estado{display:none;padding:14px;border-radius:10px;margin-bottom:15px;white-space:pre-line}.estado.ok{display:block;background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}.estado.error{display:block;background:#fef2f2;color:#991b1b;border:1px solid #fecaca}.mini{font-size:12px;min-height:18px;color:#6b7280}.mini.ok{color:#047857}.mini.error{color:#b91c1c}.badge{padding:5px 10px;border-radius:20px;background:#e0f2fe;color:#075985;font-size:12px;font-weight:700}.badge.factura{background:#fef3c7;color:#92400e}.login{max-width:520px;margin:70px auto}.source{border:1px dashed #cbd5e1;background:#f8fafc;padding:10px 12px;border-radius:9px;margin-bottom:15px;font-size:12px}.wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:1080px}th{background:#f3f4f6;padding:10px 8px;font-size:12px;text-align:left;white-space:nowrap}td{padding:8px;border-bottom:1px solid #e5e7eb;vertical-align:middle}td input,td select{min-width:80px}.desc{min-width:220px}.unidad{min-width:150px}.afe-igv{min-width:165px}.total-line{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid #e5e7eb}.total-final{font-size:24px;font-weight:700;border:0;padding-top:18px}.note{font-size:12px;color:#6b7280;margin-top:8px;line-height:1.45}@media(max-width:900px){.grid,.form-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="topbar"><div><h1>Salón & Spa · Facturación</h1><small>Boletas y facturas electrónicas</small></div><span id="badge" class="badge">BOLETA · SUNAT BETA</span></div>

<div id="loginPanel" class="container login">
    <div class="card">
        <h2>Ingresar al sistema</h2>
        <div id="loginEstado" class="estado"></div>
        <div class="group"><label>Correo</label><input id="loginEmail" type="email" value="admin@local.test"></div><br>
        <div class="group"><label>Contraseña</label><input id="loginPassword" type="password"></div><br>
        <button class="btn primary" onclick="login()">Ingresar</button>
    </div>
</div>

<div id="appPanel" class="container hidden">
    <div id="estadoGeneral" class="estado"></div>
    <div id="sourceBox" class="source hidden"></div>
    <div class="grid">
        <div>
            <div class="card">
                <h2>Datos del comprobante</h2>
                <div class="form-grid">
                    <div class="group"><label>Tipo de comprobante</label><select id="tipoComprobante" onchange="cambiarComprobante()"><option value="boleta">Boleta</option><option value="factura">Factura</option></select></div>
                    <div class="group"><label>Serie</label><input id="serie" value="B001" readonly></div>
                    <div class="group"><label>Fecha de emisión</label><input id="fechaEmision" type="date"></div>
                    <div class="group"><label>Forma de pago</label><select id="formaPago"><option value="Contado">Contado</option><option value="Credito">Crédito</option></select></div>
                    <div class="group full"><label>Moneda</label><select id="moneda" class="notranslate" translate="no" onchange="calcularTotales()"><option value="PEN" translate="no">Soles (PEN)</option><option value="USD" translate="no">Dólares (USD)</option></select></div>
                </div>
            </div>

            <div class="card">
                <h2>Cliente</h2>
                <div class="form-grid">
                    <div class="group"><label>Tipo de documento</label><select id="tipoDocumento" onchange="cambiarDocumento()"><option value="1">DNI</option><option value="6">RUC</option></select></div>
                    <div class="group"><label>Número de documento</label><input id="numeroDocumento" inputmode="numeric" autocomplete="off"><div id="consultaEstado" class="mini"></div></div>
                    <div class="group full"><label>Nombre / Razón social</label><input id="razonSocial"></div>
                    <div class="group full"><label>Dirección</label><input id="direccionCliente" placeholder="Opcional para DNI"></div>
                </div>
            </div>

            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:15px">
                    <h2 style="border:0;margin:0">Detalle de venta</h2>
                    <button class="btn secondary" onclick="agregarDetalle()">+ Agregar detalle</button>
                </div>
                <div class="wrap">
                    <table>
                        <thead><tr><th>Código</th><th>Descripción</th><th>Unidad</th><th>Cant.</th><th>Precio final</th><th>Afectación IGV</th><th>Base</th><th>IGV</th><th>Total</th><th></th></tr></thead>
                        <tbody id="detalleBody"></tbody>
                    </table>
                </div>
                <div class="note">Servicio (ZZ) y Producto / Unidad (NIU) son los códigos que se envían a SUNAT. En <strong>Precio final</strong> ingresa lo que realmente paga el cliente. Por cada detalle puedes elegir Gravado 18%, Exonerado o Inafecto.</div>
            </div>
        </div>

        <div>
            <div class="card">
                <h2>Resumen</h2>
                <div class="total-line"><span>Base gravada</span><strong id="totalBase">S/ 0.00</strong></div>
                <div class="total-line"><span>Oper. exoneradas</span><strong id="totalExonerado">S/ 0.00</strong></div>
                <div class="total-line"><span>Oper. inafectas</span><strong id="totalInafecto">S/ 0.00</strong></div>
                <div class="total-line"><span>IGV 18%</span><strong id="totalIgv">S/ 0.00</strong></div>
                <div class="total-line total-final"><span>Total</span><strong id="totalFinal">S/ 0.00</strong></div>
                <div class="actions"><button id="btnCrear" class="btn primary" onclick="crearComprobante()">Crear boleta</button><button id="btnEnviar" class="btn success" onclick="enviarSunat()" disabled>Enviar a SUNAT</button></div>
                <div class="actions"><button id="btnPdf" class="btn blue" onclick="generarPdf()" disabled>Generar PDF</button><button id="btnVerPdf" class="btn secondary" onclick="descargarPdf()" disabled>Descargar PDF</button></div>
                <div class="actions"><button id="btnXml" class="btn secondary" onclick="descargarXml()" disabled>XML</button><button id="btnCdr" class="btn secondary" onclick="descargarCdr()" disabled>CDR</button></div>
                <hr style="border:0;border-top:1px solid #e5e7eb;margin:20px 0">
                <button class="btn secondary" onclick="cerrarSesion()">Cerrar sesión</button>
            </div>
        </div>
    </div>
</div>

<script>
const API=window.location.origin;
let token=localStorage.getItem('sunat_token')||'',comprobanteId=null,numeroComprobante=null,consultaTimer=null,cargandoPreload=false;
function fechaLocal(){const d=new Date();return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`}
document.getElementById('fechaEmision').value=fechaLocal();
function tipoActual(){return document.getElementById('tipoComprobante').value==='factura'?'factura':'boleta'}
function recurso(){return tipoActual()==='factura'?'invoices':'boletas'}
function simbolo(){return document.getElementById('moneda').value==='USD'?'$':'S/'}
function resetCreado(){comprobanteId=null;numeroComprobante=null;['btnEnviar','btnPdf','btnVerPdf','btnXml','btnCdr'].forEach(x=>document.getElementById(x).disabled=true)}
function cambiarComprobante(){const f=tipoActual()==='factura',td=document.getElementById('tipoDocumento'),b=document.getElementById('badge');document.getElementById('serie').value=f?'F001':'B001';document.getElementById('btnCrear').textContent=f?'Crear factura':'Crear boleta';b.textContent=(f?'FACTURA':'BOLETA')+' · SUNAT BETA';b.classList.toggle('factura',f);if(f){td.value='6';td.disabled=true}else td.disabled=false;actualizarPlaceholder();resetCreado();programarConsulta(true)}
function cambiarDocumento(){actualizarPlaceholder();document.getElementById('razonSocial').value='';document.getElementById('direccionCliente').value='';resetCreado();programarConsulta(true)}
function actualizarPlaceholder(){const r=document.getElementById('tipoDocumento').value==='6';document.getElementById('numeroDocumento').placeholder=r?'11 dígitos de RUC':'8 dígitos de DNI'}
async function login(){try{const r=await fetch(API+'/api/auth/login',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({email:loginEmail.value.trim(),password:loginPassword.value})}),d=await leerJson(r);if(!r.ok)throw new Error(errorMsg(d));token=d.access_token;localStorage.setItem('sunat_token',token);mostrarApp();await cargarPreload()}catch(e){mostrarEstado('loginEstado',e.message,false)}}
async function validarToken(){if(!token)return;try{const r=await fetch(API+'/api/v1/auth/me',{headers:{Authorization:'Bearer '+token,Accept:'application/json'}});if(r.ok){mostrarApp();await cargarPreload()}else{localStorage.removeItem('sunat_token');token=''}}catch(e){console.error(e)}}
function mostrarApp(){loginPanel.classList.add('hidden');appPanel.classList.remove('hidden')}
function cerrarSesion(){localStorage.removeItem('sunat_token');location.reload()}
function programarConsulta(ya=false){clearTimeout(consultaTimer);const t=tipoDocumento.value,n=numeroDocumento.value.replace(/\D/g,''),l=t==='6'?11:8;numeroDocumento.value=n;if(!n){consultaEstado.textContent='';return}if(n.length<l){consultaEstado.textContent=`Faltan ${l-n.length} dígito(s).`;return}if(n.length>l){estadoConsulta('Longitud de documento inválida.',false);return}consultaTimer=setTimeout(consultarDocumento,ya?0:450)}
async function consultarDocumento(){if(!token)return;const t=tipoDocumento.value,n=numeroDocumento.value.replace(/\D/g,''),l=t==='6'?11:8;if(n.length!==l)return;estadoConsulta(t==='6'?'Consultando RUC...':'Consultando DNI...');try{const r=await fetch(API+`/api/v1/documentos/consultar?tipo=${encodeURIComponent(t)}&numero=${encodeURIComponent(n)}`,{headers:{Authorization:'Bearer '+token,Accept:'application/json'}}),d=await leerJson(r);if(!r.ok||d.success===false)throw new Error(errorMsg(d));razonSocial.value=d.data?.razon_social||'';direccionCliente.value=d.data?.direccion||'';estadoConsulta(t==='6'?'RUC encontrado.':'DNI encontrado.',true)}catch(e){estadoConsulta(e.message,false)}}
function estadoConsulta(m,ok=null){consultaEstado.textContent=m||'';consultaEstado.className='mini'+(ok===true?' ok':ok===false?' error':'')}
numeroDocumento.addEventListener('input',()=>programarConsulta(false));
numeroDocumento.addEventListener('blur',()=>programarConsulta(true));
function esc(v){return String(v??'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;')}
function agregarDetalle(c='',d='',u='ZZ',q=1,p=0,afe='10'){
    const tr=document.createElement('tr');
    const afeSeguro=['10','20','30'].includes(String(afe))?String(afe):'10';
    tr.innerHTML=`<td><input class="codigo" value="${esc(c)}"></td><td><input class="descripcion desc" value="${esc(d)}"></td><td><select class="unidad notranslate" translate="no"><option value="ZZ" ${u==='ZZ'?'selected':''}>Servicio (ZZ)</option><option value="NIU" ${u==='NIU'?'selected':''}>Producto / Unidad (NIU)</option></select></td><td><input class="cantidad" type="number" min="0.001" step="0.001" value="${Number(q)||1}"></td><td><input class="precio" type="number" min="0" step="0.01" value="${Number(p)||0}"></td><td><select class="afe-igv"><option value="10" ${afeSeguro==='10'?'selected':''}>Sí · Gravado 18%</option><option value="20" ${afeSeguro==='20'?'selected':''}>No · Exonerado</option><option value="30" ${afeSeguro==='30'?'selected':''}>No · Inafecto</option></select></td><td class="base">0.00</td><td class="igv">0.00</td><td class="total">0.00</td><td><button class="btn danger" onclick="this.closest('tr').remove();calcularTotales();resetCreado()">×</button></td>`;
    tr.querySelectorAll('input,select').forEach(el=>el.addEventListener('input',()=>{calcularTotales();resetCreado()}));
    tr.querySelectorAll('select').forEach(el=>el.addEventListener('change',()=>{calcularTotales();resetCreado()}));
    detalleBody.appendChild(tr);
    calcularTotales();
}
function calcularTotales(){let gravado=0,exonerado=0,inafecto=0,igvTotal=0,total=0;document.querySelectorAll('#detalleBody tr').forEach(r=>{const q=Number(r.querySelector('.cantidad').value)||0,p=Number(r.querySelector('.precio').value)||0,afe=r.querySelector('.afe-igv').value,tt=q*p;let base=tt,igv=0;if(afe==='10'){base=tt/1.18;igv=tt-base;gravado+=base}else if(afe==='20'){exonerado+=base}else{inafecto+=base}r.querySelector('.base').textContent=base.toFixed(2);r.querySelector('.igv').textContent=igv.toFixed(2);r.querySelector('.total').textContent=tt.toFixed(2);igvTotal+=igv;total+=tt});totalBase.textContent=`${simbolo()} ${gravado.toFixed(2)}`;totalExonerado.textContent=`${simbolo()} ${exonerado.toFixed(2)}`;totalInafecto.textContent=`${simbolo()} ${inafecto.toFixed(2)}`;totalIgv.textContent=`${simbolo()} ${igvTotal.toFixed(2)}`;totalFinal.textContent=`${simbolo()} ${total.toFixed(2)}`}
function validarCliente(){const f=tipoActual()==='factura',t=tipoDocumento.value,n=numeroDocumento.value.replace(/\D/g,'');if(f&&(t!=='6'||!/^\d{11}$/.test(n)))return 'Para emitir factura debes usar un RUC válido de 11 dígitos.';if(t==='1'&&!/^\d{8}$/.test(n))return 'El DNI debe tener exactamente 8 dígitos.';if(t==='6'&&!/^\d{11}$/.test(n))return 'El RUC debe tener exactamente 11 dígitos.';if(!razonSocial.value.trim())return 'Completa el nombre o razón social del cliente.';return ''}
function detalles(){const out=[],filas=document.querySelectorAll('#detalleBody tr');if(!filas.length)throw new Error('Agrega al menos un servicio o producto.');for(const r of filas){const c=r.querySelector('.codigo').value.trim(),d=r.querySelector('.descripcion').value.trim(),u=r.querySelector('.unidad').value,q=Number(r.querySelector('.cantidad').value),p=Number(r.querySelector('.precio').value),afe=r.querySelector('.afe-igv').value;if(!c||!d||q<=0||p<=0)throw new Error('Completa correctamente todos los detalles de la venta.');const aplicaIgv=afe==='10';out.push({codigo:c,descripcion:d,unidad:u,cantidad:q,mto_valor_unitario:Number((aplicaIgv?p/1.18:p).toFixed(6)),porcentaje_igv:aplicaIgv?18:0,tip_afe_igv:afe})}return out}
async function crearComprobante(){limpiarEstado();const e=validarCliente();if(e)return mostrarEstadoGeneral(e,false);let ds;try{ds=detalles()}catch(x){return mostrarEstadoGeneral(x.message,false)}const payload={company_id:1,branch_id:1,serie:serie.value,fecha_emision:fechaEmision.value,ubl_version:'2.1',tipo_operacion:'0101',moneda:moneda.value,metodo_envio:'individual',forma_pago_tipo:formaPago.value,client:{tipo_documento:tipoDocumento.value,numero_documento:numeroDocumento.value.replace(/\D/g,''),razon_social:razonSocial.value.trim(),direccion:direccionCliente.value.trim()},detalles:ds};try{btnCrear.disabled=true;const r=await fetch(API+'/api/v1/'+recurso(),{method:'POST',headers:{Authorization:'Bearer '+token,'Content-Type':'application/json',Accept:'application/json'},body:JSON.stringify(payload)}),d=await leerJson(r);if(!r.ok)throw new Error(errorMsg(d));const x=d.data||{};comprobanteId=x.id??x.id_factura??x.id_boleta??null;numeroComprobante=x.numero_completo??x.numero??((x.serie||payload.serie)+'-'+String(x.correlativo||'').padStart(6,'0'));if(!comprobanteId)throw new Error('La API no devolvió el ID del comprobante.');btnEnviar.disabled=false;mostrarEstadoGeneral(`${tipoActual()==='factura'?'Factura':'Boleta'} creada correctamente:\n${numeroComprobante}`,true)}catch(x){mostrarEstadoGeneral(x.message,false)}finally{btnCrear.disabled=false}}
async function enviarSunat(){if(!comprobanteId)return;try{btnEnviar.disabled=true;mostrarEstadoGeneral('Enviando a SUNAT...',true);const r=await fetch(`${API}/api/v1/${recurso()}/${comprobanteId}/send-sunat`,{method:'POST',headers:{Authorization:'Bearer '+token,Accept:'application/json'}}),d=await leerJson(r);if(!r.ok)throw new Error(errorMsg(d));const e=d.data?.estado_sunat??d.data?.sunat?.estado??'ACEPTADO';mostrarEstadoGeneral(`${numeroComprobante}\nEstado SUNAT: ${e}`,true);btnPdf.disabled=btnXml.disabled=btnCdr.disabled=false}catch(x){mostrarEstadoGeneral(x.message,false);btnEnviar.disabled=false}}
async function generarPdf(){try{const r=await fetch(`${API}/api/v1/${recurso()}/${comprobanteId}/generate-pdf`,{method:'POST',headers:{Authorization:'Bearer '+token,Accept:'application/json'}}),d=await leerJson(r);if(!r.ok)throw new Error(errorMsg(d));btnVerPdf.disabled=false;mostrarEstadoGeneral('PDF generado correctamente.',true)}catch(x){mostrarEstadoGeneral(x.message,false)}}
async function bajar(suf,nombre){try{const r=await fetch(`${API}/api/v1/${recurso()}/${comprobanteId}/${suf}`,{headers:{Authorization:'Bearer '+token}});if(!r.ok)throw new Error(errorMsg(await leerJson(r)));const b=await r.blob(),u=URL.createObjectURL(b),a=document.createElement('a');a.href=u;a.download=nombre;document.body.appendChild(a);a.click();a.remove();URL.revokeObjectURL(u)}catch(x){mostrarEstadoGeneral(x.message,false)}}
function descargarPdf(){return bajar('download-pdf',numeroComprobante+'.pdf')}
function descargarXml(){return bajar('download-xml',numeroComprobante+'.xml')}
function descargarCdr(){return bajar('download-cdr','R-'+numeroComprobante+'.zip')}
async function cargarPreload(){if(cargandoPreload||!token)return;const k=new URLSearchParams(location.search).get('preload');if(!k)return;cargandoPreload=true;try{const r=await fetch(API+'/api/v1/comprobantes/preload/'+encodeURIComponent(k),{headers:{Authorization:'Bearer '+token,Accept:'application/json'}}),d=await leerJson(r);if(!r.ok)throw new Error(errorMsg(d));aplicarPreload(d.data||{});mostrarEstadoGeneral('Venta cargada correctamente. Revisa y emite el comprobante.',true)}catch(x){mostrarEstadoGeneral(x.message,false)}finally{cargandoPreload=false}}
function aplicarPreload(d){tipoComprobante.value=d.tipo_comprobante==='factura'?'factura':'boleta';cambiarComprobante();const c=d.client||{};if(tipoActual()==='factura')tipoDocumento.value='6';else if(['1','6'].includes(String(c.tipo_documento||'')))tipoDocumento.value=String(c.tipo_documento);numeroDocumento.value=String(c.numero_documento||'').replace(/\D/g,'');razonSocial.value=c.razon_social||'';direccionCliente.value=c.direccion||'';detalleBody.innerHTML='';(Array.isArray(d.items)?d.items:[]).forEach(x=>{let afe=['10','20','30'].includes(String(x.tip_afe_igv||''))?String(x.tip_afe_igv):(x.aplica_igv===false?'30':'10');agregarDetalle(x.codigo||'',x.descripcion||x.nombre||'',x.unidad==='NIU'?'NIU':'ZZ',x.cantidad??1,x.precio_final??x.precio??0,afe)});if(!detalleBody.children.length)agregarDetalle();if(['PEN','USD'].includes(d.moneda))moneda.value=d.moneda;if(['Contado','Credito'].includes(d.forma_pago_tipo))formaPago.value=d.forma_pago_tipo;calcularTotales();const o=[d.origen,d.venta_id?'Venta #'+d.venta_id:''].filter(Boolean).join(' · ');if(o){sourceBox.textContent='Datos cargados desde: '+o;sourceBox.classList.remove('hidden')}if(numeroDocumento.value&&!razonSocial.value)programarConsulta(true)}
async function leerJson(r){const t=await r.text();try{return JSON.parse(t)}catch{return{message:t||'Respuesta inválida del servidor.'}}}
function errorMsg(d){if(d?.errors)return Object.values(d.errors).flat().join('\n');return d?.message||d?.error||'Ocurrió un error.'}
function mostrarEstadoGeneral(m,ok){mostrarEstado('estadoGeneral',m,ok)}
function mostrarEstado(id,m,ok){const e=document.getElementById(id);e.textContent=m;e.className='estado '+(ok?'ok':'error')}
function limpiarEstado(){estadoGeneral.className='estado';estadoGeneral.textContent=''}
cambiarComprobante();agregarDetalle();validarToken();
</script>
</body>
</html>
