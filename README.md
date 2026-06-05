# USO OEST — Aplicación Web y Android

Aplicación multiplataforma desarrollada para la **sección sindical del sindicato USO** dentro de la empresa **OEST (Operadora de Estaciones de Servicio en el Territorio)**, con el objetivo de centralizar la comunicación interna, el acceso a documentación laboral, calendarios de turno y un asistente virtual de orientación sindical, tanto desde la web como desde dispositivos Android.

---

## Índice

1. [Descripción general](#1-descripción-general)
2. [Stack tecnológico](#2-stack-tecnológico)
3. [Arquitectura del sistema](#3-arquitectura-del-sistema)
4. [Estructura del proyecto](#4-estructura-del-proyecto)
5. [Funcionalidades del sitio público](#5-funcionalidades-del-sitio-público)
6. [Panel de administración](#6-panel-de-administración)
7. [Aplicación Android (Wrapper)](#7-aplicación-android-wrapper)
8. [Notificaciones Push](#8-notificaciones-push)
9. [Base de datos](#9-base-de-datos)
10. [Seguridad](#10-seguridad)
11. [Variables de entorno y configuración](#11-variables-de-entorno-y-configuración)
12. [Puesta en marcha local](#12-puesta-en-marcha-local)
13. [Despliegue en producción](#13-despliegue-en-producción)
14. [Documentación técnica adicional](#14-documentación-técnica-adicional)

---

## 1. Descripción general

**USO OEST** es una aplicación web y móvil diseñada para dar servicio a los trabajadores afiliados a la sección sindical de **USO (Unión Sindical Obrera)** en la empresa **OEST**. Su objetivo principal es ofrecer un canal digital centralizado que permita:

- Consultar noticias y comunicados sindicales actualizados en tiempo real.
- Acceder a documentación laboral (convenios, circulares, calendarios laborales).
- Visualizar los calendarios de turnos de trabajo personalizados por rotación.
- Resolver dudas y consultas mediante un asistente virtual inteligente.
- Recibir notificaciones push inmediatas ante avisos o novedades importantes.
- Consultar un directorio de contacto organizado por áreas y guardias.

La aplicación se distribuye en **dos canales simultáneos**:

- **Web**: accesible desde cualquier navegador moderno en `https://uso-oest.es`.
- **Android**: publicada como aplicación nativa en formato APK/Play Store, implementada como un **wrapper WebView** que encapsula el sitio web y añade integraciones nativas como notificaciones FCM y descarga de archivos.

Esta dualidad web/app permite mantener una única base de código para el frontend, minimizando el mantenimiento, mientras se ofrece una experiencia de usuario cercana a la de una app nativa en Android.

---

## 2. Stack tecnológico

### Backend

| Tecnología | Versión mínima | Uso |
|---|---|---|
| **PHP** | 8.0 | Lógica de servidor, controladores, API endpoints |
| **MySQL / MariaDB** | 5.7 / 10.4 | Base de datos relacional |
| **Apache** | 2.4 | Servidor web con mod_rewrite para URLs limpias |
| **Composer** | 2.x | Gestión de dependencias PHP |

**Extensiones PHP requeridas:** `PDO`, `PDO_MySQL`, `mbstring`, `fileinfo`, `json`, `openssl`

**Librerías PHP (via Composer):**

| Paquete | Uso |
|---|---|
| `minishlink/web-push` | Envío de notificaciones Web Push (VAPID) a navegadores |
| `guzzlehttp/guzzle` | Cliente HTTP para llamadas a APIs externas (OpenAI, FCM) |
| `web-token/jwt-library` | Generación y validación de tokens JWT para VAPID |

### Frontend

| Tecnología | Uso |
|---|---|
| **HTML5** | Estructura semántica de vistas |
| **CSS3** | Estilos propios sin frameworks externos (paleta USO rojo/negro) |
| **JavaScript (ES5+)** | Lógica del cliente, sin frameworks ni bundlers |
| **jQuery (slim)** | Utilidades DOM en el panel de administración |

El frontend está desarrollado **sin frameworks como React, Vue o Angular**, siguiendo un enfoque de PHP renderizado en servidor con vistas en plantillas PHP (arquitectura MVC ligera).

### Android

| Tecnología | Uso |
|---|---|
| **Java** | Lenguaje principal de la app Android |
| **Android SDK** | APIs nativas (WebView, DownloadManager, NotificationManager) |
| **Firebase Cloud Messaging (FCM)** | Notificaciones push nativas en Android |
| **WebView** | Renderizado del sitio web dentro de la app |

### Servicios externos

| Servicio | Uso |
|---|---|
| **OpenAI API (GPT-4o)** | Motor de IA del asistente virtual |
| **Firebase Cloud Messaging** | Push notifications en la app Android |
| **SMTP / PHP mail()** | Envío de correos (recuperación de contraseña, contacto) |

---

## 3. Arquitectura del sistema

La aplicación sigue un patrón **MVC ligero** implementado manualmente en PHP, sin uso de frameworks como Laravel o Symfony:

```
Petición HTTP
      │
      ▼
 .htaccess (mod_rewrite)
      │
      ▼
 index.php
      │
      ▼
 parse_clean_request()    ←── URLs amigables (/noticias/42, /calendarios?year=2026)
      │
      ▼
 get_route($page)         ←── routes.php (tabla de rutas públicas)
      │
      ▼
 Controller::action()     ←── HomeController / NewsController
      │
      ▼
 $data (array)
      │
      ▼
 views/layouts/base.php   ←── Plantilla base: head + header + content + footer
      │
      ▼
 views/pages/{page}.php   ←── Vista específica de la página
      │
      ▼
 Respuesta HTML al cliente
```

**Panel de administración** (`uso_admin.php`) sigue el mismo principio pero con un ciclo de vida propio: todas las acciones POST pasan por `app/admin/actions.php` y las vistas se renderizan desde `app/admin/views/`.

**API interna** (`/api/*.php`): endpoints sin estado que devuelven JSON. Accesibles desde el frontend JS y desde el wrapper Android.

---

## 4. Estructura del proyecto

```text
webuso/
├── index.php                        ← Punto de entrada principal (frontend público)
├── uso_admin.php                    ← Punto de entrada del panel de administración
├── composer.json                    ← Dependencias PHP
├── .htaccess                        ← Reglas mod_rewrite (URLs amigables)
├── .gitignore
│
├── app/                             ← Núcleo de la aplicación
│   ├── api/                         ← Endpoints JSON (API interna)
│   │   ├── assistant.php            ← Asistente virtual (comandos + IA)
│   │   ├── contact.php              ← Formulario de contacto
│   │   ├── enviar_web_push.php      ← Envío masivo de notificaciones Web Push
│   │   ├── guardar_token_push.php   ← Registro de tokens FCM (Android)
│   │   ├── guardar_web_push_subscription.php ← Registro de suscripciones VAPID
│   │   └── news_share.php           ← Compartición social de noticias
│   │
│   ├── config/
│   │   ├── config.php               ← Constantes globales, DB, VAPID, Firebase, mailer
│   │   └── routes.php               ← Tabla de rutas públicas
│   │
│   ├── controllers/
│   │   ├── HomeController.php       ← Inicio, contacto, documentación, directorio, calendarios
│   │   └── NewsController.php       ← Listado, búsqueda y detalle de noticias
│   │
│   ├── data/
│   │   └── contacts.php             ← Directorio de contacto centralizado (teléfonos, emails)
│   │
│   ├── helpers/
│   │   ├── functions.php            ← Helpers globales: URL builder, mailer, DB queries, OpenAI
│   │   ├── FcmSender.php            ← Envío de notificaciones FCM via HTTP v1 (OAuth2)
│   │   └── push_helpers.php         ← Orquestación de push (FCM + Web Push unificados)
│   │
│   ├── lib/
│   │   └── WebPushSender.php        ← Envío de Web Push VAPID via minishlink/web-push
│   │
│   ├── admin/                       ← Módulo del panel administrativo
│   │   ├── actions.php              ← Manejadores de todas las acciones POST del panel
│   │   ├── functions.php            ← Helpers exclusivos del admin (DB, CSRF, sesión, URLs)
│   │   └── views/                   ← Vistas del panel
│   │       ├── layout.php           ← Plantilla base del panel
│   │       ├── login.php            ← Formulario de autenticación y recuperación de contraseña
│   │       ├── home.php             ← Dashboard con tarjetas de navegación
│   │       ├── news.php             ← Gestión de noticias (listado, crear, editar)
│   │       ├── documents.php        ← Gestión de documentos
│   │       ├── images.php           ← Galería y gestión de imágenes
│   │       ├── calendar.php         ← Editor de calendarios de turnos
│   │       ├── push.php             ← Envío de notificaciones push personalizadas
│   │       └── user.php             ← Gestión de usuarios e invitaciones
│   │
│   └── views/                       ← Vistas del sitio público
│       ├── layouts/
│       │   └── base.php             ← Plantilla HTML base (head + header + content + footer)
│       ├── partials/
│       │   ├── head.php             ← <head>: meta, CSS, scripts inline (VAPID key, base URL)
│       │   ├── header.php           ← Cabecera con navegación principal y menú móvil
│       │   └── footer.php           ← Pie de página
│       ├── components/
│       │   ├── card_noticia.php     ← Tarjeta de noticia reutilizable
│       │   ├── hero_slider.php      ← Slider de portada
│       │   └── pagination.php       ← Paginación server-side
│       └── pages/                   ← Una vista por ruta pública
│           ├── home.php             ← Portada
│           ├── noticias.php         ← Listado, búsqueda y detalle de noticias
│           ├── documentacion.php    ← Documentos y calendarios descargables
│           ├── calendarios.php      ← Calendarios de turno dinámicos
│           ├── asistente.php        ← Interfaz del asistente virtual
│           ├── directorio.php       ← Directorio de contacto
│           ├── contactanos.php      ← Formulario de contacto
│           ├── privacidad.php       ← Política de privacidad
│           └── 404.php              ← Página de error 404
│
├── public/                          ← Archivos servidos directamente
│   ├── push-sw.js                   ← Service Worker para Web Push (scope raíz)
│   └── assets/
│       ├── css/
│       │   ├── styles.css           ← Estilos principales del sitio
│       │   ├── responsive.css       ← Media queries y adaptaciones móviles
│       │   └── admin.css            ← Estilos del panel de administración
│       ├── js/
│       │   ├── main.js              ← Lógica general del sitio
│       │   ├── menu.js              ← Menú principal y navegación móvil
│       │   ├── assistant.js         ← Interfaz del asistente virtual
│       │   ├── contact.js           ← Validación y envío del formulario de contacto
│       │   ├── calendars.js         ← Navegación por meses en los calendarios
│       │   ├── webpush.js           ← Lógica Web Push VAPID (suscripción, banner)
│       │   ├── admin.js             ← Lógica del panel de administración
│       │   └── jquery.min.js        ← jQuery (solo para el panel admin)
│       ├── img/
│       │   ├── noticias/            ← Imágenes subidas para las noticias
│       │   └── ...                  ← Recursos estáticos (logo, iconos, robot, etc.)
│       └── files/
│           ├── *.pdf / *.docx       ← Documentos laborales descargables
│           └── calendarios/         ← Documentos de calendarios descargables
│
├── docs/                            ← Documentación técnica interna
│   ├── ADMIN_CONTEXT.md             ← Contexto técnico del panel de administración
│   ├── CALENDAR_DDL.sql             ← DDL de las tablas de calendarios
│   ├── PUSH_DDL.sql                 ← DDL de la tabla app_push_tokens (FCM)
│   ├── web_push_setup.md            ← Guía de instalación y configuración Web Push
│   └── android/                     ← Código de referencia de la app Android
│       ├── AndroidManifest.xml
│       ├── MainActivity.java
│       └── MyFirebaseMessagingService.java
│
├── private/                         ← Scripts de utilidad (fuera de acceso web)
│   └── generate_vapid_keys.php      ← Generador de claves VAPID (uso único por CLI)
│
└── vendor/                          ← Dependencias Composer (no se versiona)
```

---

## 5. Funcionalidades del sitio público

### 5.1 Portada

- Muestra las **6 últimas noticias** publicadas en tarjetas visuales con imagen, título y extracto.
- Slider de portada con imágenes destacadas.
- Acceso rápido a todas las secciones desde la navegación principal.
- Robot asistente flotante visible en todas las páginas (excepto en la propia página del asistente), que cambia de imagen cuando la sesión de IA está desbloqueada.

### 5.2 Noticias

- **Listado paginado** — 6 noticias por página con paginación server-side.
- **Búsqueda avanzada** — Búsqueda por texto en título y contenido con las siguientes características:
  - Insensible a mayúsculas/minúsculas.
  - Insensible a tildes y diacríticos.
  - Búsqueda por términos sueltos (no requiere frase exacta).
  - Normalización de caracteres especiales.
- **Vista de detalle** — Contenido HTML enriquecido, imágenes adjuntas, documentos descargables asociados y botón de compartición social.
- **URLs amigables** — `/noticias/42`, `/noticias/slug/titulo-noticia`, `/noticias/pagina/2`, `/noticias/buscar/termino`.

### 5.3 Documentación

- Listado dinámico de documentos desde la base de datos (tabla `uso_documents`).
- Organizado en dos bloques:
  - **Documentos generales** — convenios, circulares, comunicados.
  - **Calendarios laborales** — documentos PDF de calendarios por año.
- Descarga con nombre de archivo amigable generado a partir del `display_name` configurado en el panel.

### 5.4 Calendarios de turno

- Visualización de calendarios de turnos de trabajo **generados dinámicamente** a partir de patrones de rotación configurados en el panel.
- Selector de **año** y **rotación** (turno al que pertenece el trabajador).
- Vista mensual con indicación visual de días laborables, festivos nacionales y locales.
- Navegación por meses con botones anterior/siguiente y selector directo.
- Los datos proceden de cuatro tablas relacionales de MySQL que modelan años, festivos, rotaciones y patrones semanales.

### 5.5 Asistente Virtual USO

El asistente combina **respuestas predefinidas por comandos** con **inteligencia artificial (OpenAI GPT-4o)**:

- **Modo comandos** — Detecta palabras clave normalizadas en el mensaje y responde con:
  - Documentos descargables (convenios, nóminas, etc.).
  - Imágenes informativas.
  - Teléfonos y datos de contacto del directorio.
  - URLs de secciones del sitio.
- **Modo IA** — Si el comando no se reconoce y el modo IA está activo:
  - Acceso restringido a afiliados mediante **código de seguridad** de sesión (válido 24 horas).
  - Una vez validado, envía la consulta a **OpenAI GPT-4o** con un *prompt* y *vector store* configurados en el panel de OpenAI, orientados al ámbito sindical y laboral de USO OEST.
  - Mantiene contexto de la conversación (hasta 5 mensajes anteriores).
- **Registro de consultas** — Las consultas se registran en base de datos para análisis posterior.

### 5.6 Directorio de contacto

- Listado estructurado de teléfonos y correos por áreas:
  - Teléfonos de guardia.
  - Administración y recursos humanos.
  - Delegados sindicales.
  - Enlaces web de interés.
- Los datos están centralizados en `app/data/contacts.php`, compartido entre la vista pública y el asistente virtual.
- Acciones directas: llamar (tel:), enviar email (mailto:) y abrir WhatsApp.

### 5.7 Formulario de contacto

- Formulario con validación en frontend (JS) y backend (PHP).
- Envío de correo al buzón sindical mediante SMTP autenticado o `mail()` como fallback.
- Protección básica contra spam.

### 5.8 Política de privacidad

- Página estática con la política de privacidad de la aplicación, requerida tanto para la web como para la publicación en Google Play.

---

## 6. Panel de administración

Accesible en `/uso_admin`. Requiere autenticación y está completamente separado del sitio público.

### 6.1 Autenticación y seguridad

- Login con usuario y contraseña almacenada con `password_hash()` / `password_verify()`.
- Sesión PHP con cookie `HttpOnly`, `SameSite=Lax`.
- **Token CSRF** generado por sesión en todas las acciones POST para prevenir ataques de falsificación de solicitudes.
- Logout explícito con destrucción de sesión.

### 6.2 Recuperación de contraseña

- El usuario introduce su correo electrónico.
- Si el correo existe, se genera un token único hasheado (SHA-256) y se envía un enlace de restauración.
- El token caduca en **60 minutos** y solo puede usarse una vez (`used_at`).
- Tabla de soporte: `uso_password_resets`.

### 6.3 Gestión de usuarios

- **Cambio de contraseña** del usuario logado.
- **Invitación de nuevos usuarios** (solo disponible para el usuario administrador principal):
  - Se introduce el correo del nuevo usuario.
  - Se envía un enlace único de registro (caduca en 48 horas).
  - El destinatario define su usuario y contraseña a través del enlace.
- **Listado de usuarios** — Visualización y eliminación de cuentas (excepto la propia).
- Tabla de soporte: `uso_user_invites`.

### 6.4 Gestión de noticias

- **Crear noticia**: título, contenido enriquecido (editor de texto con negrita, cursiva, listas, enlaces), estado (borrador/publicada/archivada), fecha de creación.
- **Adjuntos por noticia**:
  - Múltiples imágenes (`.jpg`, `.png`, `.webp`, `.gif`).
  - Múltiples documentos (`.pdf`, `.doc`, `.docx`, `.xls`, `.xlsx`, etc.).
  - Los archivos se almacenan en `public/assets/files/noticias/` con nombre alfanumérico único.
  - Registro en tabla `noticias_adjuntos` con tipo, nombre original, ruta y MIME type.
- **Editar noticia**: formulario precargado con datos actuales y gestión de adjuntos existentes.
- **Eliminar noticia**: borrado del registro y de todos sus adjuntos físicos.
- **Enviar notificación push** desde la vista de edición para anunciar la noticia a todos los suscriptores.

### 6.5 Gestión de documentos

- Subida de documentos con nombre a mostrar (`display_name`) y carpeta destino.
- Dos categorías: `files` (documentos generales) y `files/calendarios` (calendarios laborales).
- Listado, apertura y eliminación (registro + archivo físico).

### 6.6 Gestión de imágenes

- Listado en galería de las imágenes almacenadas en `public/assets/img/noticias/`.
- Información: miniatura, nombre de archivo, tamaño y fecha de modificación.
- Eliminación individual.

### 6.7 Editor de calendarios de turno

- Gestión de **años** de calendario.
- Gestión de **festivos** por año (nacionales y locales) con fecha y etiqueta.
- Gestión de **rotaciones** (turnos): nombre, ciclo de semanas, patrón semanal configurable día a día (laborable / descanso).
- Vista previa en tiempo real del calendario generado para el año y rotación seleccionados.

### 6.8 Notificaciones push

- Formulario de envío de notificación personalizada: título, mensaje y URL de destino opcional.
- **Doble canal unificado** mediante `enviarNotificacionUnificada()`:
  - **FCM**: envía a todos los tokens activos en `app_push_tokens`.
  - **Web Push**: envía a todas las suscripciones activas en `web_push_subscriptions`.
- Resultado con desglose de enviados, fallidos y tokens desactivados automáticamente.

---

## 7. Aplicación Android (Wrapper)

La app Android está implementada como un **WebView Wrapper** que encapsula el sitio web `https://uso-oest.es` añadiendo capacidades nativas que un navegador móvil no puede ofrecer.

### 7.1 Características técnicas

- **WebView con JavaScript activado** y almacenamiento DOM habilitado.
- **User-Agent personalizado** con sufijo `USOOEST-WRAPPER`, que permite al frontend detectar el contexto nativo y deshabilitar Web Push (ya que se usa FCM en su lugar).
- **Interceptación de enlaces**: los enlaces externos al dominio `uso-oest.es` se abren en el navegador del sistema. Los enlaces `tel:`, `mailto:` y `whatsapp:` llaman a las apps nativas.
- **Soporte de ventanas nuevas** (`target="_blank"`) abiertas en el navegador del sistema.
- **Gestor de descargas nativo**: los archivos descargables (PDFs, documentos) se gestionan con `DownloadManager` de Android, guardándose en la carpeta `Descargas` del dispositivo.

### 7.2 Integración con Firebase Cloud Messaging (FCM)

- Al cargar cualquier página del dominio `uso-oest.es`, la app obtiene el **token FCM** del dispositivo via `FirebaseMessaging.getInstance().getToken()`.
- El token se inyecta en el WebView mediante la función JavaScript `window.recibirTokenPushAndroid(token)`, definida en `app/views/partials/head.php`.
- Esta función JS llama al endpoint `/api/guardar_token_push.php` para registrar o actualizar el token en la base de datos.
- Cuando se emite una notificación desde el panel admin, el backend envía el payload FCM a todos los tokens activos.
- `MyFirebaseMessagingService` recibe el mensaje y muestra la notificación local. Al pulsarla, abre `MainActivity` con la URL del contenido.

### 7.3 Solicitud de permisos

- En Android 13+ (`API 33`), se solicita el permiso `POST_NOTIFICATIONS` en tiempo de ejecución durante el primer arranque.

---

## 8. Notificaciones Push

El sistema implementa un **canal doble** para garantizar la cobertura en todos los dispositivos:

### 8.1 Web Push (VAPID) — Navegadores de escritorio y móviles

- Estándar **W3C Web Push** con autenticación VAPID.
- Service Worker registrado en `public/push-sw.js` con scope `/`.
- Flujo en el cliente (`webpush.js`):
  1. Detecta si es el wrapper Android → aborta (se usa FCM en su lugar).
  2. Comprueba soporte de Push API y Notification API.
  3. Muestra un banner de activación si el usuario no ha decidido aún.
  4. Al aceptar, solicita permiso y crea la suscripción con la clave pública VAPID.
  5. Envía la suscripción (endpoint, p256dh, auth) al endpoint `/api/guardar_web_push_subscription.php`.
- Envío desde el servidor mediante `WebPushSender.php` (basado en `minishlink/web-push`).

### 8.2 FCM (Firebase Cloud Messaging) — App Android

- Los tokens FCM se registran en la tabla `app_push_tokens` via `/api/guardar_token_push.php`.
- Envío desde el servidor mediante `FcmSender.php` usando la API FCM HTTP v1 con autenticación OAuth2 (Service Account de Firebase).
- Los tokens inválidos (`UNREGISTERED`, `INVALID_ARGUMENT`) se marcan automáticamente como inactivos.

### 8.3 Función unificada

`enviarNotificacionUnificada(titulo, mensaje, url, tipo)` en `push_helpers.php` orquesta ambos canales de forma transparente y devuelve un resumen con el total de dispositivos alcanzados, desglosando app Android y navegadores web.

---

## 9. Base de datos

### Tablas del sitio público

| Tabla | Descripción |
|---|---|
| `noticias` | Contenido editorial (título, texto HTML, estado, fecha, slug) |
| `noticias_adjuntos` | Imágenes y documentos vinculados a noticias |
| `uso_documents` | Documentos descargables con nombre a mostrar y carpeta |

### Tablas del panel de administración

| Tabla | Descripción |
|---|---|
| `uso_users` | Usuarios del panel admin (username, password hash, email) |
| `uso_password_resets` | Tokens de recuperación de contraseña (hasheados, caducidad) |
| `uso_user_invites` | Invitaciones de registro enviadas por correo |

### Tablas de calendarios

| Tabla | Descripción |
|---|---|
| `uso_calendar_years` | Años de calendario configurados |
| `uso_calendar_holidays` | Festivos (nacionales y locales) por año |
| `uso_calendar_rotations` | Rotaciones/turnos definidos por año |
| `uso_calendar_rotation_pattern` | Patrón semanal de cada rotación (día × semana del ciclo) |

### Tablas de notificaciones push

| Tabla | Descripción |
|---|---|
| `app_push_tokens` | Tokens FCM de dispositivos Android |
| `web_push_subscriptions` | Suscripciones Web Push VAPID de navegadores |

Los DDL completos se encuentran en:
- `docs/CALENDAR_DDL.sql`
- `docs/PUSH_DDL.sql`
- `docs/web_push_setup.md` (sección 1, tabla `web_push_subscriptions`)

---

## 10. Seguridad

| Medida | Implementación |
|---|---|
| Autenticación segura | `password_hash()` con bcrypt, `password_verify()` |
| Protección CSRF | Token de sesión en todas las acciones POST del panel |
| Sanitización de HTML | Strip y whitelist de etiquetas en el contenido del editor |
| Prevención de path traversal | Validación de rutas de eliminación contra directorios permitidos |
| Validación de tipos de archivo | Comprobación de MIME type + extensión en subidas |
| Cookies de sesión seguras | `HttpOnly`, `SameSite=Lax`, `lifetime=0` |
| Tokens hasheados | SHA-256 para reset de contraseña e invitaciones |
| Caducidad de tokens | 60 min (reset) y 48 h (invitación) |
| Credenciales fuera del código | Claves VAPID, Firebase y API keys en ficheros externos al project root |
| Headers de no-caché | Todas las respuestas dinámicas llevan `Cache-Control: no-store` |

---

## 11. Variables de entorno y configuración

Todas las credenciales sensibles se cargan desde un fichero externo al document root (`config_variables.php`) o como variables de entorno del servidor. **Nunca se versionan en el repositorio.**

| Variable | Descripción |
|---|---|
| `DB_HOST` | Host de la base de datos |
| `DB_NAME` | Nombre de la base de datos |
| `DB_USER` | Usuario de la base de datos |
| `DB_PASS` | Contraseña de la base de datos |
| `MAIL_TRANSPORT` | `smtp` o `mail` |
| `MAIL_HOST` | Servidor SMTP |
| `MAIL_PORT` | Puerto SMTP (587 / 465) |
| `MAIL_ENCRYPTION` | `tls` o `ssl` |
| `MAIL_USERNAME` | Usuario SMTP |
| `MAIL_PASSWORD` | Contraseña SMTP |
| `MAIL_FROM_EMAIL` | Dirección de remite |
| `MAIL_FROM_NAME` | Nombre de remite |
| `OPENAI_API_KEY` | Clave de acceso a la API de OpenAI |
| `OPENAI_PROMPT_ID` | ID del prompt almacenado en OpenAI |
| `OPENAI_PROMPT_VERSION` | Versión del prompt |
| `OPENAI_VECTOR_STORE_ID` | ID del vector store con documentación sindical |
| `FIREBASE_SERVICE_ACCOUNT_PATH` | Ruta absoluta al JSON de Service Account de Firebase |
| `VAPID_KEYS_PATH` | Ruta al fichero JSON con claves VAPID (public/private/subject) |
| `WEB_PUSH_INTERNAL_API_KEY` | Clave interna opcional para llamar al endpoint de push sin sesión admin |

---

## 12. Puesta en marcha local

**Requisitos:** XAMPP (Apache + MySQL + PHP 8.0+) y Composer.

```bash
# 1. Clonar el repositorio
git clone https://github.com/usuario/webuso.git C:/xampp/htdocs/webuso

# 2. Instalar dependencias PHP
cd C:/xampp/htdocs/webuso
composer install

# 3. Crear el fichero de configuración local (fuera del project root)
# Copiar y rellenar con los valores reales:
# C:/xampp/private/config_variables.php

# 4. Generar claves VAPID (solo la primera vez)
php private/generate_vapid_keys.php
# Guardar el resultado en C:/xampp/private/vapid-keys.json

# 5. Importar el esquema de base de datos en phpMyAdmin o cliente MySQL:
#   docs/CALENDAR_DDL.sql
#   docs/PUSH_DDL.sql
#   (+ DDL de web_push_subscriptions del archivo docs/web_push_setup.md)

# 6. Iniciar Apache y MySQL desde el panel de XAMPP

# 7. Acceder en el navegador:
#   http://localhost/webuso/
#   http://localhost/webuso/uso_admin   (panel de administración)
```

---

## 13. Despliegue en producción

El sitio está alojado en un servidor **Plesk (SiteGround)** con PHP 8.x y MySQL.

**Pasos de despliegue:**

1. Subir el código via Git o FTP (excluir `vendor/` y ficheros de credenciales).
2. Ejecutar `composer install --no-dev --optimize-autoloader` en el servidor.
3. Colocar los ficheros de credenciales en la ruta fuera del document root configurada en `config.php`.
4. Verificar permisos de escritura en:
   - `public/assets/img/noticias/`
   - `public/assets/files/`
   - `public/assets/files/calendarios/`
5. Asegurarse de que el `.htaccess` funciona con `AllowOverride All` habilitado en la configuración de Apache del vhost.

> **Nota:** El `.htaccess` requiere `Options +FollowSymLinks` para que mod_rewrite procese las URLs amigables. Sin esta directiva, Apache ignora silenciosamente las reglas de reescritura y todas las rutas devuelven 404.

---

## 14. Documentación técnica adicional

| Documento | Contenido |
|---|---|
| `docs/ADMIN_CONTEXT.md` | Flujos internos, decisiones de diseño y mantenimiento del panel |
| `docs/CALENDAR_DDL.sql` | DDL completo de las tablas de calendarios de turno |
| `docs/PUSH_DDL.sql` | DDL de la tabla de tokens FCM (app Android) |
| `docs/web_push_setup.md` | Guía completa de instalación y configuración Web Push VAPID |
| `docs/android/` | Código fuente de referencia de la app Android wrapper |

