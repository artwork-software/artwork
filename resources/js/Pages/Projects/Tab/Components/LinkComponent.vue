<template>
    <div class="my-2 flex items-start gap-x-4">
        <div>
            <div class="flex items-center gap-x-2">
                <label
                    for="email"
                    class="block text-sm font-bold leading-6"
                    :class="inSidebar ? 'text-white' : ' text-gray-900'"
                >
                    {{ projectData.data.label }}
                </label>

                <component
                    :is="IconEdit"
                    class="inline size-4 ml-2 cursor-pointer text-gray-400 hover:text-gray-600"
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
                    :label="projectData.data.placeholder"
                    without-translation
                    name="email"
                    id="email"
                    :input-classes="inSidebar ? '!bg-artwork-navigation-background !border-zinc-600 !text-white' : ''"
                />
            </div>

            <div v-else>
                <a
                    :href="text"
                    target="_blank"
                    class="text-blue-600 hover:underline"
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
import { ref, computed, watch, onMounted } from "vue";
import axios from 'axios';
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import { useProjectDataListener } from "@/Composeables/Listener/useProjectDataListener.js";
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

// Listener wie zuvor im mounted
onMounted(() => {
    useProjectDataListener(props.data, props.projectId).init();
});

// Server-Update mit axios (ohne Page Reload)
async function updateTextData() {
    try {
        await axios.patch(
            route("project.tab.component.update", {
                project: props.projectId,
                component: props.data.id,
            }),
            { data: { text: text.value } }
        );
        // Edit-Modus schließen nach erfolgreichem Update
        showTextField.value = false;
        // Keine weitere Aktion nötig - der Broadcast aktualisiert die Komponente
    } catch (error) {
        console.error('Fehler beim Aktualisieren:', error);
    }
}

// Deep-Watch: wenn projectData geändert wird, Text synchronisieren
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

<style scoped>
</style>
