<template>
    <ArtworkBaseModal
        title="Add projects to group"
        description="Select a project to add to this group."
        modal-size="sm:max-w-2xl"
        @close="emits('close')"
    >
        <div class="space-y-5">
            <ProjectSearch no-project-groups @project-selected="addProjectToGroup" />

            <ul v-if="projectGroupForm.projectIdsToAdd.length > 0" class="flex flex-wrap items-center gap-2">
                <li
                    v-for="(groupProject, index) in projectGroupForm.projectIdsToAdd"
                    :key="groupProject.id"
                    class="flex items-center gap-x-2 rounded-full border border-border-subtle bg-surface-sunken py-1 pr-1.5 pl-1"
                >
                    <img
                        class="size-7 shrink-0 rounded-full object-cover"
                        :src="groupProject?.key_visual_path ? '/storage/keyVisual/' + groupProject.key_visual_path : '/storage/logo/artwork_logo_small.svg'"
                        alt=""
                    />
                    <span class="text-sm font-medium text-text">{{ groupProject.name }}</span>
                    <button
                        type="button"
                        class="inline-flex size-6 cursor-pointer items-center justify-center rounded-full text-text-subtle transition-colors duration-150 hover:bg-surface-hover hover:text-danger motion-reduce:transition-none"
                        :aria-label="$t('Remove')"
                        @click="deleteGroupFormListByIndex(index)"
                    >
                        <PropertyIcon name="IconX" class="size-4" stroke-width="1.5" aria-hidden="true" />
                    </button>
                </li>
            </ul>

            <LastedProjects
                :limit="10"
                without-group
                @select="addProjectToGroup"
            />

            <BaseAlertComponent
                type="warning"
                message="Please note: If you remove and save a project here, the project will be removed from the group"
                use-translation
                class="!mb-0"
            />
        </div>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="projectGroupForm.processing"
                @click="emits('close')"
            />
            <BaseUIButton
                label="Add projects to group"
                variant="primary"
                icon="IconFolderPlus"
                :processing="projectGroupForm.processing"
                @click="addProjectsToGroup"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {useForm} from "@inertiajs/vue3";
import ProjectSearch from "@/Components/SearchBars/ProjectSearch.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import LastedProjects from "@/Artwork/LastedProjects.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

defineOptions({name: 'ProjectGroupAddProject'})

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    projectsInGroup: {
        type: [Array, Object],
        default: () => [],
    }
})

const emits = defineEmits([
    'close',
]);

// Eigene Kopie: Ohne Speichern darf sich die Gruppenliste im Projektkopf nicht ändern
const projectGroupForm = useForm({
    projectIdsToAdd: [...Object.values(props.projectsInGroup ?? {})],
});

const addProjectToGroup = (projectToAdd) => {
    if (!projectToAdd?.id || projectGroupForm.projectIdsToAdd.some(project => project.id === projectToAdd.id)) {
        return;
    }

    projectGroupForm.projectIdsToAdd.push(projectToAdd);
}

const deleteGroupFormListByIndex = (index) => {
    projectGroupForm.projectIdsToAdd.splice(index, 1);
}

const addProjectsToGroup = () => {
    if (projectGroupForm.processing) {
        return;
    }
    projectGroupForm.post(route('project-group.add-projects', {projectGroup: props.project.id}), {
        preserveScroll: true,
        onSuccess: () => {
            emits('close');
        }
    });
}
</script>
