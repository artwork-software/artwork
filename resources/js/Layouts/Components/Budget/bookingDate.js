/**
 * Formatiert ein Sage-Buchungsdatum (ISO-String) als DD.MM.YYYY.
 * Zentraler Helper - vorher in mehreren Komponenten identisch dupliziert.
 */
export function formatBookingDataDate(dateString) {
    if (!dateString) {
        return '';
    }
    const datePart = String(dateString).split('T')[0];
    const [year, month, day] = datePart.split('-');
    if (!year || !month || !day) {
        return String(dateString);
    }
    return `${day}.${month}.${year}`;
}

/**
 * Formatiert einen Zeitstempel als DD.MM.YYYY, HH:MM (de-DE).
 */
export function formatDateTime(dateString) {
    if (!dateString) {
        return '';
    }
    const dateObj = new Date(dateString);
    if (isNaN(dateObj.getTime())) {
        return String(dateString);
    }
    return dateObj.toLocaleString('de-DE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}
