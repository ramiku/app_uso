# Contexto técnico de administración (uso_admin)

Este documento complementa el README y resume decisiones de diseño, flujo operativo y mantenimiento del panel administrativo.

## 1. Objetivo del panel

uso_admin.php centraliza la gestión interna de:
- Noticias
- Documentos
- Imágenes de noticias
- Credenciales de acceso del usuario autenticado

Además, expone recuperación de contraseña para usuarios de la tabla uso_users.

## 2. Flujos principales

### 2.1 Noticias
- Crear noticia:
  - Fecha de creación
  - Estado
  - Título
  - Imagen opcional (subida)
  - Contenido enriquecido
- Editar noticia:
  - Mismo formulario, precargado
- Eliminar noticia:
  - Borrado en tabla noticias

Notas:
- El contenido se sanitiza en backend antes de guardar.
- La imagen se guarda en public/assets/img/noticias con nombre alfanumérico único.

### 2.2 Documentos
- Subir documento:
  - Nombre a mostrar (display_name)
  - Carpeta destino (files o files/calendarios)
  - Archivo físico
- Registro en uso_documents:
  - display_name
  - file_path
  - folder
- Gestión de documentos:
  - Lista desde BBDD
  - Apertura
  - Eliminación (archivo + registro)

### 2.3 Imágenes
- Se listan únicamente archivos de public/assets/img/noticias.
- Se permite eliminar cada imagen.

### 2.4 Notificaciones push
- Formulario para enviar notificación personalizada (título, mensaje, URL opcional).
- Doble canal unificado mediante `enviarNotificacionUnificada()` en push_helpers.php:
  - **FCM (Firebase Cloud Messaging)**: notificaciones a tokens registrados en `app_push_tokens` (app Android nativa).
  - **Web Push (VAPID)**: notificaciones a suscripciones registradas en `web_push_subscriptions` (navegadores web).
- Desde la sección de edición de noticias se puede lanzar la notificación directamente (`send_push_news`).
- Los tokens FCM se registran automáticamente al cargar la web en el wrapper Android mediante `window.recibirTokenPushAndroid()` (head.php).
- Las suscripciones Web Push se gestionan desde el cliente via webpush.js + Service Worker (push-sw.js).

## 3. Seguridad aplicada

- Autenticación por uso_users (password_hash / password_verify).
- CSRF token en acciones POST del panel.
- Sanitización de HTML del editor enriquecido.
- Validación de tipo de archivo para subida de imágenes y documentos.
- Rutas de borrado restringidas a raíces permitidas para evitar path traversal.

## 4. Recuperación de contraseña

Tabla: uso_password_resets
- token_hash almacenado hasheado
- expires_at a 60 minutos
- used_at para invalidación de token consumido

Flujo:
1. Usuario introduce correo.
2. Si existe, se crea token y se envía enlace.
3. Enlace abre modo reset con email+token.
4. Se actualiza contraseña y se marca token como usado.

## 5. Integración frontend pública

### Noticias
- Fuente de datos: tabla noticias
- Orden: fecha_creaccion DESC, id DESC
- Búsqueda:
  - título + contenido
  - sin sensibilidad a mayúsculas/minúsculas
  - sin sensibilidad a tildes
  - ignora caracteres especiales
  - búsqueda por términos sueltos (todas las palabras)

### Documentación
- Fuente de datos: tabla uso_documents
- Separación en dos grupos por folder:
  - files → Documentos
  - files/calendarios → Calendarios

### Calendarios de turnos
- Fuente de datos: tablas de calendario (uso_calendar_years, uso_calendar_rotations, uso_calendar_holidays, uso_calendar_generated)
- Gestionados desde panel admin (sección Calendario).
- Vista pública en la página Calendarios con navegación por mes.

### Push (canal doble)
- FCM (Android): los tokens se registran desde la app WebView al cargar cualquier página.
- Web Push (navegadores): suscripción via banner + Service Worker (`push-sw.js`).

## 6. Mantenimiento recomendado

1. Copias de seguridad periódicas de tablas:
   - noticias
   - uso_users
   - uso_documents
   - uso_password_resets
   - app_push_tokens
   - web_push_subscriptions
2. Revisar y depurar tokens de recuperación vencidos de forma periódica.
3. Depurar tokens FCM inactivos (`activo = 0`) en `app_push_tokens` ocasionalmente.
4. Depurar suscripciones Web Push inactivas (`activo = 0`) en `web_push_subscriptions`.
5. Validar permisos de escritura en:
   - public/assets/img/noticias
   - public/assets/files
   - public/assets/files/calendarios
4. En producción, configurar MAIL_TRANSPORT=smtp y credenciales SMTP válidas (MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION).
5. Evitar credenciales hardcoded en app/config/config.php y usar variables de entorno.

## 7. Checklist de despliegue

- Configurar DB_HOST, DB_NAME, DB_USER, DB_PASS
- Verificar conectividad MySQL
- Crear tablas requeridas
- Verificar permisos de carpetas de subida
- Comprobar envío de email
- Probar login admin, recuperación, CRUD y búsquedas
