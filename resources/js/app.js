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
