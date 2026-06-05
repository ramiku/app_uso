<?php
declare(strict_types=1);
?>
<section class="section container" aria-labelledby="contactanos-title">
    <h1 id="contactanos-title" class="section__title"><?php echo e($data['title'] ?? 'Contáctanos'); ?></h1>
    <p class="section__lead"><?php echo e($data['body'] ?? 'Puedes ponerte en contacto con nuestro equipo para cualquier consulta.'); ?></p>

    <div class="contact-card">
        <form class="contact-form" id="contact-form" data-endpoint="<?php echo e(BASE_URL . '/app/api/contact.php'); ?>" novalidate>
            <div class="contact-form__grid">
                <div class="contact-form__field">
                    <label for="contact-name">Nombre</label>
                    <input id="contact-name" name="name" type="text" required maxlength="120" autocomplete="name">
                </div>

                <div class="contact-form__field">
                    <label for="contact-email">Correo electrónico</label>
                    <input id="contact-email" name="email" type="email" required maxlength="180" autocomplete="email">
                </div>

                <div class="contact-form__field">
                    <label for="contact-phone">Teléfono <span>(opcional)</span></label>
                    <input id="contact-phone" name="phone" type="tel" maxlength="40" autocomplete="tel">
                </div>

                <div class="contact-form__field contact-form__field--full">
                    <label for="contact-message">Mensaje</label>
                    <textarea id="contact-message" name="message" rows="6" required maxlength="2000"></textarea>
                </div>
            </div>

            <div class="contact-form__actions">
                <button type="submit" class="button" id="contact-submit">Enviar mensaje</button>
            </div>

            <p class="contact-form__status" id="contact-status" aria-live="polite"></p>
        </form>
    </div>
</section>
