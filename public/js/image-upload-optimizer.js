(() => {
    const inputs = document.querySelectorAll('input[type="file"][data-optimize-image]');

    if (!inputs.length) {
        return;
    }

    const createOptimizedBlob = (canvas, file, outputType, quality) => new Promise((resolve) => {
        canvas.toBlob((blob) => {
            resolve(blob ?? file);
        }, outputType, quality);
    });

    const readFile = (file) => new Promise((resolve, reject) => {
        const reader = new FileReader();

        reader.onload = () => resolve(reader.result);
        reader.onerror = () => reject(new Error('No se pudo leer la imagen.'));
        reader.readAsDataURL(file);
    });

    const loadImage = (source) => new Promise((resolve, reject) => {
        const image = new Image();

        image.onload = () => resolve(image);
        image.onerror = () => reject(new Error('No se pudo procesar la imagen.'));
        image.src = source;
    });

    const fitInside = (width, height, maxWidth, maxHeight) => {
        const ratio = Math.min(maxWidth / width, maxHeight / height, 1);

        return {
            width: Math.max(1, Math.round(width * ratio)),
            height: Math.max(1, Math.round(height * ratio)),
        };
    };

    const optimizeImage = async (input, file) => {
        const maxWidth = Number(input.dataset.maxWidth || 1600);
        const maxHeight = Number(input.dataset.maxHeight || maxWidth);
        const quality = Number(input.dataset.quality || 0.82);
        const outputPreference = input.dataset.output || 'webp';

        if (!file.type.startsWith('image/') || file.type === 'image/svg+xml' || file.type === 'image/gif') {
            return file;
        }

        const source = await readFile(file);
        const image = await loadImage(source);
        const targetSize = fitInside(image.naturalWidth, image.naturalHeight, maxWidth, maxHeight);

        if (!targetSize.width || !targetSize.height) {
            return file;
        }

        const canvas = document.createElement('canvas');
        canvas.width = targetSize.width;
        canvas.height = targetSize.height;

        const context = canvas.getContext('2d', { alpha: true });

        if (!context) {
            return file;
        }

        context.drawImage(image, 0, 0, targetSize.width, targetSize.height);

        let outputType = file.type;

        if (outputPreference === 'webp') {
            outputType = 'image/webp';
        } else if (outputPreference === 'jpeg') {
            outputType = 'image/jpeg';
        }

        const optimizedBlob = await createOptimizedBlob(canvas, file, outputType, quality);

        if (!(optimizedBlob instanceof Blob) || optimizedBlob.size >= file.size) {
            return file;
        }

        const extension = outputType === 'image/webp'
            ? 'webp'
            : outputType === 'image/jpeg'
                ? 'jpg'
                : file.name.split('.').pop();

        const fileName = `${file.name.replace(/\.[^.]+$/, '')}.${extension}`;

        return new File([optimizedBlob], fileName, {
            type: outputType,
            lastModified: Date.now(),
        });
    };

    const setBusyState = (input, busy, message = '') => {
        input.dataset.optimizing = busy ? 'true' : 'false';

        let helper = input.parentElement?.querySelector('[data-image-optimize-status]');

        if (!helper) {
            helper = document.createElement('div');
            helper.dataset.imageOptimizeStatus = 'true';
            helper.style.fontSize = '13px';
            helper.style.color = '#6b7280';
            helper.style.marginTop = '-4px';
            helper.style.marginBottom = '12px';
            input.insertAdjacentElement('afterend', helper);
        }

        helper.textContent = message;
    };

    inputs.forEach((input) => {
        input.addEventListener('change', async () => {
            const [file] = input.files ?? [];

            if (!file) {
                setBusyState(input, false, '');
                return;
            }

            setBusyState(input, true, 'Optimizando imagen antes de subir...');

            try {
                const optimizedFile = await optimizeImage(input, file);
                const transfer = new DataTransfer();
                transfer.items.add(optimizedFile);
                input.files = transfer.files;

                if (optimizedFile === file) {
                    setBusyState(input, false, 'La imagen se mantuvo original porque ya estaba optimizada o no se pudo reducir más.');
                    return;
                }

                const savedKb = Math.max(0, Math.round((file.size - optimizedFile.size) / 1024));
                setBusyState(input, false, `Imagen optimizada. Reduccion aproximada: ${savedKb} KB.`);
            } catch (error) {
                setBusyState(input, false, 'No se pudo optimizar automaticamente. Se subira la imagen original.');
            }
        });
    });
})();
