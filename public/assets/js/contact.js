(function () {
    var form = document.getElementById('contact-form');
    if (!form) {
        return;
    }

    var endpoint = form.getAttribute('data-endpoint') || '';
    var submitButton = document.getElementById('contact-submit');
    var statusNode = document.getElementById('contact-status');

    function setStatus(text, type) {
        statusNode.textContent = text || '';
        statusNode.classList.remove('is-error', 'is-success');
        if (type === 'error') {
            statusNode.classList.add('is-error');
        }
        if (type === 'success') {
            statusNode.classList.add('is-success');
        }
    }

    function setPending(isPending) {
        submitButton.disabled = isPending;
        submitButton.classList.toggle('is-loading', isPending);
        submitButton.textContent = isPending ? 'Enviando' : 'Enviar mensaje';
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        var formData = new FormData(form);
        var payload = {
            name: String(formData.get('name') || '').trim(),
            email: String(formData.get('email') || '').trim(),
            phone: String(formData.get('phone') || '').trim(),
            message: String(formData.get('message') || '').trim()
        };

        if (!payload.name || !payload.email || !payload.message) {
            setStatus('Nombre, correo electrónico y mensaje son obligatorios.', 'error');
            return;
        }

        setStatus('');
        setPending(true);

        try {
            var response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            var result = await response.json();
            if (!response.ok || !result.success) {
                setStatus(result.message || 'No se pudo enviar el mensaje.', 'error');
                return;
            }

            setStatus(result.message || 'Mensaje enviado correctamente.', 'success');
            form.reset();
        } catch (error) {
            setStatus('Error de red al enviar el mensaje. Inténtalo de nuevo.', 'error');
        } finally {
            setPending(false);
        }
    });
})();
