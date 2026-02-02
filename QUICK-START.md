# GUÍA DE INSTALACIÓN RÁPIDA

## 1️⃣ Descargar e Instalar

```bash
# Copiar el plugin a tu WordPress
cp -r replanta-ghost-orders-detector /ruta/al/sitio/wp-content/plugins/

# O descargar directamente si está en ZIP
unzip replanta-ghost-orders-detector.zip -d wp-content/plugins/
```

## 2️⃣ Activar en WordPress

1. Ve a **WordPress Admin Dashboard**
2. Navega a **Plugins**
3. Busca **"Replanta Ghost Orders Detector"**
4. Haz clic en **Activar**

### Verificar que se activó
- Deberías ver un nuevo menú: **WooCommerce → 👻 Pedidos Fantasma**
- No debería haber errores en el admin

## 3️⃣ Configuración de Cloudflare (2 minutos)

### Obtener credenciales:

**API Key:**
1. Ve a https://dash.cloudflare.com
2. Clica en tu perfil (abajo a la izquierda)
3. **API Tokens** → **Global API Key** → **Ver**
4. Introduce tu contraseña
5. Copia la clave

**Zone ID:**
1. Ve al dashboard de tu dominio en Cloudflare
2. Busca **Resumen de dominio**
3. En la esquina inferior derecha verás **Zone ID**
4. Cópialo

### En el plugin:

1. Ve a **WooCommerce → 👻 Pedidos Fantasma → Configuración**
2. Pega **API Key**
3. Pega **Zone ID**
4. Clica **🧪 Probar Conexión** (debe decir ✅)
5. Clica **✅ Aplicar Reglas de Cloudflare**
6. Clica **💾 Guardar Configuración**

## 4️⃣ LiteSpeed Cache (opcional, 1 minuto)

Si tienes LiteSpeed Cache instalado:

1. En la misma página de **Configuración**
2. Busca la sección **⚡ Configuración de LiteSpeed Cache**
3. Clica **✅ Aplicar Reglas de LiteSpeed Cache**

*Si no ves esta sección, significa que LiteSpeed Cache no está activo.*

## 5️⃣ Seleccionar Modo (1 minuto)

En **Configuración**, selecciona uno:

- **🔍 Modo Vigilante** (Recomendado al principio)
  - El plugin DETECTA problemas
  - TÚ DECIDES qué hacer
  
- **⚙️ Modo Automático**
  - El plugin DETECTA Y CORRIGE automáticamente
  - Cambia estado a "Procesando"

## 6️⃣ Verificar que Funciona

### En el Dashboard:

1. Ve a **WooCommerce → 👻 Pedidos Fantasma**
2. Deberías ver:
   - ☁️ Cloudflare: ✅ Configurado
   - ⚡ LiteSpeed Cache: ✅ Activo (o ℹ️ No instalado)
   - 👻 Pedidos Fantasma: (número de detectados)

### Probar detección:

1. Ve a la pestaña **Pedidos Fantasma**
2. Selecciona "Últimos 30 días"
3. Clica **🔄 Actualizar**
4. Deberías ver pedidos que fueron pagados pero cancelados

## ✅ ¡Listo!

El plugin está funcionando. Ahora:

- 📊 Ve a **Dashboard** para ver estadísticas
- 👻 Ve a **Pedidos Fantasma** para ver y procesar
- 📋 Ve a **Registros** para ver el historial de eventos

---

## 🆘 Solución de Problemas Rápida

### "❌ Error de conexión con Cloudflare"
→ Verifica que el API Key y Zone ID sean correctos (cópiálos de nuevo)

### "No veo la pestaña de WooCommerce"
→ Asegúrate de que **WooCommerce está activo**

### "No se detectan pedidos fantasma"
→ Ve a **Configuración** y asegúrate de que **Detección activa: Sí**

### "LiteSpeed Cache no aparece"
→ LiteSpeed Cache no está instalado (es opcional)

### "El plugin se ve roto/sin estilos"
→ Haz clic en **Limpiar caché** en LiteSpeed Cache o tu CDN

---

## 🔗 Links útiles

- 📖 [Documentación Completa](README.md)
- ⚙️ [Configuración Personalizada](replanta-god-config.example.php)
- 🆘 [Preguntas Frecuentes](README.md#preguntas-frecuentes)

---

## ❓ ¿Necesitas ayuda?

Contacta a: **soporte@replanta.es**

*Replanta Ghost Orders Detector v1.0.0*
