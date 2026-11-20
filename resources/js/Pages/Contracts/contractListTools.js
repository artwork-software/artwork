/**
 * Reine Hilfsfunktionen der Vertragsübersicht (Suche, Sortierwerte), ohne Vue.
 */

/** Suche über Vertragspartner:in, Projekt, Dateiname, Vertragsart, Rechtsform, Beschreibung und Status. */
export function matchesContractSearch(contract, term) {
    const needle = String(term ?? '').trim().toLowerCase();
    if (needle === '') {
        return true;
    }
    return [
        contract?.partner,
        contract?.project?.name,
        contract?.name,
        contract?.contract_type?.name,
        contract?.company_type?.name,
        contract?.description,
        contract?.contract_state,
    ].some(value => String(value ?? '').toLowerCase().includes(needle));
}

/** Sortierwerte je Spalte: Text kleingeschrieben, Betrag als Zahl, leere Werte als null. */
export const contractSortAccessors = {
    partner: contract => textOrNull(contract?.partner),
    project: contract => textOrNull(contract?.project?.name),
    contractType: contract => textOrNull(contract?.contract_type?.name),
    filename: contract => textOrNull(contract?.name),
    status: contract => textOrNull(contract?.contract_state),
    openTasks: contract => Number(contract?.open_tasks_count) || 0,
    deadline: contract => contract?.deadline_date ? String(contract.deadline_date).substring(0, 10) : null,
    amount: (contract) => {
        if (contract?.amount === null || contract?.amount === undefined || contract?.amount === '') {
            return null;
        }
        const amount = Number(String(contract.amount).replace(',', '.'));
        return Number.isFinite(amount) ? amount : null;
    },
};

function textOrNull(value) {
    const text = String(value ?? '').trim().toLowerCase();
    return text === '' ? null : text;
}

/** Heutiges Datum als YYYY-MM-DD in Ortszeit (für den Vergleich mit Stichtagen). */
export function todayDateString(today = new Date()) {
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return `${today.getFullYear()}-${month}-${day}`;
}

/**
 * Schnellfilter für Stichtage: 'overdue' (vor heute), 'week' (heute bis Sonntag dieser Woche),
 * 'month' (heute bis Monatsende). Ohne Filter passt jeder Vertrag, ohne Stichtag keiner.
 */
export function matchesDeadlineFilter(contract, filter, today = new Date()) {
    if (!filter) {
        return true;
    }
    const deadline = contract?.deadline_date ? String(contract.deadline_date).substring(0, 10) : null;
    if (!deadline) {
        return false;
    }
    const todayString = todayDateString(today);
    if (filter === 'overdue') {
        return deadline < todayString;
    }

    const end = new Date(today.getFullYear(), today.getMonth(), today.getDate());
    if (filter === 'week') {
        // Montag ist Wochenbeginn: bis zum kommenden Sonntag
        end.setDate(end.getDate() + ((7 - end.getDay()) % 7));
    } else if (filter === 'month') {
        end.setMonth(end.getMonth() + 1, 0);
    } else {
        return true;
    }
    return deadline >= todayString && deadline <= todayDateString(end);
}

/** Vorhandene Statuswerte (Freitext) alphabetisch und ohne Dubletten, für den Status-Filter. */
export function distinctContractStates(contracts) {
    const states = new Map();
    for (const contract of contracts ?? []) {
        const state = String(contract?.contract_state ?? '').trim();
        if (state !== '' && !states.has(state.toLowerCase())) {
            states.set(state.toLowerCase(), state);
        }
    }
    return [...states.values()].sort((a, b) => a.localeCompare(b));
}
