<template>
    <app-layout :title="$t('Checklist template') + ' ' + $t('Create')">
        <div class="artwork-container">
            <div class="flex-wrap">
                <div class="flex">
                    <h2 class="mb-4 headline1">{{$t('Checklist template')}}</h2>
                </div>
                <div class="xsLight max-w-screen-sm">
                    {{$t('You can create and edit your checklist template here - it can then be used in any project.')}}
                </div>
                <div class="flex mt-8">
                    <div class="max-w-2xl w-full">
                        <BaseInput
                            id="teamName"
                            v-model="templateForm.name"
                            :label="$t('Name of the checklist template')"/>
                        <span v-if="showEmptyTaskNameError" class="errorText">{{ $t('You must enter a name.')}}</span>
                    </div>
                </div>
                <div class="flex items-center mt-6 mr-8">
                    <div v-if="templateForm.users.length === 0">
                        <span
                            class="text-secondary subpixel-antialiased cursor-pointer">{{ $t('No users added yet')}}</span>
                    </div>
                    <div v-else class="-mr-3 my-auto" v-for="(user, index) in templateForm.users">
                        <img class="h-10 w-10 mr-2 object-cover rounded-full border-2 border-white"
                             :class="index !== 0 ? '-ml-2' : ''"
                             :src="user.profile_photo_url"
                             alt=""/>
                    </div>
                    <div @click="openChangeUsersModal"
                         class="text-secondary ml-4 flex items-center px-2 py-2 text-sm subpixel-antialiased cursor-pointer">
                        <PencilAltIcon
                            class="h-5 w-5 text-primaryText group-hover:text-white"
                            aria-hidden="true"/>
                    </div>
                </div>
                <div class="flex">
                    <div class="flex w-full mt-12">
                        <div class="">
                            <button @click="openAddTaskModal()" type="button"
                                    class="flex hover:bg-success my-auto items-center border border-transparent rounded-full shadow-sm text-white bg-artwork-buttons-create focus:outline-none">
                                <PlusSmIcon class="h-5 w-5" aria-hidden="true"/>
                            </button>
                        </div>
                        <div v-if="this.$page.props.show_hints" class="flex">
                            <SvgCollection svgName="arrowLeft" class="ml-2"/>
                            <span
                                class="ml-1 my-auto hind">{{$t('Create new tasks')}}</span>
                        </div>
                    </div>
                </div>
                <div class="mt-6">
                    <draggable ghost-class="opacity-50" tag="transition-group" item-key="draggableID"
                               v-model="templateForm.task_templates" @start="dragging=true" @end="dragging=false">
                        <template #item="{element}">
                            <div class="flex mt-6 flex-wrap w-full"
                                 :class="dragging? 'cursor-grabbing' : 'cursor-grab'">
                                <div class="flex w-full group">
                                    <input v-model="element.done"
                                           type="checkbox"
                                           class="input-checklist"/>
                                    <p class="ml-4 my-auto font-black"
                                       :class="element.done ? 'text-secondary' : 'sDark'">
                                        {{ element.name }}</p>
                                    <button type="button" @click="deleteTaskFromTemplate(element)">
                                        <span class="sr-only">
                                            {{$t('Remove task from checklist template')}}
                                        </span>
                                        <XCircleIcon class="ml-4 h-5 w-5 hover:text-error group-hover:block hidden "/>
                                    </button>
                                </div>
                                <div class="ml-10 xsLight">
                                    {{ element.description }}
                                </div>
                            </div>
                        </template>
                    </draggable>
                </div>
                <div class="pt-8">
                    <div class="mt-2 items-center">
                        <FormButton
                            v-if="!showSuccess"
                            @click="createChecklistTemplate"
                            :text="$t('Create template')"
                            />
                        <button v-else type="submit"
                                class="items-center rounded-full px-16 py-1 border bg-success focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                        >
                            <CheckIcon class="h-10 w-9 inline-block text-secondaryHover"/>
                        </button>
                    </div>
                </div>
            </div>

        </div>
        <!-- Add Task Modal-->
        <BaseModal @closed="closeAddTaskModal" v-if="addingTask" :show-image="false">
                <div class="mx-4">
                    <ModalHeader
                        :title="$t('New task')"
                    />
                    <form @submit.prevent="addTaskToTemplate" class="grid grid-cols-1 gap-4">
                        <BaseInput
                            id="task_name"
                            v-model="newTaskName"
                            :label="$t('Task')"
                            required
                        />
                        <BaseTextarea
                            :label="$t('Comment')"
                            v-model="newTaskDescription"
                            id="newTaskDescription"
                            rows="3"
                        />
                        <div class="flex items-center justify-center">
                            <FormButton
                                type="submit"
                                :disabled="this.newTaskName === ''"
                                :text="$t('Add')"/>
                        </div>
                    </form>

                </div>
        </BaseModal>
        <!-- Change Teams Modal -->
        <BaseModal @closed="closeChangeUsersModal" v-if="showChangeUsersModal" modal-image="/Svgs/Overlays/illu_checklist_team_assign.svg">
                <div class="mx-3">
                    <ModalHeader
                        :title="$t('Assign checklist template')"
                        :description="$t('Type the name of the user to whom you want to assign the checklist template.')"
                    />
                    <div>
                        <UserSearch v-model="user_query" @userSelected="addUser"/>
                    </div>
                    <div class="mt-4">
                        <div v-for="(user,index) in templateForm.users"
                              class="flex mt-4 mr-1 rounded-full items-center font-bold text-primary">
                             <div class="flex items-center">
                                <img class="h-12 w-12 mr-2 object-cover rounded-full"
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
                    <div class="flex items-center justify-center mt-5">
                        <FormButton
                            class=""
                            @click="closeChangeUsersModal"
                            :text="$t('Assign')" />
                    </div>
                </div>
        </BaseModal>
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
import Button from "@/Jetstream/Button.vue";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";

