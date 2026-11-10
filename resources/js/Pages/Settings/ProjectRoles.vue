<template>
    <ProjectSettingsHeader :title="$t('Project Role Settings')">
        <template #actions>
            <BaseUIButton variant="primary" hide-icon @click="showAddProjectRoleModal = true">
                <PropertyIcon name="IconCirclePlus" stroke-width="1" class="size-5" />
                {{ $t('Add Project Role') }}
            </BaseUIButton>
        </template>
            <SettingsGuideBanner
                class="mb-6"
                storage-key="settings-guide.project.roles"
                title="What are project roles?"
                :paragraphs="[
                    'Project roles are plain labels such as project management, technology or dramaturgy — they are independent of system permissions and do not grant any rights.',
                    'You assign them per person in the project team. They are visible in the user profile and in the project role matrix export.',
                    'If the option “CRM contacts in project team” is enabled, roles can also be assigned to CRM contacts in the team.',
                ]"
            />
            <div v-for="role in projectRoles" :key="role.id">
                <div class="rounded-lg bg-surface-sunken px-4 py-5 mb-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-lg">
                                {{role.name}}
                            </div>
                        </div>
                        <div class="flex gap-x-1">
                            <button
                                type="button"
                                class="inline-flex size-8 cursor-pointer items-center justify-center rounded-md text-text-muted transition-colors duration-150 hover:bg-surface-hover hover:text-text motion-reduce:transition-none"
                                :aria-label="$t('Edit project role')"
                                @click="openRoleEditForm(role)"
                            >
                                <PropertyIcon name="IconEdit" class="size-5" stroke-width="1.5" aria-hidden="true"/>
                            </button>
                            <button
                                type="button"
                                class="inline-flex size-8 cursor-pointer items-center justify-center rounded-md text-text-muted transition-colors duration-150 hover:bg-surface-hover hover:text-danger motion-reduce:transition-none"
                                :aria-label="$t('Delete project role')"
                                @click="openDeleteRoleModal(role)"
                            >
                                <PropertyIcon name="IconTrash" class="size-5" stroke-width="1.5" aria-hidden="true"/>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        <ArtworkBaseModal
            v-if="showAddProjectRoleModal"
            :title="projectRoleForm.id ? 'Edit project role' : 'Add Project Role'"
            :description="projectRoleForm.id ? '' : 'Add a new project role.'"
            modal-size="sm:max-w-lg"
            @close="closeAddProjectRoleModal"
        >
            <form id="projectRoleForm" @submit.prevent="addProjectRole">
                <BaseInput
                    id="projectRoleName"
                    v-model="projectRoleForm.name"
                    label="Name"
                    required
                    :error="projectRoleForm.errors.name"
                    autofocus
                />
            </form>
            <template #footer>
                <BaseUIButton
                    label="Cancel"
                    is-cancel-button
                    hide-icon
                    :disabled="projectRoleForm.processing"
                    @click="closeAddProjectRoleModal"
                />
                <BaseUIButton
                    type="submit"
                    form="projectRoleForm"
                    label="Save"
                    variant="primary"
                    icon="IconDeviceFloppy"
                    :processing="projectRoleForm.processing"
                    :disabled="projectRoleForm.processing || projectRoleForm.name.trim().length < 1"
                />
            </template>
        </ArtworkBaseModal>

        <ConfirmDeleteModal
            v-if="showDeleteRoleModal"
            :title="$t('Delete project role')"
            :description="$t('Do you really want to delete this project role?')"
            @closed="closeDeleteRoleModal"
            @delete="deleteRole"
        />
    </ProjectSettingsHeader>
</template>

<script>
import ProjectSettingsHeader from "@/Pages/Settings/Components/ProjectSettingsHeader.vue";
import {useForm} from "@inertiajs/vue3";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

export default {
    name: "ProjectRoles",
    components: {
        ArtworkBaseModal,
        BaseUIButton,
        ConfirmDeleteModal,
        SettingsGuideBanner,
        PropertyIcon,
        ProjectSettingsHeader,
        BaseInput,
    },
    props: {
        projectRoles: {
            type: Array,
            required: true
        }
    },
    data() {
        return {
            projectRoleForm: useForm({
                id: null,
                name: '',
            }),
            showAddProjectRoleModal: false,
            showDeleteRoleModal: false,
            roleToDelete: null,
        }
    },
    methods: {
        closeAddProjectRoleModal() {
            this.showAddProjectRoleModal = false;
            // sonst bleibt nach "Bearbeiten" die id stehen und "Hinzufügen" überschreibt die alte Rolle
            this.projectRoleForm.reset();
            this.projectRoleForm.clearErrors();
        },
        addProjectRole() {
            if (this.projectRoleForm.processing || this.projectRoleForm.name.trim().length < 1) {
                return;
            }
            if (this.projectRoleForm.id) {
                this.projectRoleForm.patch(
                    route('project-roles.update', {project_role: this.projectRoleForm.id}),
                    {
                        preserveScroll: true,
                        onSuccess: () => {
                            this.showAddProjectRoleModal = false;
                            this.projectRoleForm.reset();
                        }
                    }
                );

                return;
            }

            this.projectRoleForm.post(route('project-roles.store'), {
                preserveScroll: true,
                onSuccess: () => {
                    this.showAddProjectRoleModal = false;
                    this.projectRoleForm.reset();
                }
            });
        },
        openRoleEditForm(role) {
            this.projectRoleForm.id = role.id;
            this.projectRoleForm.name = role.name;
            this.showAddProjectRoleModal = true;
        },
        openDeleteRoleModal(role) {
            this.roleToDelete = role;
            this.showDeleteRoleModal = true;
        },
        closeDeleteRoleModal() {
            this.showDeleteRoleModal = false;
            this.roleToDelete = null;
        },
        deleteRole() {
            if (!this.roleToDelete) {
                return;
            }

            this.projectRoleForm.delete(route('project-roles.destroy', {project_role: this.roleToDelete.id}), {
                preserveScroll: true,
                onSuccess: () => {
                    this.projectRoleForm.reset();
                    this.closeDeleteRoleModal();
                }
            });
        }
    }
}
</script>
