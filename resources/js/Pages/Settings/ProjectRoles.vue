<script>
import ProjectTabs from "@/Pages/Settings/Components/ProjectTabs.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PlusButton from "@/Layouts/Components/General/Buttons/PlusButton.vue";
import {useForm} from "@inertiajs/vue3";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import Input from "@/Jetstream/Input.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";

export default {
    name: "ProjectRoles",
    components: {FormButton, Input, BaseModal, PlusButton, AppLayout, ProjectTabs},
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
        }
    },
    methods: {
        closeAddProjectRoleModal() {
            this.showAddProjectRoleModal = false;
        },
        addProjectRole() {
            if(this.projectRoleForm.id) {
                this.projectRoleForm.patch(route('project-roles.update', {projectRole: this.projectRoleForm.id}), {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.showAddProjectRoleModal = false;
                        this.projectRoleForm.reset();
                    }
                });
            } else {
                this.projectRoleForm.post(route('project-roles.store'), {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.showAddProjectRoleModal = false;
                        this.projectRoleForm.reset();
                    }
                });
            }
        },
        openRoleEditForm(role) {
            this.projectRoleForm.id = role.id;
            this.projectRoleForm.name = role.name;
            this.showAddProjectRoleModal = true;
        },
        deleteRole(role) {
            this.projectRoleForm.delete(route('project-roles.destroy', {projectRole: role.id}), {
                preserveScroll: true,
                onSuccess: () => {
                    this.projectRoleForm.reset();
                }
            });
        }
    }
}
</script>

<template>
    <AppLayout :title="$t('Project Role Settings')">
        <div class="max-w-screen-lg my-8 ml-14 mr-40">
            <div class="">
                <h2 class="headline1 my-6">{{$t('Project Role Settings')}}</h2>
                <div class="xsLight">
                    {{$t('Define global settings for projects.')}}
                </div>
            </div>

            <ProjectTabs />


            <div class="flex items-center justify-end mb-10">
                <PlusButton @click="showAddProjectRoleModal = true"/>
            </div>

            <div v-for="role in projectRoles">
                <div class="rounded-lg bg-gray-50 px-4 py-5 mb-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-lg">
                                {{role.name}}
                            </div>
                        </div>
                        <div class="flex gap-x-3">
                            <IconEdit class="w-5 h-5 text-artwork-buttons-context cursor-pointer" @click="openRoleEditForm(role)"/>
                            <IconTrash class="w-5 h-5 text-artwork-buttons-context cursor-pointer" @click="deleteRole(role)"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <BaseModal  modal-image="/Svgs/Overlays/illu_project_edit.svg" v-if="showAddProjectRoleModal" @closed="closeAddProjectRoleModal">
            <input placeholder="Name" id="title" v-model="projectRoleForm.name" class="mt-4 p-4 inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300"/>


            <div class="justify-center flex w-full my-6">
                <FormButton :text="$t('Save')" :disabled="projectRoleForm.name.length < 1" @click="addProjectRole"/>
            </div>
        </BaseModal>
    </AppLayout>

</template>

<style scoped>

</style>
