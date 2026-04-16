import { listAccounts, listTransactions } from "../services/finance-api.js";
import { escapeHtml, formatCurrency, getPeriodEnd, getSignedAmount, initMonthNavigator, parseTransactionDate, sameMonth, showNotice } from "../utils/app-utils.js";

(function() {
    const state = {
        month: new Date().getMonth(),
        year: new Date().getFullYear(),
        accounts: [],
        transactions: []
    };

    const elements = {
        notice: document.getElementById("pageNotice"),
        accountsGrid: document.getElementById("accountsGrid"),
        accountsCount: document.getElementById("accountsCount"),
        summaryBalanceTotal: document.getElementById("summaryBalanceTotal"),
        summaryIncomeMonth: document.getElementById("summaryIncomeMonth"),
        summaryExpenseMonth: document.getElementById("summaryExpenseMonth"),
        monthLabel: document.getElementById("headerMonthLabel"),
        monthPrev: document.getElementById("headerMonthPrev"),
        monthNext: document.getElementById("headerMonthNext")
    };

    function calculateAccountBalance(account) {
        const periodEnd = getPeriodEnd(state.month, state.year);

        return state.transactions.reduce(function(total, transaction) {
            const transactionDate = parseTransactionDate(transaction.fecha);
            if (!transactionDate || transaction.cuenta_id !== account.id || transactionDate > periodEnd) {
                return total;
            }

            return total + getSignedAmount(transaction);
        }, Number(account.saldo_inicial || 0));
    }

    function renderAccounts() {
        if (!elements.accountsGrid || !elements.accountsCount) {
            return;
        }

        elements.accountsCount.textContent = state.accounts.length + " activas";

        if (state.accounts.length === 0) {
            elements.accountsGrid.innerHTML = [
                '<div class="col-12">',
                '    <article class="summary-card">',
                '        <p class="summary-label mb-0">No hay cuentas activas para mostrar.</p>',
                '    </article>',
                '</div>'
            ].join("");
            return;
        }

        elements.accountsGrid.innerHTML = state.accounts.map(function(account) {
            const balance = calculateAccountBalance(account);
            const amountClass = balance < 0 ? " text-expense" : "";

            return [
                '<div class="col-sm-6 col-xl-3">',
                '    <a class="account-card__link" href="cuentas_registros.html?cuenta_id=' + account.id + '">',
                '        <article class="summary-card account-card">',
                '            <div class="d-flex align-items-center justify-content-between gap-3">',
                '                <div>',
                '                    <p class="fw-medium account-card__name text-muted pt-2 pb-1">' + escapeHtml(account.nombre) + '</p>',
                '                    <h2 class="summary-value account-card__value' + amountClass + '">' + escapeHtml(formatCurrency(balance)) + '</h2>',
                '                </div>',
                '                <span class="summary-icon account-card__icon summary-icon-total">',
                '                    <i class="bi bi-bank"></i>',
                '                </span>',
                '            </div>',
                '        </article>',
                '    </a>',
                '</div>'
            ].join("");
        }).join("");
    }

    function renderSummary() {
        const periodEnd = getPeriodEnd(state.month, state.year);
        let income = 0;
        let expense = 0;

        state.transactions.forEach(function(transaction) {
            const transactionDate = parseTransactionDate(transaction.fecha);
            if (!transactionDate || transactionDate > periodEnd) {
                return;
            }

            const signedAmount = getSignedAmount(transaction);
            if (sameMonth(transactionDate, state.month, state.year)) {
                if (signedAmount >= 0) {
                    income += signedAmount;
                } else {
                    expense += Math.abs(signedAmount);
                }
            }
        });

        const balance = state.accounts.reduce(function(total, account) {
            return total + calculateAccountBalance(account);
        }, 0);

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

    function renderPage() {
        renderAccounts();
        renderSummary();
    }

    async function loadData() {
        const results = await Promise.all([
            listAccounts(),
            listTransactions()
        ]);

        state.accounts = results[0];
        state.transactions = results[1];
    }

    async function init() {
        initMonthNavigator({
            label: elements.monthLabel,
            prevButton: elements.monthPrev,
            nextButton: elements.monthNext,
            onChange: function(nextState) {
                state.month = nextState.month;
                state.year = nextState.year;
                renderPage();
            }
        });

        try {
            await loadData();
            showNotice(elements.notice, "", "warning");
            renderPage();
        } catch (error) {
            showNotice(elements.notice, error.message, "warning");
            if (elements.accountsGrid) {
                elements.accountsGrid.innerHTML = "";
            }
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();