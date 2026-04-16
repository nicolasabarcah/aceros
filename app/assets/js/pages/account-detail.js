import { createTransaction, getAccountById, listAccounts, listCategories, listTransactions } from "../services/finance-api.js";
import { escapeHtml, formatCurrency, formatDisplayAmount, getCategoryType, getPeriodEnd, getSignedAmount, initMonthNavigator, normalizeText, parseTransactionDate, sameMonth, showNotice } from "../utils/app-utils.js";

(function() {
    const pageSizeStorageKey = "txTablePageSize";
    const allowedPageSizes = [25, 50, 75, 100];
    const state = {
        accountId: 0,
        account: null,
        month: new Date().getMonth(),
        year: new Date().getFullYear(),
        currentPage: 1,
        pageSize: 25,
        accounts: [],
        categories: [],
        transactions: [],
        loading: {
            summary: true,
            table: true
        },
        ready: {
            accounts: false,
            categories: false,
            transactions: false
        }
    };

    const elements = {
        notice: document.getElementById("pageNotice"),
        pageTitle: document.getElementById("pageTitle"),
        pageSubtitle: document.getElementById("pageSubtitle"),
        tableTitle: document.getElementById("tableTitle"),
        monthLabel: document.getElementById("headerMonthLabel"),
        monthPrev: document.getElementById("headerMonthPrev"),
        monthNext: document.getElementById("headerMonthNext"),
        summaryBalanceTotal: document.getElementById("summaryBalanceTotal"),
        summaryIncomeMonth: document.getElementById("summaryIncomeMonth"),
        summaryExpenseMonth: document.getElementById("summaryExpenseMonth"),
        search: document.getElementById("txSearch"),
        pageSize: document.getElementById("txPageSize"),
        info: document.getElementById("txInfo"),
        pagination: document.getElementById("txPagination"),
        tbody: document.getElementById("transactionsTableBody"),
        openModalButton: document.getElementById("openTransactionModal"),
        modal: document.getElementById("transactionModal"),
        form: document.getElementById("transactionForm"),
        typeInput: document.getElementById("txType"),
        typeSwitch: document.getElementById("txTypeSwitch"),
        date: document.getElementById("txDate"),
        concept: document.getElementById("txConcept"),
        accountSelect: document.getElementById("txAccount"),
        categorySelect: document.getElementById("txCategory"),
        amountInput: document.getElementById("txAmount"),
        amountValue: document.getElementById("txAmountValue"),
        note: document.getElementById("txNote")
    };

    function formatMonthYear(month, year) {
        const label = new Intl.DateTimeFormat("es-CL", {
            month: "long",
            year: "numeric"
        }).format(new Date(year, month, 1));

        return label.charAt(0).toUpperCase() + label.slice(1);
    }

    function buildInlineSpinner(label) {
        return '<span class="app-inline-loader" aria-label="' + escapeHtml(label) + '"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span></span>';
    }

    function setSummaryLoading(isLoading) {
        state.loading.summary = isLoading;

        [
            { element: elements.summaryBalanceTotal, label: "Cargando saldo total" },
            { element: elements.summaryIncomeMonth, label: "Cargando ingresos del mes" },
            { element: elements.summaryExpenseMonth, label: "Cargando gastos del mes" }
        ].forEach(function(item) {
            if (!item.element) {
                return;
            }

            item.element.classList.toggle("summary-value--loading", isLoading);
            if (isLoading) {
                item.element.innerHTML = buildInlineSpinner(item.label);
            }
        });
    }

    function setSummaryFallback() {
        [elements.summaryBalanceTotal, elements.summaryIncomeMonth, elements.summaryExpenseMonth].forEach(function(element) {
            if (!element) {
                return;
            }

            element.classList.remove("summary-value--loading");
            element.textContent = "--";
        });
    }

    function setTableControlsDisabled(isDisabled) {
        if (elements.search) {
            elements.search.disabled = isDisabled;
        }

        if (elements.pageSize) {
            elements.pageSize.disabled = isDisabled;
        }
    }

    function setTableLoading(isLoading) {
        state.loading.table = isLoading;
        setTableControlsDisabled(isLoading);

        if (!elements.tbody || !elements.info) {
            return;
        }

        if (isLoading) {
            elements.tbody.innerHTML = [
                '<tr id="txLoadingState">',
                '    <td colspan="5" class="text-center py-4">',
                '        <span class="table-loader" aria-label="Cargando transacciones">',
                '            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>',
                '            <span>Cargando transacciones...</span>',
                '        </span>',
                '    </td>',
                '</tr>'
            ].join("");
            elements.info.textContent = "Cargando registros...";

            if (elements.pagination) {
                elements.pagination.innerHTML = "";
            }
        }
    }

    function tryRenderSummary() {
        if (!state.ready.accounts || !state.ready.transactions) {
            return;
        }

        setSummaryLoading(false);
        renderSummary();
    }

    function getQueryAccountId() {
        const params = new URLSearchParams(window.location.search);
        const accountId = parseInt(params.get("cuenta_id") || "", 10);
        return Number.isNaN(accountId) ? 0 : accountId;
    }

    function getInitialPageSize() {
        if (!window.localStorage) {
            return 25;
        }

        const storedValue = parseInt(localStorage.getItem(pageSizeStorageKey) || "", 10);
        return allowedPageSizes.indexOf(storedValue) !== -1 ? storedValue : 25;
    }

    function savePageSize(value) {
        if (!window.localStorage) {
            return;
        }

        localStorage.setItem(pageSizeStorageKey, String(value));
    }

    function getInitialBalance() {
        if (state.account) {
            return Number(state.account.saldo_inicial || 0);
        }

        return state.accounts.reduce(function(total, account) {
            return total + Number(account.saldo_inicial || 0);
        }, 0);
    }

    function getFilteredTransactions() {
        const query = normalizeText(elements.search ? elements.search.value.trim() : "");

        return state.transactions.filter(function(transaction) {
            const transactionDate = parseTransactionDate(transaction.fecha);
            if (!sameMonth(transactionDate, state.month, state.year)) {
                return false;
            }

            if (!query) {
                return true;
            }

            const sourceText = normalizeText([
                transaction.fecha,
                transaction.concepto,
                transaction.descripcion,
                transaction.cuenta ? transaction.cuenta.nombre : "",
                transaction.categoria ? transaction.categoria.nombre : "",
                formatDisplayAmount(transaction)
            ].join(" "));

            return sourceText.includes(query);
        });
    }

    function renderHeader() {
        if (!elements.pageTitle || !elements.pageSubtitle || !elements.tableTitle) {
            return;
        }

        const monthYearLabel = formatMonthYear(state.month, state.year);

        if (state.account) {
            elements.pageTitle.textContent = state.account.nombre;
            elements.pageSubtitle.textContent = "Aquí tienes el detalle y resumen de esta cuenta.";
            elements.tableTitle.textContent = "Transacciones de " + monthYearLabel;
            return;
        }

        elements.pageTitle.textContent = "Hola, Usuario";
        elements.pageSubtitle.textContent = "Aquí tienes un resumen claro de tus finanzas personales.";
        elements.tableTitle.textContent = "Transacciones de " + monthYearLabel;
    }

    function renderSummary() {
        if (state.loading.summary) {
            return;
        }

        const periodEnd = getPeriodEnd(state.month, state.year);
        let balance = getInitialBalance();
        let income = 0;
        let expense = 0;

        state.transactions.forEach(function(transaction) {
            const transactionDate = parseTransactionDate(transaction.fecha);
            if (!transactionDate || transactionDate > periodEnd) {
                return;
            }

            const signedAmount = getSignedAmount(transaction);
            balance += signedAmount;

            if (sameMonth(transactionDate, state.month, state.year)) {
                if (signedAmount >= 0) {
                    income += signedAmount;
                } else {
                    expense += Math.abs(signedAmount);
                }
            }
        });

        if (elements.summaryBalanceTotal) {
            elements.summaryBalanceTotal.textContent = formatCurrency(balance);
        }
        if (elements.summaryIncomeMonth) {
            elements.summaryIncomeMonth.textContent = formatCurrency(income);
        }
        if (elements.summaryExpenseMonth) {
            elements.summaryExpenseMonth.textContent = formatCurrency(expense);
        }
    }

    function renderPagination(totalPages) {
        if (!elements.pagination) {
            return;
        }

        elements.pagination.innerHTML = "";
        if (totalPages <= 1) {
            return;
        }

        const previousButton = document.createElement("button");
        previousButton.type = "button";
        previousButton.className = "page-btn";
        previousButton.textContent = "Anterior";
        previousButton.disabled = state.currentPage === 1;
        previousButton.addEventListener("click", function() {
            if (state.currentPage > 1) {
                state.currentPage -= 1;
                renderTable();
            }
        });
        elements.pagination.appendChild(previousButton);

        for (let page = 1; page <= totalPages; page += 1) {
            const pageButton = document.createElement("button");
            pageButton.type = "button";
            pageButton.className = "page-btn" + (page === state.currentPage ? " active" : "");
            pageButton.textContent = String(page);
            pageButton.addEventListener("click", function() {
                state.currentPage = page;
                renderTable();
            });
            elements.pagination.appendChild(pageButton);
        }

        const nextButton = document.createElement("button");
        nextButton.type = "button";
        nextButton.className = "page-btn";
        nextButton.textContent = "Siguiente";
        nextButton.disabled = state.currentPage === totalPages;
        nextButton.addEventListener("click", function() {
            if (state.currentPage < totalPages) {
                state.currentPage += 1;
                renderTable();
            }
        });
        elements.pagination.appendChild(nextButton);
    }

    function renderTable() {
        if (!elements.tbody || !elements.info || state.loading.table) {
            return;
        }

        const filtered = getFilteredTransactions();
        const totalRecords = filtered.length;
        const totalPages = Math.max(1, Math.ceil(totalRecords / state.pageSize));

        if (state.currentPage > totalPages) {
            state.currentPage = totalPages;
        }

        if (totalRecords === 0) {
            elements.tbody.innerHTML = '<tr id="txEmptyState"><td colspan="5" class="text-center py-4">No hay registros en el mes.</td></tr>';
            elements.info.textContent = "Mostrando 0 de 0 registros";
            renderPagination(0);
            return;
        }

        const startIndex = (state.currentPage - 1) * state.pageSize;
        const endIndex = Math.min(startIndex + state.pageSize, totalRecords);
        const currentPageRows = filtered.slice(startIndex, endIndex);

        elements.tbody.innerHTML = currentPageRows.map(function(transaction) {
            const categoryType = getCategoryType(transaction.categoria);
            const badgeClass = categoryType === "ingreso" ? "badge-soft-success" : "badge-soft-warning";
            const transactionDate = parseTransactionDate(transaction.fecha);
            const formattedDate = transactionDate ? transactionDate.toLocaleDateString("es-CL") : "-";

            return [
                '<tr data-tx-id="' + transaction.id + '">',
                '    <td>' + escapeHtml(formattedDate) + '</td>',
                '    <td>' + escapeHtml(transaction.concepto) + '</td>',
                '    <td><span class="badge ' + badgeClass + '">' + escapeHtml(transaction.categoria ? transaction.categoria.nombre : "Sin categoría") + '</span></td>',
                '    <td class="text-end">' + escapeHtml(formatDisplayAmount(transaction)) + '</td>',
                '    <td class="text-center action-col"><a href="#" class="edit-action disabled" aria-disabled="true" title="Edición pendiente"><i class="bi bi-pencil"></i></a></td>',
                '</tr>'
            ].join("");
        }).join("");

        elements.info.textContent = "Mostrando " + (startIndex + 1) + " a " + endIndex + " de " + totalRecords + " registros";
        renderPagination(totalPages);
    }

    function renderCategories() {
        if (!elements.categorySelect || !elements.typeInput) {
            return;
        }

        const type = elements.typeInput.value === "egreso" ? "egreso" : "ingreso";
        const currentValue = elements.categorySelect.value;
        const filtered = state.categories.filter(function(category) {
            return getCategoryType(category) === type;
        });

        const options = ['<option value="" disabled selected>Selecciona una categoría</option>'].concat(
            filtered.map(function(category) {
                const selected = String(category.id) === currentValue ? ' selected' : '';
                return '<option value="' + category.id + '"' + selected + '>' + escapeHtml(category.nombre) + '</option>';
            })
        );

        elements.categorySelect.innerHTML = options.join("");
        if (currentValue && filtered.some(function(category) { return String(category.id) === currentValue; })) {
            elements.categorySelect.value = currentValue;
        }
    }

    function renderAccountsSelect() {
        if (!elements.accountSelect) {
            return;
        }

        const options = ['<option value="" disabled>Selecciona una cuenta</option>'].concat(
            state.accounts.map(function(account) {
                const selected = state.account && state.account.id === account.id ? ' selected' : '';
                return '<option value="' + account.id + '"' + selected + '>' + escapeHtml(account.nombre) + '</option>';
            })
        );

        elements.accountSelect.innerHTML = options.join("");
        if (state.account) {
            elements.accountSelect.value = String(state.account.id);
        }
    }

    function setDefaultFormValues() {
        if (elements.form) {
            elements.form.reset();
        }
        if (elements.amountInput) {
            elements.amountInput.value = "";
        }
        if (elements.amountValue) {
            elements.amountValue.value = "";
        }
        if (elements.note) {
            elements.note.value = "";
        }
        if (elements.date) {
            elements.date.value = new Date().toISOString().slice(0, 10);
        }
        if (window.AppModal && elements.modal) {
            window.AppModal.resetVariantState(elements.modal);
        }
        renderAccountsSelect();
        renderCategories();
    }

    function bindFormFormatting() {
        if (!elements.amountInput || !elements.amountValue) {
            return;
        }

        elements.amountInput.addEventListener("input", function() {
            const digits = this.value.replace(/\D/g, "");
            elements.amountValue.value = digits;
            this.value = digits ? formatCurrency(Number(digits)) : "";
        });
    }

    function bindTableControls() {
        if (elements.search) {
            elements.search.addEventListener("input", function() {
                state.currentPage = 1;
                renderTable();
            });
        }

        if (elements.pageSize) {
            elements.pageSize.value = String(state.pageSize);
            elements.pageSize.addEventListener("change", function() {
                const value = parseInt(this.value, 10);
                state.pageSize = allowedPageSizes.indexOf(value) !== -1 ? value : 25;
                savePageSize(state.pageSize);
                state.currentPage = 1;
                renderTable();
            });
        }
    }

    function bindFormEvents() {
        if (elements.modal) {
            elements.modal.addEventListener("appmodal:open", function() {
                setDefaultFormValues();
            });
        }

        if (elements.typeSwitch) {
            elements.typeSwitch.addEventListener("change", function() {
                renderCategories();
            });
        }

        if (!elements.form) {
            return;
        }

        elements.form.addEventListener("submit", async function(event) {
            event.preventDefault();

            const amount = Number(elements.amountValue ? elements.amountValue.value : 0);
            const categoryId = Number(elements.categorySelect ? elements.categorySelect.value : 0);
            const accountId = Number(elements.accountSelect ? elements.accountSelect.value : 0);

            if (!elements.date.value || !elements.concept.value.trim() || !accountId || !categoryId || !amount) {
                showNotice(elements.notice, "Completa todos los campos obligatorios para guardar la transacción.", "warning");
                return;
            }

            try {
                await createTransaction({
                    fecha: elements.date.value,
                    concepto: elements.concept.value,
                    cuentaId: accountId,
                    categoriaId: categoryId,
                    monto: amount,
                    descripcion: elements.note ? elements.note.value : ""
                });

                setSummaryLoading(true);
                setTableLoading(true);
                state.transactions = await listTransactions({ accountId: state.accountId });
                state.ready.transactions = true;
                showNotice(elements.notice, "", "warning");
                setTableLoading(false);
                tryRenderSummary();
                renderTable();

                if (window.AppModal && elements.modal) {
                    window.AppModal.close(elements.modal);
                }

                if (window.Swal) {
                    window.setTimeout(function() {
                        window.Swal.fire({
                            title: "Transacción guardada",
                            text: "La transacción fue registrada correctamente.",
                            icon: "success",
                            confirmButtonText: "Aceptar",
                            confirmButtonColor: "#0d6efd"
                        });
                    }, 220);
                }
            } catch (error) {
                setSummaryLoading(false);
                setTableLoading(false);
                showNotice(elements.notice, error.message, "warning");
            }
        });
    }

    async function loadSummaryData() {
        try {
            const results = await Promise.all([
                listAccounts(),
                state.accountId > 0 ? getAccountById(state.accountId) : Promise.resolve(null)
            ]);

            state.accounts = results[0];
            state.account = results[1];
            state.ready.accounts = true;

            renderHeader();
            renderAccountsSelect();

            if (state.accountId > 0 && !state.account) {
                showNotice(elements.notice, "La cuenta solicitada no existe o no está activa.", "warning");
            }

            tryRenderSummary();
        } catch (error) {
            state.loading.summary = false;
            setSummaryFallback();
            showNotice(elements.notice, error.message, "warning");
        }
    }

    async function loadCategoriesData() {
        try {
            state.categories = await listCategories();
            state.ready.categories = true;
            renderCategories();
            renderAccountsSelect();
        } catch (error) {
            showNotice(elements.notice, error.message, "warning");
        }
    }

    async function loadTransactionsData() {
        try {
            state.transactions = await listTransactions({ accountId: state.accountId });
            state.ready.transactions = true;
            setTableLoading(false);
            renderTable();
            tryRenderSummary();
        } catch (error) {
            setTableLoading(false);
            setSummaryLoading(false);
            setSummaryFallback();

            if (elements.tbody) {
                elements.tbody.innerHTML = '<tr id="txEmptyState"><td colspan="5" class="text-center py-4">No se pudieron cargar las transacciones.</td></tr>';
            }

            if (elements.info) {
                elements.info.textContent = "No fue posible cargar los registros";
            }

            showNotice(elements.notice, error.message, "warning");
        }
    }

    async function init() {
        state.accountId = getQueryAccountId();
        state.pageSize = getInitialPageSize();

        renderHeader();
        setSummaryLoading(true);
        setTableLoading(true);

        initMonthNavigator({
            label: elements.monthLabel,
            prevButton: elements.monthPrev,
            nextButton: elements.monthNext,
            onChange: function(nextState) {
                state.month = nextState.month;
                state.year = nextState.year;
                renderHeader();
                renderSummary();
                renderTable();
            }
        });

        bindFormFormatting();
        bindTableControls();
        bindFormEvents();

        showNotice(elements.notice, "", "warning");
        setDefaultFormValues();

        loadSummaryData();
        loadCategoriesData();
        loadTransactionsData();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();