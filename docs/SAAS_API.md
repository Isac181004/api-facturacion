# API SaaS multiempresa SUNAT

El usuario con rol `super_admin` administra las empresas, sus credenciales SUNAT y las claves de integración. Los sistemas clientes nunca envían `company_id`: la empresa se determina desde `X-API-Key`.

## Puesta en marcha

```bash
php artisan migrate
php artisan config:clear
php artisan test
```

Configura `APP_KEY` antes de migrar. Los secretos SOL y certificados existentes se cifran durante la migración.

## Administración

Todas estas rutas requieren el token Sanctum del superadministrador:

- `PUT /api/v1/companies/{company}/sunat-credentials`
- `GET /api/v1/companies/{company}/sunat-diagnostics`
- `POST /api/v1/integration-clients`
- `POST /api/v1/integration-clients/{integrationClient}/rotate`
- `DELETE /api/v1/integration-clients/{integrationClient}`

El certificado se carga como `multipart/form-data` en el campo `certificado`. El PEM debe contener `BEGIN CERTIFICATE` y una clave privada. La clave API se muestra una sola vez al crearla o rotarla.

## Consumo desde sistemas externos

```http
X-API-Key: sunat_clave_entregada_al_cliente
Accept: application/json
Content-Type: application/json
```

Rutas:

- `GET /api/external/v1/documentos/consultar?tipo=1&numero=12345678`
- `GET /api/external/v1/documentos/consultar?tipo=6&numero=20123456789`
- `POST /api/external/v1/invoices`
- `POST /api/external/v1/invoices/{id}/send-sunat`
- `POST /api/external/v1/boletas`
- `POST /api/external/v1/boletas/{id}/send-sunat`
- `GET /api/external/v1/documents/{invoice|boleta}/{id}`

## Flujo comprobable de facturación

1. Crear empresa y sucursal.
2. Cargar credenciales SOL y certificado.
3. Ejecutar el diagnóstico SUNAT.
4. Crear la clave de integración.
5. Consultar DNI o RUC y completar el cliente.
6. Crear la factura o boleta.
7. Enviar a SUNAT.
8. Consultar el documento y descargar XML/CDR desde el panel protegido.

Un error de transporte, certificado, credenciales o endpoint queda como `ERROR_ENVIO`. Solo una respuesta CDR negativa de SUNAT queda como `RECHAZADO`; una respuesta conforme queda como `ACEPTADO`.

## Diagnóstico de Beta

El endpoint efectivo debe ser:

`https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService`

El diagnóstico comprueba entorno, endpoint, presencia de clave privada, vencimiento y posible coincidencia del RUC del certificado. El XML firmado se conserva cuando se generó antes de un fallo de transporte, facilitando separar errores UBL de errores SOAP/`sendBill`.
