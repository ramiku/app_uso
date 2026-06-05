/**
 * webpush.js — Lógica cliente Web Push (VAPID)
 *
 * Flujo:
 *  1. Detectar si estamos dentro del wrapper Android → abortar.
 *  2. Comprobar soporte de Push API.
 *  3. Mostrar banner de activación solo si es necesario.
 *  4. Al pulsar el botón pedir permiso y suscribir.
 *  5. Enviar la suscripción al backend.
 */

'use strict';

/* ────────────────────────────────────────────────────────────────────────────
   Configuración
   ──────────────────────────────────────────────────────────────────────────*/

/** Clave pública VAPID (Base64url). Se inyecta desde PHP en head.php. */
var WEB_PUSH_PUBLIC_KEY = window.WEB_PUSH_PUBLIC_KEY || '';

var _BASE       = (window.APP_BASE_URL || '').replace(/\/$/, '');
var SW_URL      = _BASE + '/push-sw.js';
var SAVE_URL    = _BASE + '/api/guardar_web_push_subscription.php';

/* ────────────────────────────────────────────────────────────────────────────
   Detección wrapper Android
   ──────────────────────────────────────────────────────────────────────────*/

/**
 * Devuelve true si la web se está ejecutando dentro del wrapper Android nativo.
 * En ese caso NO debe registrarse WebPush porque las notificaciones se gestionan
 * con FCM nativo.
 *
 * Señales defensivas:
 *   - Objetos inyectados por la WebView (window.Android, window.USOOESTAndroid…)
 *   - User-Agent con "USOOEST-WRAPPER" (añadido manualmente en la WebView)
 *   - User-Agent con "; wv)" característico de Android WebView (ChromeWebView)
 */
function esWrapperAndroid() {
    var ua = navigator.userAgent || '';

    return Boolean(
        window.Android ||
        window.AndroidInterface ||
        window.USOOESTAndroid ||
        ua.indexOf('USOOEST-WRAPPER') !== -1 ||
        ua.indexOf('; wv)') !== -1 ||
        (ua.indexOf('Version/4.0') !== -1 && ua.indexOf('Mobile Safari') !== -1 && ua.indexOf(' wv') !== -1)
    );
}

/* ────────────────────────────────────────────────────────────────────────────
   Soporte de la API
   ──────────────────────────────────────────────────────────────────────────*/

function soportaWebPush() {
    return (
        'serviceWorker' in navigator &&
        'PushManager'   in window &&
        'Notification'  in window
    );
}

/* ────────────────────────────────────────────────────────────────────────────
   Utilidad: convertir clave Base64url → Uint8Array
   ──────────────────────────────────────────────────────────────────────────*/

function base64UrlToUint8Array(base64url) {
    var padding = '='.repeat((4 - (base64url.length % 4)) % 4);
    var base64  = (base64url + padding).replace(/-/g, '+').replace(/_/g, '/');
    var rawData = window.atob(base64);
    var output  = new Uint8Array(rawData.length);
    for (var i = 0; i < rawData.length; i++) {
        output[i] = rawData.charCodeAt(i);
    }
    return output;
}

/* ────────────────────────────────────────────────────────────────────────────
   Guardar suscripción en el backend
   ──────────────────────────────────────────────────────────────────────────*/

function guardarSuscripcion(subscription) {
    var json = subscription.toJSON();

    return fetch(SAVE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
            endpoint:   json.endpoint,
            keys:       json.keys,
            user_agent: navigator.userAgent,
        }),
    })
    .then(function (res) {
        if (!res.ok) {
            console.error('[WebPush] Error HTTP al guardar suscripción:', res.status, res.statusText);
        }
        return res.json();
    })
    .then(function (data) {
        if (data && data.ok) {
            console.log('[WebPush] Suscripción guardada correctamente.');
        } else {
            console.warn('[WebPush] Respuesta inesperada al guardar:', data);
        }
        return data;
    })
    .catch(function (err) {
        console.error('[WebPush] Error al guardar suscripción:', err);
    });
}

/* ────────────────────────────────────────────────────────────────────────────
   Activar notificaciones (llamado desde el botón)
   ──────────────────────────────────────────────────────────────────────────*/

/**
 * Registra el SW, pide permiso y suscribe.
 * Devuelve una Promise que resuelve con 'granted' | 'denied' | 'unsupported'.
 */
