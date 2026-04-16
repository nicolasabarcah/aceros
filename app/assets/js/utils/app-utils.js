export const MONTH_NAMES = ["ENE", "FEB", "MAR", "ABR", "MAY", "JUN", "JUL", "AGO", "SEP", "OCT", "NOV", "DIC"];

export function formatCurrency(value) {
    return "$ " + Number(value || 0).toLocaleString("es-CL", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
}

export function parseTransactionDate(rawDate) {
    if (!rawDate) {
        return null;
    }

    const parsedDate = new Date(rawDate + "T00:00:00");
    if (Number.isNaN(parsedDate.getTime())) {
        return null;
    }

    return parsedDate;
}

export function normalizeText(value) {
    return String(value || "")
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "");
}

export function getCategoryType(category) {
    return category && category.ingreso ? "ingreso" : "egreso";
}

export function getSignedAmount(transaction) {
    const amount = Math.abs(Number(transaction && transaction.monto ? transaction.monto : 0) || 0);
    return getCategoryType(transaction ? transaction.categoria : null) === "ingreso" ? amount : -amount;
}

export function formatDisplayAmount(transaction) {
    const signedAmount = getSignedAmount(transaction);
    const sign = signedAmount >= 0 ? "+" : "-";
    return sign + formatCurrency(Math.abs(signedAmount));
}

export function getPeriodEnd(month, year) {
    return new Date(year, month + 1, 0, 23, 59, 59, 999);
}

export function sameMonth(date, month, year) {
    return date && date.getMonth() === month && date.getFullYear() === year;
}

export function initMonthNavigator(options) {
    const label = options.label;
    const prevButton = options.prevButton;
    const nextButton = options.nextButton;
    const storageKey = options.storageKey || "cuentasSelectedMonth";
    const onChange = typeof options.onChange === "function" ? options.onChange : function() {};

    if (!label || !prevButton || !nextButton) {
        return null;
    }

    let month = 0;
    let year = 0;

    function setCurrentMonth() {
        const now = new Date();
        month = now.getMonth();
        year = now.getFullYear();
    }

    function saveState() {
        if (!window.localStorage) {
            return;
        }

        localStorage.setItem(storageKey, JSON.stringify({ month, year }));
    }

    function loadState() {
        if (!window.localStorage) {
            return false;
        }

        try {
            const rawValue = localStorage.getItem(storageKey);
            if (!rawValue) {
                return false;
            }

            const parsedValue = JSON.parse(rawValue);
            const storedMonth = parseInt(parsedValue.month, 10);
            const storedYear = parseInt(parsedValue.year, 10);

            if (Number.isNaN(storedMonth) || storedMonth < 0 || storedMonth > 11 || Number.isNaN(storedYear)) {
                return false;
            }

            month = storedMonth;
            year = storedYear;
            return true;
        } catch (error) {
            return false;
        }
    }

    function emitChange() {
        label.dataset.month = String(month);
        label.dataset.year = String(year);
        label.textContent = MONTH_NAMES[month] + " " + String(year);
        saveState();
        onChange({ month, year });
        document.dispatchEvent(new CustomEvent("appmonthchange", {
            detail: { month, year }
        }));
    }

    function resetToCurrentMonth() {
        setCurrentMonth();
        emitChange();
    }

    prevButton.addEventListener("click", function() {
        month -= 1;
        if (month < 0) {
            month = 11;
            year -= 1;
        }
        emitChange();
    });

    nextButton.addEventListener("click", function() {
        month += 1;
        if (month > 11) {
            month = 0;
            year += 1;
        }
        emitChange();
    });

    label.addEventListener("click", resetToCurrentMonth);
    label.addEventListener("keydown", function(event) {
        if (event.key !== "Enter" && event.key !== " ") {
            return;
        }

        event.preventDefault();
        resetToCurrentMonth();
    });

    if (!loadState()) {
        setCurrentMonth();
    }

    emitChange();

    return {
        getState: function() {
            return { month, year };
        },
        setCurrentMonth: resetToCurrentMonth
    };
}

export function showNotice(element, message, variant) {
    if (!element) {
        return;
    }

    if (!message) {
        element.className = "d-none";
        element.textContent = "";
        return;
    }

    element.className = "alert alert-" + (variant || "warning") + " mb-4";
    element.textContent = message;
}

export function escapeHtml(value) {
    return String(value || "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/\"/g, "&quot;")
        .replace(/'/g, "&#039;");
}