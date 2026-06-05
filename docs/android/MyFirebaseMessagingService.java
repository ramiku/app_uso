package com.ramiku.usooest;

import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.os.Build;
import android.util.Log;

import androidx.annotation.NonNull;
import androidx.core.app.NotificationCompat;

import com.google.firebase.messaging.FirebaseMessagingService;
import com.google.firebase.messaging.RemoteMessage;

import java.util.Map;
import java.util.concurrent.atomic.AtomicInteger;

/**
 * MyFirebaseMessagingService
 *
 * Servicio Firebase que:
 * 1. Recibe el token FCM y lo inyecta en el WebView.
 * 2. Muestra notificaciones locales cuando la app está en primer o segundo plano.
 * 3. Al pulsar la notificación, abre MainActivity con la URL recibida.
 */
public class MyFirebaseMessagingService extends FirebaseMessagingService {

    private static final String TAG = "FCMService";
    private static final String CHANNEL_ID = "canal_general";
    private static final String CHANNEL_NAME = "Notificaciones USO OEST";
    private static final String CHANNEL_DESC = "Canal general de notificaciones";
    private static final String ALLOWED_DOMAIN = "uso-oest.es";

    /** Contador para IDs únicos de notificación */
    private static final AtomicInteger notificationIdCounter = new AtomicInteger(1000);

    // -----------------------------------------------------------------------
    // Nuevo token FCM
    // -----------------------------------------------------------------------

    @Override
    public void onNewToken(@NonNull String token) {
        super.onNewToken(token);
        Log.d(TAG, "Token FCM renovado: " + token);

        // Notificar a MainActivity si está activa para que lo inyecte en el WebView
        Intent intent = new Intent("com.ramiku.usooest.ACTION_NEW_FCM_TOKEN");
        intent.putExtra("fcm_token", token);
        sendBroadcast(intent);
    }

    // -----------------------------------------------------------------------
    // Mensaje recibido
    // -----------------------------------------------------------------------

    @Override
    public void onMessageReceived(@NonNull RemoteMessage remoteMessage) {
        super.onMessageReceived(remoteMessage);
        Log.d(TAG, "=== onMessageReceived LLAMADO ===");
        Log.d(TAG, "From: " + remoteMessage.getFrom());
        Log.d(TAG, "Data keys: " + remoteMessage.getData().keySet().toString());
        Log.d(TAG, "Notification presente: " + (remoteMessage.getNotification() != null));

        Map<String, String> data = remoteMessage.getData();

        // Leer datos del payload (campo 'data' del mensaje FCM)
        String title = safeGet(data, "title");
        String body  = safeGet(data, "body");
        String url   = safeGet(data, "url");

        Log.d(TAG, "title=" + title + " | body=" + body + " | url=" + url);

        // Fallback a campos de 'notification' si 'data' viene vacío
        RemoteMessage.Notification notification = remoteMessage.getNotification();
        if (notification != null) {
            if (title.isEmpty() && notification.getTitle() != null) {
                title = notification.getTitle();
            }
            if (body.isEmpty() && notification.getBody() != null) {
                body = notification.getBody();
            }
        }

        // Validar URL antes de usarla
        if (!isUrlAllowed(url)) {
            Log.w(TAG, "URL de notificación no permitida, usando URL base: " + url);
            url = "https://uso-oest.es/";
        }

        mostrarNotificacion(title.isEmpty() ? "USO OEST" : title,
                            body.isEmpty()  ? "" : body,
                            url);
    }

    // -----------------------------------------------------------------------
    // Mostrar notificación local
    // -----------------------------------------------------------------------

    private void mostrarNotificacion(String title, String body, String url) {
        crearCanalNotificacion();

        Intent intent = new Intent(this, MainActivity.class);
        intent.putExtra("push_url", url);
        intent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP | Intent.FLAG_ACTIVITY_SINGLE_TOP);

        int flags = PendingIntent.FLAG_UPDATE_CURRENT;
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            flags |= PendingIntent.FLAG_IMMUTABLE;
        }

        PendingIntent pendingIntent = PendingIntent.getActivity(
                this,
                notificationIdCounter.get(),
                intent,
                flags
        );

        int iconRes = getNotificationIcon();
        Log.d(TAG, "Mostrando notificación con icono resId=" + iconRes + " canal=" + CHANNEL_ID);

        NotificationCompat.Builder builder = new NotificationCompat.Builder(this, CHANNEL_ID)
                // Usar ic_notification si existe, si no el launcher como fallback seguro
                .setSmallIcon(iconRes)
                .setContentTitle(title)
                .setContentText(body)
                .setStyle(new NotificationCompat.BigTextStyle().bigText(body))
                .setPriority(NotificationCompat.PRIORITY_HIGH)
                .setAutoCancel(true)
                .setContentIntent(pendingIntent);

        NotificationManager manager =
                (NotificationManager) getSystemService(Context.NOTIFICATION_SERVICE);

        if (manager != null) {
            manager.notify(notificationIdCounter.getAndIncrement(), builder.build());
        }
    }

    // -----------------------------------------------------------------------
    // Canal de notificación (Android 8+)
    // -----------------------------------------------------------------------

    private void crearCanalNotificacion() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            NotificationManager manager = getSystemService(NotificationManager.class);
            if (manager == null) {
                Log.e(TAG, "NotificationManager es null - no se puede crear canal");
                return;
            }
            if (manager.getNotificationChannel(CHANNEL_ID) == null) {
                NotificationChannel channel = new NotificationChannel(
                        CHANNEL_ID,
                        CHANNEL_NAME,
                        NotificationManager.IMPORTANCE_HIGH
                );
                channel.setDescription(CHANNEL_DESC);
                channel.enableVibration(true);
                channel.enableLights(true);
                manager.createNotificationChannel(channel);
                Log.d(TAG, "Canal '" + CHANNEL_ID + "' creado");
            } else {
                Log.d(TAG, "Canal '" + CHANNEL_ID + "' ya existe");
            }
        }
    }

    // -----------------------------------------------------------------------
    // Utilidades
    // -----------------------------------------------------------------------

    /** Devuelve el icono de notificación. Usa ic_notification si existe, si no el launcher. */
    private int getNotificationIcon() {
        int resId = getResources().getIdentifier("ic_notification", "drawable", getPackageName());
        return resId != 0 ? resId : R.mipmap.ic_launcher;
    }

    /** Devuelve el valor del mapa o cadena vacía si es null. */
    private static String safeGet(@NonNull Map<String, String> map, String key) {
        String value = map.get(key);
        return value != null ? value.trim() : "";
    }

    /**
     * Valida que la URL pertenezca a uso-oest.es (https) antes de abrirla.
     * Evita que un payload malicioso abra URLs arbitrarias.
     */
    private static boolean isUrlAllowed(String url) {
        if (url == null || url.isEmpty()) return false;
        try {
            java.net.URL parsed = new java.net.URL(url);
            String host = parsed.getHost().toLowerCase();
            String protocol = parsed.getProtocol().toLowerCase();
            return "https".equals(protocol)
                    && (host.equals(ALLOWED_DOMAIN) || host.endsWith("." + ALLOWED_DOMAIN));
        } catch (java.net.MalformedURLException e) {
            return false;
        }
    }
}
