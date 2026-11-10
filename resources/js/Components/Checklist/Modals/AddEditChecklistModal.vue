<template>
    <ArtworkBaseModal
        :title="checklistToEdit ? 'Edit checklist' : 'New checklist'"
        :description="checklistToEdit ? 'Edit your checklist' : 'Create a new checklist. To save time, you can choose a template and customize it customize it afterwards.'"
        modal-size="sm:max-w-xl"
        @close="closeModal"
    >
        <form id="addEditChecklistForm" class="space-y-4" @submit.prevent="submit">
            <ArtworkBaseListbox
                v-if="!checklistToEdit"
                v-model="selectedTemplate"
                :items="templateOptions"
                by="id"
                option-label="name"
                label="Template"
            />

            <BaseInput
                v-if="!selectedTemplate?.id"
                id="checklistName"
                v-model="checklistForm.name"
                label="Name of the checklist"
                required
                :error="checklistForm.errors.name"
            />

            <div v-if="!project" class="space-y-3">
                <ProjectSearch @project-selected="addProjectToChecklist" />

                <div
                    v-if="selectedProject"
                    class="flex items-center justify-between gap-x-3 rounded-md border border-border-subtle bg-surface-sunken px-3 py-2"
                >
                    <span class="truncate text-sm font-medium text-text">{{ selectedProject.name }}</span>
                    <button
                        type="button"
                        class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-text-subtle transition-colors duration-150 hover:bg-surface-hover hover:text-danger motion-reduce:transition-none"
                        :aria-label="$t('Remove')"
                        @click="deleteSelectedProject"
                    >
                        <PropertyIcon name="IconX" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                    </button>
                </div>

                <LastedProjects
                    :limit="10"
                    @select="addProjectToChecklist"
                />
            </div>

            <!-- Tab-Auswahl: Pflicht, wenn ein Projekt verknüpft ist und kein fester Tab übergeben wurde -->
            <div v-if="(selectedProject || project) && !tab_id">
                <ArtworkBaseListbox
                    v-model="selectedTab"
                    :items="tabs"
                    by="id"
                    option-label="name"
                    label="Project tab"
                    placeholder="Select project tab"
                />
            </div>
            <!-- z. B. 422, wenn der Tab für die Person nicht (mehr) sichtbar ist -->
            <p v-if="checklistForm.errors.tab_id" class="text-[11.5px] text-danger">{{ checklistForm.errors.tab_id }}</p>

            <div v-if="!selectedTemplate?.id" class="space-y-3">
                <ArtworkBaseToggle
                    id="checklistPrivate"
                    v-model="checklistForm.private"
                    label="Private"
                    use-translation
                    is-small
                />
                <BaseAlertComponent
                    v-if="checklistForm.private"
                    type="info"
                    message="Only you can see this checklist. If you want to share the checklist with others, you can edit it later and make it visible to everyone."
                    use-translation
                    class="mb-0!"
                />
            </div>
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="checklistForm.processing"
                @click="closeModal"
            />
            <BaseUIButton
                type="submit"
                form="addEditChecklistForm"
                :label="checklistToEdit ? 'Save' : 'Create'"
                variant="primary"
                :icon="checklistToEdit ? 'IconDeviceFloppy' : 'IconCirclePlus'"
                :processing="checklistForm.processing"
                :disabled="!canSubmit"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, onMounted, ref, watch} from "vue";
import {useForm, usePage} from "@inertiajs/vue3";
import {useI18n} from "vue-i18n";
import axios from 'axios';
import ProjectSearch from "@/Components/SearchBars/ProjectSearch.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import ArtworkBaseToggle from "@/Artwork/Toggles/ArtworkBaseToggle.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import LastedProjects from "@/Artwork/LastedProjects.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

defineOptions({name: 'AddEditChecklistModal'})

const {t} = useI18n();

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

const emits = defineEmits([
    'closed'
]);

const noTemplateOption = {id: null, name: t('No template')};

const templateOptions = computed(() => [
    noTemplateOption,
    ...Object.values(props.checklist_templates ?? {}),
]);

const selectedTemplate = ref(noTemplateOption);
const selectedProject = ref(null);
const tabs = ref([]);
const selectedTab = ref(null);

const loadTabs = async () => {
    try {
        const {data} = await axios.get(route('tab.list'));
        tabs.value = Array.isArray(data) ? data : [];
    } catch (e) {
        tabs.value = [];
    }
};

// Tabs sind global, nicht projektbezogen: einmal laden
onMounted(() => {
    loadTabs();
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

watch(selectedTab, (val) => {
    // Ohne festen Tab aus den Props die Auswahl ins Formular übernehmen
    if (!props.tab_id) {
        checklistForm.tab_id = val?.id ?? null;
    }
});

const needsTabSelection = computed(() => Boolean(selectedProject.value || props.project) && !props.tab_id);

const canSubmit = computed(() => {
    if (checklistForm.processing) {
        return false;
    }
    if (!selectedTemplate.value?.id && !checklistForm.name?.trim()) {
        return false;
    }
    return !(needsTabSelection.value && !selectedTab.value?.id);
});

const closeModal = () => {
    if (checklistForm.processing) {
        return;
    }
    emits('closed');
}

const addProjectToChecklist = (project) => {
    checklistForm.project_id = project.id;
    selectedProject.value = project;
    // Bei Projektwechsel die Tab-Auswahl zurücksetzen
    if (!props.tab_id) {
        selectedTab.value = null;
        checklistForm.tab_id = null;
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

const submit = () => {
    if (!canSubmit.value) {
        return;
    }
    if (selectedTemplate.value?.id) {
        checklistForm.template_id = selectedTemplate.value.id;
        checklistForm.name = selectedTemplate.value.name;
    } else {
        checklistForm.template_id = null;
    }

    const options = {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            emits('closed', true)
        }
    };

    if (props.checklistToEdit) {
        checklistForm.patch(route('checklists.update', {checklist: props.checklistToEdit.id}), options);
    } else {
        checklistForm.post(route('checklists.store'), options);
    }
}
</script>
