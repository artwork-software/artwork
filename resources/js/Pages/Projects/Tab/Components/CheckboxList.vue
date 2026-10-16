<template>
    <div class="flex my-2 items-start gap-x-4">
        <fieldset class="min-w-0">
            <legend class="componentLabel" :class="{ '!text-white': inSidebar }">
                {{ data.data.label }}
            </legend>
            <div class="mt-2 space-y-1.5">
                <label
                    v-for="(option, index) in options"
                    :key="option"
                    :for="`checkbox-list-${data.id}-${index}`"
                    class="flex items-start gap-2 text-sm"
                    :class="inSidebar ? 'text-white' : 'text-text'"
                >
                    <input
                        :id="`checkbox-list-${data.id}-${index}`"
                        type="checkbox"
                        class="input-checklist mt-0.5"
                        :checked="selected.includes(option)"
                        :disabled="!canEditComponent"
                        @change="toggle(option, $event.target.checked)"
                    />
                    <span class="break-words">{{ option }}</span>
                </label>
                <p v-if="!options.length" class="text-xs text-text-subtle">
                    {{ $t('No options defined yet.') }}
                </p>
            </div>
        </fieldset>

        <InfoButtonComponent :component="component" />
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import axios from 'axios';
import InfoButtonComponent from '@/Pages/Projects/Tab/Components/InfoButtonComponent.vue';
import { useProjectDataListener } from '@/Composeables/Listener/useProjectDataListener.js';
import { createSerializedSaver } from '@/Helper/serializedSave.js';
import { selectedValues, selectionOptions } from '@/Helper/ComponentSelection.js';

defineOptions({
    name: 'CheckboxList',
});

const props = defineProps({
    data: { type: Object, required: true },
    projectId: { type: Number, required: true },
    inSidebar: { type: Boolean, default: false },
    canEditComponent: { type: Boolean, required: true },
    component: { type: Object, required: true },
});

const options = computed(() => selectionOptions(props.data.data));

// Ohne eigenen Projektwert gilt die Vorauswahl der Komponente
function storedSelected() {
    return selectedValues(props.data.project_value ? props.data.project_value.data?.selected : props.data.data?.selected);
}

const selected = ref(storedSelected());

// Getter: Inertia-Besuche mit preserveState ersetzen props.data, die Komponente bleibt gemountet
const dataListener = useProjectDataListener(() => props.data, props.projectId);
onMounted(() => dataListener.init());
onBeforeUnmount(() => dataListener.stop());

// Nacheinander speichern: bei schnellem Klicken gewinnt sonst ggf. eine ältere Antwort
const selectionSaver = createSerializedSaver({
    send: (values) => axios.patch(
        route('project.tab.component.update', {
            project: props.projectId,
            component: props.data.id,
        }),
        { data: { selected: values } }
    ),
    onSaved: (response) => {
        dataListener.saved(response?.data?.project_value);
        selected.value = storedSelected();
    },
    onIntermediateSaved: (response) => {
        dataListener.saved(response?.data?.project_value);
    },
    onFailed: (error) => {
        console.error('Fehler beim Aktualisieren:', error);
        selected.value = storedSelected();
    },
});

watch(
    () => props.data,
    () => {
        if (selectionSaver.isSaving()) return;
        selected.value = storedSelected();
    },
    { deep: true }
);

function toggle(option, checked) {
    const next = checked
        ? [...selected.value, option]
        : selected.value.filter((value) => value !== option);
    // Reihenfolge wie die Optionen der Komponente (so speichert es auch das Backend)
    selected.value = options.value.filter((value) => next.includes(value));
    selectionSaver.save(selected.value);
}
</script>
