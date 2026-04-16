(function() {
    function initTransactionsTable() {
        const table = document.getElementById("transactionsTable");
        if (!table) {
            return;
        }

        const tbody = table.querySelector("tbody");
        const headers = Array.from(table.querySelectorAll("thead th"));
        const allRows = Array.from(tbody.querySelectorAll("tr:not(#txEmptyState)"));
        const emptyStateRow = document.getElementById("txEmptyState");
        const searchInput = document.getElementById("txSearch");
        const pageSizeSelect = document.getElementById("txPageSize");
        const info = document.getElementById("txInfo");
        const pagination = document.getElementById("txPagination");
        const pageSizeStorageKey = "txTablePageSize";
        const allowedPageSizes = [25, 50, 75, 100];

        let filteredRows = [].concat(allRows);
        let currentPage = 1;
        let pageSize = 25;

        function getInitialPageSize() {
            const stored = window.localStorage ? parseInt(localStorage.getItem(pageSizeStorageKey) || "", 10) : NaN;
            if (allowedPageSizes.indexOf(stored) !== -1) {
                return stored;
            }
            return 25;
        }

        function savePageSize(value) {
            if (!window.localStorage) {
                return;
            }

            localStorage.setItem(pageSizeStorageKey, String(value));
        }

        function normalizeText(value) {
            return value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        }

        function applyCellLabels() {
            allRows.forEach(function(row) {
                const cells = Array.from(row.querySelectorAll("td"));
                cells.forEach(function(cell, index) {
                    const headerText = headers[index] ? headers[index].textContent.trim() : "Dato";
                    cell.setAttribute("data-label", headerText);
                });
            });
        }

        function filterRows(query) {
            if (!query) {
                filteredRows = [].concat(allRows);
                return;
            }

            const normalizedQuery = normalizeText(query);
            filteredRows = allRows.filter(function(row) {
                return normalizeText(row.innerText).includes(normalizedQuery);
            });
        }

        function renderPagination(totalPages) {
            pagination.innerHTML = "";

            if (totalPages <= 1) {
                return;
            }

            const prevBtn = document.createElement("button");
            prevBtn.type = "button";
            prevBtn.className = "page-btn";
            prevBtn.textContent = "Anterior";
            prevBtn.disabled = currentPage === 1;
            prevBtn.addEventListener("click", function() {
                if (currentPage > 1) {
                    currentPage -= 1;
                    renderTable();
                }
            });
            pagination.appendChild(prevBtn);

            for (let page = 1; page <= totalPages; page += 1) {
                const pageBtn = document.createElement("button");
                pageBtn.type = "button";
                pageBtn.className = "page-btn" + (page === currentPage ? " active" : "");
                pageBtn.textContent = String(page);
                pageBtn.addEventListener("click", function() {
                    currentPage = page;
                    renderTable();
                });
                pagination.appendChild(pageBtn);
            }

            const nextBtn = document.createElement("button");
            nextBtn.type = "button";
            nextBtn.className = "page-btn";
            nextBtn.textContent = "Siguiente";
            nextBtn.disabled = currentPage === totalPages;
            nextBtn.addEventListener("click", function() {
                if (currentPage < totalPages) {
                    currentPage += 1;
                    renderTable();
                }
            });
            pagination.appendChild(nextBtn);
        }

        function renderTable() {
            allRows.forEach(function(row) {
                row.classList.add("d-none");
            });

            const totalRecords = filteredRows.length;
            const totalPages = Math.max(1, Math.ceil(totalRecords / pageSize));

            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            if (totalRecords === 0) {
                emptyStateRow.classList.remove("d-none");
                info.textContent = "Mostrando 0 de 0 registros";
                renderPagination(0);
                return;
            }

            emptyStateRow.classList.add("d-none");

            const startIndex = (currentPage - 1) * pageSize;
            const endIndex = Math.min(startIndex + pageSize, totalRecords);
            const pageRows = filteredRows.slice(startIndex, endIndex);

            pageRows.forEach(function(row) {
                row.classList.remove("d-none");
            });

            info.textContent = "Mostrando " + (startIndex + 1) + " a " + endIndex + " de " + totalRecords + " registros";
            renderPagination(totalPages);
        }

        pageSize = getInitialPageSize();

        if (pageSizeSelect) {
            pageSizeSelect.value = String(pageSize);
            pageSizeSelect.addEventListener("change", function() {
                const selected = parseInt(this.value, 10);
                pageSize = allowedPageSizes.indexOf(selected) !== -1 ? selected : 25;
                savePageSize(pageSize);
                currentPage = 1;
                renderTable();
            });
        }

        if (searchInput) {
            searchInput.addEventListener("input", function() {
                currentPage = 1;
                filterRows(this.value.trim());
                renderTable();
            });
        }

        applyCellLabels();
        renderTable();
    }

    function initHeaderMonthNavigator() {
        const monthLabel = document.getElementById("headerMonthLabel");
        const monthPrevBtn = document.getElementById("headerMonthPrev");
        const monthNextBtn = document.getElementById("headerMonthNext");
        if (!monthLabel || !monthPrevBtn || !monthNextBtn) {
            return;
        }

        const monthNames = ["ENE", "FEB", "MAR", "ABR", "MAY", "JUN", "JUL", "AGO", "SEP", "OCT", "NOV", "DIC"];
        const now = new Date();
        let month = now.getMonth();
        let year = now.getFullYear();

        function updateLabel() {
            monthLabel.textContent = monthNames[month] + " " + String(year);
        }

        monthPrevBtn.addEventListener("click", function() {
            month -= 1;
            if (month < 0) {
                month = 11;
                year -= 1;
            }
            updateLabel();
        });

        monthNextBtn.addEventListener("click", function() {
            month += 1;
            if (month > 11) {
                month = 0;
                year += 1;
            }
            updateLabel();
        });

        updateLabel();
    }

    function initTransactionForm() {
        const modal = document.getElementById("transactionModal");
        const form = document.getElementById("transactionForm");
        if (!modal || !form) {
            return;
        }

        if (window.AppModal && typeof window.AppModal.resetVariantState === "function") {
            window.AppModal.resetVariantState(modal);
        }

        form.addEventListener("submit", function(event) {
            event.preventDefault();
            form.reset();

            if (window.AppModal) {
                window.AppModal.resetVariantState(modal);
                window.AppModal.close(modal);
            }
        });
    }

    function initPage() {
        initTransactionsTable();
        initHeaderMonthNavigator();
        initTransactionForm();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initPage);
    } else {
        initPage();
    }
})();