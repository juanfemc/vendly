(() => {
    const inputs = document.querySelectorAll('[data-product-image-preview]');

    if (!inputs.length) {
        return;
    }

    const renderPreview = (input) => {
        const preview = document.getElementById(input.dataset.previewTarget || '');

        if (!preview) {
            return;
        }

        preview.innerHTML = '';
        const files = Array.from(input.files ?? []);
        preview.hidden = files.length === 0;

        files.forEach((file) => {
            if (!file.type.startsWith('image/')) {
                return;
            }

            const item = document.createElement('div');
            item.className = 'product-image-preview-item';

            const image = document.createElement('img');
            image.alt = file.name;
            image.src = URL.createObjectURL(file);
            image.onload = () => URL.revokeObjectURL(image.src);

            const caption = document.createElement('span');
            caption.textContent = file.name;

            item.append(image, caption);
            preview.appendChild(item);
        });
    };

    inputs.forEach((input) => {
        input.addEventListener('change', () => renderPreview(input));
        input.addEventListener('image-upload:optimized', () => renderPreview(input));
    });
})();
