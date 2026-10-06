<template>
    <div class="my-2 flex items-start gap-x-4">
        <div>
            <div class="flex items-center gap-x-2">
                <label
                    for="email"
                    class="componentLabel"
                    :class="{'!text-white': inSidebar}"
                >
                    {{ projectData.data.label }}
                </label>

                <component
                    :is="IconEdit"
                    class="inline size-4 ml-2 cursor-pointer"
                    :class="inSidebar ? 'text-white/70 hover:text-white' : 'text-text-subtle hover:text-text-muted'"
                    v-if="canEditComponent"
                    @click="showTextField = !showTextField"
                />
            </div>

            <div class="mt-2 w-96" v-if="showTextField">
                <BaseInput
                    :id="projectData.id"
                    type="text"
                    :disabled="!canEditComponent"
                    @focusout="updateTextData"
                    v-model="text"
                    :placeholder="projectData.data.placeholder"
                    name="email"
                    id="email"
                    :input-classes="inSidebar ? '!bg-surface-inverse !border-white/20 !text-text-inverse' : ''"
                />
            </div>

            <div v-else>
                <a
                    :href="text"
                    target="_blank"
                    class="text-accent-600 hover:underline"
                    v-if="text && text.length > 0"
                >
                    {{ text }}
                </a>
            </div>
        </div>

        <InfoButtonComponent :component="component" v-if="component" />
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";
import axios from 'axios';
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import { useProjectDataListener } from "@/Composeables/Listener/useProjectDataListener.js";
import { createComponentTextSave } from "@/Composeables/componentTextSave.js";
import InfoButtonComponent from "@/Pages/Projects/Tab/Components/InfoButtonComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import { IconEdit } from "@tabler/icons-vue";

// Für DevTools
defineOptions({ name: "LinkComponent" });

const props = defineProps({
    data: { type: Object, required: true },
    projectId: { type: [String, Number], required: true },
    inSidebar: { type: Boolean, default: false },
    canEditComponent: { type: Boolean, default: true },
    component: { type: Object, default: null },
});

// alias/read-only Ansicht der eingehenden Daten
const projectData = computed(() => props.data);

// initialer Textwert
const text = ref(
    props.data.project_value
        ? props.data.project_value.data.text
        : props.data.data.text
);

// Edit-UI toggeln
const showTextField = ref(false);

// Getter: Inertia-Besuche mit preserveState ersetzen props.data, die Komponente bleibt gemountet
const dataListener = useProjectDataListener(() => props.data, props.projectId);
onMounted(() => {
    dataListener.init();
});
onBeforeUnmount(() => {
    dataListener.stop();
});

// Nur bei Änderung, nacheinander (neuester Wert gewinnt), eigene Antwort übernehmen. Offener Editor
// mit ungespeicherter Eingabe wird von Live-Updates nicht überschrieben.
const textSave = createComponentTextSave({
    text,
    isEditing: showTextField,
    getStoredText: () => (props.data.project_value ? props.data.project_value.data.text : props.data.data.text),
    dataListener,
    send: (value) => axios.patch(
        route("project.tab.component.update", {
            project: props.projectId,
            component: props.data.id,
        }),
        { data: { text: value } }
    ),
    // Edit-Modus schließen nach erfolgreichem Update (außer es wurde inzwischen weitergetippt)
    afterSaved: () => {
        if (!textSave.isDirty()) {
            showTextField.value = false;
        }
    },
});

function updateTextData() {
    if (!textSave.submit()) {
        showTextField.value = false;
    }
}

// Deep-Watch: wenn projectData geändert wird, Text synchronisieren
watch(
    () => props.data,
    () => textSave.syncFromStored(),
    { deep: true }
);
</script>

<style scoped>
</style>
