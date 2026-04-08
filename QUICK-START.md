# Guia de Inicio Rapido

## Instalacion

1. **Subir el plugin**
   - Descarga el archivo ZIP desde GitHub Releases
   - Ve a WordPress Admin > Plugins > Anadir nuevo > Subir plugin
   - Selecciona el archivo ZIP y haz clic en "Instalar ahora"

2. **Activar**
   - Activa el plugin desde la lista de plugins

3. **Configurar**
   - Ve a WooCommerce > Pedidos Fantasma

## Configuracion de Cloudflare

### Metodo 1: API Token (Recomendado)

1. **Obtener credenciales**
   - Inicia sesion en Cloudflare Dashboard
   - Ve a tu dominio > Overview > API
   - Crea un token con permisos de "Zone.Firewall Services"
   - Copia el Zone ID

2. **Configurar en el plugin**
   - Ve a Configuracion
   - Ingresa el API Token (campo 1)
   - Deja el Email vacio
   - Ingresa el Zone ID
   - Haz clic en "Aplicar Reglas"

### Metodo 2: Global API Key + Email

1. **Obtener credenciales**
   - Inicia sesion en Cloudflare Dashboard
   - Ve a Perfil > API Tokens > Global API Key
   - Copia la Global API Key
   - Anota tu email de Cloudflare
   - Copia el Zone ID de tu dominio

2. **Configurar en el plugin**
   - Ve a Configuracion
   - Ingresa la Global API Key (campo 1)
   - Ingresa tu email de Cloudflare (campo 2)
   - Ingresa el Zone ID
   - Haz clic en "Aplicar Reglas"

## Test de Pedido Especifico

1. **Verificar un pedido individual**
   - Ve a Configuracion > Test de Pedido Especifico
   - Ingresa el ID del pedido (ej: 47632)
   - Haz clic en "Probar Pedido"
   - Revisa el resultado detallado que muestra:
     - Estado actual del pedido
     - Si es un pedido fantasma
     - Codigo de autorizacion de Redsys
     - Datos de transaccion

## Uso Basico

1. **Dashboard**
   - Muestra estadisticas de pedidos fantasma detectados
   - Estado de configuracion de Cloudflare y LiteSpeed

2. **Pedidos Fantasma**
   - Lista de pedidos pagados en Redsys pero cancelados
   - Acciones individuales o masivas para corregir

3. **Modos de operacion**
   - **Vigilante**: Solo detecta, no corrige automaticamente
   - **Automatico**: Detecta y corrige el estado de los pedidos

## Verificacion

Despues de configurar, verifica que:

- El estado de Cloudflare aparece como "Configurado" en el Dashboard
- Las reglas WAF estan activas en tu panel de Cloudflare
- Los nuevos pedidos de Redsys se procesan correctamente
