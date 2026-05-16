# Webuso (PHP + HTML + CSS + JS)

Aplicación web modular para USO OEST con sitio público y panel administrativo propio.

Estado actual del proyecto:
- Noticias dinámicas desde base de datos.
- Sección Documentación dinámica desde base de datos.
- Asistente virtual con modo reglas + modo IA con acceso premium por código.
- Panel administrativo con autenticación, CRUD de noticias, gestión documental, gestión de imágenes y recuperación de contraseña.

---

## 1) Requisitos

- PHP 8.0 o superior
- Apache/Nginx con soporte PHP
- MySQL/MariaDB
- Extensiones PHP recomendadas: PDO, mbstring, fileinfo

---

## 2) Puesta en marcha

1. Copiar el proyecto en el servidor local, por ejemplo:
    - c:/xampp/htdocs/webuso
2. Configurar base de datos en app/config/config.php o mediante variables de entorno.
3. Iniciar Apache y MySQL.
4. Abrir en navegador:
    - http://localhost/webuso/index.php?page=home

---

## 3) Estructura principal

```text
/
├─ index.php
├─ uso_admin.php
├─ .htaccess
├─ README.md
├─ docs/
│  └─ ADMIN_CONTEXT.md
├─ app/
│  ├─ api/
│  │  ├─ assistant.php
│  │  ├─ contact.php
│  ├─ config/
│  ├─ controllers/
│  ├─ helpers/
│  └─ views/
│     ├─ components/
│     ├─ layouts/
│     ├─ pages/
│     └─ partials/
└─ public/
    └─ assets/
         ├─ css/
         ├─ files/
         │  └─ calendarios/
         ├─ img/
         │  └─ noticias/
         └─ js/
```

---

## 4) Funcionalidades públicas implementadas

### 4.1 Navegación y diseño
- Cabecera responsive con menú principal.
- Footer adaptado con enlaces y redes.
- Estilo visual alineado a USO (paleta roja/negra, look minimalista).

### 4.2 Noticias (sitio público)
- Fuente de datos: tabla noticias (ya no usa mocks).
- Orden: fecha_creaccion DESC, id DESC.
- Soporte de adjuntos por noticia:
   - Imágenes asociadas (se visualizan en el detalle de la noticia).
   - Documentos asociados (se muestran como descargas en el detalle).
- Inicio:
   - Muestra las 6 últimas noticias.
   - Para ver más noticias, el usuario debe entrar a la sección Noticias.
- Listado de noticias:
   - Paginación server-side.
   - Búsqueda por texto en título + contenido.
   - Búsqueda tolerante a mayúsculas/minúsculas, tildes y caracteres especiales.
   - Búsqueda por términos sueltos (no requiere frase exacta).
- Detalle de noticia:
   - Botón Leer más abre la noticia completa por id.
   - Renderiza contenido enriquecido (HTML permitido del editor).

### 4.3 Documentación
- Sección Documentación ahora es dinámica desde base de datos (tabla uso_documents).
- Muestra dos bloques:
   - Documentos (folder files)
   - Calendarios (folder files/calendarios)
- Los títulos visibles vienen de display_name en BBDD y no del nombre de archivo.

### 4.4 Contacto
- Formulario con validación básica.
- Endpoint app/api/contact.php.
- Envío de correo a uso-oest@hotmail.es.

### 4.5 Asistente Virtual USO
- Endpoint: app/api/assistant.php
- Modo reglas (comandos internos con enlaces/descargas/imágenes).
- Modo IA con control premium:
   - Si no está autorizado, solicita validación.
   - Validación por código de seguridad.
   - Sesión autorizada con duración temporal (24h).
- Registro de consultas en base de datos (usuario_prueba) cuando hay configuración de DB.

### 4.6 Robot asistente
- Robot flotante global (excepto en la propia página de asistente).
- Cambio automático de imagen del robot cuando la sesión de IA está autorizada.

---

## 5) Panel de administración (uso_admin.php)

### 5.1 Acceso y autenticación
- Login por tabla uso_users.
- Contraseñas almacenadas con hash seguro (password_hash / password_verify).
- Usuario de prueba creado:
   - Usuario: userprueba
   - Contraseña: passprueba
   - Correo: ramiku@gmail.com

### 5.2 Recuperación y cambio de contraseña
- En pantalla de login existe Recuperar contraseña por correo.
- Flujo:
   1. Introducir correo.
   2. Si existe, envío de enlace de restauración.
   3. Token con caducidad de 60 minutos.
   4. Formulario de nueva contraseña.
- Tabla de soporte: uso_password_resets.
- Usuario logado puede cambiar su contraseña desde el panel.

