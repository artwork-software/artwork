<template>
    <ArtworkBaseListbox
        v-model="selected"
        :items="units"
        by="id"
        option-label="name"
        use-translations
        :label="$t('Unit of the period')"
    />
</template>

<script setup>
import {computed} from "vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";

/**
 * Einheit der Überstunden-Abbaufrist: Tage (Standard, auch bei leerem Wert) oder Kalenderwochen. Bei
 * Kalenderwochen endet die Frist am Sonntag der N-ten Kalenderwoche nach der Woche, in der die
 * Überstunden entstanden sind.
 */
const props = defineProps({
    modelValue: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue']);

const units = [
    { id: 'days', name: 'Days' },
    { id: 'calendar_weeks', name: 'Calendar weeks (until the end of the week)' },
];

const selected = computed({
    get: () => units.find(unit => unit.id === props.modelValue) ?? units[0],
    set: (unit) => emit('update:modelValue', unit?.id ?? 'days'),
});
</script>
