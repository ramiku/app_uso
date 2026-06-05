/**
 * push-sw.js — Service Worker para Web Push (VAPID)
 * Colocado en la raíz pública para tener scope completo: /push-sw.js
 *
 * Escucha eventos:
 *  - push          → muestra la notificación
 *  - notificationclick → abre/focaliza la URL indicada
 */

'use strict';

var ICON_URL = '/public/assets/img/icon-192.png';
var BADGE_URL = '/public/assets/img/icon-192.png';

/* ── push ───────────────────────────────────────────────────────────────── */
self.addEventListener('push', function (event) {
    var data = {};

    if (event.data) {
        try {
            data = event.data.json();
        } catch (e) {
            // Payload no JSON: usar texto plano como mensaje
            data = {
                titulo: 'USO OEST',
                mensaje: event.data.text(),
                url: '/',
            };
        }
    }

    var title   = String(data.titulo   || 'USO OEST');
    var body    = String(data.mensaje  || '');
    var url     = String(data.url      || '/');

    var options = {
        body: body,
        icon: ICON_URL,
        badge: BADGE_URL,
        data: { url: url },
        requireInteraction: false,
        vibrate: [200, 100, 200],
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

/* ── notificationclick ──────────────────────────────────────────────────── */
self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    var targetUrl = (event.notification.data && event.notification.data.url)
        ? event.notification.data.url
        : '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (windowClients) {
            // Focalizar pestaña ya abierta si tiene la misma URL
            for (var i = 0; i < windowClients.length; i++) {
                var client = windowClients[i];
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }
            // Si no hay pestaña abierta, abrir una nueva
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});

/* ── activate: limpiar cachés antiguas si las hubiera ──────────────────── */
self.addEventListener('activate', function (event) {
    event.waitUntil(clients.claim());
});
