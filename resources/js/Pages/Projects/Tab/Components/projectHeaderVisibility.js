/**
 * Geteilter Schalter, mit dem ein Tab den Projektkopf ausblenden kann (z. B. das Budget für
 * mehr Platz in der Tabelle). Der Kopf liegt im Layout oberhalb der Tabs, daher ein kleines
 * Modul statt Events über mehrere Ebenen.
 */
import { reactive } from 'vue';

export const projectHeaderVisibility = reactive({
    hidden: false,
});