function activarNotificacionesWeb() {
    if (esWrapperAndroid()) {
        console.log('[WebPush] Omitido: wrapper Android detectado.');
        return Promise.resolve('android_wrapper');
    }

    if (!soportaWebPush()) {
        console.warn('[WebPush] La API Web Push no está soportada en este navegador.');
        return Promise.resolve('unsupported');
    }

    if (WEB_PUSH_PUBLIC_KEY === '') {
        console.error('[WebPush] WEB_PUSH_PUBLIC_KEY no definida. Revisa head.php.');
        return Promise.resolve('error');
    }

    // Compatibilidad: algunos navegadores usan la API antigua basada en callback.
    var permissionPromise = (function () {
        try {
            var p = Notification.requestPermission();
            if (p && typeof p.then === 'function') {
                return p;
            }
        } catch (e) {}
        return new Promise(function (resolve) {
            Notification.requestPermission(resolve);
        });
    })();

    return permissionPromise.then(function (permission) {
        if (permission === 'denied') {
            console.log('[WebPush] El usuario ha denegado las notificaciones.');
            return permission;
        }
        if (permission !== 'granted') {
            // 'default': el usuario cerró el diálogo sin elegir.
            console.log('[WebPush] Diálogo cerrado sin respuesta (estado: ' + permission + '). Puedes intentarlo de nuevo.');
            return permission;
        }

        return navigator.serviceWorker.register(SW_URL).then(function (registration) {
            console.log('[WebPush] Service Worker registrado:', registration.scope);
            return registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: base64UrlToUint8Array(WEB_PUSH_PUBLIC_KEY),
            });
        })
        .then(function (subscription) {
            return guardarSuscripcion(subscription).then(function () {
                return 'granted';
            });
        });
    });
}

/* ────────────────────────────────────────────────────────────────────────────
   Re-sincronizar suscripción existente (sin volver a pedir permiso)
   ──────────────────────────────────────────────────────────────────────────*/

function resincronizarSuscripcion() {
    if (esWrapperAndroid() || !soportaWebPush()) {
        return Promise.resolve(false);
    }
    if (Notification.permission !== 'granted') {
        return Promise.resolve(false);
    }

    return navigator.serviceWorker.register(SW_URL).then(function (registration) {
        return registration.pushManager.getSubscription();
    }).then(function (subscription) {
        if (subscription) {
            return guardarSuscripcion(subscription).then(function () { return true; });
        }
        return false;
    }).catch(function (err) {
        console.error('[WebPush] Error re-sincronizando:', err);
        return false;
    });
}

/* ────────────────────────────────────────────────────────────────────────────
   Banner de activación
   ──────────────────────────────────────────────────────────────────────────*/

function initWebPushBanner() {
    if (esWrapperAndroid()) {
        return;
    }

    if (!soportaWebPush()) {
        return;
    }

    var permission = Notification.permission;

    // Permiso ya concedido → re-sincronizar silenciosamente, sin banner
    if (permission === 'granted') {
        resincronizarSuscripcion();
        return;
    }

    // Permiso bloqueado → mostrar instrucción fija, sin banner
    if (permission === 'denied') {
        var deniedMsg = document.getElementById('webpush-denied-msg');
        if (deniedMsg) {
            deniedMsg.textContent = 'Notificaciones bloqueadas. Haz clic en el 🔒 de la barra de direcciones → Notificaciones → Permitir.';
            deniedMsg.style.display = 'block';
        }
        return;
    }

    // Estado 'default' → mostrar banner de activación
    var banner = document.getElementById('webpush-banner');
    if (!banner) {
        return;
    }

    banner.style.display = 'flex';

    var btnActivar = document.getElementById('webpush-btn-activar');
    if (btnActivar) {
        btnActivar.addEventListener('click', function () {
            btnActivar.disabled = true;

            activarNotificacionesWeb().then(function (result) {
                if (result === 'granted') {
                    banner.style.display = 'none';
                } else if (result === 'denied') {
                    banner.style.display = 'none';
                    var msg = document.getElementById('webpush-denied-msg');
                    if (msg) {
                        msg.textContent = 'Notificaciones bloqueadas. Haz clic en el 🔒 de la barra de direcciones → Notificaciones → Permitir.';
                        msg.style.display = 'block';
                    }
                } else {
                    // 'default': Chrome quiet mode — el usuario debe pulsar el icono de campana en la barra
                    btnActivar.disabled = false;
                    var quietMsg = document.getElementById('webpush-quiet-msg');
                    if (quietMsg) { quietMsg.style.display = 'block'; }
                }
            }).catch(function () {
                btnActivar.disabled = false;
            });
        });
    }

    var btnCerrar = document.getElementById('webpush-btn-cerrar');
    if (btnCerrar) {
        btnCerrar.addEventListener('click', function () {
            banner.style.display = 'none';
        });
    }
}

/* ────────────────────────────────────────────────────────────────────────────
   Arranque
   ──────────────────────────────────────────────────────────────────────────*/

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initWebPushBanner);
} else {
    initWebPushBanner();
}
