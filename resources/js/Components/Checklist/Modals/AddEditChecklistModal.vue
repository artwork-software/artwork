<template>
    <ArtworkBaseModal @close="$emit('closed')" full-modal v-if="true" :title="checklistToEdit ? $t('Edit checklist') : $t('New checklist')" :description="checklistToEdit ? $t('Edit your checklist') : $t('Create a new checklist. To save time, you can choose a template and customize it customize it afterwards.')">
        <form @submit.prevent="submit" class="space-y-5">
            <div class="" v-if="!checklistToEdit">
                <Listbox class="w-full" v-model="selectedTemplate">
                    <div class="relative">
                        <ListboxButton class="menu-button">
                            <span v-if="selectedTemplate.name !== ''">{{ selectedTemplate.name }}</span>
                            <span v-else>
                                {{ $t('No template') }}
                            </span>
                            <IconChevronDown class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                        </ListboxButton>
                        <transition leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0">
                            <ListboxOptions
                                class="absolute z-10 mt-1 w-full bg-artwork-navigation-background shadow-lg max-h-32 rounded-md text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                <ListboxOption as="template" class="max-h-8"
                                               :key="'keineVorlage'"
                                               :value="{name:'',id:null}"
                                               v-slot="{ active, selected }">
                                    <li :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                            <span :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                {{ $t('No template') }}
                                            </span>
                                        <span :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                  <IconCircleCheckFilled v-if="selected"
                                                                         class="h-5 w-5 flex text-success"
                                                                         aria-hidden="true"
                                                  />
                                            </span>
                                    </li>
                                </ListboxOption>
                                <ListboxOption as="template" class="max-h-8"
                                               v-for="template in checklist_templates"
                                               :key="template.id"
                                               :value="template"
                                               v-slot="{ active, selected }">
                                    <li :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                            <span
                                                :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                {{ template.name }}
                                            </span>
                                        <span :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                  <IconCircleCheckFilled v-if="selected"
                                                                         class="h-5 w-5 flex text-success"
                                                                         aria-hidden="true"
                                                  />
                                            </span>
                                    </li>
                                </ListboxOption>
                            </ListboxOptions>
                        </transition>
                    </div>
                </Listbox>
            </div>
            <div class="">
                <div class="flex">
                    <BaseInput
                        id="checklistName"
                        v-if="selectedTemplate.name === ''"
                        v-model="checklistForm.name"
                        label="Name of the checklist*"
                    />
                </div>
            </div>

            <div class="" v-if="!project">
                <div>
                    <ProjectSearch @project-selected="addProjectToChecklist" />
                </div>

                <TagComponent
                    class="mt-4"
                    v-if="selectedProject"
                    :property="selectedProject"
                    :displayed-text="selectedProject.name"
                    :method="deleteSelectedProject"
                />

                <LastedProjects
                    :limit="10"
                    @select="addProjectToChecklist"
                />
            </div>

            <!-- Tab selection: required if a project is linked and no fixed tab_id is provided -->
            <div v-if="(selectedProject || project) && !tab_id" class="mt-4">
                <Listbox v-model="selectedTab" class="w-full">
                    <div class="relative">
                        <ListboxButton class="menu-button">
                            <span v-if="selectedTab?.name">{{ selectedTab.name }}</span>
                            <span v-else>
                                {{ $t('Select project tab') }}
                            </span>
                            <IconChevronDown class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                        </ListboxButton>
                        <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                            <ListboxOptions class="absolute z-10 mt-1 w-full bg-artwork-navigation-background shadow-lg max-h-40 rounded-md text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                <ListboxOption as="template" v-for="t in tabs" :key="t.id" :value="t" v-slot="{ active, selected }">
                                    <li :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                        <span :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">{{ t.name }}</span>
                                        <span :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                            <IconCircleCheckFilled v-if="selected" class="h-5 w-5 flex text-success" aria-hidden="true" />
                                        </span>
                                    </li>
                                </ListboxOption>
                            </ListboxOptions>
                        </transition>
                    </div>
                </Listbox>
            </div>

            <div class="" v-if="selectedTemplate.name === ''">
                <div class="flex items-center my-2" >
                    <Switch @click="checklistForm.private = !checklistForm.private" :class="[checklistForm.private ? 'bg-success' : 'bg-gray-300', 'relative inline-flex flex-shrink-0 h-3 w-6 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none']">
                        <span aria-hidden="true" :class="[checklistForm.private ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200']"/>
                    </Switch>
                    <span class="ml-2 text-sm" :class="checklistForm.private ? 'text-primary' : 'text-secondary'">
                        {{ $t('Private') }}
                    </span>
                </div>
                <AlertComponent
                    v-if="checklistForm.private"
                    :text="$t('Only you can see this checklist. If you want to share the checklist with others, you can edit it later and make it visible to everyone.')"
                    show-icon
                    icon-size="h-6 w-6"
                />
            </div>
            <div class="mt-4">
                <div class="flex items-center justify-end">
                    <BaseUIButton
                        type="submit"
                        :disabled="((!selectedTemplate.id) ? checklistForm.name.length === 0 : false) || (((selectedProject || project) && !tab_id) && !(selectedTab && selectedTab.id)) || checklistForm.processing"
                        :label="checklistToEdit ? $t('Save') : $t('Create')"
                    />
                </div>
            </div>
        </form>
    </ArtworkBaseModal>
