<template>
    <div class="flex my-2 items-start gap-x-4">
        <Listbox
            as="div"
            class="w-96"
            v-model="selected"
            :disabled="!canEditComponent"
        >
            <ListboxLabel
                class="block text-sm font-bold leading-6"
                :class="inSidebar ? 'text-white' : 'text-gray-900'"
            >
                {{ data.data.label }}
            </ListboxLabel>

            <div class="relative mt-2">
                <ListboxButton
                    class="menu-button"
                    :class="inSidebar ? 'bg-primary text-white border  border-gray-300' : ''"
                >
                    <div class="block truncate">{{ selected }}</div>
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                    <IconChevronDown class="h-5 w-5 text-gray-400" aria-hidden="true" />
                  </span>
                </ListboxButton>

                <transition
                    leave-active-class="transition ease-in duration-100"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <ListboxOptions
                        class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base ring-1 shadow-lg ring-black/5 focus:outline-hidden sm:text-sm"
                        :class="inSidebar ? 'bg-primary text-white border border-gray-300' : 'bg-white'"
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
                              active ? 'bg-indigo-600 text-white' : inSidebar ? 'text-white' : 'text-gray-900',
                              'relative cursor-default select-none py-2 pl-3 pr-9'
                            ]"
                                        >
                            <span :class="[isSelected ? 'font-semibold' : 'font-normal', 'block truncate']">
                              {{ item.value }}
                            </span>

                                <span
                                    v-if="isSelected"
                                    :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']"
                                >
                  <IconCircleCheck class="h-5 w-5" aria-hidden="true" />
                </span>
                            </li>
                        </ListboxOption>
                    </ListboxOptions>
                </transition>
            </div>
        </Listbox>

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
import { computed, onMounted, ref, watch } from "vue";
import axios from 'axios';
import InfoButtonComponent from "@/Pages/Projects/Tab/Components/InfoButtonComponent.vue";
import {IconChevronDown , IconCircleCheck} from "@tabler/icons-vue";
import {useProjectDataListener} from "@/Composeables/Listener/useProjectDataListener.js";


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

onMounted(() => {
    useProjectDataListener(projectData.value, props.projectId).init();
});


watch(
    () => props.data,
    (newVal) => {
        selected.value = normalizeSelected(newVal.project_value ? newVal.project_value.data.selected : newVal.data.selected);
    },
    { deep: true }
);

async function updateTextData(value) {
    try {
        await axios.patch(
            route("project.tab.component.update", {
                project: props.projectId,
                component: props.data.id,
            }),
            { data: { selected: value } }
        );
        // Keine weitere Aktion nötig - der Broadcast aktualisiert die Komponente
    } catch (error) {
        console.error('Fehler beim Aktualisieren:', error);
    }
}
</script>

<style scoped>
</style>
