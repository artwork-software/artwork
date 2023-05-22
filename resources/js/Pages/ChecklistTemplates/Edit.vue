<template>
    <app-layout>
        <div class="max-w-screen-lg my-12 ml-20 mr-40">
            <div class="flex-wrap">
                <div class="flex">
                    <h2 class="font-black text-primary mb-4 font-lexend text-3xl">Checklistenvorlage</h2>
                </div>
                <div class="text-secondary subpixel-antialiased max-w-screen-sm">
                    Hier kannst du deine Checklistenvorlage anlegen und bearbeiten - sie kann anschließend in jedem
                    Projekt genutzt werden.
                </div>
                <div class="flex mt-14">
                    <div class="relative w-full max-w-2xl">
                        <input id="teamName" v-model="templateForm.name" type="text"
                               class="peer pl-0 h-12 w-full text-xl font-bold focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                               placeholder="placeholder"/>
                        <label for="teamName"
                               class="absolute left-0 text-gray-600 text-sm -top-2.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name
                            der Checklistenvorlage</label>
                    </div>
                </div>
                <div class="flex items-center mt-6 mr-8">
                    <div v-if="templateForm.users.length === 0">
                        <span
                            class="text-secondary subpixel-antialiased cursor-pointer">Noch keine Nutzer*innen hinzugefügt</span>
                    </div>
                    <div v-else class="-mr-3 my-auto" v-for="(user, index) in templateForm.users">
                        <img class="h-10 w-10 mr-2 object-cover rounded-full border border-2 border-white"
                             :class="index !== 0 ? '-ml-2' : ''"
                             :src="user.profile_photo_url"
                             alt=""/>
                    </div>
                    <div @click="openChangeTeamsModal"
                         class="text-secondary ml-4 flex items-center px-2 py-2 text-sm subpixel-antialiased cursor-pointer">
                        <PencilAltIcon
                            class="h-5 w-5 text-primaryText group-hover:text-white"
                            aria-hidden="true"/>
                    </div>
                </div>
                <div class="flex">
                    <div class="flex w-full mt-12">
                        <div class="ml-0.5">
                            <button @click="openAddTaskModal()" type="button"
                                    class="flex my-auto items-center border border-transparent rounded-full shadow-sm text-white bg-buttonBlue hover:bg-primaryHover focus:outline-none">
                                <PlusSmIcon class="h-5 w-5" aria-hidden="true"/>
                            </button>
                        </div>
                        <div v-if="$page.props.can.show_hints" class="flex">
                            <SvgCollection svgName="arrowLeft" class="ml-2"/>
                            <span
                                class="hind text-secondary tracking-tight ml-1 my-auto tracking-tight text-xl">Lege neue Aufgaben an</span>
                        </div>
                    </div>
                </div>
                <div class="mt-10 mb-6">
                    <draggable ghost-class="opacity-50" tag="transition-group" item-key="draggableID"
                               v-model="templateForm.task_templates" @start="dragging=true" @end="dragging=false">
                        <template #item="{element}" :key="element.id">
                            <div class="flex">
                            <div class="flex mt-6 flex-wrap"
                                 :class="dragging? 'cursor-grabbing' : 'cursor-grab'">
                                <div class="flex w-full group">
                                    <input v-model="element.done"
                                           type="checkbox"
                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                    <p class="ml-4 my-auto font-black"
                                       :class="element.done ? 'text-secondary' : 'text-primary'">
                                        {{ element.name }}</p>
                                    <button type="button" @click="deleteTaskFromTemplate(element)">
                                        <span class="sr-only">Task aus Checklistenvorlage entfernen</span>
                                        <XCircleIcon class="ml-4 mt-1 h-5 w-5 hover:text-error group-hover:block hidden "/>
                                    </button>
                                </div>
                                <div class="ml-10 text-secondary text-sm">
                                    {{ element.description }}
                                </div>

                            </div>

                            </div>
                        </template>
                    </draggable>
                </div>
                <div class="pt-12">
                    <div class="mt-4">
                        <AddButton v-if="!showSuccess" class=" bg-primary hover:bg-primaryHover focus:outline-none mt-6 items-center px-20 py-3 border border-transparent
                            text-base font-bold shadow-sm text-secondaryHover"
                                   @click="editChecklistTemplate"
                                   text="Speichern" mode="page" />
                        <button v-else
                                class="px-24 ml-6 rounded-full items-center py-2.5 border bg-success focus:outline-none border-transparent"
                        >
                            <CheckIcon class="h-7 w-7 inline-block text-secondaryHover"/>
                        </button>
                    </div>
                </div>
            </div>

        </div>
        <!-- Add Task Modal-->
        <jet-dialog-modal :show="addingTask" @close="closeAddTaskModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                        Neue Aufgabe
                    </div>
                    <XIcon @click="closeAddTaskModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="mt-12">
                        <div class="flex">
                            <div class="relative flex w-full mr-4">
                                <input id="task_name" v-model="newTaskName" type="text"
                                       class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-xl font-bold text-primary placeholder-secondary placeholder-transparent"
                                       placeholder="placeholder"/>
                                <label for="task_name"
                                       class="absolute left-0 text-base -top-4 text-gray-600 -top-6 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Aufgabe</label>
                            </div>
                        </div>
                        <div class="mt-8 mr-4">
                                            <textarea
                                                placeholder="Kommentar"
                                                v-model="newTaskDescription" rows="3"
                                                class="focus:border-primary placeholder-secondary border-2 w-full font-semibold border border-gray-300 "/>
                        </div>
                        <AddButton :class="this.newTaskName === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none'"
                                   class="mt-6 mx-auto items-center px-20 py-3 border border-transparent
                            text-base font-bold shadow-sm text-secondaryHover"
                                   @click="addTaskToTemplate"
                                   :disabled="this.newTaskName === ''"
                                   text="Hinzufügen" mode="modal"/>
                    </div>

                </div>

            </template>
        </jet-dialog-modal>
        <!-- Change Teams Modal -->
        <jet-dialog-modal :show="showChangeTeamsModal" @close="closeChangeTeamsModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_checklist_team_assign.svg" class="-ml-6 -mt-8 mb-4" />
                <div class="mx-3">
                    <div class="font-bold font-lexend text-primary text-2xl my-2">
                        Checklistenvorlage zuweisen
                    </div>
                    <XIcon @click="closeChangeTeamsModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-secondary tracking-tight leading-6 sub">
                        Tippe den Namen des Nutzer*innen dem du die Checklistenvorlage zuweisen möchtest.
                    </div>
                    <div class="mt-6 relative">
                        <div class="my-auto w-full">
                            <input id="userSearch" v-model="user_query" type="text" autocomplete="off"
                                   class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                                   placeholder="placeholder"/>
                            <label for="userSearch"
                                   class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name</label>
                        </div>

                        <transition leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0">
                            <div v-if="user_search_results.length > 0 && user_query.length > 0"
                                 class="absolute z-10 mt-1 w-full max-h-60 bg-primary shadow-lg
                                         text-base ring-1 ring-black ring-opacity-5
                                         overflow-auto focus:outline-none sm:text-sm">
                                <div class="border-gray-200">
                                    <div v-for="(user, index) in user_search_results" :key="index"
                                         class="flex items-center cursor-pointer">
                                        <div class="flex-1 text-sm py-4">
                                            <p @click="addUserToTeamsArray(user)"
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
                            <button type="button" @click="deleteUserFromTemplate(user)">
                                <span class="sr-only">Team aus Checklistenvorlage entfernen</span>
                                <XCircleIcon class="ml-2 mt-1 h-5 w-5 hover:text-error "/>
                            </button>
                        </span>
                    </div>
                    <AddButton
                        class="mt-8 inline-flex items-center px-20 py-3 border focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                        @click="closeChangeTeamsModal"
                        text="Zuweisen" mode="modal" />
                </div>

            </template>

        </jet-dialog-modal>
    </app-layout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {PencilAltIcon, TrashIcon, XIcon} from "@heroicons/vue/outline";
