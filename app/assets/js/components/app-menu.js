(function() {
    async function injectMenu(container) {
        const menuSrc = container.getAttribute("data-menu-src") || "menu.html";
        const activeKey = container.getAttribute("data-menu-active") || "";

        try {
            const response = await fetch(menuSrc, { cache: "no-cache" });

            if (!response.ok) {
                throw new Error("No se pudo cargar el menú compartido.");
            }

            const markup = await response.text();
            const template = document.createElement("template");
            template.innerHTML = markup.trim();

            template.content.querySelectorAll("[data-menu-key]").forEach(function(item) {
                item.classList.toggle("active", item.getAttribute("data-menu-key") === activeKey);
            });

            container.replaceWith(template.content);
        } catch (error) {
            console.error(error);
            container.remove();
        }
    }

    function initSharedMenu() {
        document.querySelectorAll("[data-app-menu]").forEach(function(container) {
            injectMenu(container);
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initSharedMenu);
    } else {
        initSharedMenu();
    }
})();