(function () {
    const page = document.querySelector('.cart-page');

    if (!page) {
        return;
    }

    const feedback = document.getElementById('cartFeedback');
    const totalEl = document.querySelector('[data-role="total"]');
    const grandTotalEl = document.querySelector('[data-role="grand-total"]');
    const shippingTotalEl = document.querySelector('[data-role="shipping-total"]');
    const shippingOptions = Array.from(document.querySelectorAll('[data-shipping-option]'));
    const departmentSelect = document.querySelector('[data-department-select]');
    const cityInput = document.querySelector('[data-city-input]');
    const cityOptions = cityInput?.matches('select') ? Array.from(cityInput.options) : [];
    const localDeliveryPreview = document.querySelector('[data-local-delivery-preview]');
    const localDeliveryLabel = document.querySelector('[data-local-delivery-label]');
    const localDeliveryPrice = document.querySelector('[data-local-delivery-price]');
    const clearCartButton = document.getElementById('clearCartButton');
    const csrfToken = page.dataset.csrf || '';
    const updatedText = page.dataset.feedbackUpdated || 'Carrito actualizado';
    const updateErrorText = page.dataset.feedbackUpdateError || 'No se pudo actualizar el carrito.';
    const emptyErrorText = page.dataset.feedbackEmptyError || 'No se pudo vaciar el carrito.';
    const storeSlug = page.dataset.storeSlug || '';
    const freeShippingMinimum = Number(page.dataset.freeShippingMinimum || 0);
    const localDeliveryEnabled = page.dataset.localDeliveryEnabled === '1';
    const localDeliveryArea = page.dataset.localDeliveryArea || '';
    const localDeliveryCityCode = page.dataset.localDeliveryCityCode || '';
    const localDeliveryCost = Number(page.dataset.localDeliveryCost || 0);
    const outsideDeliveryCost = Number(page.dataset.outsideDeliveryCost || 0);
    let subtotal = Number(page.dataset.cartSubtotal || 0);
    let feedbackTimer;

    const money = (value) => `$ ${new Intl.NumberFormat('es-CO').format(value || 0)}`;
    const freeShippingApplies = () => freeShippingMinimum > 0 && subtotal >= freeShippingMinimum;
    const normalizeArea = (value) => String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9\s-]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
    const selectedCityName = () => {
        if (!cityInput) {
            return '';
        }

        if (!cityInput.matches('select')) {
            return cityInput.value;
        }

        return cityInput.selectedOptions[0]?.dataset.cityName || '';
    };
    const selectedCityCode = () => {
        if (!cityInput?.matches('select')) {
            return '';
        }

        return cityInput.value || '';
    };
    const isLocalDeliveryCity = () => {
        const cityCode = selectedCityCode();

        if (localDeliveryCityCode && cityCode) {
            return localDeliveryCityCode === cityCode;
        }

        return normalizeArea(selectedCityName()) === normalizeArea(localDeliveryArea);
    };
    const hasSelectedCity = () => normalizeArea(selectedCityName()) !== '';

    const showFeedback = (message) => {
        if (!feedback) return;
        feedback.textContent = message;
        feedback.classList.add('is-visible');
        clearTimeout(feedbackTimer);
        feedbackTimer = setTimeout(() => feedback.classList.remove('is-visible'), 1800);
    };

    const shippingCost = () => {
        if (localDeliveryEnabled) {
            if (!hasSelectedCity()) {
                return 0;
            }

            const baseCost = isLocalDeliveryCity()
                ? localDeliveryCost
                : outsideDeliveryCost;

            return freeShippingApplies() ? 0 : baseCost;
        }

        const selected = shippingOptions.find((option) => option.checked);

        if (!selected) {
            return 0;
        }

        const baseCost = Number(selected.dataset.shippingCost || 0);

        return freeShippingApplies() ? 0 : baseCost;
    };

    const updateShippingLabels = () => {
        if (localDeliveryEnabled) {
            if (!localDeliveryLabel || !localDeliveryPrice) {
                return;
            }

            if (!hasSelectedCity()) {
                localDeliveryLabel.textContent = 'Envio por ciudad';
                localDeliveryPrice.textContent = 'Por calcular';
                return;
            }

            const isLocal = isLocalDeliveryCity();
            const baseCost = isLocal ? localDeliveryCost : outsideDeliveryCost;
            const nextCost = freeShippingApplies() ? 0 : baseCost;
            localDeliveryLabel.textContent = isLocal
                ? `Envio local: ${localDeliveryArea}`
                : `Envio fuera de ${localDeliveryArea}`;
            localDeliveryPrice.textContent = nextCost > 0 ? money(nextCost) : 'Gratis';
            localDeliveryPreview?.classList.toggle('is-local', isLocal);
            return;
        }

        shippingOptions.forEach((option) => {
            const price = option.closest('.shipping-option')?.querySelector('[data-shipping-price]');

            if (!price) {
                return;
            }

            const baseCost = Number(option.dataset.shippingCost || 0);
            const nextCost = freeShippingApplies() ? 0 : baseCost;
            price.textContent = nextCost > 0 ? money(nextCost) : 'Gratis';
        });
    };

    const updateSummary = (data = null) => {
        if (data && typeof data.total !== 'undefined') {
            subtotal = Number(data.total || 0);
            page.dataset.cartSubtotal = String(subtotal);
        }

        const cost = shippingCost();
        const awaitingCity = localDeliveryEnabled && !hasSelectedCity();

        updateShippingLabels();
        if (totalEl) totalEl.textContent = money(subtotal);
        if (shippingTotalEl) shippingTotalEl.textContent = awaitingCity ? 'Por calcular' : (cost > 0 ? money(cost) : 'Gratis');
        if (grandTotalEl) grandTotalEl.textContent = money(subtotal + (awaitingCity ? 0 : cost));
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

    shippingOptions.forEach((option) => {
        option.addEventListener('change', () => updateSummary());
    });

    const syncCityOptions = () => {
        if (!departmentSelect || !cityInput?.matches('select')) {
            return;
        }

        const department = departmentSelect.value;

        cityInput.disabled = department === '';

        cityOptions.forEach((option) => {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            option.hidden = department !== '' && option.dataset.department !== department;
        });

        if (cityInput.selectedOptions[0]?.hidden) {
            cityInput.value = '';
        }

        updateSummary();
    };

    departmentSelect?.addEventListener('change', syncCityOptions);
    cityInput?.addEventListener('input', () => updateSummary());
    cityInput?.addEventListener('change', () => updateSummary());

    syncCityOptions();
    updateSummary();
})();
