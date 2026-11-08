(function () {
                              

                const disableElement = (element) => {
                    if (!element || element.dataset.loading === 'true') {
                        return;
                    }

                    element.dataset.loading = 'true';

                    if (element.tagName === 'A') {
                        element.style.pointerEvents = 'none';
                        element.setAttribute('aria-disabled', 'true');
                        element.classList.add('disabled');
                        return;
                    }

                    element.disabled = true;
                };

                const setButtonLoading = (button, label) => {
                    if (!button || button.dataset.loading === 'true') {
                        return;
                    }

                    disableElement(button);
                    button.dataset.originalHtml = button.innerHTML;
                    button.innerHTML = `
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        ${label}
                    `;

                    setTimeout(() => {
                        if (button.dataset.originalHtml) {
                            button.innerHTML = button.dataset.originalHtml;
                            button.disabled = false;
                            delete button.dataset.loading;
                            delete button.dataset.originalHtml;
                        }
                    }, 2000);
                };

                const isActionableLink = (link) => {
                    if (!link || !link.href) {
                        return false;
                    }

                    const href = link.getAttribute('href');
                    if (!href || href.startsWith('#') || href.startsWith('javascript:') || href === 'mailto:' || href === 'tel:') {
                        return false;
                    }

                    if (link.target && link.target !== '_self') {
                        return false;
                    }

                    if (link.hasAttribute('download')) {
                        return false;
                    }

                    return link.hostname === window.location.hostname;
                };

                document.addEventListener('DOMContentLoaded', function () {
                    hidePageLoader();

                    document.querySelectorAll('a').forEach(link => {
                        if (!isActionableLink(link)) {
                            return;
                        }

                        link.addEventListener('click', function () {
                            disableElement(link);
                            showPageLoader();

                            if (typeof NProgress !== 'undefined') {
                                NProgress.start();
                            }
                        });
                    });

                    document.querySelectorAll('button, input[type="submit"], input[type="button"], input[type="reset"]').forEach(control => {
                        control.addEventListener('click', function (event) {
                            const button = event.currentTarget;

                            if (button.matches('[data-bs-toggle], [data-toggle], .dropdown-toggle')) {
                                return;
                            }

                            if (button.closest('form') && (button.type === 'submit' || button.getAttribute('type') === 'submit')) {
                                return;
                            }

                            setButtonLoading(button, button.dataset.loadingText || 'Loading...');
                        });
                    });

                    document.querySelectorAll('form').forEach(form => {
                        form.addEventListener('submit', function () {
                            const button = form.querySelector("button[type='submit'], input[type='submit']");
                            if (button) {
                                setButtonLoading(button, button.dataset.loadingText || 'Processing...');
                            }
                        });
                    });
                });

                // Handle both normal load and cache restoration (back button)
                window.addEventListener('load', function () {
                    hidePageLoader();
                    if (typeof NProgress !== 'undefined') {
                        NProgress.done();
                    }
                });

                // Handle pageshow event (fires on load and cache restoration)
                window.addEventListener('pageshow', function (event) {
                    // Hide loader immediately when page is shown from cache or normal load
                    hidePageLoader();
                    if (typeof NProgress !== 'undefined') {
                        NProgress.done();
                    }
                    // If this is a cached page restoration, ensure loader is hidden
                    if (event.persisted) {
                        loaderHideTimeout = setTimeout(hidePageLoader, 50);
                    }
                });

                // Handle pagehide event to prepare for navigation
                window.addEventListener('pagehide', function () {
                    // Clear any pending operations
                    if (loaderHideTimeout) {
                        clearTimeout(loaderHideTimeout);
                    }
                });

                // Safety timeout: hide loader if still visible after 3 seconds
                loaderHideTimeout = setTimeout(hidePageLoader, 3000);
            })();