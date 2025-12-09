<script setup>
import { ref, watch, onMounted, computed } from "vue";
import axios from 'axios';
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import { useProjectDataListener } from "@/Composeables/Listener/useProjectDataListener.js";
import InfoButtonComponent from "@/Pages/Projects/Tab/Components/InfoButtonComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

// Komponentenname (für Devtools)
defineOptions({ name: "TextField" });

const props = defineProps({
    data: { type: Object, required: true },
    projectId: { type: [String, Number], required: true },
    inSidebar: { type: Boolean, default: false },
    canEditComponent: { type: Boolean, default: true },
    component: { type: Object, default: null },
});

// Alias für die Props-Daten (read-only)
const projectData = computed(() => props.data);

// Initialwert für das Textfeld (wie zuvor)
const text = ref(
    props.data.project_value
        ? props.data.project_value.data.text
        : props.data.data.text
);

// Listener initialisieren (wie zuvor im mounted)
onMounted(() => {
    useProjectDataListener(props.data, props.projectId).init();
});

// Patch-Aufruf mit axios (ohne Page Reload)
async function updateTextData() {
    try {
        await axios.patch(
            route("project.tab.component.update", {
                project: props.projectId,
                component: props.data.id,
            }),
            {
                data: {
                    text: text.value,
                },
            }
        );
        // Keine weitere Aktion nötig - der Broadcast aktualisiert die Komponente
    } catch (error) {
        console.error('Fehler beim Aktualisieren:', error);
    }
}

// Deep-Watch auf eingehende Daten (entspricht deinem watcher auf projectData)
watch(
    () => props.data,
    (newVal) => {
        text.value = newVal.project_value
            ? newVal.project_value.data.text
            : newVal.data.text;
    },
    { deep: true }
);
</script>

<template>
    <div class="my-2 flex items-start gap-x-4">
        <div>
            <label
                for="email"
                class="block text-sm font-bold leading-6"
                :class="inSidebar ? 'text-white' : ' text-gray-900'"
            >
                {{ projectData.data.label }}
            </label>

            <div class="mt-2 w-96">
                <BaseInput
                    :id="projectData.id"
                    type="text"
                    :disabled="!canEditComponent"
                    @focusout="updateTextData"
                    v-model="text"
                    :label="projectData.data.placeholder"
                    without-translation
                    :input-classes="inSidebar ? '!bg-artwork-navigation-background !border-zinc-600 !text-white' : ''"
                />
            </div>
        </div>

        <InfoButtonComponent :component="component" v-if="component" />
    </div>
</template>

<style scoped>
</style>
