/**
 * Menge eines Artikels für einen bestimmten Status ermitteln.
 *
 * Detail-Artikel führen ihre Status an den Detail-Einträgen (Summe der
 * Detail-Mengen mit diesem Status), normale Artikel am Pivot-Wert der
 * status_values-Relation. Spiegelt die Backend-Logik des Status-Filters
 * (InventoryArticleService::applyStatusFilter).
 */
export const getArticleStatusQuantity = (article, statusId) => {
    if (!article || !statusId) {
        return 0
    }

    const id = Number(statusId)

    if (article.is_detailed_quantity) {
        return (article.detailed_article_quantities ?? []).reduce((sum, detailed) => {
            const detailedStatusId = Number(detailed?.inventory_article_status_id ?? detailed?.status?.id)
            return detailedStatusId === id ? sum + (Number(detailed?.quantity) || 0) : sum
        }, 0)
    }

    const status = (article.status_values ?? []).find((statusValue) => Number(statusValue?.id) === id)

    return Number(status?.pivot?.value) || 0
}

/**
 * Zahlformat der Mengen-Spalte (Tausenderpunkte), aber ohne den
 * "Out of stock"-Sonderfall — eine Statusmenge von 0 bleibt "0".
 */
export const formatStatusQuantity = (quantity) =>
    (Number(quantity) || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')