### 5.3 Alta de nuevos usuarios por invitación
- En Gestión de usuario, si el usuario logado es ramiku, se habilita el envío de invitaciones por correo.
- Flujo:
   1. ramiku introduce el correo del nuevo usuario.
   2. Se envía un enlace único de alta (caduca en 48 horas).
   3. El destinatario accede al enlace y define usuario + contraseña.
   4. Si las validaciones son correctas, se crea el usuario en uso_users.
- En esa misma sección, ramiku puede visualizar todos los usuarios dados de alta y eliminar usuarios (excepto su propio usuario activo).
- Tabla de soporte: uso_user_invites.

### 5.4 Gestión de noticias
- Menú principal → Gestionar noticias.
- Submenú:
   - AÑADIR NOTICIA
   - GESTIONAR NOTICIAS
- Alta/edición/borrado de noticias.
- En crear/editar noticia se permite subir múltiples imágenes y documentos asociados.
- Los archivos se guardan en public/assets/files/noticias con nombre alfanumérico único.
- Editor enriquecido para contenido:
   - Negrita, cursiva, subrayado, listas, enlaces y limpiar formato.

### 5.5 Gestión de documentos
- Menú principal → Gestionar documentos.
- Submenú:
   - SUBIR UN NUEVO DOCUMENTO
   - GESTIONAR DOCUMENTOS
- Al subir documento se solicita:
   - Nombre a mostrar (display_name)
   - Carpeta destino (files o files/calendarios)
   - Archivo
- Se guarda:
   - Archivo físico en public/assets/files o public/assets/files/calendarios
   - Registro en tabla uso_documents (display_name, file_path, folder)
- Gestión:
   - Listado de documentos desde BBDD
   - Apertura de documento
   - Eliminación de documento (registro + archivo físico cuando existe)

### 5.6 Gestión de imágenes
- Menú principal → Gestionar imágenes.
- Alcance: solo imágenes de public/assets/img/noticias.
- Vista tipo galería con miniatura, tamaño, fecha y acción de borrado.

### 5.7 UI del panel
- Rediseño visual para alinearse con estética USO.
- Cabecera tipo web principal con navegación por secciones.
- Tarjetas de menú con iconos y estilo minimalista.
- Flujo post-acción:
   - Al crear noticia vuelve al menú principal de Noticias.
   - Al subir documento vuelve al menú principal de Documentos.

---

## 6) Esquema de base de datos (resumen)

### 6.1 Tabla noticias
Campos principales:
- id
- fecha_creaccion
- titulo
- texto
- slug (opcional)
- estado (borrador/publicada/archivada)
- created_at, updated_at

### 6.2 Tabla uso_users
Campos:
- id
- username
- password (hash)
- email
- created_at, updated_at

### 6.3 Tabla uso_documents
Campos:
- id
- display_name
- file_path
- folder (files | files/calendarios)
- created_at, updated_at

### 6.4 Tabla uso_password_resets
Campos:
- id
- user_id
- email
- token_hash
- expires_at
- used_at
- created_at

### 6.5 Tabla uso_user_invites
Campos:
- id
- email
- token_hash
- expires_at
- used_at
- created_by_user_id
- created_at

### 6.6 Tabla noticias_adjuntos
Campos:
- id
- noticia_id (relación con noticias.id)
- tipo (imagen|documento)
- nombre_original
- ruta_archivo
- mime_type
- created_at

---

## 7) Variables de configuración

Definidas en app/config/config.php (se recomienda mover credenciales sensibles a variables de entorno):
- DB_HOST
- DB_NAME
- DB_USER
- DB_PASS
- MAIL_TRANSPORT (mail|smtp)
- MAIL_HOST
- MAIL_PORT
- MAIL_ENCRYPTION (tls|ssl)
- MAIL_USERNAME
- MAIL_PASSWORD
- MAIL_TIMEOUT
- MAIL_FROM_EMAIL
- MAIL_FROM_NAME
- OPENAI_API_KEY
- OPENAI_PROMPT_ID
- OPENAI_PROMPT_VERSION
- OPENAI_VECTOR_STORE_ID

---

## 8) Endpoints principales

- app/api/contact.php
   - POST
   - Envío de formulario de contacto.
- app/api/assistant.php
   - POST
   - Comandos del asistente, validación premium y respuestas IA.

---

## 9) Notas de seguridad y operación

- Las contraseñas de usuarios admin siempre se almacenan hasheadas.
- El panel admin utiliza token CSRF para acciones POST.
- El editor enriquecido sanitiza HTML permitido en backend.
- La recuperación de contraseña invalida tokens anteriores activos por usuario.
- El envío de correo usa SMTP autenticado si MAIL_TRANSPORT=smtp; si no, usa mail() como fallback.

---

## 10) Documentación adicional

- Ver docs/ADMIN_CONTEXT.md para contexto técnico y operativo ampliado del panel.
#   w e b _ u s o  
 