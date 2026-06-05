<?php
declare(strict_types=1);
?>
<section class="section container assistant-page" aria-label="Asistente Virtual USO">
    <div class="assistant-layout">
        <aside class="assistant-visual" aria-hidden="true">
            <img class="assistant-visual__bot" src="<?php echo e(asset('img/uso-assistant-bot.svg')); ?>" alt="">
        </aside>

        <div class="chatbot" id="uso-chatbot" data-endpoint="<?php echo e(BASE_URL . '/app/api/assistant.php'); ?>">
            <div class="chatbot__messages" id="chatbot-messages" role="log" aria-live="polite" aria-label="Conversación del asistente">
                <article class="chatbot__message chatbot__message--bot">
                    <p>Hola, soy el Asistente Virtual USO. ¿En qué puedo ayudarte hoy?</p>
                </article>
            </div>

            <div class="chatbot__quick-actions" aria-label="Consultas rápidas">
                <button type="button" class="chatbot__chip" data-prompt="telefonos guardia">Teléfonos de guardia</button>
                <button type="button" class="chatbot__chip" data-prompt="contacto">Correos electrónicos y teléfonos</button>
                <button type="button" class="chatbot__chip" data-prompt="enlaces">Enlaces</button>
                <button type="button" class="chatbot__chip" data-prompt="he tenido un accidente">He tenido un accidente</button>
                <button type="button" class="chatbot__chip" data-prompt="documentos">Documentos</button>
                <button type="button" class="chatbot__chip" data-prompt="calendarios">Calendarios</button>
            </div>

            <form class="chatbot__form" id="chatbot-form">
                <label class="sr-only" for="chatbot-input">Escribe tu consulta</label>
                <textarea id="chatbot-input" class="chatbot__input" rows="2" maxlength="900" placeholder="Escribe tu consulta aquí..." required></textarea>
                <button class="button chatbot__submit" type="submit">Enviar</button>
            </form>

            <p class="chatbot__status" id="chatbot-status" aria-live="polite">Listo para responder.</p>
        </div>
    </div>

    <div class="auth-modal" id="auth-modal" hidden aria-hidden="true">
        <div class="auth-modal__backdrop" data-auth-close="true"></div>
        <div class="auth-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="auth-modal-title">
            <h2 id="auth-modal-title" class="auth-modal__title">Asesor con IA para afiliados</h2>
            <p class="auth-modal__text" id="auth-modal-text">El uso del asesor con IA es una funcionalidad exclusiva para afiliados.</p>

            <form id="auth-modal-form" class="auth-modal__form">
                <label for="auth-security-code">Código de seguridad</label>
                <input id="auth-security-code" name="securityCode" type="password" autocomplete="off" maxlength="50" required>
                <p class="auth-modal__status" id="auth-modal-status" aria-live="polite"></p>

                <div class="auth-modal__actions">
                    <button type="submit" class="button" id="auth-modal-submit">Validar código</button>
                    <button type="button" class="button button--ghost" data-auth-close="true">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</section>
