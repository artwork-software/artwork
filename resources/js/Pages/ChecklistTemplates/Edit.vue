<template>
    <app-layout :title="$t('Checklist template') + ' (' +  templateForm.name + ') ' + $t('edit')">
        <div class="max-w-screen-lg my-12 ml-20 mr-40">
            <div class="flex-wrap">
                <div class="flex">
                    <h2 class="font-black text-primary mb-4 font-lexend text-3xl">{{ $t('Checklist template')}}</h2>
                </div>
                <div class="text-secondary subpixel-antialiased max-w-screen-sm">
                    {{$t('You can create and edit your checklist template here - it can then be used in any project.')}}
                </div>
                <div class="flex mt-14">
                    <div class="relative w-full max-w-2xl">
                        <input id="teamName" v-model="templateForm.name" type="text"
                               class="peer pl-0 h-12 w-full text-xl font-bold focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                               placeholder="placeholder"/>
                        <label for="teamName"
                               class="absolute left-0 text-gray-600 text-sm -top-2.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">
                            {{ $t('Name of the checklist template')}}
                        </label>
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
                <div class="mt-10">
                    <draggable ghost-class="opacity-50" tag="transition-group" item-key="draggableID"
                               v-model="templateForm.task_templates" @start="dragging=true" @end="dragging=false">
                        <template #item="{element}" :key="element.id">
                            <div class="flex mb-5">
                                <div class="flex flex-wrap"
                                     :class="dragging? 'cursor-grabbing' : 'cursor-grab'">
                                    <div class="flex flex-col w-full group">
                                        <div class="flex flex-row items-center">
                                            <div class="group-hover:flex hidden">
                                                <DotsVerticalIcon
                                                    class="h-5 w-5 -mr-3.5 text-secondary"></DotsVerticalIcon>
                                                <DotsVerticalIcon
                                                    class="h-5 w-5 text-secondary"></DotsVerticalIcon>
                                            </div>
                                            <input v-model="element.done"
                                                   type="checkbox"
                                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
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
                <div class="mt-10">
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
        <BaseModal @closed="closeAddTaskModal" v-if="addingTask" modal-image="/Svgs/Overlays/illu_checklist_team_assign.svg" :show-image="false">
                <div class="mx-4">
                    <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                        {{$t('New task')}}
                    </div>
                    <div class="mt-12">
                        <div class="flex">
                            <div class="relative flex w-full mr-4">
                                <input id="task_name" v-model="newTaskName" type="text"
                                       class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-xl font-bold text-primary placeholder-secondary placeholder-transparent"
                                       placeholder="placeholder"/>
                                <label for="task_name"
                                       class="absolute left-0 text-base -top-4 text-gray-600 -top-6 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">{{$t('Task')}}</label>
                            </div>
                        </div>
                        <div class="mt-8 mr-4">
                                            <textarea
                                                :placeholder="$t('Comment')"
                                                v-model="newTaskDescription" rows="3"
                                                class="focus:border-primary placeholder-secondary border-2 w-full font-semibold border border-gray-300 "/>
                        </div>
                        <FormButton @click="addTaskToTemplate"
                                   :disabled="this.newTaskName === ''"
                                   :text="$t('Add')"/>
                    </div>

                </div>
        </BaseModal>
        <!-- Change Teams Modal -->
        <BaseModal @closed="closeChangeUsersModal" v-if="showChangeUsersModal" modal-image="/Svgs/Overlays/illu_checklist_team_assign.svg" >
                <div class="mx-3">
                    <div class="font-bold font-lexend text-primary text-2xl my-2">
                        {{$t('Assign checklist template')}}
                    </div>
                    <div class="text-secondary tracking-tight leading-6 sub">
                        {{$t('Type the name of the user to whom you want to assign the checklist template.')}}
                    </div>
                    <div class="mt-6 relative">
                        <div class="my-auto w-full">
                            <input id="userSearch" v-model="user_query" type="text" autocomplete="off"
                                   class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                                   placeholder="placeholder"/>
                            <label for="userSearch"
                                   class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">{{ $t('Name')}}</label>
                        </div>

                        <transition leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0">
                            <div v-if="user_search_results.length > 0 && user_query.length > 0"
                                 class="absolute z-10 mt-1 w-full max-h-60 bg-artwork-navigation-background shadow-lg
                                         text-base ring-1 ring-black ring-opacity-5
                                         overflow-auto focus:outline-none sm:text-sm">
                                <div class="border-gray-200">
                                    <div v-for="(user, index) in user_search_results" :key="index"
                                         class="flex items-center cursor-pointer">
                                        <div class="flex-1 text-sm py-4">
                                            <p @click="addUser(user)"
                                               class="flex items-center font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                <img class="h-5 w-5 mr-2 object-cover rounded-full"
                                                     :src="user.profile_photo_url"
                                                     alt=""/>
                                                {{ user.first_name }} {{ user.last_name }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>
                    <div class="mt-4">
                        <div class="flex">
                        </div>
                        <span v-for="(user,index) in templateForm.users"
                              class="flex mt-4 mr-1 rounded-full items-center font-bold text-primary">
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
                        </span>
                    </div>
                    <FormButton
                        @click="closeChangeUsersModal"
                        :text="$t('Assign')"/>
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
import Permissions from "@/Mixins/Permissions.vue";
import AddButtonBig from "@/Layouts/Components/General/Buttons/AddButtonBig.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    mixins: [Permissions],
    name: "Template Edit",
    props: ['checklist_template'],
    components: {
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
                user_id: this.$page.props.user.id,
                task_templates: this.checklist_template.task_templates? this.checklist_template.task_templates : [],
                users: this.checklist_template.users? this.checklist_template.users : [],
            }),
            newTaskName:"",
            newTaskDescription:"",
            taskForm: useForm({
                name: "",
                description: "",
            }),
            showEmptyTaskNameError: false
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
