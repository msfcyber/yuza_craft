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

    nameInput?.addEventListener('input', () => {
        const value = nameInput.value.trim();
        if (namePreview) namePreview.textContent = value || 'Nama kamu';
        if (nameLength) nameLength.textContent = String([...nameInput.value].length);
        if (viewer) viewer.dataset.customName = value;
    });

    updateViewerColors();
}
