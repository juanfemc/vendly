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
    const storeSlug = page.dataset.storeSlug || '';
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

    const updateItemQuantity = (item, quantity) => {
        const qtyEl = item.querySelector('[data-role="quantity"]');
        const qtyBadge = item.querySelector('[data-role="quantity-badge"]');

        if (qtyEl) qtyEl.textContent = quantity;
        if (qtyBadge) qtyBadge.textContent = quantity;
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

    page.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-action]');

        if (!button) {
            return;
        }

        event.preventDefault();

            const productId = button.dataset.productId;
            const item = button.closest('[data-cart-item]');

            if (!item) return;

            const qtyEl = item.querySelector('[data-role="quantity"]');
            const itemTotalEl = item.querySelector('[data-role="item-total"]');
            const currentQty = Number(qtyEl?.textContent || 1);
            const originalQty = currentQty;

            try {
                let data;
                button.disabled = true;

                if (button.dataset.action === 'remove') {
                    data = await sendCartRequest(`/cart/item/${encodeURIComponent(productId)}`, 'DELETE');
                    item.remove();
                } else {
                    const nextQty = button.dataset.action === 'increase' ? currentQty + 1 : currentQty - 1;

                    if (nextQty < 1) {
                        data = await sendCartRequest(`/cart/item/${encodeURIComponent(productId)}`, 'DELETE');
                        item.remove();
                    } else {
                        updateItemQuantity(item, nextQty);
                        data = await sendCartRequest(`/cart/item/${encodeURIComponent(productId)}`, 'PATCH', JSON.stringify({ quantity: nextQty }));
                        updateItemQuantity(item, data.item_quantity || nextQty);
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
                updateItemQuantity(item, originalQty);
                showFeedback(error.message || updateErrorText);
            } finally {
                button.disabled = false;
            }
    });

    if (clearCartButton) {
        clearCartButton.addEventListener('click', async () => {
            try {
                const clearUrl = storeSlug ? `/cart?store=${encodeURIComponent(storeSlug)}` : '/cart';
                const data = await sendCartRequest(clearUrl, 'DELETE');
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
