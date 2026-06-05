# Webuso

Aplicación web para **USO OEST** con sitio público y panel administrativo propio.

## Stack tecnológico

- **Backend:** PHP 8.0+
- **Base de datos:** MySQL / MariaDB
- **Servidor:** Apache / Nginx
- **Frontend:** HTML, CSS, JavaScript (sin frameworks)

## Funcionalidades principales

### Sitio público

- **Noticias** — Listado dinámico con paginación, búsqueda y vista de detalle con adjuntos (imágenes y documentos).
- **Documentación** — Descarga de documentos y calendarios de turnos gestionados desde el panel.
- **Contacto** — Formulario de contacto con envío de correo.
- **Asistente Virtual** — Chatbot con modo de respuestas predefinidas y modo IA (acceso controlado).
- **Notificaciones push** — Soporte para notificaciones en navegadores (Web Push / VAPID) y app Android (FCM).

### Panel de administración

Acceso restringido con autenticación segura. Permite gestionar:

- Noticias (alta, edición, borrado, adjuntos).
- Documentos y calendarios (subida, organización y eliminación).
- Imágenes del sitio.
- Usuarios (invitación por correo, cambio de contraseña, recuperación de acceso).
- Envío de notificaciones push personalizadas.

## Requisitos

- PHP 8.0 o superior con extensiones: `PDO`, `mbstring`, `fileinfo`
- Apache o Nginx con soporte PHP
- MySQL / MariaDB

## Puesta en marcha

1. Clonar o copiar el proyecto en el servidor.
2. Configurar las variables de entorno o el archivo de configuración (`app/config/config.php`).
3. Importar el esquema de base de datos (ver carpeta `docs/`).
4. Iniciar el servidor web y la base de datos.
5. Acceder al sitio desde el navegador.

## Documentación técnica

La documentación técnica detallada (esquema de BD, endpoints, variables de configuración, flujos internos) se encuentra en la carpeta `docs/`, que no se expone públicamente.
