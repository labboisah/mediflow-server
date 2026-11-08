import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = window.Alpine || Alpine;

if (!window.__mediflowModernAlpineStarted) {
    window.__mediflowModernAlpineStarted = true;
    Alpine.start();
}

window.mediflowToast = (message, type = 'success') => {
    window.dispatchEvent(new CustomEvent('mediflow-toast', {
        detail: { message, type },
    }));
};

document.addEventListener('livewire:init', () => {
    Livewire.on('toast', (event) => {
        window.mediflowToast(event.message, event.type || 'success');
    });
});
