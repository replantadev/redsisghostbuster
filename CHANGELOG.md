# Changelog

Todos los cambios notables en este proyecto se documentan en este archivo.

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
