import axios from "axios";

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
     */
    const download = async (url, params, fallbackFilename) => {
        const response = await axios.get(url, {params, responseType: "blob"});
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