import {CheckIcon, ChevronDownIcon, DotsVerticalIcon, XCircleIcon, PlusSmIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import JetButton from "@/Jetstream/Button";
import JetDialogModal from "@/Jetstream/DialogModal";
import JetInput from "@/Jetstream/Input";
import JetInputError from "@/Jetstream/InputError";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import draggable from "vuedraggable";
import {Inertia} from "@inertiajs/inertia";
import {useForm} from "@inertiajs/inertia-vue3";
import AddButton from "@/Layouts/Components/AddButton";
import Permissions from "@/mixins/Permissions.vue";

export default {
    mixins: [Permissions],
    name: "Template Edit",
    props: ['checklist_template'],
    components: {
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
        AddButton
    },
    data() {
        return {
            deletingTeam: false,
            showSuccess: false,
            deletingAllMembers: false,
            user_query: "",
            addingTask: false,
            dragging: false,
            showChangeTeamsModal: false,
            user_search_results: [],
            templateForm: this.$inertia.form({
                _method: 'PATCH',
                name: this.checklist_template.name,
                //user who created the template
                user_id: this.$page.props.user.id,
                task_templates: this.checklist_template.task_templates? this.checklist_template.task_templates : [],
                users: this.checklist_template.users? this.checklist_template.users : [],
            }),
            newTaskName:"",
            newTaskDescription:"",
            taskForm: useForm({
                name: "",
                description: "",
            })
        }
    },
    methods: {
        openChangeTeamsModal(){
            this.showChangeTeamsModal = true;
        },
        closeChangeTeamsModal(){
            this.showChangeTeamsModal = false;
        },
        openAddTaskModal(){
            this.addingTask = true;
        },
        closeAddTaskModal(){
            this.addingTask = false;
            this.newTaskName = "";
            this.newTaskDescription = "";
        },
        deleteUserFromTemplate(user) {
            this.templateForm.users.splice(this.templateForm.users.indexOf(user), 1);
        },
        showSuccessButton() {
            this.showSuccess = true;
            setTimeout(() => {
                this.showSuccess = false
            }, 1000)
        },
        editChecklistTemplate() {
            this.templateForm.patch(route('checklist_templates.update',{checklist_template: this.checklist_template.id}));
            this.showSuccessButton();
        },
        addUserToTeamsArray(user) {
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
    },
    setup() {
        return {}
    }
}
</script>

<style scoped>

</style>
