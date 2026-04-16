(function() {
    let activeModal = null;

    function findFirstField(modal) {
        return modal.querySelector("input:not([type='hidden']):not([disabled]), select:not([disabled]), textarea:not([disabled])");
    }

    function syncVariantState(modal) {
        if (!modal) {
            return;
        }

        const variantSwitch = modal.querySelector("[data-modal-variant-switch]");
        if (!variantSwitch) {
            return;
        }

        const dialog = modal.querySelector(".app-modal__dialog");
        const hiddenInput = modal.querySelector("[data-modal-type-input]");
        const labels = modal.querySelectorAll("[data-switch-label]");
        const checkedValue = variantSwitch.getAttribute("data-checked-value") || "egreso";
        const uncheckedValue = variantSwitch.getAttribute("data-unchecked-value") || "ingreso";
        const currentValue = variantSwitch.checked ? checkedValue : uncheckedValue;

        if (hiddenInput) {
            hiddenInput.value = currentValue;
        }

        if (dialog) {
            dialog.classList.toggle("is-expense", currentValue === "egreso");
        }

        labels.forEach(function(label) {
            label.classList.toggle("is-active", label.getAttribute("data-switch-label") === currentValue);
        });
    }

    function resetVariantState(modal) {
        if (!modal) {
            return;
        }

        const variantSwitch = modal.querySelector("[data-modal-variant-switch]");
        if (!variantSwitch) {
            return;
        }

        variantSwitch.checked = variantSwitch.getAttribute("data-default-checked") === "true";
        syncVariantState(modal);
    }

    function openModal(modal, opener) {
        if (!modal) {
            return;
        }

        // Resetear posición al abrir
        const dialog = modal.querySelector(".app-modal__dialog");
        if (dialog) {
            dialog.style.left = "";
            dialog.style.top = "";
            dialog.style.transform = "";
        }

        activeModal = modal;
        modal.__opener = opener || document.activeElement;
        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");
        document.body.classList.add("modal-open");

        const firstField = findFirstField(modal);
        if (firstField) {
            firstField.focus();
        }
    }

    function closeModal(modal) {
        if (!modal || modal.classList.contains("is-closing")) {
            return;
        }

        const dialog = modal.querySelector(".app-modal__dialog");
        const wasDragged = dialog && dialog.style.left !== "";

        if (dialog) {
            // Limpiar animation inline puesto por el drag, si no el CSS de cierre no se aplica
            dialog.style.animation = "";

            if (wasDragged) {
                // slideOut asume translateX(-50%) y rompe si fue arrastrado: usar solo fade
                dialog.classList.add("is-fading-out");
            }
        }

        modal.classList.add("is-closing");

        setTimeout(function() {
            modal.classList.remove("is-open", "is-closing");
            modal.setAttribute("aria-hidden", "true");

            if (dialog) {
                dialog.classList.remove("is-fading-out");
            }

            if (activeModal === modal) {
                activeModal = null;
                document.body.classList.remove("modal-open");
            }

            if (modal.__opener && typeof modal.__opener.focus === "function") {
                modal.__opener.focus();
            }
        }, 200);
    }

    function makeDraggable(modal) {
        const dialog = modal.querySelector(".app-modal__dialog");
        const header = modal.querySelector(".app-modal__header");
        if (!dialog || !header) {
            return;
        }

        let startX, startY, startLeft, startTop;
        let dragging = false;

        header.addEventListener("mousedown", function(e) {
            if (e.target.closest("[data-close-modal]") || e.target.closest("[data-modal-variant-switch]")) {
                return;
            }

            // Detener cualquier animación CSS activa antes de mover
            dialog.style.animation = "none";

            dragging = true;
            header.classList.add("is-dragging");

            const rect = dialog.getBoundingClientRect();
            startX = e.clientX;
            startY = e.clientY;
            startLeft = rect.left;
            startTop = rect.top;

            // Fijar posición actual y quitar transform centrado
            dialog.style.left = startLeft + "px";
            dialog.style.top = startTop + "px";
            dialog.style.transform = "none";

            e.preventDefault();
        });

        document.addEventListener("mousemove", function(e) {
            if (!dragging) {
                return;
            }

            const dx = e.clientX - startX;
            const dy = e.clientY - startY;

            const newLeft = Math.max(0, Math.min(window.innerWidth - dialog.offsetWidth, startLeft + dx));
            const newTop = Math.max(0, Math.min(window.innerHeight - dialog.offsetHeight, startTop + dy));

            dialog.style.left = newLeft + "px";
            dialog.style.top = newTop + "px";
        });

        document.addEventListener("mouseup", function() {
            if (!dragging) {
                return;
            }

            dragging = false;
            header.classList.remove("is-dragging");
        });
    }

    function bindModal(modal) {
        if (!modal || modal.dataset.modalReady === "true") {
            return;
        }

        modal.dataset.modalReady = "true";

        modal.querySelectorAll("[data-close-modal]").forEach(function(button) {
            button.addEventListener("click", function() {
                if (button.classList.contains("app-modal__backdrop")) {
                    return;
                }

                closeModal(modal);
            });
        });

        makeDraggable(modal);

        const variantSwitch = modal.querySelector("[data-modal-variant-switch]");
        if (variantSwitch) {
            syncVariantState(modal);
            variantSwitch.addEventListener("change", function() {
                syncVariantState(modal);
            });
        }
    }

    function init() {
        document.querySelectorAll(".app-modal[data-modal]").forEach(function(modal) {
            bindModal(modal);
        });

        document.querySelectorAll("[data-modal-target]").forEach(function(trigger) {
            if (trigger.dataset.modalTriggerReady === "true") {
                return;
            }

            trigger.dataset.modalTriggerReady = "true";
            trigger.addEventListener("click", function() {
                const targetSelector = trigger.getAttribute("data-modal-target");
                if (!targetSelector) {
                    return;
                }

                openModal(document.querySelector(targetSelector), trigger);
            });
        });
    }

    document.addEventListener("keydown", function(event) {
        if (event.key === "Escape" && activeModal) {
            closeModal(activeModal);
        }
    });

    window.AppModal = {
        close: closeModal,
        init: init,
        open: openModal,
        resetVariantState: resetVariantState,
        syncVariantState: syncVariantState
    };

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();