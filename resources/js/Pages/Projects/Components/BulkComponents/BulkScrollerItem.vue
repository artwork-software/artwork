<template>
    <component :is="tag" ref="el">
        <slot />
    </component>
</template>

<script setup>
import {nextTick, ref, watch} from 'vue';
import {useDynamicScrollerItem} from 'vue-virtual-scroller';

/**
 * Ersatz für DynamicScrollerItem (vue-virtual-scroller 2.x) mit einer Korrektur:
 *
 * Die Bibliothek misst eine wiederverwendete (recycelte) Zeile NICHT neu, wenn für die
 * neu zugewiesene Item-ID bereits eine Höhe im Cache steht — sie vertraut dem Cache und
 * verlässt sich sonst nur auf den ResizeObserver. Der feuert aber nur, wenn sich das
 * DOM-Element selbst in der Höhe ändert. Wechselt eine Zeile von Termin A (z. B. 76 px
 * mit Fehlerhinweis) zu Termin B, dessen gecachte Höhe veraltet ist, aber dessen echte
 * Höhe der von A entspricht, bleibt der falsche Cache-Wert stehen: Zeilen werden mit
 * zu großem/kleinem Abstand positioniert, bis die Seite neu geladen wird.
 *
 * Veraltet wird der Cache immer dann, wenn sich die Höhe eines Termins ändert, während
 * er NICHT gerendert ist (Validierungsfehler für alle Zeilen, Beschreibungsspalte an/aus,
 * Terminname ergänzt …). Deshalb: bei jedem ID-Wechsel der Zeile einmal neu messen —
 * ein offsetHeight-Read nach dem Render, vernachlässigbar gegenüber dem Zeilen-Render.
 */
const props = defineProps({
    item: {type: Object, required: true},
    active: {type: Boolean, default: false},
    index: {type: Number, default: undefined},
    sizeDependencies: {type: [Object, Array], default: null},
    tag: {type: String, default: 'div'},
});
const emit = defineEmits(['resize']);

const el = ref(null);

const {id, updateSize} = useDynamicScrollerItem(
    () => ({
        item: props.item,
        active: props.active,
        index: props.index,
        sizeDependencies: props.sizeDependencies,
        watchData: false,
        emitResize: true,
    }),
    el,
    {onResize: (resizedId) => emit('resize', resizedId)},
);

// Recycelte Zeile bekommt ein anderes Item: nach dem Render frisch messen, auch wenn
// die Bibliothek wegen eines Cache-Treffers darauf verzichtet hätte.
watch(id, () => {
    nextTick(() => updateSize());
});
</script>
