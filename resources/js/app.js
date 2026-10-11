if (document.querySelector('[data-3d-viewer]')) {
    import('./viewer.js');
}

document.querySelectorAll('input[name="selected_variant"]').forEach((input) => {
    input.addEventListener('change', (event) => {
        const selected = event.currentTarget;
        const checkoutLink = document.querySelector('#checkout-link');
        const viewer = document.querySelector('[data-3d-viewer]');

        if (checkoutLink) {
            checkoutLink.href = selected.dataset.checkoutUrl;
        }

        if (viewer) {
            viewer.dispatchEvent(new CustomEvent('model-color-change', { detail: selected.dataset.color }));
        }
    });
});

const quantityInput = document.querySelector('#quantity');
const subtotalLabel = document.querySelector('#checkout-subtotal');

if (quantityInput && subtotalLabel) {
    const updateSubtotal = () => {
        const quantity = Math.max(1, Number.parseInt(quantityInput.value || '1', 10));
        const unitPrice = Number.parseInt(subtotalLabel.dataset.unitPrice || '0', 10);
        subtotalLabel.textContent = `Rp ${(unitPrice * quantity).toLocaleString('id-ID')}`;
    };

    quantityInput.addEventListener('input', updateSubtotal);
    updateSubtotal();
}

const customizationType = document.querySelector('#customization_type');

if (customizationType) {
    const clickerSettings = document.querySelectorAll('[data-clicker-settings]');
    const updateClickerSettingsVisibility = () => {
        clickerSettings.forEach((section) => {
            section.classList.toggle('hidden', customizationType.value !== 'clicker');
        });
    };

    customizationType.addEventListener('change', updateClickerSettingsVisibility);
    updateClickerSettingsVisibility();
}

const clickerForm = document.querySelector('#clicker-customizer');

if (clickerForm) {
    const viewer = document.querySelector('[data-3d-viewer]');
    const nameInput = clickerForm.querySelector('#custom-name');
    const namePreview = clickerForm.querySelector('#custom-name-preview');
    const nameLength = clickerForm.querySelector('#name-length');
    const priceEstimate = document.querySelector('#custom-price-estimate');
    const updateNamePreview = () => {
        const characters = Array.from(nameInput.value.normalize('NFC').replace(/[^\p{L}\p{N}]/gu, ''));
        const value = characters.slice(0, nameInput.maxLength).join('');
        const characterCount = [...value].length;
        nameInput.value = value;
        const includedCharacters = Number.parseInt(priceEstimate?.dataset.includedCharacterCount || '4', 10);
        const additionalCharacters = Math.max(0, characterCount - includedCharacters);
        const estimatedPrice = Number.parseInt(priceEstimate?.dataset.basePrice || '0', 10)
            + additionalCharacters * Number.parseInt(priceEstimate?.dataset.additionalCharacterPrice || '0', 10);

        if (namePreview) namePreview.textContent = value || 'Nama kamu';
        if (nameLength) nameLength.textContent = String(characterCount);
        if (priceEstimate) priceEstimate.textContent = `Estimasi harga: Rp ${estimatedPrice.toLocaleString('id-ID')}`;
        if (viewer) {
            viewer.dataset.customName = value;
            viewer.dispatchEvent(new CustomEvent('keycap-name-change', { detail: value }));
        }
    };

    const updateViewerColors = () => {
        const colors = {};
        clickerForm.querySelectorAll('input[data-component]:checked').forEach((input) => {
            colors[input.dataset.component] = input.dataset.color;
        });

        if (viewer) {
            Object.entries(colors).forEach(([component, color]) => {
                viewer.dataset[`${component}Color`] = color;
            });
            viewer.dispatchEvent(new CustomEvent('component-color-change', { detail: colors }));
        }

        const nameColor = colors.name || '#6d7847';
        if (namePreview) namePreview.style.color = nameColor;
    };

    clickerForm.querySelectorAll('input[data-component]').forEach((input) => {
        input.addEventListener('change', updateViewerColors);
    });

    nameInput?.addEventListener('input', updateNamePreview);

    updateNamePreview();
    updateViewerColors();
}
