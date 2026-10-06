<template>
    <div class="flex my-2 items-start gap-x-4">
        <Listbox
            as="div"
            class="w-96"
            v-model="selected"
            :disabled="!canEditComponent"
        >
            <ListboxLabel
                class="componentLabel"
                :class="{'!text-white': inSidebar}"
            >
                {{ data.data.label }}
            </ListboxLabel>

            <div class="relative mt-2">
                <ListboxButton
                    class="menu-button"
                    :class="inSidebar ? '!bg-surface-inverse !border-white/10 !text-white' : 'bg-white'"
                >
                    <div class="block truncate">{{ selected }}</div>
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                    <IconChevronDown class="h-5 w-5" :class="inSidebar ? 'text-white/70' : 'text-text-subtle'" aria-hidden="true" />
                  </span>
                </ListboxButton>

                <transition
                    leave-active-class="transition ease-in duration-100"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <ListboxOptions
                        class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base ring-1 shadow-lg ring-black/5 sm:text-sm"
                        :class="inSidebar ? '!bg-surface-inverse !border-white/10' : 'bg-white'"
                    >
                        <ListboxOption
                            as="template"
                            v-for="item in data.data.options"
                            :key="item.value"
                            :value="item.value"
                            v-slot="{ active, selected: isSelected }"
                        >
                            <li
                                @click="updateTextData(item.value)"
                                :class="[
                                    active
                                        ? 'bg-accent-600 text-white'
                                        : isSelected
                                            ? '!bg-accent-600/10'
                                            : '',
                                    inSidebar ? 'text-white' : 'text-text',
                                    'relative cursor-default select-none py-2 pl-3 pr-9'
                                ]"
                                        >
                            <span :class="[isSelected ? 'font-semibold' : 'font-normal', 'block truncate']">
                              {{ item.value }}
                            </span>

                                <span
                                    v-if="isSelected"
                                    :class="[active ? 'text-white' : 'text-accent-600', 'absolute inset-y-0 right-0 flex items-center pr-4']"
                                >
                                    <button
                                        type="button"
                                        class="inline-flex items-center"
                                        @mouseenter="onClearEnter"
                                        @mouseleave="onClearLeave"
                                        @focusin="onClearEnter"
                                        @focusout="onClearLeave"
                                        @click.stop.prevent="updateTextData(null)"
                                    >
                                        <IconX class="h-5 w-5" aria-hidden="true" />
                                    </button>
                </span>
                            </li>
                        </ListboxOption>
                    </ListboxOptions>
                </transition>
            </div>
        </Listbox>

        <Teleport to="body">
            <div
                v-if="clearTooltip.visible"
                class="pointer-events-none fixed z-[9999] -translate-y-1/2 whitespace-nowrap rounded bg-surface-inverse px-2 py-1 text-xs text-text-inverse shadow-sm"
                :style="{ left: clearTooltip.x + 'px', top: clearTooltip.y + 'px' }"
            >
                Auswahl aufheben
            </div>
        </Teleport>

        <InfoButtonComponent :component="component" />
    </div>
</template>

<script setup>
import {
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
} from "@headlessui/vue";
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from "vue";
import axios from 'axios';
import InfoButtonComponent from "@/Pages/Projects/Tab/Components/InfoButtonComponent.vue";
import {IconChevronDown , IconX} from "@tabler/icons-vue";
import {useProjectDataListener} from "@/Composeables/Listener/useProjectDataListener.js";
import {createSerializedSaver} from "@/Helper/serializedSave.js";


defineOptions({
    name: "DropDown",
});

const props = defineProps({
    data: { type: Object, required: true },
    projectId: { type: Number, required: true },
    inSidebar: { type: Boolean, default: false },
    canEditComponent: { type: Boolean, required: true },
    component: { type: Object, required: true },
});


const projectData = computed(() => props.data);


const normalizeSelected = (val) => {
    if (val === null || val === undefined) return '';
    return typeof val === 'object' && val !== null && 'value' in val ? val.value : val;
};
const selected = ref(
    normalizeSelected(props.data.project_value ? props.data.project_value.data.selected : props.data.data.selected)
);

const clearTooltip = reactive({
    visible: false,
    x: 0,
    y: 0,
});

let clearTooltipAnchorEl = null;

function positionClearTooltip() {
    if (!clearTooltipAnchorEl) return;
    const rect = clearTooltipAnchorEl.getBoundingClientRect();
    clearTooltip.x = rect.right + 8;
    clearTooltip.y = rect.top + rect.height / 2;
}

async function onClearEnter(event) {
    clearTooltipAnchorEl = event?.currentTarget ?? null;
    await nextTick();
    positionClearTooltip();
    clearTooltip.visible = true;
    window.addEventListener('scroll', positionClearTooltip, true);
    window.addEventListener('resize', positionClearTooltip);
}

function onClearLeave() {
    clearTooltip.visible = false;
    clearTooltipAnchorEl = null;
    window.removeEventListener('scroll', positionClearTooltip, true);
    window.removeEventListener('resize', positionClearTooltip);
}

// Getter: Inertia-Besuche mit preserveState ersetzen props.data, die Komponente bleibt gemountet
const dataListener = useProjectDataListener(() => props.data, props.projectId);
onMounted(() => {
    dataListener.init();
});

onBeforeUnmount(() => {
    onClearLeave();
    dataListener.stop();
});


function storedSelected() {
    return normalizeSelected(props.data.project_value ? props.data.project_value.data.selected : props.data.data.selected);
}

// Nacheinander speichern: bei schnellem Umwählen gewinnt sonst ggf. eine ältere Antwort
const selectionSaver = createSerializedSaver({
    send: (value) => axios.patch(
        route("project.tab.component.update", {
            project: props.projectId,
            component: props.data.id,
        }),
        { data: { selected: value } }
    ),
    onSaved: (response) => {
        // Broadcast geht an die anderen – hier und in weiteren Instanzen den gespeicherten Wert übernehmen
        dataListener.saved(response?.data?.project_value);
        // Angezeigt wird, was gespeichert ist (ggf. ein neuerer fremder Stand)
        selected.value = storedSelected();
    },
    // Zwischenstand steht schon in der DB: übernehmen, Auswahl (neuerer Wert folgt) nicht anfassen
    onIntermediateSaved: (response) => {
        dataListener.saved(response?.data?.project_value);
    },
    onFailed: (error) => {
        console.error('Fehler beim Aktualisieren:', error);
        // Auf den zuletzt ERFOLGREICH gespeicherten Stand zurück (Zwischenerfolge sind übernommen)
        selected.value = storedSelected();
    },
});

watch(
    () => props.data,
    () => {
        // Laufende eigene Speicherung: optimistische Auswahl nicht durch Live-Updates zurücksetzen
        if (selectionSaver.isSaving()) return;
        selected.value = storedSelected();
    },
    { deep: true }
);

function updateTextData(value) {
    // Optimistisches UI-Update (Antwort/Broadcast synchronisiert final)
    selected.value = normalizeSelected(value);
    selectionSaver.save(value);
}
</script>

<style scoped>
</style>
