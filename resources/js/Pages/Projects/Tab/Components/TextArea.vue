<template>
    <div class="my-2 flex items-start w-full">
        <div class="w-full">
            <label
                :for="'component-' + data.id"
                class="componentLabel"
                :class="{'!text-white': inSidebar}"
            >
                {{ projectData.data.label }}
            </label>
            <!-- Mit Schreibrecht immer direkt das Eingabefeld (leer = sofort erkennbar, dass man hier eintragen kann) -->
            <div v-if="canEditComponent" class="mt-2 w-full flex">
                <BaseTextarea
                    :placeholder="data.data.placeholder"
                    :rows="4"
                    :bg-color="inSidebar ? '!bg-surface-inverse !border-white/10 !w-80' : 'bg-white'"
                    class="w-full"
                    :class="inSidebar ? '!w-80' : 'w-full'"
                    :id="'component-' + data.id"
                    no-margin-top
                    @focusout="updateTextData()"
                    v-model="text"
                    :maxlength="2000"
                />
            </div>
            <!-- Nur-Lesen: Text anzeigen -->
            <div
                v-else
                class="mt-1 subpixel-antialiased whitespace-pre-line text-sm"
                :class="inSidebar ? 'text-white/70' : (text ? 'text-text' : 'text-text-subtle')"
            >{{ text || '–' }}</div>
        </div>

        <InfoButtonComponent :component="component" v-if="component" />
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import axios from 'axios';
import { useProjectDataListener } from "@/Composeables/Listener/useProjectDataListener.js";

import InfoButtonComponent from "@/Pages/Projects/Tab/Components/InfoButtonComponent.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";

defineOptions({ name: "TextArea" });

// Props
const props = defineProps({
    data: { type: Object, required: true },
    projectId: { type: [String, Number], required: true },
    inSidebar: { type: Boolean, default: false },
    canEditComponent: { type: Boolean, default: true },
    projectWriteIds: { type: Array, default: () => [] },
    project: { type: Object, default: () => ({}) },
    projectManagerIds: { type: Array, default: () => [] },
    component: { type: Object, default: null },
});

// Ableitungen/State
const projectData = computed(() => props.data);

// Editor-Text (Plain, ohne HTML) – identisch zur Options-API
const text = ref(
    props.data.project_value?.text_without_html
        ? props.data.project_value.text_without_html
        : props.data.data.text
);
// Nur bei tatsächlicher Änderung speichern (das Feld ist jetzt dauerhaft offen, Fokuswechsel sind häufig)
let lastSavedText = text.value;

// Listener initialisieren (wie zuvor im mounted)
onMounted(() => {
    useProjectDataListener(props.data, props.projectId).init();
});

// Deep-Watch: wenn sich eingehende Daten ändern, Editor-Inhalt synchronisieren
watch(
    () => props.data,
    (newVal) => {
        text.value = newVal.project_value?.text_without_html
            ? newVal.project_value.text_without_html
            : newVal.data.text;
        lastSavedText = text.value;
    },
    { deep: true }
);

// Patch-Aufruf mit axios (ohne Page Reload)
async function updateTextData() {
    if ((text.value ?? '') === (lastSavedText ?? '')) return;
    try {
        await axios.patch(
            route("project.tab.component.update", {
                project: props.projectId,
                component: props.data.id,
            }),
            { data: { text: text.value } }
        );
        lastSavedText = text.value;
        // Keine weitere Aktion nötig - der Broadcast aktualisiert die Komponente
    } catch (error) {
        console.error('Fehler beim Aktualisieren:', error);
    }
}

</script>

<style scoped>
</style>
