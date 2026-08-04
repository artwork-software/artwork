<template>
    <ProjectSettingsHeader :title="$t('Project Role Settings')">
        <template #actions>
            <button class="ui-button-add" @click="showAddProjectRoleModal = true">
                <PropertyIcon name="IconCirclePlus" stroke-width="1" class="size-5" />
                {{ $t('Add Project Role') }}
            </button>
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
            <div v-for="role in projectRoles">
                <div class="rounded-lg bg-surface-sunken px-4 py-5 mb-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-lg">
                                {{role.name}}
                            </div>
                        </div>
                        <div class="flex gap-x-3">
                            <PropertyIcon name="IconEdit" class="w-5 h-5 text-artwork-buttons-context cursor-pointer" @click="openRoleEditForm(role)"/>
                            <PropertyIcon name="IconTrash "class="w-5 h-5 text-artwork-buttons-context cursor-pointer" @click="openDeleteRoleModal(role)"/>
                        </div>
                    </div>
                </div>
            </div>

        <BaseModal  modal-image="/Svgs/Overlays/illu_project_edit.svg" v-if="showAddProjectRoleModal" @closed="closeAddProjectRoleModal">
            <ModalHeader
                :title="projectRoleForm.id ? $t('Edit project role') : $t('Add Project Role')"
                :description="$t('Add a new project role.')"
            />
            <BaseInput label="Name" id="title" v-model="projectRoleForm.name" />
            <div class="justify-center flex w-full my-6">
                <FormButton :text="$t('Save')" :disabled="projectRoleForm.name.length < 1" @click="addProjectRole"/>
            </div>
        </BaseModal>

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
import PlusButton from "@/Layouts/Components/General/Buttons/PlusButton.vue";
import {useForm} from "@inertiajs/vue3";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import Input from "@/Jetstream/Input.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import GlassyIconButton from "@/Artwork/Buttons/GlassyIconButton.vue";
import {IconCirclePlus} from "@tabler/icons-vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";

export default {
    name: "ProjectRoles",
    components: {
        ConfirmDeleteModal,
        SettingsGuideBanner,
        PropertyIcon,
        ProjectSettingsHeader,
        GlassyIconButton,
        BaseInput,
        TextInputComponent, ModalHeader, FormButton, Input, BaseModal, PlusButton},
    mixins: [IconLib],
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
        IconCirclePlus,
        closeAddProjectRoleModal() {
            this.showAddProjectRoleModal = false;
        },
        addProjectRole() {
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
