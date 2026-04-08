# Changelog

Todos los cambios notables en este proyecto se documentan en este archivo.

## [1.2.4] - 2026-04-08

### Fixed
- **CRÍTICO**: Nombre del ZIP corregido a `replanta-rgb-*.zip` para coincidir con carpeta instalada
- Sistema de actualizaciones ahora busca el ZIP con nombre correcto
- Estructura del ZIP ahora incluye carpeta raíz `replanta-rgb/`

### Technical
- Actualizador de GitHub prioriza ZIPs con nombre `replanta-rgb-*.zip`
- Comando git archive ahora usa `--prefix=replanta-rgb/` para estructura correcta

## [1.2.3] - 2026-04-08

### Added
- Botón "Comprobar actualizaciones" en la página de configuración
- Verificación manual de nuevas versiones desde GitHub
- Detección visual de actualizaciones disponibles
- Link directo a página de actualizaciones de WordPress

### Improved
- Interfaz de actualizaciones muestra versión actual y disponible
- Forzado de limpieza de cache para detectar actualizaciones inmediatamente

## [1.2.2] - 2026-04-08

### Fixed
- **CRÍTICO**: Corregido error fatal en página de configuración que impedía acceder al plugin
- Eliminadas llamadas a métodos inexistentes `save_from_post()` y `get_all()` en settings-form.php
- Formulario ahora usa AJAX correctamente en lugar de POST tradicional
- Agregado botón "Probar Conexión" para verificar credenciales de Cloudflare
- Corregidos IDs de botones para aplicar reglas de Cloudflare y LSWC

## [1.2.1] - 2026-04-08

### Fixed
- Campo de email de Cloudflare ahora se guarda correctamente en sanitize_settings()
- Agregada funcionalidad de test por pedido específico en la configuración

## [1.2.0] - 2026-04-08

### Added
- Campo de email de Cloudflare para autenticación con Global API Key
- Soporte dual para autenticación: API Token (recomendado) o Global API Key + Email
- Vista detallada del estado de Redsys para pedidos individuales

### Fixed
- Autenticación de Cloudflare API ahora soporta ambos métodos (Token y Key+Email)

## [1.0.3] - 2026-04-08

### Agregado
- Campo de email de Cloudflare para autenticación con Global API Key
- Soporte dual para autenticación: API Token (recomendado) o Global API Key + Email
- Funcionalidad de test por pedido específico en la configuración
- Vista detallada del estado de Redsys para pedidos individuales

### Corregido
- Campo de email de Cloudflare ahora se guarda correctamente
- Autenticación de Cloudflare API ahora soporta ambos métodos (Token y Key+Email)
- JavaScript actualizado para incluir campos de email en formularios

## [1.0.1] - 2025-01-XX

### Cambiado
- Eliminados emojis de toda la interfaz de usuario y documentacion
- Interfaz mas limpia y profesional

## [1.0.0] - 2025-01-XX

### Agregado
- Sistema de deteccion de pedidos fantasma (pagados en Redsys pero cancelados en WooCommerce)
- Integracion con Cloudflare API v4 para configurar reglas WAF automaticamente
- Integracion con LiteSpeed Cache para exclusiones de cache
- Panel de administracion en WooCommerce
- Sistema de logs asincrono
- Procesamiento masivo de pedidos fantasma
- Notificaciones por email al detectar pedidos fantasma
- Sistema de actualizaciones automaticas desde GitHub
- Dos modos de operacion: Vigilante (solo detectar) y Automatico (detectar y corregir)

### Seguridad
- Validacion de nonces en todas las acciones AJAX
- Sanitizacion de entradas de usuario
- Capacidades de usuario requeridas para acciones administrativas

### Documentacion
- README completo con instrucciones de instalacion
- Guia de inicio rapido
- Documentacion tecnica
## [1.2.1] - 2026-04-08

### Corregido
- Campo de email de Cloudflare se guarda correctamente en sanitize_settings

