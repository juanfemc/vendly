(function () {
    const panels = document.querySelectorAll('[data-ai-panel]');

    if (!panels.length) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const fieldValue = (form, name) => form?.querySelector(`[name="${name}"]`)?.value || '';
    const imageTypeLabels = {
        store_cover_image: 'portada',
        product_image: 'imagen ecommerce',
    };

    const setStatus = (panel, message, isError = false) => {
        const status = panel.querySelector('[data-ai-status]');

        if (!status) {
            return;
        }

        status.textContent = message;
        status.classList.toggle('is-error', isError);
    };

    const updateCreditBalance = (panel, data) => {
        const balance = panel.querySelector('[data-ai-credit-balance]');

        if (balance && data.ai_credits && typeof data.ai_credits.balance !== 'undefined') {
            balance.textContent = data.ai_credits.balance;
        }
    };

    const productPayload = (panel, type) => {
        const form = panel.closest('form');

        return {
            type,
            store_id: panel.dataset.storeId || fieldValue(form, 'store_id'),
            product_id: panel.dataset.productId || null,
            name: fieldValue(form, 'name'),
            category: fieldValue(form, 'category'),
            material: fieldValue(form, 'material'),
            price: fieldValue(form, 'price'),
            description: fieldValue(form, 'description'),
            features: fieldValue(form, 'features'),
        };
    };

    const announcementPayload = (panel, type) => ({
        type,
        store_id: panel.dataset.storeId,
        topic: Array.from(document.querySelectorAll('input[name^="announcement_items"]'))
            .map((input) => input.value.trim())
            .filter(Boolean)
            .join(' | '),
    });

    const applyProductResult = (panel, type, data) => {
        const form = panel.closest('form');

        if (type === 'product_name' && data.name) {
            form.querySelector('[name="name"]').value = data.name;
        }

        if (type === 'product_description' && data.description) {
            form.querySelector('[name="description"]').value = data.description;
        }

        if (type === 'product_badges' && Array.isArray(data.badges)) {
            const badges = form.querySelector('[name="custom_badges"]');

            if (badges) {
                badges.value = data.badges.join(', ');
            }
        }

        if (type === 'product_features' && Array.isArray(data.features)) {
            const input = form.querySelector('[name="features"]');
            const editor = form.querySelector('[data-rich-content]');
            const html = `<ul>${data.features.map((feature) => `<li>${escapeHtml(feature)}</li>`).join('')}</ul>`;

            if (input) {
                input.value = html;
            }

            if (editor) {
                editor.innerHTML = html;
            }
        }
    };

    const escapeHtml = (value) => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const applyAnnouncementResult = (data) => {
        const inputs = Array.from(document.querySelectorAll('input[name^="announcement_items"]'));
        const announcements = Array.isArray(data.announcements) ? data.announcements : [];

        announcements.forEach((announcement, index) => {
            if (inputs[index]) {
                inputs[index].value = announcement;
            }
        });
    };

    const setHiddenField = (form, name, value) => {
        let input = form.querySelector(`[name="${name}"]`);

        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            form.appendChild(input);
        }

        input.value = value;
    };

    const showImagePreview = (panel, data) => {
        const preview = panel.querySelector('[data-ai-preview]');

        if (!preview || !data.image_url) {
            return;
        }

        preview.hidden = false;
        preview.innerHTML = '';

        const image = document.createElement('img');
        image.src = data.image_url;
        image.alt = 'Imagen generada con IA';
        image.loading = 'lazy';

        const note = document.createElement('p');
        note.textContent = 'Vista previa generada. Guarda los cambios para publicarla.';

        preview.append(image, note);
    };

    const applyImageResult = (panel, type, data) => {
        const form = panel.closest('form');

        if (!form || !data.image_path) {
            return;
        }

        if (type === 'store_cover_image') {
            setHiddenField(form, 'ai_generated_cover_path', data.image_path);
        }

        if (type === 'product_image') {
            setHiddenField(form, 'ai_generated_image_path', data.image_path);
        }

        showImagePreview(panel, data);
    };

    const generate = async (panel, button, payload, endpoint, onSuccess, doneMessage) => {
        button.disabled = true;
        setStatus(panel, 'Generando con IA...');

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'No se pudo generar contenido.');
            }

            onSuccess(data);
            updateCreditBalance(panel, data);
            setStatus(panel, doneMessage);
        } catch (error) {
            setStatus(panel, error.message || 'No se pudo generar contenido.', true);
        } finally {
            button.disabled = false;
        }
    };

    panels.forEach((panel) => {
        panel.querySelectorAll('[data-ai-type]').forEach((button) => {
            button.addEventListener('click', async () => {
                const type = button.dataset.aiType;
                const isAnnouncements = panel.dataset.aiContext === 'announcements';
                const payload = isAnnouncements
                    ? announcementPayload(panel, type)
                    : productPayload(panel, type);

                generate(
                    panel,
                    button,
                    payload,
                    panel.dataset.aiEndpoint,
                    (data) => isAnnouncements ? applyAnnouncementResult(data) : applyProductResult(panel, type, data),
                    'Listo. Revisa el texto antes de guardar.'
                );
            });
        });

        panel.querySelectorAll('[data-ai-image-type]').forEach((button) => {
            button.addEventListener('click', async () => {
                const type = button.dataset.aiImageType;
                const payload = productPayload(panel, type);

                generate(
                    panel,
                    button,
                    payload,
                    panel.dataset.aiImageEndpoint,
                    (data) => applyImageResult(panel, type, data),
                    `Listo. Revisa la ${imageTypeLabels[type] || 'imagen'} antes de guardar.`
                );
            });
        });
    });
})();
