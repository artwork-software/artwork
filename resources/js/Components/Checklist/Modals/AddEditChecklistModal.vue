<template>
    <BaseModal @closed="$emit('closed')" full-modal v-if="true" :modal-image="checklistToEdit ? '/Svgs/Overlays/illu_checklist_edit.svg' : '/Svgs/Overlays/illu_checklist_new.svg'">
        <form @submit.prevent="submit">
            <div class="px-8 py-3 mb-4">
                <div class="font-bold font-lexend text-primary text-3xl my-2">
                    {{ checklistToEdit ? $t('Edit checklist') : $t('New checklist') }}
                </div>
                <div class="xsLight">
                    {{ checklistToEdit ? $t('Edit your checklist') : $t('Create a new checklist. To save time, you can choose a template and customize it customize it afterwards.') }}
                </div>
            </div>
            <div class="px-8 py-2" v-if="!checklistToEdit">
                <Listbox class="w-full" v-model="selectedTemplate">
                    <div class="relative">
                        <ListboxButton class="w-full h-12 flex xsDark items-center text-left border-2 border-gray-300 bg-white px-4 py-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
                            <div class="block truncate items-center">
                                <span>{{ selectedTemplate.name }}</span>
                            </div>
                            <div v-if="selectedTemplate.name === ''" class="block truncate">
                                {{ $t('No template') }}
                            </div>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                <IconChevronDown class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                            </div>
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
                                    <li :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                            <span :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                {{ $t('No template') }}
                                            </span>
                                        <span :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
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
                                    <li :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                            <span
                                                :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                {{ template.name }}
                                            </span>
                                        <span :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
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
            <div class="px-8 py-2 mb-6">
                <div class="flex">
                    <TextInputComponent
                        id="checklistName"
                        v-if="selectedTemplate.name === ''"
                        v-model="checklistForm.name"
                        :label="$t('Name of the checklist*')"
                    />
                </div>
            </div>

            <div class="bg-artwork-project-background px-8 py-4 mb-5" v-if="!project">
                <div >
                    <ProjectSearch @project-selected="addProjectToChecklist" />
                </div>


                <TagComponent
                    class="mt-4"
                    v-if="selectedProject"
                    :property="selectedProject"
                    :displayed-text="selectedProject.name"
                    :method="deleteSelectedProject"
                />
            </div>

            <div class="bg-artwork-project-background px-8 py-4 mb-5" v-if="selectedTemplate.name === ''">
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
            <div class="px-8 py-4 mb-4">
                <div class="flex items-center justify-center">
                    <FormButton
                        type="submit"
                        :disabled="checklistForm.name.length === 0 && !selectedTemplate.id"
                        :text="checklistToEdit ? $t('Save') : $t('Create')"
                    />
                </div>
            </div>
        </form>
    </BaseModal>
</template>

<script setup>

import {useForm, usePage} from "@inertiajs/vue3";
import {IconChevronDown, IconCircleCheckFilled} from "@tabler/icons-vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import {ref} from "vue";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions, Switch} from "@headlessui/vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import ProjectSearch from "@/Components/SearchBars/ProjectSearch.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";

const props = defineProps({
    project: {
        type: Object,
        required: true
    },
    tab_id: {
        type: Number,
        required: false
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
}

</script>

<style scoped>

</style>