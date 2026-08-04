<template>
    <ArtworkBaseModal @close="$emit('close')" :title="$t('Add projects to group')" :description="$t('Select a project to add to this group.')">
        <div>

            <div>
                <ProjectSearch no-project-groups @project-selected="addProjectToGroup" />
            </div>

            <div class="my-5 flex items-center flex-wrap gap-3">
                <div v-for="(groupProject, index) in projectGroupForm.projectIdsToAdd" class="group block shrink-0 bg-surface-sunken w-fit pr-3 rounded-full border border-border">
                    <div class="flex items-center">
                        <div>
                            <img class="inline-block size-9 rounded-full object-cover" :src="groupProject?.key_visual_path ? '/storage/keyVisual/' + groupProject?.key_visual_path : '/storage/logo/artwork_logo_small.svg'" alt="" />
                        </div>
                        <div class="mx-2">
                            <p class="text-sm/5 font-semibold text-text group-hover:text-text">{{ groupProject.name}}</p>
                        </div>
                        <div class="flex items-center">
                            <button type="button" @click="deleteGroupFormListByIndex(index)">
                                <IconX class="h-4 w-4 text-text-subtle hover:text-danger" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="my-4">
                <LastedProjects
                    :limit="10"
                    @select="handleOpenProject"
                    without-group
                />
            </div>


            <div class="mb-4">
                <AlertComponent type="error" :text="$t('Please note: If you remove and save a project here, the project will be removed from the group')" />
            </div>

            <div class="flex items-center justify-center">
                <BaseUIButton :label="$t('Add projects to group')" is-add-button @click="addProjectsToGroup" />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import {IconX} from "@tabler/icons-vue";

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import ProjectSearch from "@/Components/SearchBars/ProjectSearch.vue";
import {ref} from "vue";
import {useForm} from "@inertiajs/vue3";
import Button from "@/Jetstream/Button.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import LastedProjects from "@/Artwork/LastedProjects.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    projectsInGroup: {
        type: Object,
        required: true,
    }
})


const emits = defineEmits([
    'close',
]);

const projectGroupForm = useForm({
    projectIdsToAdd: props.projectsInGroup ? props.projectsInGroup : [],
});

const addProjectToGroup = (projectToAdd) => {
    // Prüfen, ob ein Projekt mit derselben ID bereits in der Liste ist
    if (projectGroupForm.projectIdsToAdd.some(project => project.id === projectToAdd.id)) {
        return;
    }

    projectGroupForm.projectIdsToAdd.push(projectToAdd);
}

const deleteGroupFormListByIndex = (index) => {
    projectGroupForm.projectIdsToAdd.splice(index, 1);
}

const addProjectsToGroup = () => {
    projectGroupForm.post(route('project-group.add-projects', {projectGroup: props.project.id}), {
        preserveScroll: true,
        onSuccess: () => {
            emits('close');
        }
    });
}

const handleOpenProject = (project) => {
    addProjectToGroup(project);
}
</script>

<style scoped>

</style>
