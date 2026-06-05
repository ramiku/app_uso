# Web Push + VAPID — Guía de instalación

## 1. Crear tabla MySQL

Ejecutar en phpMyAdmin o cliente MySQL:

```sql
CREATE TABLE IF NOT EXISTS `web_push_subscriptions` (
    `id`            INT           NOT NULL AUTO_INCREMENT,
    `usuario`       VARCHAR(100)  NULL,
    `endpoint`      TEXT          NOT NULL,
    `endpoint_hash` CHAR(64)      NOT NULL,
    `p256dh`        VARCHAR(255)  NOT NULL,
    `auth`          VARCHAR(255)  NOT NULL,
    `user_agent`    TEXT          NULL,
    `plataforma`    VARCHAR(50)   NULL,
    `fecha_alta`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_ultima`  DATETIME      NULL,
    `activo`        TINYINT(1)    NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_endpoint_hash` (`endpoint_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 2. Instalar minishlink/web-push

### Opción A — Composer disponible en el servidor (recomendado)

```bash
composer require minishlink/web-push
```

### Opción B — Sin Composer en producción (SiteGround hosting compartido)

1. En local instala con Composer:
   ```bash
   composer require minishlink/web-push
   ```
2. Comprime la carpeta `vendor/` generada.
3. Súbela vía FTP/SFTP a la raíz del proyecto (misma carpeta que `index.php`).

La estructura debe quedar:
```
/vendor/autoload.php
/vendor/minishlink/web-push/...
```

---

## 3. Generar claves VAPID

Ejecutar **una sola vez** desde terminal en el servidor o en local:

```bash
php private/generate_vapid_keys.php
```

O desde PHP interactivo:
```php
require 'vendor/autoload.php';
$keys = \Minishlink\WebPush\VAPID::createVapidKeys();
echo 'PUBLIC:  ' . $keys['publicKey'] . PHP_EOL;
echo 'PRIVATE: ' . $keys['privateKey'] . PHP_EOL;
```

Copiar los valores y añadirlos al archivo de configuración del servidor
como **variables de entorno** (nunca en código versionado):

```
WEB_PUSH_VAPID_PUBLIC_KEY=BExamplePublicKeyHere...
WEB_PUSH_VAPID_PRIVATE_KEY=ExamplePrivateKeyHere...
WEB_PUSH_VAPID_SUBJECT=mailto:admin@uso-oest.es
```

En SiteGround puedes añadir variables de entorno desde:
**cPanel → Administrador de archivos → .env** o en el panel de
Environment Variables si el plan lo soporta.

Alternativamente edita `/private/vapid_keys.php` (fuera de public_html).

---

## 4. Wrapper Android — identificación

Para que la detección sea fiable, añadir **uno** de estos mecanismos en el wrapper Java/Kotlin:

### A) User-Agent personalizado (más sencillo)

```java
WebSettings settings = webView.getSettings();
String ua = settings.getUserAgentString();
settings.setUserAgentString(ua + " USOOEST-WRAPPER");
```

### B) Inyección de objeto JS

```java
webView.addJavascriptInterface(new Object() {}, "USOOESTAndroid");
```

O ejecutar tras cargar la página:
```java
webView.evaluateJavascript("window.USOOESTAndroid = true;", null);
```

Con cualquiera de los dos métodos, `esWrapperAndroid()` en el JS cliente
devolverá `true` y el flujo Web Push quedará completamente desactivado.

---

## 5. Archivos creados

| Archivo | Descripción |
|---|---|
| `public/push-sw.js` | Service Worker Web Push |
| `public/assets/js/webpush.js` | Lógica cliente JS |
| `app/api/guardar_web_push_subscription.php` | Endpoint guardar suscripción |
| `app/api/enviar_web_push.php` | Endpoint envío notificaciones |
| `app/lib/WebPushSender.php` | Clase PHP envío VAPID |
| `private/generate_vapid_keys.php` | Script generación de claves |
| `docs/CALENDAR_DDL.sql` ya existente; ver nro. 1 arriba para el nuevo DDL |

---

## 6. Prueba rápida

1. Abrir `https://uso-oest.es/` en Chrome escritorio.
2. Aparece el banner "Activar notificaciones" en la parte inferior.
3. Pulsar → se pide permiso → conceder.
4. Ir a **Panel admin → Notificaciones → Enviar Web Push**.
5. El navegador recibe la notificación de prueba.
