# Chrystal API - Sistema de Cotizaciones

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Sistema completo de gestión de cotizaciones, ventas y administración empresarial desarrollado con Laravel. Permite a empresas gestionar sus productos, clientes, cotizaciones y equipos de ventas con un sistema de roles robusto.

## 🚀 Características Principales

### 📊 Dashboard Inteligente
- **Dashboard personalizado por rol** (Admin, Manager, Company, Seller)
- **Métricas en tiempo real** de ventas, cotizaciones y rendimiento
- **Gráficos y estadísticas** de desempeño empresarial
- **Notificaciones y alertas** contextuales

### 👥 Gestión de Usuarios Multi-Rol
- **Admin**: Control total del sistema
- **Manager**: Supervisión de empresas y vendedores
- **Company**: Gestión de su propia empresa y vendedores
- **Seller**: Creación de cotizaciones y gestión de clientes

### 🏢 Gestión Empresarial
- **Registro de empresas** con validación por email
- **Sistema de licencias** con claves de activación
- **Gestión de vendedores** por empresa
- **Control de permisos** granular por rol

### 💼 Sistema de Cotizaciones
- **Creación y gestión** de cotizaciones
- **Estados de cotización**: Borrador, Enviada, Aprobada, Rechazada, Expirada
- **Cálculo automático** de impuestos y descuentos
- **Duplicación de cotizaciones** existentes
- **Filtros avanzados** por fecha, estado, empresa

### 📦 Gestión de Productos
- **Catálogo de productos** con categorías
- **Control de inventario** y stock mínimo
- **Precios y costos** por producto
- **Alertas de stock bajo**
- **Búsqueda y filtros** avanzados

### 👤 Gestión de Clientes
- **Base de datos de clientes** completa
- **Información de contacto** y ubicación
- **Historial de cotizaciones** por cliente
- **Segmentación** por empresa

### 📱 Sincronización Offline
- **API de sincronización** para aplicaciones móviles
- **Trabajo offline** con sincronización posterior
- **Logs de sincronización** detallados

## 🛠️ Tecnologías Utilizadas

- **Backend**: Laravel 11.x
- **Base de Datos**: MySQL/PostgreSQL
- **Autenticación**: Laravel Sanctum
- **Cache**: Redis/File
- **Email**: Laravel Mail
- **Validación**: Laravel Validation
- **Testing**: PHPUnit

## 📋 Requisitos del Sistema

- PHP 8.2 o superior
- Composer
- MySQL 8.0+ o PostgreSQL 13+
- Redis (opcional, para cache)
- Node.js & NPM (para assets)

## ⚡ Instalación Rápida

### 1. Clonar el Repositorio
```bash
git clone https://github.com/tu-usuario/chrystal-api.git
cd chrystal-api
```

### 2. Instalar Dependencias
```bash
composer install
npm install
```

### 3. Configurar Entorno
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configurar Base de Datos
Edita el archivo `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chrystal_db
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

### 5. Migrar y Sembrar
```bash
php artisan migrate --seed
```

### 6. Configurar Storage
```bash
php artisan storage:link
```

### 7. Iniciar Servidor
```bash
php artisan serve
```

## 🔧 Configuración Detallada

### Variables de Entorno Principales

```env
# Aplicación
APP_NAME="Chrystal API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Base de Datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chrystal_db
DB_USERNAME=root
DB_PASSWORD=

