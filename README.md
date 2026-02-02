# 👻 Redsys Ghost Buster

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-Required-purple.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPLv2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![GitHub release](https://img.shields.io/github/v/release/replantadev/redsisghostbuster)](https://github.com/replantadev/redsisghostbuster/releases)

**Plugin profesional para detectar y corregir pedidos fantasma en WooCommerce + Redsys**

Los "pedidos fantasma" son aquellos que fueron **pagados y autorizados en Redsys** pero aparecen como **cancelados en WooCommerce**. Esto ocurre cuando las notificaciones IPN de Redsys son bloqueadas por Cloudflare u otros sistemas de seguridad.

![Dashboard Preview](https://via.placeholder.com/800x400?text=Redsys+Ghost+Buster+Dashboard)

---

## ✨ Características

- 🔍 **Detección inteligente** de pedidos fantasma
- ☁️ **Integración automática con Cloudflare** - Crea reglas WAF con un clic
- ⚡ **Integración con LiteSpeed Cache** - Configura exclusiones automáticamente
- 📊 **Dashboard profesional** con estadísticas en tiempo real
- 🔄 **Actualizaciones automáticas** desde este repositorio GitHub
- 📝 **Logging asíncrono** que no ralentiza tu sitio
- 🎯 **Procesamiento en lote** de múltiples pedidos
- 🔐 **Seguridad robusta** con validación de nonce y permisos

---

## 🚀 Instalación

### Opción 1: Descarga directa (Recomendado)

1. Ve a [Releases](https://github.com/replantadev/redsisghostbuster/releases)
2. Descarga el archivo `.zip` de la última versión
3. En WordPress, ve a **Plugins → Añadir nuevo → Subir plugin**
4. Selecciona el archivo ZIP y haz clic en **Instalar ahora**
5. **Activa** el plugin

### Opción 2: Clonar repositorio

```bash
cd wp-content/plugins/
git clone https://github.com/replantadev/redsisghostbuster.git
```

### Opción 3: Composer (próximamente)

```bash
composer require replantadev/redsisghostbuster
```

---

## ⚙️ Configuración Rápida (3 minutos)

### 1️⃣ Obtener credenciales de Cloudflare

**API Key:**
1. Ve a https://dash.cloudflare.com
2. Perfil → **API Tokens** → **Global API Key** → Ver
3. Copia la clave

**Zone ID:**
1. Dashboard de tu dominio → **Resumen de dominio**
2. Esquina inferior derecha → **Zone ID**
3. Cópialo

### 2️⃣ Configurar el plugin

1. Ve a **WooCommerce → 👻 Pedidos Fantasma**
2. Pestaña **Configuración**
3. Pega **API Key** y **Zone ID**
4. Clic en **🧪 Probar Conexión**
5. Clic en **✅ Aplicar Reglas de Cloudflare**
6. **💾 Guardar Configuración**

### 3️⃣ LiteSpeed Cache (opcional)

Si tienes LiteSpeed Cache:
- Clic en **✅ Aplicar Reglas de LiteSpeed Cache**

¡Listo! El plugin está funcionando.

---

## 📖 ¿Cómo funciona?

### El problema

```
Cliente paga en Redsys → Redsys envía IPN → Cloudflare BLOQUEA → WooCommerce no recibe → Pedido CANCELADO
```

### La solución

```
Cliente paga en Redsys → Redsys envía IPN → Cloudflare PERMITE (regla WAF) → WooCommerce recibe → Pedido COMPLETADO ✅
```

### Regla de Cloudflare creada

```
Expression: (http.request.uri.query contains "wc-api=WC_redsys") or (ip.geoip.asnum eq 31627)
Action: Skip → Browser Integrity Check
```

Esto permite que Redsys (ASN 31627) envíe notificaciones sin ser bloqueado.

---

## 🔄 Actualizaciones Automáticas

El plugin **detecta automáticamente nuevas versiones** desde este repositorio GitHub.

- Verifica releases/tags cada 12 horas
- Notifica en el dashboard de WordPress cuando hay actualización
- Un clic para actualizar (como cualquier plugin de WordPress)

### Forzar verificación

```php
// En tu tema o plugin personalizado
Replanta_Ghost_Orders_GitHub_Updater::force_check();
```

---

## 🎯 Modos de Operación

### Modo Vigilante (Recomendado)
- Detecta pedidos fantasma
- NO los modifica automáticamente
- Tú decides qué hacer manualmente

### Modo Automático
- Detecta pedidos fantasma
- Cambia estado a "Procesando" automáticamente
- Envía notificación al cliente

---

## 📊 Estructura del Plugin

```
redsisghostbuster/
├── 📄 redsys-ghost-buster.php          (Principal)
├── 📂 includes/
│   ├── class-settings.php              (Configuración)
│   ├── class-async-logger.php          (Logger no-blocking)
│   ├── class-cloudflare-api.php        (API Cloudflare)
│   ├── class-lswc-config.php           (LiteSpeed Cache)
│   ├── class-detector.php              (Detección)
│   ├── class-admin-page.php            (Interfaz admin)
│   └── class-github-updater.php        (Auto-updates)
├── 📂 templates/
│   └── settings-form.php
├── 📂 assets/
│   ├── css/admin-style.css
│   └── js/admin-script.js
└── 📄 uninstall.php
```

---

## 🔧 Hooks y Filtros

### Actions

```php
// Cuando se detecta un pedido fantasma
add_action('replanta_god_ghost_detected', function($order_id) {
    // Tu código aquí
});

// Cuando se procesa un pedido fantasma
add_action('replanta_god_ghost_processed', function($order_id, $new_status) {
    // Tu código aquí
}, 10, 2);
```

### Filters

```php
// Excluir estados de la detección
add_filter('replanta_god_excluded_statuses', function($statuses) {
    $statuses[] = 'draft';
    return $statuses;
});

// Añadir campos al log
add_filter('replanta_god_log_extra_fields', function($extra) {
    $extra['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
    return $extra;
});
```

---

## 🆘 Solución de Problemas

### "Error de conexión con Cloudflare"
→ Verifica API Key y Zone ID (cópialos de nuevo)

### "No se detectan pedidos fantasma"
→ Ve a Configuración y asegúrate de que "Detección activa" esté habilitado

### "LiteSpeed Cache no aparece"
→ LiteSpeed Cache no está instalado (es opcional)

### "El plugin no muestra estilos"
→ Limpia la caché de tu CDN o LiteSpeed Cache

---

## 📋 Requisitos

| Requisito | Versión |
|-----------|---------|
| WordPress | 5.0+ |
| PHP | 7.4+ |
| WooCommerce | 4.0+ |
| MySQL | 5.6+ |

---

## 🤝 Contribuir

¡Las contribuciones son bienvenidas!

1. Fork el repositorio
2. Crea una rama (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -am 'Añadir nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

---

## 📝 Changelog

### v1.0.0 (2024)
- ✅ Primera versión estable
- ✅ Detección de pedidos fantasma
- ✅ Integración Cloudflare + LiteSpeed Cache
- ✅ Dashboard profesional
- ✅ Sistema de actualizaciones desde GitHub
- ✅ Logging asíncrono

Ver [CHANGELOG.md](CHANGELOG.md) para historial completo.

---

## 📄 Licencia

Este plugin es software libre bajo licencia [GPL v2](LICENSE.txt) o posterior.

---

## 👥 Autores

- **Replanta** - [replanta.es](https://replanta.es)

---

## ⭐ Apoya el proyecto

Si este plugin te ha sido útil:

- ⭐ Dale una estrella al repositorio
- 🐛 Reporta bugs o sugiere mejoras
- 📢 Compártelo con otros desarrolladores

---

<p align="center">
  <strong>Hecho con ❤️ por <a href="https://replanta.es">Replanta</a></strong><br>
  <em>Soluciones inteligentes para WooCommerce + Redsys</em>
</p>
