(function () {
    const page = document.querySelector('.cart-page');

    if (!page) {
        return;
    }

    const feedback = document.getElementById('cartFeedback');
    const totalEl = document.querySelector('[data-role="total"]');
    const clearCartButton = document.getElementById('clearCartButton');
    const csrfToken = page.dataset.csrf || '';
    const updatedText = page.dataset.feedbackUpdated || 'Carrito actualizado';
    const updateErrorText = page.dataset.feedbackUpdateError || 'No se pudo actualizar el carrito.';
    const emptyErrorText = page.dataset.feedbackEmptyError || 'No se pudo vaciar el carrito.';
    let feedbackTimer;

    const money = (value) => `$ ${new Intl.NumberFormat('es-CO').format(value || 0)}`;

    const showFeedback = (message) => {
        if (!feedback) return;
        feedback.textContent = message;
        feedback.classList.add('is-visible');
        clearTimeout(feedbackTimer);
        feedbackTimer = setTimeout(() => feedback.classList.remove('is-visible'), 1800);
    };

    const updateSummary = (data) => {
        if (totalEl) totalEl.textContent = money(data.total);
    };

    const sendCartRequest = async (url, method, body = null) => {
        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        };

        if (body) {
            headers['Content-Type'] = 'application/json';
        }

        const response = await fetch(url, {
            method,
            headers,
            body,
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || updateErrorText);
        }

        return data;
    };

    document.querySelectorAll('[data-action]').forEach((button) => {
        button.addEventListener('click', async () => {
            const productId = button.dataset.productId;
            const item = document.querySelector(`[data-cart-item="${productId}"]`);

            if (!item) return;

            const qtyEl = item.querySelector('[data-role="quantity"]');
            const itemTotalEl = item.querySelector('[data-role="item-total"]');
            const currentQty = Number(qtyEl?.textContent || 1);

            try {
                let data;

                if (button.dataset.action === 'remove') {
                    data = await sendCartRequest(`/cart/item/${encodeURIComponent(productId)}`, 'DELETE');
                    item.remove();
                } else {
                    const nextQty = button.dataset.action === 'increase' ? currentQty + 1 : currentQty - 1;

                    if (nextQty < 1) {
                        data = await sendCartRequest(`/cart/item/${encodeURIComponent(productId)}`, 'DELETE');
                        item.remove();
                    } else {
                        data = await sendCartRequest(`/cart/item/${encodeURIComponent(productId)}`, 'PATCH', JSON.stringify({ quantity: nextQty }));
                        if (qtyEl) qtyEl.textContent = nextQty;
                        if (itemTotalEl && data.item_total !== null) {
                            itemTotalEl.textContent = money(data.item_total);
                        }
                    }
                }

                updateSummary(data);
                showFeedback(data.message || updatedText);

                if (data.cart_is_empty) {
                    window.location.reload();
                }
            } catch (error) {
                showFeedback(error.message || updateErrorText);
            }
        });
    });

    if (clearCartButton) {
        clearCartButton.addEventListener('click', async () => {
            try {
                const data = await sendCartRequest('/cart', 'DELETE');
                updateSummary(data);
                showFeedback(data.message || updatedText);

                if (data.cart_is_empty) {
                    window.location.reload();
                }
            } catch (error) {
                showFeedback(error.message || emptyErrorText);
            }
        });
    }
})();
