<template>
    <app-layout full :title="$t('Checklist template') + ' (' +  templateForm.name + ') ' + $t('edit')">
        <div class="artwork-container">
            <div class="">
                <div class="max-w-screen-lg">
                    <div class="px-24">
                        <div class="flex">
                            <h2 class="font-black text-primary mb-4 font-lexend text-3xl">{{ $t('Checklist template')}}</h2>
                        </div>
                        <div class="text-secondary subpixel-antialiased max-w-screen-sm">
                            {{$t('You can create and edit your checklist template here - it can then be used in any project.')}}
                        </div>
                    </div>
                    <div class="flex mt-4 px-24">
                        <TextInputComponent
                            id="checklistName"
                            v-model="templateForm.name"
                            :label="$t('Name of the checklist template')"
                        />
                    </div>
                </div>

                <div class="bg-gray-100 py-4 px-24 mt-10">
                    <div>
                        <div class="headline4 flex items-center gap-x-5">
                            {{ $t('Checklist users') }}
                            <div @click="openChangeUsersModal"
                                 class="text-secondary flex items-center text-sm subpixel-antialiased cursor-pointer">
                                <IconEdit stroke-width="2"
                                    class="h-5 w-5 text-primaryText group-hover:text-white"
                                    aria-hidden="true"/>
                            </div>
                        </div>
                        <AlertComponent
                            class="mt-2 mb-2 w-1/3"
                            type="info"
                            text-size="text-sm"
                            :text="$t('The tasks in this checklist are automatically assigned to all users. Users who are not in the project are added automatically.')"
                        />
                    </div>
                    <div class="flex items-center">
                        <div v-if="templateForm.users.length === 0">
                        <span
                            class="text-secondary subpixel-antialiased cursor-pointer">{{ $t('No users added yet')}}</span>
                        </div>
                        <div v-else class="-mr-3 my-auto" v-for="(user, index) in templateForm.users">
                            <UserPopoverTooltip :user="user" height="12" width="12" :class="index > 0 ? '-ml-1' : ''" classes="!ring-2 ring-white"/>
                        </div>

                    </div>
                </div>

                <div class="flex px-24">
                    <div class="flex w-full mt-12">
                        <div>
                            <AddButtonBig @click="openAddTaskModal()" :text="$t('New task')"/>
                        </div>
                        <div v-if="this.$page.props.show_hints" class="flex">
                            <SvgCollection svgName="arrowLeft" class="ml-2"/>
                            <span
                                class="hind text-secondary tracking-tight ml-1 my-auto text-xl">{{ $t('Create new tasks')}}</span>
                        </div>
                    </div>
                </div>


                <div class="mt-10 px-24">
                    <draggable ghost-class="opacity-50" tag="transition-group" item-key="draggableID" v-model="templateForm.task_templates" @start="dragging=true" @end="dragging=false">
                        <template #item="{element}" :key="element.id">
                            <div class="flex mb-5">
                                <div class="flex flex-wrap"
                                     :class="dragging? 'cursor-grabbing' : 'cursor-grab'">
                                    <div class="flex flex-col w-full group">
                                        <div class="flex flex-row items-center">
                                            <div class="flex">
                                                <DotsVerticalIcon
                                                    class="h-5 w-5 -mr-3.5 text-secondary"></DotsVerticalIcon>
                                                <DotsVerticalIcon
                                                    class="h-5 w-5 text-secondary"></DotsVerticalIcon>
                                            </div>
                                            <input v-model="element.done"
                                                   type="checkbox"
                                                   class="input-checklist"/>
                                            <p class="ml-4 my-auto font-black"
                                               :class="element.done ? 'text-secondary' : 'text-primary'">
                                                {{ element.name }}</p>
                                            <button type="button" @click="deleteTaskFromTemplate(element)">
                                                <span class="sr-only">{{ $t('Remove task from checklist template')}}</span>
                                                <XCircleIcon class="ml-2 h-5 w-5 hover:text-error group-hover:block hidden "/>
                                            </button>
                                        </div>
                                        <div class="ml-10 text-secondary text-sm">
                                            {{ element.description }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </draggable>
                </div>
                <div class="mt-10 px-24">
                    <FormButton v-if="!showSuccess"
                               @click="editChecklistTemplate"
                               :text="$t('Save')" />
                    <button v-else
                            class="px-24 rounded-full items-center py-2.5 border bg-success focus:outline-none border-transparent"
                    >
                        <CheckIcon class="h-7 w-7 inline-block text-secondaryHover"/>
                    </button>
                </div>
            </div>
        </div>
        <!-- Add Task Modal-->
        <BaseModal @closed="closeAddTaskModal" v-if="addingTask" modal-image="/Svgs/Overlays/illu_task_new.svg" :show-image="true">
                <form @submit.prevent="addTaskToTemplate" class="mx-4">
                    <ModalHeader
                        :title="$t('New task')"
                    />
                    <div class="grid grid-cols-1 gap-4">
                        <TextInputComponent
                            id="task_name"
                            v-model="newTaskName"
                            :label="$t('Task')"
                        />
                        <TextareaComponent
                            :label="$t('Comment')"
                            v-model="newTaskDescription"
                            rows="4"
                            id="newTaskDescription"
                        />
                        <div class="flex items-center justify-center mt-4">
                            <FormButton
                                type="submit"
                                :disabled="this.newTaskName === ''"
                                :text="$t('Add')"
                            />
                        </div>
                    </div>

                </form>
        </BaseModal>
        <!-- Change Teams Modal -->
        <BaseModal @closed="closeChangeUsersModal" full-modal v-if="showChangeUsersModal" modal-image="/Svgs/Overlays/illu_checklist_team_assign.svg" >
                <div class="mx-4">
                    <ModalHeader
                        :title="$t('Assign checklist template')"
                        :description="$t('Type the name of the user to whom you want to assign the checklist template.')"
                    />
                    <div class="mt-6 relative">
                    <UserSearch @user-selected="addUser" />
                </div>
                    <div class="py-4 bg-gray-100 px-6 mt-10" v-if="templateForm.users.length > 0">
                        <div v-for="(user,index) in templateForm.users"
                              class="flex mr-1 my-3 rounded-full items-center font-bold text-primary">
                             <div class="flex items-center">
                                <img class="h-5 w-5 mr-2 object-cover rounded-full"
                                     :src="user.profile_photo_url"
                                     alt=""/>
                                {{ user.first_name }} {{ user.last_name }}
                            </div>
                            <button type="button" @click="deleteUser(user)">
                                <span class="sr-only">{{ $t('Remove user from checklist template')}}</span>
                                <XCircleIcon class="ml-2 mt-1 h-5 w-5 hover:text-error "/>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center justify-center mb-4 mt-10">
                        <FormButton
                            @click="closeChangeUsersModal"
                            :text="$t('Assign')"/>
                    </div>
                </div>
        </BaseModal>

        <ConfirmDeleteModal
            title="Möchtest du die Seite verlassen?"
            description="Alle ungespeicherten Änderungen gehen verloren."
            @delete="confirmBack"
            v-if="showUnsavedConfirmModal"
            @closed="showUnsavedConfirmModal = false"
            />
    </app-layout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {PencilAltIcon, TrashIcon, XIcon} from "@heroicons/vue/outline";
import {CheckIcon, ChevronDownIcon, DotsVerticalIcon, XCircleIcon, PlusSmIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import JetButton from "@/Jetstream/Button.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import JetInput from "@/Jetstream/Input.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import draggable from "vuedraggable";
import {useForm} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import AddButtonBig from "@/Layouts/Components/General/Buttons/AddButtonBig.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import IconLib from "@/Mixins/IconLib.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import ConfirmationModal from "@/Jetstream/ConfirmationModal.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";

export default {
    mixins: [Permissions, IconLib],
    name: "Template Edit",
    props: ['checklist_template'],
    components: {
        TextareaComponent,
        ModalHeader,
        ConfirmDeleteModal,
        ConfirmationModal,
        UserPopoverTooltip,
        UserSearch,
        AlertComponent,
        TextInputComponent,
        BaseModal,
        FormButton,
        AddButtonBig,
        TeamIconCollection,
        AppLayout,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        XIcon,
        PencilAltIcon,
        TrashIcon,
        DotsVerticalIcon,
        SvgCollection,
        XCircleIcon,
        JetButton,
        JetDialogModal,
        JetInput,
        JetInputError,
        CheckIcon,
        ChevronDownIcon,
        PlusSmIcon,
        draggable,
    },
    data() {
        return {
            deletingTeam: false,
            showSuccess: false,
            deletingAllMembers: false,
            user_query: "",
            addingTask: false,
            dragging: false,
            showChangeUsersModal: false,
            user_search_results: [],
            templateForm: useForm({
                name: this.checklist_template.name,
                user_id: this.$page.props.auth.user.id,
                task_templates: this.checklist_template.task_templates? this.checklist_template.task_templates : [],
                users: this.checklist_template.users? this.checklist_template.users : [],
            }),
            newTaskName:"",
            newTaskDescription:"",
            taskForm: useForm({
                name: "",
                description: "",
            }),
            showEmptyTaskNameError: false,
            showUnsavedConfirmModal: false
        }
    },
    methods: {
        openChangeUsersModal(){
            this.showChangeUsersModal = true;
        },
        closeChangeUsersModal(){
            this.showChangeUsersModal = false;
        },
        openAddTaskModal(){
            this.addingTask = true;
        },
        closeAddTaskModal(){
            this.addingTask = false;
            this.newTaskName = "";
            this.newTaskDescription = "";
        },
        showSuccessButton() {
            this.showSuccess = true;
            setTimeout(() => {
                this.showSuccess = false
            }, 1000)
        },
        editChecklistTemplate() {
            if (this.templateForm.name === '') {
                this.showEmptyTaskNameError = true;
                return;
            }

            this.showEmptyTaskNameError = false;

            this.templateForm.patch(
                route(
                    'checklist_templates.update',
                    {
                        checklist_template: this.checklist_template.id
                    }
                )
            );
            this.showSuccessButton();
        },
        addUser(user) {
            for (let assignedUser of this.templateForm.users) {
                //if team is already assigned do nothing
                if (user.id === assignedUser.id) {
                    this.user_query = ""
                    return;
                }
            }
            this.templateForm.users.push(user);
            this.user_query = "";
            this.user_search_results = []
        },
        deleteUser(user) {
            this.templateForm.users.splice(this.templateForm.users.indexOf(user), 1);
        },
        addTaskToTemplate(){
            this.templateForm.task_templates.push({name:this.newTaskName,description:this.newTaskDescription});

            this.closeAddTaskModal();
        },
        deleteTaskFromTemplate(taskToDelete){
            this.templateForm.task_templates.splice(this.templateForm.task_templates.indexOf(taskToDelete), 1);
        }
    },
    watch: {
        user_query: {
            handler() {
                if (this.user_query.length > 0) {
                    axios.get('/users/search', {
                        params: {query: this.user_query}
                    }).then(response => {
                        this.user_search_results = response.data
                    })
                }
            },
            deep: true
        }
    }
}
</script>

<style scoped>

</style>
