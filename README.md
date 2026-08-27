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
- Certificado digital SUNAT (.pfx)

### Pasos de Instalación

1. **Clonar el repositorio**
```bash
git clone https://github.com/Isac181004/api-facturacion.git
cd api-facturacion
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

6. **Configurar certificados SUNAT**
- Colocar certificado .pfx en `storage/certificates/`
- Configurar rutas en el archivo .env

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

### Interfaz web de facturación

Después de iniciar el servidor, abra una de estas direcciones:

```text
http://127.0.0.1:8000/
http://127.0.0.1:8000/facturacion
```

La interfaz permite:

- Iniciar sesión con un usuario de la API.
- Seleccionar empresa y sucursal sin identificadores fijos.
- Crear facturas y boletas.
- Buscar primero un cliente guardado y, si es nuevo, consultar DNI o RUC.
- Mostrar nombres y apellidos separados para DNI.
- Mostrar razón social, dirección, estado y condición para RUC.
- Agregar múltiples productos o servicios y calcular IGV.
- Enviar el comprobante a SUNAT y descargar PDF, XML y CDR.

### Consulta automática de DNI y RUC

Configure el proveedor de consulta en `.env`:

```env
DOCUMENT_LOOKUP_DRIVER=apiinti
DOCUMENT_LOOKUP_CACHE_HOURS=24
APIINTI_KEY=coloque_aqui_su_token
APIINTI_BASE_URL=https://app.apiinti.dev/api/v1
```

Después de modificar `.env`, limpie la configuración:

```bash
php artisan config:clear
```

Rutas disponibles con autenticación Bearer:

```text
GET /api/v1/documentos/consultar?tipo=1&numero=12345678
GET /api/v1/documentos/dni/12345678
GET /api/v1/documentos/ruc/20601234567
```

RENIEC ofrece su Web Service de Datos mediante solicitud y convenio para empresas.
SUNAT ofrece el Padrón Reducido del RUC para descarga. La integración actual mantiene
el proveedor encapsulado para que pueda sustituirse cuando se cuente con acceso oficial.

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

## 🤝 Contribuciones

- Fork el proyecto
- Crea una rama para tu feature
- Envía un pull request

---

**⚡ Desarrollado con Laravel 12 y Greenter 5.1 para la comunidad peruana**

*"Facilitando la facturación electrónica en Perú - Un documento a la vez"*
