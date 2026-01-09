<template>
    <div class="my-2 flex items-start w-full">
        <div>
            <label
                :for="'component-' + data.id"
                class="block text-sm font-bold leading-6"
                :class="inSidebar ? 'text-white' : ' text-gray-900'"
            >
                {{ projectData.data.label }}
            </label>
            <!-- Anzeige (HTML) bis geklickt wird -->
            <div v-if="descriptionClicked === false" @click="handleDescriptionClick()" class="flex items-center gap-x-1">
                <component v-if="!projectData.project_value?.data?.text" :is="IconBlockquote" class="size-4 text-gray-400" />
                <div
                    class="subpixel-antialiased"
                    :class="[projectData.project_value?.data?.text ? inSidebar ? 'text-gray-400 text-sm' : 'text-gray-800 text-sm' : 'text-gray-400 text-sm italic', ]"
                    v-html="projectData.project_value?.data?.text ? projectData.project_value.data.text : (canEditComponent ? t('Click here to add text') : '')">
                </div>
            </div>

            <!-- Editor -->
            <div v-else class="w-full" ref="descriptionWrapRef">
                <BaseTextarea
                    :disabled="!canEditComponent"
                    :label="data.data.placeholder"
                    ref="descriptionRef"
                    :rows="5"
                    :bg-color="inSidebar ? '!bg-artwork-navigation-background !border-zinc-600 !w-80' : '!w-96'"
                    :id="'component-' + data.id"
                    :show-label="false"
                    no-margin-top
                    @focusout="updateTextData()"
                    v-model="text"
                    :maxlength="2000"
                />
            </div>
        </div>

        <InfoButtonComponent :component="component" v-if="component" />
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick, getCurrentInstance } from "vue";
import axios from 'axios';
import { useI18n } from "vue-i18n";
import { useProjectDataListener } from "@/Composeables/Listener/useProjectDataListener.js";

import InfoButtonComponent from "@/Pages/Projects/Tab/Components/InfoButtonComponent.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import {IconBlockquote} from "@tabler/icons-vue";

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

// i18n
const { t } = useI18n();

// Zugriff auf globale Helfer ($can, $role, route) aus Mixins/Plugins
const { proxy } = getCurrentInstance();

// Ableitungen/State
const projectData = computed(() => props.data);

// Editor-Text (Plain, ohne HTML) – identisch zur Options-API
const text = ref(
    props.data.project_value?.text_without_html
        ? props.data.project_value.text_without_html
        : props.data.data.text
);

// Toggle zwischen Anzeige und Bearbeitung
const descriptionClicked = ref(false);

// Ref auf das Textarea (falls du Fokus/Selection wieder aktivieren willst)
const descriptionRef = ref(null);
const descriptionWrapRef = ref(null)

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
    },
    { deep: true }
);

// Patch-Aufruf mit axios (ohne Page Reload)
async function updateTextData() {
    try {
        await axios.patch(
            route("project.tab.component.update", {
                project: props.projectId,
                component: props.data.id,
            }),
            { data: { text: text.value } }
        );
        // Editor schließen nach erfolgreichem Update
        descriptionClicked.value = false;
        // Keine weitere Aktion nötig - der Broadcast aktualisiert die Komponente
    } catch (error) {
        console.error('Fehler beim Aktualisieren:', error);
    }
}

// Klick-Handler: nur bei Berechtigung in den Edit-Modus
function handleDescriptionClick() {
    if (!props.canEditComponent) return;

    const canWriteGlobally =
        proxy?.$can?.("write projects") ||
        proxy?.$role?.("artwork admin") ||
        proxy?.$can?.("admin projects");

    const userId = proxy?.$page?.props?.auth?.user?.id;
    const hasProjectWrite =
        Array.isArray(props.projectWriteIds) && userId
            ? props.projectWriteIds.includes(userId)
            : false;

    const isProjectManager =
        Array.isArray(props.projectManagerIds) && userId
            ? props.projectManagerIds.includes(userId)
            : false;

    const isDeptMember = !!props.project?.isMemberOfADepartment;

    if (props.canEditComponent || canWriteGlobally || hasProjectWrite || isProjectManager || isDeptMember) {
        descriptionClicked.value = true;

        nextTick(() => {
            requestAnimationFrame(() => {
                const root = descriptionWrapRef.value
                const ta = root?.querySelector?.("textarea")
                ta?.focus()
                ta?.select()
            })
        })
    }
}
</script>

<style scoped>
</style>
