<script setup>
import { ref, watch, onMounted, onBeforeUnmount, computed } from "vue";
import axios from 'axios';
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import { useProjectDataListener } from "@/Composeables/Listener/useProjectDataListener.js";
import { createComponentTextSave } from "@/Composeables/componentTextSave.js";
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

// Während getippt wird, überschreiben Live-Updates die ungespeicherte Eingabe nicht
const isFocused = ref(false);

// Getter: Inertia-Besuche mit preserveState ersetzen props.data, die Komponente bleibt gemountet
const dataListener = useProjectDataListener(() => props.data, props.projectId);
onMounted(() => {
    dataListener.init();
});
onBeforeUnmount(() => {
    dataListener.stop();
});

// Nur bei Änderung, nacheinander (neuester Wert gewinnt), eigene Antwort übernehmen
const textSave = createComponentTextSave({
    text,
    isEditing: isFocused,
    getStoredText: () => (props.data.project_value ? props.data.project_value.data.text : props.data.data.text),
    dataListener,
    send: (value) => axios.patch(
        route("project.tab.component.update", {
            project: props.projectId,
            component: props.data.id,
        }),
        { data: { text: value } }
    ),
});

function updateTextData() {
    isFocused.value = false;
    textSave.submit();
}

// Deep-Watch auf eingehende Daten (Broadcast-Nachladen, Abgleich anderer Instanzen)
watch(
    () => props.data,
    () => textSave.syncFromStored(),
    { deep: true }
);
</script>

<template>
    <div class="my-2 flex items-start gap-x-4">
        <!-- grow + min-w-0: als Flex-Kind sonst nur so breit wie der Platzhalter -->
        <div class="grow min-w-0">
            <label
                for="email"
                class="componentLabel"
                :class="{'!text-white': inSidebar}"
            >
                {{ projectData.data.label }}
            </label>

            <!-- Volle Spaltenbreite (die Tab-Spalte begrenzt auf max-w-2xl), statt hartem w-96 -->
            <div class="mt-2 w-full">
                <BaseInput
                    :id="projectData.id"
                    type="text"
                    :disabled="!canEditComponent"
                    @focusin="isFocused = true"
                    @focusout="updateTextData"
                    v-model="text"
                    :placeholder="projectData.data.placeholder"
                    :input-classes="inSidebar ? '!bg-surface-inverse !border-white/10 !text-text-inverse' : ''"
                />
            </div>
        </div>

        <InfoButtonComponent :component="component" v-if="component" />
    </div>
</template>

<style scoped>
</style>
