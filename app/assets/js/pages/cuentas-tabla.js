(function() {
    function parseTransactionDate(rawDate) {
        if (!rawDate) {
            return null;
        }

        const parsedDate = new Date(rawDate + "T00:00:00");
        if (Number.isNaN(parsedDate.getTime())) {
            return null;
        }

        return parsedDate;
    }

    function formatCurrency(value) {
        return "$ " + Number(value || 0).toLocaleString("es-CL", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    }

    function initMonthlySummaryCards() {
        const summary = document.getElementById("accountMonthlySummary");
        const table = document.getElementById("transactionsTable");
        const monthLabel = document.getElementById("headerMonthLabel");
        const balanceTotal = document.getElementById("summaryBalanceTotal");
        const incomeMonth = document.getElementById("summaryIncomeMonth");
        const expenseMonth = document.getElementById("summaryExpenseMonth");
        if (!summary || !table || !monthLabel || !balanceTotal || !incomeMonth || !expenseMonth) {
            return;
        }

        const rows = Array.from(table.querySelectorAll("tbody tr:not(#txEmptyState)"));
        const initialBalance = parseFloat(summary.dataset.initialBalance || "0") || 0;

        function getSelectedMonthState() {
            const month = parseInt(monthLabel.dataset.month || "", 10);
            const year = parseInt(monthLabel.dataset.year || "", 10);

            return {
                month: Number.isNaN(month) ? new Date().getMonth() : month,
                year: Number.isNaN(year) ? new Date().getFullYear() : year
            };
        }

        function calculateMonthlySummary() {
            const selected = getSelectedMonthState();
            const periodEnd = new Date(selected.year, selected.month + 1, 0, 23, 59, 59, 999);
            let runningBalance = initialBalance;
            let monthIncome = 0;
            let monthExpense = 0;

            rows.forEach(function(row) {
                const rowDate = parseTransactionDate(row.getAttribute("data-tx-date") || "");
                const rawAmount = parseFloat(row.getAttribute("data-tx-amount") || "0") || 0;
                const txType = row.getAttribute("data-tx-type") || "egreso";
                const amount = Math.abs(rawAmount);

                if (!rowDate || rowDate > periodEnd) {
                    return;
                }

                runningBalance += txType === "ingreso" ? amount : -amount;

                if (rowDate.getMonth() === selected.month && rowDate.getFullYear() === selected.year) {
                    if (txType === "ingreso") {
                        monthIncome += amount;
                    } else {
                        monthExpense += amount;
                    }
                }
            });

            balanceTotal.textContent = formatCurrency(runningBalance);
            incomeMonth.textContent = formatCurrency(monthIncome);
            expenseMonth.textContent = formatCurrency(monthExpense);
        }

        document.addEventListener("appmonthchange", calculateMonthlySummary);
        calculateMonthlySummary();
    }

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
        const monthLabel = document.getElementById("headerMonthLabel");
        const pageSizeStorageKey = "txTablePageSize";
        const allowedPageSizes = [25, 50, 75, 100];

        let filteredRows = [].concat(allRows);
        let currentPage = 1;
        let pageSize = 25;
        let selectedMonth = new Date().getMonth();
        let selectedYear = new Date().getFullYear();

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

        function parseRowDate(row) {
            return parseTransactionDate(row.getAttribute("data-tx-date") || "");
        }

        function rowMatchesSelectedMonth(row) {
            const rowDate = parseRowDate(row);
            if (!rowDate) {
                return false;
            }

            return rowDate.getMonth() === selectedMonth && rowDate.getFullYear() === selectedYear;
        }

        function syncSelectedMonthFromLabel() {
            if (!monthLabel) {
                return;
            }

            const monthValue = parseInt(monthLabel.dataset.month || "", 10);
            const yearValue = parseInt(monthLabel.dataset.year || "", 10);

            if (!Number.isNaN(monthValue)) {
                selectedMonth = monthValue;
            }

            if (!Number.isNaN(yearValue)) {
                selectedYear = yearValue;
            }
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
            const normalizedQuery = normalizeText(query || "");

            filteredRows = allRows.filter(function(row) {
                if (!rowMatchesSelectedMonth(row)) {
                    return false;
                }

                if (!normalizedQuery) {
                    return true;
                }

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

        document.addEventListener("appmonthchange", function(event) {
            if (event && event.detail) {
                selectedMonth = event.detail.month;
                selectedYear = event.detail.year;
            } else {
                syncSelectedMonthFromLabel();
            }

            currentPage = 1;
            filterRows(searchInput ? searchInput.value.trim() : "");
            renderTable();
        });

        syncSelectedMonthFromLabel();
        applyCellLabels();
        filterRows(searchInput ? searchInput.value.trim() : "");
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
        const monthStorageKey = "cuentasSelectedMonth";
        let month = 0;
        let year = 0;

        function saveSelectedMonth() {
            if (!window.localStorage) {
                return;
            }

            localStorage.setItem(monthStorageKey, JSON.stringify({
                month: month,
                year: year
            }));
        }

        function loadSelectedMonth() {
            if (!window.localStorage) {
                return false;
            }

            try {
                const storedValue = localStorage.getItem(monthStorageKey);
                if (!storedValue) {
                    return false;
                }

                const parsedValue = JSON.parse(storedValue);
                const storedMonth = parseInt(parsedValue.month, 10);
                const storedYear = parseInt(parsedValue.year, 10);

                if (Number.isNaN(storedMonth) || Number.isNaN(storedYear) || storedMonth < 0 || storedMonth > 11) {
                    return false;
                }

                month = storedMonth;
                year = storedYear;
                return true;
            } catch (error) {
                return false;
            }
        }

        function setCurrentMonth() {
            const now = new Date();
            month = now.getMonth();
            year = now.getFullYear();
        }

        function updateLabel() {
            monthLabel.dataset.month = String(month);
            monthLabel.dataset.year = String(year);
            monthLabel.textContent = monthNames[month] + " " + String(year);
            saveSelectedMonth();
            document.dispatchEvent(new CustomEvent("appmonthchange", {
                detail: {
                    month: month,
                    year: year
                }
            }));
        }

        function resetToCurrentMonth() {
            setCurrentMonth();
            updateLabel();
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

        monthLabel.addEventListener("click", function() {
            resetToCurrentMonth();
        });

        monthLabel.addEventListener("keydown", function(event) {
            if (event.key !== "Enter" && event.key !== " ") {
                return;
            }

            event.preventDefault();
            resetToCurrentMonth();
        });

        if (!loadSelectedMonth()) {
            setCurrentMonth();
        }
        updateLabel();
    }

    function initTransactionForm() {
        const modal = document.getElementById("transactionModal");
        const form = document.getElementById("transactionForm");
        const amountInput = document.getElementById("txAmount");
        const amountHiddenInput = document.getElementById("txAmountValue");
        if (!modal || !form) {
            return;
        }

        function sanitizeAmount(value) {
            return value.replace(/\D/g, "");
        }

        function formatAmount(value) {
            if (!value) {
                return "";
            }

            return "$ " + new Intl.NumberFormat("es-CL").format(Number(value));
        }

        function syncAmountField(rawValue) {
            const sanitizedValue = sanitizeAmount(rawValue);

            if (amountHiddenInput) {
                amountHiddenInput.value = sanitizedValue;
            }

            if (amountInput) {
                amountInput.value = formatAmount(sanitizedValue);
            }
        }

        function resetTransactionForm() {
            form.reset();

            if (amountHiddenInput) {
                amountHiddenInput.value = "";
            }

            if (amountInput) {
                amountInput.value = "";
            }

            if (window.AppModal && typeof window.AppModal.resetVariantState === "function") {
                window.AppModal.resetVariantState(modal);
            }
        }

        if (amountInput) {
            amountInput.addEventListener("input", function(event) {
                syncAmountField(event.target.value);
            });
        }

        resetTransactionForm();

        modal.addEventListener("appmodal:open", function() {
            resetTransactionForm();
        });

        form.addEventListener("submit", function(event) {
            event.preventDefault();
            resetTransactionForm();

            if (window.AppModal) {
                window.AppModal.close(modal);
            }
        });
    }

    function initPage() {
        initMonthlySummaryCards();
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