<p align="center">
  <img src="./public/assets/images/sunat.png" alt="SUNAT Logo" width="250">
</p>

# API de Facturación Electrónica SUNAT - Perú

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Greenter-5.1-4CAF50?style=for-the-badge" alt="Greenter 5.1">
  <img src="https://img.shields.io/badge/SUNAT-Compatible-0066CC?style=for-the-badge" alt="SUNAT Compatible">
</p>

Sistema completo de facturación electrónica para SUNAT Perú desarrollado con **Laravel 12** y la librería **Greenter 5.1**. Este proyecto implementa todas las funcionalidades necesarias para la generación, envío y gestión de comprobantes de pago electrónicos según las normativas de SUNAT.

## 🚀 Características Principales

### Documentos Electrónicos Soportados
- ✅ **Facturas** (Tipo 01)
- ✅ **Boletas de Venta** (Tipo 03)
- ✅ **Notas de crédito** (Tipo 07)
- ✅ **Notas de débito** (Tipo 08)
- ✅ **Guías de remisión** (Tipo 09)

### Funcionalidades del Sistema
- 🏢 **Multi-empresa**: Gestión de múltiples empresas y sucursales
- 🔐 **Autenticación OAuth2** para APIs de SUNAT
- 📄 **Generación automática de PDF** con diseño profesional

### Tecnologías Utilizadas
- **Framework**: Laravel 12 con PHP 8.2+
- **SUNAT Integration**: Greenter 5.1
- **Base de Datos**: MySQL/PostgreSQL compatible
- **PDF Generation**: DomPDF con plantillas personalizadas
- **QR Codes**: Endroid QR Code
- **Authentication**: Laravel Sanctum
- **Testing**: PestPHP

## 🛠️ Instalación

### Requisitos Previos
- PHP 8.2 o superior
- Composer
- MySQL 8.0+ o PostgreSQL
- Certificado digital SUNAT (.pfx convertible a `.pem`)

### Pasos de Instalación

1. **Clonar el repositorio**
```bash
git clone clone https://github.com/yorchavez9/Api-de-facturacion-electronica-sunat-Peru.git
cd Api-de-facturacion-electronica-sunat-Peru
```

2. **Instalar dependencias**
```bash
composer install
```

3. **Configurar variables de entorno**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configurar base de datos en .env**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=facturacion_sunat
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password
```

5. **Ejecutar migraciones**
```bash
php artisan migrate
```

6. **Configurar la empresa y el certificado SUNAT**
- Crea el primer superadministrador con un `POST` a `/api/auth/initialize` (`name`, `email` y `password`).
- Ingresa al portal web en `/login`; crea empresas mediante la API autenticada de administración y gestiona su configuración desde el portal.
- Cada empresa carga su propio archivo `.pem` desde Configuración SUNAT. El archivo se almacena en el disco privado `storage/app/private/sunat/certificates/companies/{company_id}/`; no requiere una ruta en `.env` ni `storage:link`.

### Conversión de Certificado .pfx a .pem

Si necesitas convertir tu certificado de formato .pfx a .pem, ejecuta el siguiente comando en terminal:

```bash
# Convertir de .PFX a .PEM
openssl pkcs12 -in certificado.pfx -out certificado_correcto.pem -nodes
```

**Nota:** Este comando te pedirá la contraseña de tu certificado .pfx y generará un archivo .pem que puedes usar directamente en el sistema.

## 🏗️ Arquitectura del Sistema

### Estructura de Modelos
- **Company**: Empresas emisoras
- **Branch**: Sucursales por empresa
- **Client**: Clientes y proveedores
- **Invoice/Boleta/CreditNote/DebitNote**: Documentos electrónicos
- **DailySummary**: Resúmenes diarios de boletas
- **CompanyConfiguration**: Configuraciones por empresa

### Servicios Principales
- **DocumentService**: Lógica de negocio para documentos
- **SunatService**: Integración con APIs de SUNAT  
- **PdfService**: Generación de documentos PDF
- **FileService**: Gestión de archivos XML/PDF
- **TaxCalculationService**: Cálculo de impuestos
- **SeriesService**: Gestión de series documentarias

## 📚 Documentación de la API

### 🎥 Video Tutorial Completo
**Aprende a implementar el sistema paso a paso:**
👉 **[Ver Playlist Completa en YouTube](https://www.youtube.com/watch?v=HrrEdjY_7MU&list=PLfwfiNJ5Qw-ZlCfGnWjnILOI4OJfJkGp5)**

Esta playlist incluye:
- Instalación completa del sistema
- Configuración de certificados SUNAT
- Ejemplos reales de implementación
- Casos de uso prácticos
- Resolución de problemas comunes

### 📖 Documentación y Ejemplos

**Documentación completa y actualizada:**
👉 **[https://apigo.apuuraydev.com/](https://apigo.apuuraydev.com/)**

**Ejemplos listos para usar:**
En el directorio `ejemplos-postman/` encontrarás colecciones completas listas para importar en Postman o herramientas similares, con ejemplos de:
- Facturas, boletas y notas
- Guías de remisión
- Consultas CPE
- Configuraciones avanzadas

## 🏢 Multiempresa, portal y API Keys

La capa multiempresa mantiene la integración SUNAT existente y agrega aislamiento por empresa para empresas, sucursales, clientes, documentos, correlativos y configuraciones. Los usuarios de empresa quedan vinculados a un único `company_id`; las claves de integración resuelven el tenant desde la credencial autenticada, no desde un identificador enviado por el consumidor.

> Antes de aplicar las migraciones en una base con datos reales, realiza un respaldo y valida primero en staging la migración de clientes históricos: redistribuye asociaciones de facturas/boletas por empresa y reemplaza el índice único global por uno compuesto. La migración de limpieza elimina credenciales GRE compartidas de demostración y su rollback es intencionalmente irreversible.

### Certificado y configuración SUNAT

- Abre `/login` y entra con una cuenta habilitada.
- En el área de empresa, configura usuario/clave SOL y carga un `.pem` que contenga certificado X.509 y clave privada.
- El archivo se guarda de forma privada bajo `storage/app/private/sunat/certificates/companies/{company_id}/certificate.pem`.
- El PEM público histórico sólo se admite como compatibilidad en una instalación que tenga una única empresa. En una plataforma multiempresa, cada empresa debe cargar su propio PEM privado desde el portal.
- Los XML, CDR y PDF nuevos también se guardan en almacenamiento privado por empresa y se entregan sólo por rutas autenticadas.
- El portal no vuelve a mostrar contraseñas ni secretos guardados.
- Sólo el superadministrador puede autorizar producción; se requiere usuario SOL, clave SOL y PEM privado válido. El modo Beta sigue siendo el predeterminado.
- En instalaciones existentes, después del respaldo y antes de producción, ejecuta `php artisan sunat:privatize-document-files`. El comando copia las referencias históricas a rutas privadas por empresa, incluso si varios documentos compartían el mismo archivo público, y elimina el original sólo cuando ya no queda ninguna referencia. Si encuentra archivos faltantes, informa el documento para revisión manual.

### Crear y usar una API Key

1. En Configuración SUNAT, crea una API Key con nombre descriptivo. El secreto se muestra una única vez: guárdalo en un gestor de secretos.
2. Las claves tienen ambiente `test` o `live`; el ambiente debe coincidir con el modo autorizado de la empresa. Emitir `live` requiere autorización de superadministrador.
3. Envía la clave como Bearer token. El consumidor no necesita enviar `company_id`:

```bash
curl -H 'Authorization: Bearer mc_test_<identificador>_<secreto>' \
  https://tu-dominio/api/v1/external/clients
