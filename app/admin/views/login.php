<div class="auth-page">
    <div class="auth-card">

        <img class="auth-card__logo"
             src="<?= e(asset('img/logo_uso_oest.png')) ?>"
             alt="USO OEST – Panel de administración">

        <?php if ($isResetMode): ?>
        <?php /* ─────────── Restablecer contraseña ─────────── */ ?>

            <h1 class="auth-card__title">Restablecer contraseña</h1>
            <p class="auth-card__sub">Introduce tu nueva contraseña para <strong><?= e($resetEmail) ?></strong>.</p>

            <?php if ($errorMessage !== ''): ?>
            <div class="flash flash--error" style="margin-bottom:1rem;" role="alert">
                <?= e($errorMessage) ?>
            </div>
            <?php endif; ?>

            <form method="post" action="<?= e(admin_url()) ?>" autocomplete="off" novalidate>
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="reset_email" value="<?= e($resetEmail) ?>">
                <input type="hidden" name="reset_token" value="<?= e($resetToken) ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="label label--required" for="new_password">Nueva contraseña</label>
                        <input id="new_password" name="new_password" type="password" class="input"
                               autocomplete="new-password" minlength="8" required>
                        <p class="helper">Al menos 8 caracteres.</p>
                    </div>
                    <div class="form-group">
                        <label class="label label--required" for="confirm_password">Confirmar contraseña</label>
                        <input id="confirm_password" name="confirm_password" type="password" class="input"
                               autocomplete="new-password" minlength="8" required>
                    </div>
                    <button type="submit" class="btn btn--primary" style="width:100%">
                        Guardar nueva contraseña
                    </button>
                </div>
            </form>

        <?php elseif ($isRegisterMode): ?>
        <?php /* ─────────── Alta de nuevo usuario ─────────── */ ?>

            <h1 class="auth-card__title">Crear tu cuenta</h1>
            <p class="auth-card__sub">Invitación para <strong><?= e($registerEmail) ?></strong>. Elige tu usuario y contraseña.</p>

            <?php if ($errorMessage !== ''): ?>
            <div class="flash flash--error" style="margin-bottom:1rem;" role="alert">
                <?= e($errorMessage) ?>
            </div>
            <?php endif; ?>

            <form method="post" action="<?= e(admin_url()) ?>" autocomplete="off" novalidate>
                <input type="hidden" name="action" value="complete_registration">
                <input type="hidden" name="register_email" value="<?= e($registerEmail) ?>">
                <input type="hidden" name="register_token" value="<?= e($registerToken) ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="label label--required" for="register_username">Nombre de usuario</label>
                        <input id="register_username" name="register_username" type="text" class="input"
                               autocomplete="username" minlength="3" maxlength="40"
                               pattern="[A-Za-z0-9_.\-]{3,40}" required>
                        <p class="helper">Entre 3 y 40 caracteres (letras, números, guiones, puntos o _).</p>
                    </div>
                    <div class="form-group">
                        <label class="label label--required" for="reg_password">Contraseña</label>
                        <input id="reg_password" name="new_password" type="password" class="input"
                               autocomplete="new-password" minlength="8" required>
                    </div>
                    <div class="form-group">
                        <label class="label label--required" for="reg_confirm">Confirmar contraseña</label>
                        <input id="reg_confirm" name="confirm_password" type="password" class="input"
                               autocomplete="new-password" minlength="8" required>
                    </div>
                    <button type="submit" class="btn btn--primary" style="width:100%">
                        Crear cuenta
                    </button>
                </div>
            </form>

        <?php else: ?>
        <?php /* ─────────── Inicio de sesión ─────────── */ ?>

            <h1 class="auth-card__title">Panel de administración</h1>
            <p class="auth-card__sub">Accede con tu usuario y contraseña de administrador.</p>

            <?php if ($errorMessage !== ''): ?>
            <div class="flash flash--error" style="margin-bottom:1rem;" role="alert">
                <?= e($errorMessage) ?>
            </div>
            <?php endif; ?>

            <?php if (is_array($flash) && isset($flash['type'], $flash['message'])): ?>
            <div class="flash flash--<?= e($flash['type']) ?>" style="margin-bottom:1rem;"
                 role="alert" data-auto-dismiss="6000">
                <?= e($flash['message']) ?>
            </div>
            <?php endif; ?>

            <form method="post" action="<?= e(admin_url()) ?>" autocomplete="on" novalidate>
                <input type="hidden" name="action" value="login">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="label label--required" for="login_username">Usuario</label>
                        <input id="login_username" name="username" type="text" class="input"
                               autocomplete="username" required autofocus>
                    </div>
                    <div class="form-group">
                        <label class="label label--required" for="login_password">Contraseña</label>
                        <input id="login_password" name="password" type="password" class="input"
                               autocomplete="current-password" required>
                    </div>
                    <button type="submit" class="btn btn--primary" style="width:100%">
                        Iniciar sesión
                    </button>
                </div>
            </form>

            <div class="auth-divider">o</div>

            <p style="text-align:center;font-size:0.88rem;color:var(--a-muted);margin:0">
                ¿Olvidaste tu contraseña?
                <button type="button" class="btn-link" id="js-forgot-btn">Restablécela aquí</button>.
            </p>

        <?php endif; ?>

    </div><?php /* .auth-card */ ?>
</div><?php /* .auth-page */ ?>

<?php if (!$isResetMode && !$isRegisterMode): ?>
<!-- Modal recuperación de contraseña -->
<div class="a-modal-backdrop" id="js-forgot-backdrop" hidden aria-hidden="true">
    <div class="a-modal" role="dialog" aria-modal="true" aria-labelledby="forgot-modal-title">
        <div class="a-modal__header">
            <h2 class="a-modal__title" id="forgot-modal-title">Recuperar contraseña</h2>
            <button type="button" class="a-modal__close" id="js-forgot-close" aria-label="Cerrar">✕</button>
        </div>
        <div class="a-modal__body">
            <p style="font-size:.9rem;color:var(--a-muted);margin:0 0 1rem">
                Introduce el correo de tu cuenta. Si existe, recibirás un enlace para restablecer tu contraseña.
            </p>

            <?php if ($errorMessage !== '' && isset($_POST['action']) && $_POST['action'] === 'request_password_reset'): ?>
            <div class="flash flash--error" style="margin-bottom:1rem" role="alert">
                <?= e($errorMessage) ?>
            </div>
            <?php endif; ?>

            <form method="post" action="<?= e(admin_url()) ?>" autocomplete="off" novalidate
                  id="js-forgot-form">
                <input type="hidden" name="action" value="request_password_reset">
                <div class="form-group" style="margin-bottom:1rem">
                    <label class="label label--required" for="reset_email_input">Correo electrónico</label>
                    <input id="reset_email_input" name="email" type="email" class="input"
                           autocomplete="email" required autofocus>
                </div>
                <button type="submit" class="btn btn--primary" style="width:100%">
                    Enviar enlace de recuperación
                </button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
