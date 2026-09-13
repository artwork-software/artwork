import axios from "axios";

/** Zeitraum-Deckel der Dienstplan-Exporte (Server: ExportPeriodLimit::MAX_DAYS) in Tagen. */
export const MAX_EXPORT_PERIOD_DAYS = 366;

/**
 * Fehler eines Blob-Downloads. `fromServer` = die Meldung stammt aus der Server-Antwort
 * (Validierung 422 / Konflikt 409) und kann dem Nutzer direkt gezeigt werden; sonst ist
 * `message` nur die technische Axios-Meldung und der Aufrufer zeigt seinen generischen Text.
 */
export class BlobDownloadError extends Error {
    constructor(message, {status = null, cause = null, fromServer = false} = {}) {
        super(message);
        this.name = "BlobDownloadError";
        this.status = status;
        this.cause = cause;
        this.fromServer = fromServer;
    }
}

/**
 * Meldung aus einer Fehlerantwort lesen. Bei responseType "blob" kommt auch die JSON-Fehlerantwort
 * als Blob an und muss erst als Text gelesen werden. Erste Validierungsmeldung (errors[*][0]),
 * sonst `message` — Letzteres nur bei 422/409, damit keine englischen Laravel-Standardtexte
 * (401/403/419/500) roh in der Oberfläche landen.
 *
 * @returns {Promise<string|null>}
 */
async function readServerMessage(error) {
    const response = error?.response;
    if (!response) return null;

    let payload = response.data;
    try {
        if (payload instanceof Blob) {
            const text = await payload.text();
            payload = text ? JSON.parse(text) : null;
        } else if (typeof payload === "string") {
            payload = JSON.parse(payload);
        }
    } catch {
        return null;
    }
    if (!payload || typeof payload !== "object") return null;

    const firstValidationMessage = Object.values(payload.errors ?? {})
        .flatMap((messages) => (Array.isArray(messages) ? messages : [messages]))
        .find((message) => typeof message === "string" && message.trim() !== "");
    if (firstValidationMessage) return firstValidationMessage;

    if ([422, 409].includes(response.status) && typeof payload.message === "string" && payload.message.trim() !== "") {
        return payload.message;
    }

    return null;
}

/**
 * Excel-/Datei-Download per XHR (Blob) für die Export-Tabs: Dateiname aus Content-Disposition,
 * Fallback-Name vom Aufrufer. Gemeinsam genutzt von den Excel-Tabs des Export-Dialogs.
 */
export function useBlobDownload() {
    const responseFilename = (response, fallback) => {
        const disposition = response.headers["content-disposition"] ?? "";
        const encodedFilename = disposition.match(/filename\*=UTF-8''([^;]+)/i)?.[1];
        if (encodedFilename) {
            return decodeURIComponent(encodedFilename.replace(/^"|"$/g, ""));
        }

        return disposition.match(/filename="?([^";]+)"?/i)?.[1] ?? fallback;
    };

    const saveResponse = (response, fallbackFilename) => {
        const blob = response.data instanceof Blob
            ? response.data
            : new Blob([response.data], {type: response.headers["content-type"]});
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = responseFilename(response, fallbackFilename);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(() => window.URL.revokeObjectURL(url), 0);
    };

    /**
     * @param {string} url absolute Route (z. B. route('…'))
     * @param {object} params Query-Parameter
     * @param {string} fallbackFilename
     * @throws {BlobDownloadError} mit der Server-Meldung (fromServer = true), sonst mit der Axios-Meldung
     */
    const download = async (url, params, fallbackFilename) => {
        let response;
        try {
            response = await axios.get(url, {params, responseType: "blob"});
        } catch (error) {
            const serverMessage = await readServerMessage(error);
            throw new BlobDownloadError(serverMessage ?? (error?.message || "Download failed"), {
                status: error?.response?.status ?? null,
                cause: error,
                fromServer: serverMessage !== null,
            });
        }
        saveResponse(response, fallbackFilename);
    };

    return {download};
}

/** Erster/letzter Tag des aktuellen Monats als YYYY-MM-DD (lokale Zeit, ohne UTC-Verschiebung). */
export function currentMonthRange() {
    const now = new Date();
    const toYmd = (d) => new Intl.DateTimeFormat("sv-SE").format(d);
    return {
        start: toYmd(new Date(now.getFullYear(), now.getMonth(), 1)),
        end: toYmd(new Date(now.getFullYear(), now.getMonth() + 1, 0)),
    };
}

/**
 * Tage zwischen zwei YYYY-MM-DD-Daten (kalendarisch, ohne Zeitzonen-/Sommerzeit-Effekte);
 * null bei fehlender oder unlesbarer Angabe.
 */
export function periodDays(from, to) {
    const parse = (value) => {
        const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(value ?? ""));
        return match ? Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3])) : null;
    };
    const start = parse(from);
    const end = parse(to);
    if (start === null || end === null) return null;
    return Math.round((end - start) / 86400000);
}

/** true, wenn beide Grenzen gesetzt sind und der Zeitraum den Export-Deckel (ein Jahr) überschreitet. */
export function exceedsExportPeriod(from, to) {
    const days = periodDays(from, to);
    return days !== null && days > MAX_EXPORT_PERIOD_DAYS;
}