```

Rutas de empresa disponibles bajo `/api/v1/external`:

- `GET /branches`, `GET /branches/{branch}`
- `GET|POST /clients`, `GET /clients/{client}`, `POST /clients/search-by-document`
- `GET|POST /invoices`, `GET /invoices/{id}`, `POST /invoices/{id}/send-sunat`
- `GET|POST /boletas`, `GET /boletas/{id}`, `POST /boletas/{id}/send-sunat`
- `GET|POST /credit-notes`, `GET /credit-notes/{id}`, `POST /credit-notes/{id}/send-sunat`
- `GET|POST /debit-notes`, `GET /debit-notes/{id}`, `POST /debit-notes/{id}/send-sunat`
- `GET|POST /dispatch-guides`, `GET /dispatch-guides/{id}`, `POST /dispatch-guides/{id}/send-sunat`, `GET /dispatch-guides/{id}/check-status`
- Las cinco familias de documentos ofrecen generación de PDF y descargas autenticadas XML/CDR/PDF; las notas y guías incluyen catálogos de motivos y modalidad aplicables.

La API Key define la empresa y se inyecta internamente el `company_id` para compatibilidad con los validadores existentes. Si el consumidor envía un `company_id` distinto, la petición se rechaza. Las claves se almacenan como hash, pueden revocarse desde el portal y registran contador/último uso. Las rutas originales `/api/v1/...` continúan disponibles para clientes autenticados con Sanctum y también aplican aislamiento tenant.

## ⚖️ Licencia y Uso

**Este proyecto es de uso libre bajo las siguientes condiciones:**

- ✅ Puedes usar, modificar y distribuir el código libremente
- ✅ Puedes usarlo para proyectos comerciales y personales
- ⚠️ **Todo el uso es bajo tu propia responsabilidad**
- ⚠️ No se ofrece garantía ni soporte oficial
- ⚠️ Debes cumplir con las normativas de SUNAT de tu país

### Importante
- Asegúrate de tener los certificados digitales válidos de SUNAT
- Configura correctamente los endpoints según tu ambiente (beta/producción)
- Realiza pruebas exhaustivas antes de usar en producción
- Mantén actualizadas las librerías de seguridad

## 🤝 Soporte y Donaciones

Si este proyecto te ha sido útil y deseas apoyar su desarrollo:

### 💰 Yape (Perú)
<p align="center">
  <img src="./public/assets/images/yape.png" alt="Yape" width="100">
</p>

**Número:** `920468502`

### 💬 WhatsApp
**Contacto:** [https://wa.link/z50dwk](https://wa.link/z50dwk)

### 📧 Contribuciones
- Fork el proyecto
- Crea una rama para tu feature
- Envía un pull request

---

## 📞 Contacto

Para consultas técnicas o colaboraciones:
- **WhatsApp**: [https://wa.link/z50dwk](https://wa.link/z50dwk)
- **Yape**: 920468502

---

**⚡ Desarrollado con Laravel 12 y Greenter 5.1 para la comunidad peruana**

*"Facilitando la facturación electrónica en Perú - Un documento a la vez"*