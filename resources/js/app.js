import './bootstrap';

import $ from 'jquery';

window.$ = window.jQuery = $;

import 'bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

import './ButtonLoad.js';

import './print.js';

const applySidebarState = (collapsed) => {
    document.body.classList.toggle('sidebar-collapsed', collapsed);

    const toggle = document.getElementById('sidebarToggle');
    const icon = toggle?.querySelector('i');

    if (!toggle || !icon) {
        return;
    }

    toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    icon.classList.toggle('bi-layout-sidebar-inset', !collapsed);
    icon.classList.toggle('bi-list', collapsed);
};

document.addEventListener('DOMContentLoaded', () => {
    if (window.__fayhosSidebarToggleBound) {
        return;
    }

    window.__fayhosSidebarToggleBound = true;

    const toggle = document.getElementById('sidebarToggle');

    if (!toggle) {
        return;
    }

    applySidebarState(localStorage.getItem('sidebar-collapsed') === 'true');

    toggle.addEventListener('click', () => {
        const collapsed = !document.body.classList.contains('sidebar-collapsed');

        localStorage.setItem('sidebar-collapsed', collapsed ? 'true' : 'false');
        applySidebarState(collapsed);
    });
});