</template>

<script setup>

import {useForm, usePage} from "@inertiajs/vue3";
import {IconChevronDown, IconCircleCheckFilled} from "@tabler/icons-vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import {ref, watch, onMounted} from "vue";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions, Switch} from "@headlessui/vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import ProjectSearch from "@/Components/SearchBars/ProjectSearch.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import LastedProjects from "@/Artwork/LastedProjects.vue";
import axios from 'axios';
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const props = defineProps({
    project: {
        type: Object,
        required: false,
        default: null
    },
    tab_id: {
        type: Number,
        required: false,
        default: null
    },
    checklist_templates: {
        type: Object,
        required: true
    },
    checklistToEdit: {
        type: Object,
        required: false
    },
    createOwnChecklist: {
        type: Boolean,
        required: false,
        default: false
    }
})

const selectedProject = ref(null);
const tabs = ref([]);
const selectedTab = ref(null);

const resolveTabListUrl = () => {
    try {
        // Prefer Ziggy route() if available
        // @ts-ignore
        if (typeof route === 'function') {
            // @ts-ignore
            return route('tab.list');
        }
    } catch (_) { /* ignore */ }
    // Fallback to hardcoded path
    return '/settings/tab/list';
};

const loadTabs = async () => {
    try {
        const url = resolveTabListUrl();
        const { data } = await axios.get(url);
        tabs.value = Array.isArray(data) ? data : [];
    } catch (e) {
        // Ensure tabs is an array to avoid empty options rendering issues
        tabs.value = [];
        // You could log to console in dev if needed
        // console.debug('Failed to load project tabs', e);
    }
};

onMounted(() => {
    // tabs are global, not project-specific; load once
    loadTabs();
});

watch(selectedTab, (val) => {
    // keep form in sync with selection when no fixed tab_id is provided via props
    if (!props.tab_id) {
        checklistForm.tab_id = val?.id ?? null;
    }
});

const emits = defineEmits([
    'closed'
]);

const selectedTemplate = ref({
    name: '',
    id: null
});

const checklistForm = useForm({
    name: props.checklistToEdit ? props.checklistToEdit.name : '',
    project_id: props.project ? props.project.id : null,
    private: props.checklistToEdit ? props.checklistToEdit.private : false,
    template_id: null,
    user_id: null,
    creator_id: usePage().props.auth.user.id,
    tab_id: props.tab_id ? props.tab_id : null
});

const addProjectToChecklist = (project) => {
    checklistForm.project_id = project.id;
    selectedProject.value = project;
    // reset tab selection when project changes
    if (!props.tab_id) {
        selectedTab.value = null;
        checklistForm.tab_id = null;
    }
}

const submit = () => {
    if ( selectedTemplate.value.id ) {
        checklistForm.template_id = selectedTemplate.value.id;
        checklistForm.name = selectedTemplate.value.name;
    }

    if (props.checklistToEdit) {
        checklistForm.patch(route('checklists.update', {checklist: props.checklistToEdit.id}), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                emits('closed', true)
            }
        });
    } else {
        checklistForm.post(route('checklists.store'), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                emits('closed', true)
            }
        });
    }

}

const deleteSelectedProject = () => {
    selectedProject.value = null
    checklistForm.project_id = null
    if (!props.tab_id) {
        selectedTab.value = null;
        checklistForm.tab_id = null;
    }
}

</script>

<style scoped>

</style>
