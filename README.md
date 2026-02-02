# Redsys Ghost Buster

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-Required-purple.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPLv2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![GitHub release](https://img.shields.io/github/v/release/replantadev/redsisghostbuster)](https://github.com/replantadev/redsisghostbuster/releases)

**Plugin profesional para detectar y corregir pedidos fantasma en WooCommerce + Redsys**

Los "pedidos fantasma" son aquellos que fueron **pagados y autorizados en Redsys** pero aparecen como **cancelados en WooCommerce**. Esto ocurre cuando las notificaciones IPN de Redsys son bloqueadas por Cloudflare u otros sistemas de seguridad.

---

## Caracteristicas

- **Deteccion inteligente** de pedidos fantasma
- **Integracion automatica con Cloudflare** - Crea reglas WAF con un clic
- **Integracion con LiteSpeed Cache** - Configura exclusiones automaticamente
- **Dashboard profesional** con estadisticas en tiempo real
- **Actualizaciones automaticas** desde este repositorio GitHub
- **Logging asincrono** que no ralentiza tu sitio
- **Procesamiento en lote** de multiples pedidos
- **Seguridad robusta** con validacion de nonce y permisos

---

## Instalacion

### Opcion 1: Descarga directa (Recomendado)

1. Ve a [Releases](https://github.com/replantadev/redsisghostbuster/releases)
2. Descarga el archivo `.zip` de la ultima version
3. En WordPress, ve a **Plugins > Anadir nuevo > Subir plugin**
4. Selecciona el archivo ZIP y haz clic en **Instalar ahora**
5. **Activa** el plugin

### Opcion 2: Clonar repositorio

```bash
cd wp-content/plugins/
git clone https://github.com/replantadev/redsisghostbuster.git
```

---

## Configuracion Rapida (3 minutos)

### 1. Obtener credenciales de Cloudflare

**API Key:**
1. Ve a https://dash.cloudflare.com
2. Perfil > **API Tokens** > **Global API Key** > Ver
3. Copia la clave

**Zone ID:**
1. Dashboard de tu dominio > **Resumen de dominio**
2. Esquina inferior derecha > **Zone ID**
3. Copialo

### 2. Configurar el plugin

1. Ve a **WooCommerce > Pedidos Fantasma**
2. Pestana **Configuracion**
3. Pega **API Key** y **Zone ID**
4. Clic en **Probar Conexion**
5. Clic en **Aplicar Reglas de Cloudflare**
6. **Guardar Configuracion**

### 3. LiteSpeed Cache (opcional)

Si tienes LiteSpeed Cache:
- Clic en **Aplicar Reglas de LiteSpeed Cache**

Listo! El plugin esta funcionando.

---

## Como funciona?

### El problema

```
Cliente paga en Redsys > Redsys envia IPN > Cloudflare BLOQUEA > WooCommerce no recibe > Pedido CANCELADO
```

### La solucion

```
Cliente paga en Redsys > Redsys envia IPN > Cloudflare PERMITE (regla WAF) > WooCommerce recibe > Pedido COMPLETADO
```

### Regla de Cloudflare creada

```
Expression: (http.request.uri.query contains "wc-api=WC_redsys") or (ip.geoip.asnum eq 31627)
Action: Skip > Browser Integrity Check
```

Esto permite que Redsys (ASN 31627) envie notificaciones sin ser bloqueado.

---

## Actualizaciones Automaticas

El plugin **detecta automaticamente nuevas versiones** desde este repositorio GitHub.

- Verifica releases/tags cada 12 horas
- Notifica en el dashboard de WordPress cuando hay actualizacion
- Un clic para actualizar (como cualquier plugin de WordPress)

### Forzar verificacion

```php
// En tu tema o plugin personalizado
Replanta_Ghost_Orders_GitHub_Updater::force_check();
```

---

## Modos de Operacion

### Modo Vigilante (Recomendado)
- Detecta pedidos fantasma
- NO los modifica automaticamente
- Tu decides que hacer manualmente

### Modo Automatico
- Detecta pedidos fantasma
- Cambia estado a "Procesando" automaticamente
- Envia notificacion al cliente

---

## Estructura del Plugin

```
redsisghostbuster/
├── redsys-ghost-buster.php          (Principal)
├── includes/
│   ├── class-settings.php              (Configuracion)
│   ├── class-async-logger.php          (Logger no-blocking)
│   ├── class-cloudflare-api.php        (API Cloudflare)
│   ├── class-lswc-config.php           (LiteSpeed Cache)
│   ├── class-detector.php              (Deteccion)
│   ├── class-admin-page.php            (Interfaz admin)
│   └── class-github-updater.php        (Auto-updates)
├── templates/
│   └── settings-form.php
├── assets/
│   ├── css/admin-style.css
│   └── js/admin-script.js
└── uninstall.php
```

---

## Hooks y Filtros

### Actions

```php
// Cuando se detecta un pedido fantasma
add_action('replanta_god_ghost_detected', function($order_id) {
    // Tu codigo aqui
});

// Cuando se procesa un pedido fantasma
add_action('replanta_god_ghost_processed', function($order_id, $new_status) {
    // Tu codigo aqui
}, 10, 2);
```

### Filters

```php
// Excluir estados de la deteccion
add_filter('replanta_god_excluded_statuses', function($statuses) {
    $statuses[] = 'draft';
    return $statuses;
});

// Anadir campos al log
add_filter('replanta_god_log_extra_fields', function($extra) {
    $extra['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
    return $extra;
});
```

---

## Solucion de Problemas

### "Error de conexion con Cloudflare"
Verifica API Key y Zone ID (copialos de nuevo)

### "No se detectan pedidos fantasma"
Ve a Configuracion y asegurate de que "Deteccion activa" este habilitado

### "LiteSpeed Cache no aparece"
LiteSpeed Cache no esta instalado (es opcional)

### "El plugin no muestra estilos"
Limpia la cache de tu CDN o LiteSpeed Cache

---

## Requisitos

| Requisito | Version |
|-----------|---------|
| WordPress | 5.0+ |
| PHP | 7.4+ |
| WooCommerce | 4.0+ |
| MySQL | 5.6+ |

---

## Contribuir

Las contribuciones son bienvenidas!

1. Fork el repositorio
2. Crea una rama (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -am 'Anadir nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

---

## Changelog

### v1.0.0 (2024)
- Primera version estable
- Deteccion de pedidos fantasma
- Integracion Cloudflare + LiteSpeed Cache
- Dashboard profesional
- Sistema de actualizaciones desde GitHub
- Logging asincrono

Ver [CHANGELOG.md](CHANGELOG.md) para historial completo.

---

## Licencia

Este plugin es software libre bajo licencia [GPL v2](LICENSE.txt) o posterior.

---

## Autores

- **Replanta** - [replanta.es](https://replanta.es)

---

## Apoya el proyecto

Si este plugin te ha sido util:

- Dale una estrella al repositorio
- Reporta bugs o sugiere mejoras
- Compartelo con otros desarrolladores

---

<p align="center">
  <strong>Hecho por <a href="https://replanta.es">Replanta</a></strong><br>
  <em>Soluciones inteligentes para WooCommerce + Redsys</em>
</p>
