<?php $currentUser = admin_user(); ?>
<div class="page-header">
    <div>
        <nav class="breadcrumb" aria-label="Ruta de navegación">
            <a href="<?= e(admin_url()) ?>">Inicio</a>
            <span class="breadcrumb__sep" aria-hidden="true">›</span>
            <span class="breadcrumb__current">Mi usuario</span>
        </nav>
        <h1 class="page-header__title">Mi usuario</h1>
    </div>
</div>

<div class="grid-2" style="gap:1rem">

    <?php /* ── Cambiar contraseña ── */ ?>
    <div class="panel">
        <h2 class="panel__title">Cambiar contraseña</h2>
        <form method="post" action="<?= e(admin_url()) ?>" autocomplete="off" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="change_password">
            <input type="hidden" name="redirect_section" value="user">
            <div class="form-grid">
                <div class="form-group">
                    <label class="label" style="color:var(--a-muted);font-weight:400">
                        Usuario actual: <strong><?= e($currentUser['username'] ?? '—') ?></strong>
                    </label>
                </div>
                <div class="form-group">
                    <label class="label label--required" for="current_password">Contraseña actual</label>
                    <input id="current_password" name="current_password" type="password" class="input"
                           autocomplete="current-password" required>
                </div>
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
                <div>
                    <button type="submit" class="btn btn--primary js-submit-btn">
                        Actualizar contraseña
                    </button>
                </div>
            </div>
        </form>
    </div>

    <?php /* ── Invitar nuevo administrador ── */ ?>
    <?php if (admin_can_send_user_invites()): ?>
    <div class="panel">
        <h2 class="panel__title">Invitar nuevo administrador</h2>
        <form method="post" action="<?= e(admin_url()) ?>" autocomplete="off" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="send_user_invite">
            <div class="form-grid">
                <div class="form-group">
                    <label class="label label--required" for="invite_email">Correo electrónico</label>
                    <input id="invite_email" name="invite_email" type="email" class="input"
                           autocomplete="off" required
                           placeholder="usuario@ejemplo.com">
                    <p class="helper">
                        Se enviará un enlace de alta al correo indicado. Caduca en 72 horas.
                    </p>
                </div>
                <div>
                    <button type="submit" class="btn btn--primary js-submit-btn">
                        Enviar invitación
                    </button>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>

</div>

<?php /* ── Lista de administradores ── */ ?>
<?php if (admin_can_send_user_invites() && is_array($adminUsers) && count($adminUsers) > 0): ?>
<div class="panel" style="margin-top:1rem">
    <h2 class="panel__title">Administradores registrados</h2>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Correo</th>
                <th style="min-width:100px">Acción</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($adminUsers as $u): ?>
        <?php
            $uid    = (int)($u['id'] ?? 0);
            $uname  = (string)($u['username'] ?? '');
            $uemail = (string)($u['email'] ?? '');
            $isSelf = $uid === (int)($currentUser['id'] ?? 0);
        ?>
        <tr>
            <td><?= $uid ?></td>
            <td>
                <?= e($uname) ?>
                <?php if ($isSelf): ?>
                <span class="badge badge--publicada" style="margin-left:.3rem">Tú</span>
                <?php endif; ?>
            </td>
            <td><?= e($uemail) ?></td>
            <td>
                <?php if (!$isSelf): ?>
                <form method="post" action="<?= e(admin_url()) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="delete_admin_user">
                    <input type="hidden" name="user_id" value="<?= $uid ?>">
                    <button type="submit" class="btn btn--danger btn--sm"
                            onclick="return confirm('¿Eliminar al usuario «<?= e(addslashes($uname)) ?>»? Esta acción es irreversible.')">
                        Eliminar
                    </button>
                </form>
                <?php else: ?>
                <span style="color:var(--a-muted);font-size:.85rem">—</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>