# Cache
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
```

## 📚 API Endpoints

### Autenticación
```http
POST   /api/login                          # Iniciar sesión
POST   /api/logout                         # Cerrar sesión
POST   /api/register                       # Registro de usuarios
GET    /api/me                             # Obtener usuario autenticado
```

### Empresas
```http
GET    /api/companies                      # Listar empresas
POST   /api/companies                      # Crear empresa
GET    /api/companies/{id}                 # Ver empresa
PUT    /api/companies/{id}                 # Actualizar empresa
DELETE /api/companies/{id}                 # Eliminar empresa
GET    /api/companies/{id}/sellers         # Vendedores de empresa
```

### Usuarios
```http
GET    /api/users                          # Listar usuarios
POST   /api/users                          # Crear usuario
GET    /api/users/{id}                     # Ver usuario
PUT    /api/users/{id}                     # Actualizar usuario
DELETE /api/users/{id}                     # Eliminar usuario
```

### Vendedores
```http
GET    /api/sellers                        # Listar vendedores
POST   /api/sellers                        # Crear vendedor
GET    /api/sellers/{id}                   # Ver vendedor
PUT    /api/sellers/{id}                   # Actualizar vendedor
DELETE /api/sellers/{id}                   # Eliminar vendedor
```

### Productos
```http
GET    /api/products                       # Listar productos
POST   /api/products                       # Crear producto
GET    /api/products/{id}                  # Ver producto
PUT    /api/products/{id}                  # Actualizar producto
DELETE /api/products/{id}                  # Eliminar producto
GET    /api/products/active                # Productos activos
GET    /api/products/low-stock             # Stock bajo
PUT    /api/products/{id}/stock            # Actualizar stock
```

### Clientes
```http
GET    /api/customers                      # Listar clientes
POST   /api/customers                      # Crear cliente
GET    /api/customers/{id}                 # Ver cliente
PUT    /api/customers/{id}                 # Actualizar cliente
DELETE /api/customers/{id}                 # Eliminar cliente
GET    /api/customers/active               # Clientes activos
```

### Cotizaciones
```http
GET    /api/quotes                         # Listar cotizaciones
POST   /api/quotes                         # Crear cotización
GET    /api/quotes/{id}                    # Ver cotización
PUT    /api/quotes/{id}                    # Actualizar cotización
DELETE /api/quotes/{id}                    # Eliminar cotización
POST   /api/quotes/{id}/send               # Enviar cotización
POST   /api/quotes/{id}/approve            # Aprobar cotización
POST   /api/quotes/{id}/reject             # Rechazar cotización
POST   /api/quotes/{id}/duplicate          # Duplicar cotización
GET    /api/quotes/stats                   # Estadísticas
```

### Dashboard
```http
GET    /api/dashboard                      # Dashboard por rol
```

### Sincronización
```http
GET    /api/sync/products                  # Sincronizar productos
GET    /api/sync/customers                 # Sincronizar clientes
POST   /api/sync/quotes                    # Subir cotizaciones offline
GET    /api/sync/quotes                    # Descargar cotizaciones
```

## 🔐 Sistema de Autenticación

### Registro de Empresa (Flujo Completo)

1. **Verificar información empresarial**:
```http
POST /api/check-company-info
{
    "email": "empresa@ejemplo.com",
    "rif": "J-12345678-9"
}
```

2. **Confirmar registro**:
```http
POST /api/confirm-company-registration
{
    "company_id": 1,
    "confirm": true
}
```

3. **Validar código enviado por email**:
```http
POST /api/validate-company-code
{
    "company_id": 1,
    "email": "empresa@ejemplo.com",
    "validation_code": "123456"
}
```

4. **Completar registro**:
```http
POST /api/complete-company-registration
{
    "validation_token": "token_recibido",
    "company_id": 1,
    "email": "admin@empresa.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

## 🎯 Ejemplos de Uso

### Crear una Cotización
```json
POST /api/quotes
{
    "customer_id": 1,
    "company_id": 1,
    "items": [
        {
            "product_id": 1,
            "quantity": 2,
            "unit_price": 150.00,
            "name": "Producto Personalizado"
        }
    ],
    "valid_until": "2024-12-31",
    "terms_conditions": "Términos y condiciones",
    "notes": "Notas adicionales",
    "discount": 0,
    "bcv_rate": 36.50
}
```

### Respuesta del Dashboard (Seller)
```json
{
    "success": true,
    "data": {
        "user_info": {
            "name": "Juan Pérez",
            "role": "seller",
            "last_login": "2024-03-15 10:30:00"
        },
        "seller_summary": {
            "companies_count": 1,
            "customers_count": 15,
            "quotes_today": 3,
            "quotes_this_month": 25,
            "sales_today": 485.30,
            "sales_month": 4250.80,
            "commission_earned_month": 191.25,
            "performance_rating": "excellent"
        },
        "recent_quotes": [...],
        "achievements": [...],
        "tips_and_insights": [...]
    }
}
```

## 🛡️ Seguridad

### Middleware de Autenticación
- Todas las rutas protegidas requieren token Bearer
- Validación de permisos por rol
- Rate limiting en endpoints críticos

### Validaciones
- Validación exhaustiva de datos de entrada
- Sanitización de inputs
- Protección contra inyección SQL
- Validación de permisos granular

## 🧪 Testing

### Ejecutar Tests
```bash
# Todos los tests
php artisan test

# Tests específicos
php artisan test --filter=AuthenticationTest
php artisan test --filter=QuoteTest

# Con cobertura
php artisan test --coverage
```

### Tests Disponibles
- **Authentication Tests**: Login, registro, permisos
- **Quote Tests**: CRUD, estados, validaciones
- **User Tests**: Gestión de usuarios por rol
- **Company Tests**: Gestión empresarial
- **Product Tests**: Catálogo y stock

## 📊 Monitoreo y Logs

### Logs del Sistema
```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Logs de sincronización
php artisan tinker
>>> App\Models\SyncLog::latest()->take(10)->get()
```

### Métricas Importantes
- Tiempo de respuesta de API
- Cotizaciones creadas por día
- Tasa de conversión de cotizaciones
- Performance por vendedor
- Sincronizaciones exitosas/fallidas

## 🚀 Deployment

### Producción con Docker
```dockerfile
FROM php:8.2-fpm

# Instalar dependencias
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip

# Configurar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install pdo pdo_mysql gd

# Copiar aplicación
COPY . /var/www
WORKDIR /var/www

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader

# Permisos
RUN chown -R www-data:www-data /var/www
RUN chmod -R 755 /var/www/storage

EXPOSE 9000
CMD ["php-fpm"]
```

### Variables de Entorno Producción
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

DB_CONNECTION=mysql
DB_HOST=tu-servidor-bd
DB_DATABASE=chrystal_production
DB_USERNAME=usuario_bd
DB_PASSWORD=contraseña_segura

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=tu-servidor-redis
REDIS_PASSWORD=contraseña_redis
```

## 🤝 Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

### Estándares de Código
- Seguir PSR-12 para PHP
- Usar PHPDoc para documentar métodos
- Escribir tests para nuevas funcionalidades
- Mantener coverage mínimo del 80%

## 📝 Changelog

### v1.2.0 (2024-03-15)
- ✨ Agregado sistema de dashboard personalizado por rol
- 🚀 Implementada sincronización offline
- 🔧 Mejorada validación de registro empresarial
- 🐛 Corregidos permisos de vendedores

### v1.1.0 (2024-03-01)
- ✨ Sistema completo de cotizaciones
- 📊 Dashboard con métricas avanzadas
- 🔐 Autenticación multi-rol
- 📱 API de sincronización móvil

### v1.0.0 (2024-02-15)
- 🎉 Versión inicial
- 👥 Gestión de usuarios y empresas
- 📦 Catálogo de productos
- 👤 Gestión de clientes

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo [LICENSE](LICENSE) para más detalles.

## 🆘 Soporte

### Documentación
- [Wiki del Proyecto](https://github.com/tu-usuario/chrystal-api/wiki)
- [API Documentation](https://tu-dominio.com/docs)
- [Postman Collection](link-to-postman)

### Contacto
- **Email**: soporte@chrystal.com
- **Slack**: [Workspace Chrystal](https://chrystal.slack.com)
- **Issues**: [GitHub Issues](https://github.com/tu-usuario/chrystal-api/issues)

### FAQ

**P: ¿Cómo reseteo la contraseña de un usuario?**
R: Usa el endpoint `/api/users/{id}` con método PUT enviando el campo `password`.

**P: ¿Puedo cambiar los porcentajes de comisión de un vendedor?**
R: Sí, a través del endpoint `/api/sellers/{id}` actualizando `percent_sales`.

**P: ¿Cómo manejo cotizaciones expiradas?**
R: Las cotizaciones se marcan automáticamente como expiradas. Usa el filtro `expired=true` en `/api/quotes`.

---

⭐ **¡Dale una estrella al proyecto si te fue útil!**

Desarrollado con ❤️ por el equipo de Chrystal