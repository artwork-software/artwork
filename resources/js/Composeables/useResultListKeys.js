import { computed, nextTick, ref, watch } from 'vue';
import { resultListKey } from '@/Composeables/resultListKeys.js';

/**
 * Bindet die Tastatursteuerung einer Trefferliste an ein Suchfeld (Pfeile, Enter, Escape) und liefert die
 * Angaben für Screenreader. Der Fokus bleibt im Suchfeld, markiert wird über aria-activedescendant.
 *
 * @param {object} options
 * @param {import('vue').Ref<Array>} options.results
 * @param {import('vue').Ref<boolean>} options.ready Treffer gehören zur aktuellen Eingabe
 * @param {(result: any) => void} options.onSelect
 * @param {() => void} options.onClose
 * @param {string} options.idPrefix eindeutig je Suchfeld
 */
export function useResultListKeys({ results, ready, onSelect, onClose, idPrefix }) {
    const activeIndex = ref(-1);
    const listId = `${idPrefix}-results`;
    const optionId = (index) => `${idPrefix}-result-${index}`;
    const activeOptionId = computed(() => activeIndex.value >= 0 ? optionId(activeIndex.value) : undefined);

    watch(results, () => {
        activeIndex.value = -1;
    });

    const onKeydown = (event) => {
        // während der Texteingabe über eine Eingabemethode (IME) nichts abfangen
        if (event.isComposing) return;
        // Treffer zu einer älteren Eingabe nicht per Tastatur wählen
        const count = ready.value ? results.value.length : 0;
        const outcome = resultListKey(event.key, activeIndex.value, count);
        if (!outcome) return;

        if (outcome.action === 'close') {
            if (results.value.length === 0 && !ready.value) return;
            event.preventDefault();
            activeIndex.value = -1;
            onClose();
            return;
        }

        event.preventDefault();
        if (outcome.action === 'select') {
            onSelect(results.value[outcome.index]);
            return;
        }

        activeIndex.value = outcome.index;
        nextTick(() => {
            document.getElementById(optionId(outcome.index))?.scrollIntoView({ block: 'nearest' });
        });
    };

    return { activeIndex, activeOptionId, listId, optionId, onKeydown };
}