export default {
    mixins: [Permissions],
    name: "Template Create",
    props: [],
    components: {
        BaseTextarea,
        BaseInput,
        TextareaComponent,
        ModalHeader,
        UserSearch,
        TextInputComponent,
        BaseModal,
        FormButton,
        Button,
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
            team_query: "",
            addingTask: false,
            dragging: false,
            showChangeUsersModal: false,
            user_query: "",
            user_search_results: [],
            templateForm: useForm({
                name: "",
                //user who created the template
                user_id: this.$page.props.auth.user.id,
                task_templates: [],
                users: [],
            }),
            newTaskName: "",
            newTaskDescription: "",
            taskForm: useForm({
                name: "",
                description: "",
            }),
            showEmptyTaskNameError: false
        }
    },
    methods: {
        openChangeUsersModal() {
            this.showChangeUsersModal = true;
        },
        closeChangeUsersModal() {
            this.showChangeUsersModal = false;
        },
        openAddTaskModal() {
            this.addingTask = true;
        },
        closeAddTaskModal() {
            this.addingTask = false;
        },
        showSuccessButton() {
            this.showSuccess = true;
            setTimeout(() => {
                this.showSuccess = false
            }, 1000)
        },
        addTaskToTemplate() {
            this.templateForm.task_templates.push({name: this.newTaskName, description: this.newTaskDescription});
            this.newTaskName = "";
            this.newTaskDescription = "";
            this.closeAddTaskModal();
        },
        createChecklistTemplate() {
            if (this.templateForm.name === '') {
                this.showEmptyTaskNameError = true;
                return;
            }

            this.showEmptyTaskNameError = false;

            this.templateForm.post(route('checklist_templates.store'));
            this.showSuccessButton();
        },
        deleteTaskFromTemplate(taskToDelete) {
            this.templateForm.task_templates.splice(this.templateForm.task_templates.indexOf(taskToDelete), 1);
        },
        addUser(user) {
            for (let assignedUser of this.templateForm.users) {
                //if user is already assigned do nothing
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
