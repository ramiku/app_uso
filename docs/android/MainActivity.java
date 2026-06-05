package com.ramiku.usooest;

import android.Manifest;
import android.app.DownloadManager;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.content.Context;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.os.Environment;
import android.os.Message;
import android.util.Log;
import android.webkit.CookieManager;
import android.webkit.DownloadListener;
import android.webkit.URLUtil;
import android.webkit.WebChromeClient;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.Toast;

import androidx.activity.EdgeToEdge;
import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.ContextCompat;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowInsetsCompat;

import com.google.firebase.messaging.FirebaseMessaging;

public class MainActivity extends AppCompatActivity {

    private static final String TAG = "MainActivity";
    private static final String ALLOWED_DOMAIN = "uso-oest.es";

    private WebView myWebView;
    private ActivityResultLauncher<String> notificationPermissionLauncher;

    // -----------------------------------------------------------------------
    // onCreate
    // -----------------------------------------------------------------------

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_main);

        // Ajuste de márgenes para pantallas modernas (EdgeToEdge)
        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main), (v, insets) -> {
            Insets systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom);
            return insets;
        });

        myWebView = (WebView) findViewById(R.id.webview);
        WebSettings webSettings = myWebView.getSettings();

        // CONFIGURACIÓN BÁSICA
        webSettings.setJavaScriptEnabled(true);
        webSettings.setDomStorageEnabled(true);

        // SOPORTE PARA TARGET="_BLANK" (Ventanas nuevas)
        webSettings.setSupportMultipleWindows(true);
        webSettings.setJavaScriptCanOpenWindowsAutomatically(true);

        // 1. MANEJO DE NAVEGACIÓN (Links normales)
        myWebView.setWebViewClient(new WebViewClient() {

            @Override
            public void onPageFinished(WebView view, String url) {
                super.onPageFinished(view, url);
                if (url.contains("uso-oest.es")) {
                    obtenerTokenFirebase();
                }
            }

            @Override
            public boolean shouldOverrideUrlLoading(WebView view, String url) {
                if (url.startsWith("tel:") || url.startsWith("mailto:") || url.startsWith("whatsapp:")) {
                    Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(url));
                    startActivity(intent);
                    return true;
                }
                if (!url.contains("uso-oest.es")) {
                    Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(url));
                    startActivity(intent);
                    return true;
                }
                return false;
            }
        });

        // 2. MANEJO DE VENTANAS NUEVAS (target="_blank")
        myWebView.setWebChromeClient(new WebChromeClient() {
            @Override
            public boolean onCreateWindow(WebView view, boolean isDialog, boolean isUserGesture, Message resultMsg) {
                WebView newWebView = new WebView(MainActivity.this);
                newWebView.setWebViewClient(new WebViewClient() {
                    @Override
                    public boolean shouldOverrideUrlLoading(WebView view, String url) {
                        Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(url));
                        startActivity(intent);
                        return true;
                    }
                });
                WebView.WebViewTransport transport = (WebView.WebViewTransport) resultMsg.obj;
                transport.setWebView(newWebView);
                resultMsg.sendToTarget();
                return true;
            }
        });

        // 3. MANEJO DE DESCARGAS
        myWebView.setDownloadListener(new DownloadListener() {
            @Override
            public void onDownloadStart(String url, String userAgent, String contentDisposition, String mimetype, long contentLength) {
                DownloadManager.Request request = new DownloadManager.Request(Uri.parse(url));
                request.setMimeType(mimetype);
                String cookies = CookieManager.getInstance().getCookie(url);
                request.addRequestHeader("cookie", cookies);
                request.addRequestHeader("User-Agent", userAgent);
                request.setDescription("Descargando archivo...");
                request.setTitle(URLUtil.guessFileName(url, contentDisposition, mimetype));
                request.allowScanningByMediaScanner();
                request.setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED);
                request.setDestinationInExternalPublicDir(
                        Environment.DIRECTORY_DOWNLOADS,
                        URLUtil.guessFileName(url, contentDisposition, mimetype));

                DownloadManager dm = (DownloadManager) getSystemService(DOWNLOAD_SERVICE);
                dm.enqueue(request);

                Toast.makeText(getApplicationContext(), "Descargando archivo...", Toast.LENGTH_LONG).show();
            }
        });

        // --- Canal de notificación (necesario antes de que llegue el primer push) ---
        crearCanalNotificacion();

        // --- Permiso POST_NOTIFICATIONS (Android 13+) ---
        registrarPermissionsLauncher();
        solicitarPermisoNotificaciones();

        // --- Si venimos de una notificación push, cargar su URL; si no, la web normal ---
        // "push_url" → lo pone nuestro servicio cuando la app estaba en primer plano
        // "url"      → lo pone FCM directamente cuando la app estaba en background/cerrada
        String pushUrl = resolvePushUrl(getIntent());
        myWebView.loadUrl(pushUrl != null ? pushUrl : "https://uso-oest.es/");
    }

    // -----------------------------------------------------------------------
    // onNewIntent — app ya abierta, el usuario pulsa una notificación push
    // -----------------------------------------------------------------------

    @Override
    protected void onNewIntent(Intent intent) {
        super.onNewIntent(intent);
        setIntent(intent);
        String pushUrl = resolvePushUrl(intent);
        if (pushUrl != null && myWebView != null) {
            myWebView.loadUrl(pushUrl);
        }
    }

    // -----------------------------------------------------------------------
    // Canal de notificación (Android 8+)
    // -----------------------------------------------------------------------

    private void crearCanalNotificacion() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            NotificationManager manager =
                    (NotificationManager) getSystemService(Context.NOTIFICATION_SERVICE);
            if (manager != null && manager.getNotificationChannel("canal_general") == null) {
                NotificationChannel channel = new NotificationChannel(
                        "canal_general",
                        "Notificaciones USO OEST",
                        NotificationManager.IMPORTANCE_HIGH
                );
                channel.setDescription("Canal general de notificaciones");
                channel.enableVibration(true);
                channel.enableLights(true);
                manager.createNotificationChannel(channel);
                Log.d(TAG, "Canal 'canal_general' creado");
            }
        }
    }

    // -----------------------------------------------------------------------
    // Token FCM
    // -----------------------------------------------------------------------

    private void obtenerTokenFirebase() {
        FirebaseMessaging.getInstance().getToken()
                .addOnCompleteListener(task -> {
                    if (!task.isSuccessful()) {
                        Log.e("FCM", "Error obteniendo token FCM", task.getException());
                        return;
                    }
                    String token = task.getResult();
                    Log.d("FCM", "Token FCM: " + token);

                    String js = "javascript:if(window.recibirTokenPushAndroid) {"
                            + "window.recibirTokenPushAndroid('" + token.replace("'", "\\'") + "');"
                            + "true;"
                            + "} else { false; }";

                    myWebView.evaluateJavascript(js, value ->
                            Log.d("FCM", "Resultado envío token a WebView: " + value));
                });
    }

    // -----------------------------------------------------------------------
    // Permiso POST_NOTIFICATIONS (Android 13+)
    // -----------------------------------------------------------------------

    private void registrarPermissionsLauncher() {
        notificationPermissionLauncher = registerForActivityResult(
                new ActivityResultContracts.RequestPermission(),
                isGranted -> {
                    if (isGranted) {
                        Log.d(TAG, "Permiso POST_NOTIFICATIONS concedido");
                    } else {
                        Log.w(TAG, "Permiso POST_NOTIFICATIONS denegado");
                    }
                }
        );
    }

    private void solicitarPermisoNotificaciones() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            if (ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS)
                    != PackageManager.PERMISSION_GRANTED) {
                notificationPermissionLauncher.launch(Manifest.permission.POST_NOTIFICATIONS);
            }
        }
    }

    // -----------------------------------------------------------------------
    // Resolución de URL de push
    // -----------------------------------------------------------------------

    /**
     * Extrae la URL de notificación del intent.
     * - "push_url": la pone MyFirebaseMessagingService cuando la app está en foreground.
     * - "url":      la pone FCM automáticamente cuando la app está en background/cerrada.
     * Devuelve null si no hay URL válida.
     */
    private static String resolvePushUrl(Intent intent) {
        if (intent == null) return null;
        String url = intent.getStringExtra("push_url");
        if (url == null || url.isEmpty()) {
            url = intent.getStringExtra("url");  // campo 'url' del payload data FCM
        }
        return (url != null && !url.isEmpty() && isUrlAllowed(url)) ? url : null;
    }

    // -----------------------------------------------------------------------
    // Validación de URL (seguridad: evita cargar URLs arbitrarias de push)
    // -----------------------------------------------------------------------

    private static boolean isUrlAllowed(String url) {
        if (url == null || url.isEmpty()) return false;
        try {
            java.net.URL parsed = new java.net.URL(url);
            String host = parsed.getHost().toLowerCase();
            return "https".equals(parsed.getProtocol())
                    && (host.equals(ALLOWED_DOMAIN) || host.endsWith("." + ALLOWED_DOMAIN));
        } catch (java.net.MalformedURLException e) {
            return false;
        }
    }

    // -----------------------------------------------------------------------
    // Botón atrás
    // -----------------------------------------------------------------------

    @Override
    public void onBackPressed() {
        if (myWebView.canGoBack()) {
            myWebView.goBack();
        } else {
            super.onBackPressed();
        }
    }
}
