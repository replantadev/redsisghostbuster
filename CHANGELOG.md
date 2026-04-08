# Changelog

Todos los cambios notables en este proyecto se documentan en este archivo.

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
