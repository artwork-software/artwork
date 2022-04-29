<template>
    <app-layout title="Teamprofil">
        <div class="max-w-screen-lg my-12 ml-20 mr-40">
            <div class="flex-wrap">
                <div class="flex">
                    <h2 class="font-bold mb-6 font-lexend text-3xl">Checklistenvorlage</h2>
                </div>
                <div class="text-secondary tracking-tight leading-6 sub max-w-screen-sm">
                    Hier kannst du deine Checklistenvorlage anlegen und bearbeiten - sie kann anschließend in jedem
                    Projekt genutzt werden.
                </div>
                <div class="flex mt-12">
                    <div class="relative w-full max-w-2xl">
                        <input id="teamName" v-model="templateForm.name" type="text"
                               class="peer pl-0 h-12 w-full text-xl font-bold focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                               placeholder="placeholder"/>
                        <label for="teamName"
                               class="absolute left-0 -top-7 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name
                            der Checklistenvorlage</label>
                    </div>
                </div>
                <div class="flex items-center mt-6 mr-8">
                    <div v-if="templateForm.assignedTeams.length === 0">
                        <span
                            class="text-secondary subpixel-antialiased cursor-pointer">Noch keine Teams hinzugefügt</span>
                    </div>
                    <div v-else class="mt-3 -mr-3" v-for="team in templateForm.assignedTeams">
                        <TeamIconCollection class="h-9 w-9" :iconName="team.svg_name"/>
                    </div>
                    <Menu as="div" class="my-auto relative">
                        <div class="flex mt-3">
                            <MenuButton
                                class="flex ml-6">
                                <DotsVerticalIcon class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                  aria-hidden="true"/>
                            </MenuButton>
                            <div v-if="$page.props.can.show_hints" class="absolute flex w-80 ml-12">
                                <div>
                                    <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                </div>
                                <div class="flex">
                                    <span class="font-nanum -mt-4 ml-2 text-secondary tracking-tight tracking-tight text-lg">Teile der Vorlage Teams zu, diese sind dann bei Benutzung der Vorlage automatisch zugewiesen</span>
                                </div>
                            </div>
                        </div>
                        <transition enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95">
                            <MenuItems
                                class="origin-top-left absolute left-0 mr-4 mt-2 w-72 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                <div class="py-1">
                                    <MenuItem v-slot="{ active }">
                                        <a @click="openChangeTeamsModal"
                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <PencilAltIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            Zugewiesene Teams bearbeiten
                                        </a>
                                    </MenuItem>
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>

                </div>
                <div class="flex">
                    <div class="flex w-full mt-12">
                        <div class="">
                            <button @click="openAddTaskModal()" type="button"
                                    class="flex border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none">
                                <PlusSmIcon class="h-5 w-5" aria-hidden="true"/>
                            </button>
                        </div>
                        <div v-if="$page.props.can.show_hints" class="flex">
                            <SvgCollection svgName="arrowLeft" class="ml-2"/>
                            <span
                                class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-xl">Lege neue Aufgaben an</span>
                        </div>
                    </div>
                </div>
                <div class="mt-6 mb-6">
                    <draggable ghost-class="opacity-50" tag="transition-group" item-key="draggableID"
                               v-model="templateForm.task_templates" @start="dragging=true" @end="dragging=false">
                        <template #item="{element}">
                            <div class="flex mt-6 flex-wrap w-full"
                                 :class="dragging? 'cursor-grabbing' : 'cursor-grab'">
                                <div class="flex w-full">
                                    <input v-model="element.done"
                                           type="checkbox"
                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                    <p class="ml-4 my-auto text-lg font-black text-sm"
                                       :class="element.done ? 'text-secondary' : 'text-primary'">
                                        {{ element.name }}</p>
                                </div>
                                <div class="ml-10 text-secondary">
                                    {{ element.description }}
                                </div>
                            </div>
                        </template>
                    </draggable>
                </div>
                <div class="pt-12">
                    <div class="mt-4 grid grid-cols-1 gap-y-4 gap-x-4 items-center sm:grid-cols-6">
                        <button v-if="!showSuccess" @click="editChecklistTemplate"
                                class="sm:col-span-3 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                        >Vorlage anlegen
                        </button>
                        <button v-else type="submit"
                                class=" sm:col-span-3 items-center py-1.5 border bg-success focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                        >
                            <CheckIcon class="h-10 w-9 inline-block text-secondaryHover"/>
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
                        <button
                            :class="[this.newTaskName === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                            class="mt-8 inline-flex items-center px-20 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                            @click="addTaskToTemplate"
                            :disabled="this.newTaskName === ''">
                            Hinzufügen
                        </button>
                    </div>

                </div>

            </template>
        </jet-dialog-modal>
        <!-- Change Teams Modal -->
        <jet-dialog-modal :show="showChangeTeamsModal" @close="closeChangeTeamsModal">
            <template #content>
                <div class="mx-3">
                    <div class="font-bold font-lexend text-primary text-2xl my-2">
                        Checklistenvorlage zuweisen
                    </div>
                    <XIcon @click="closeChangeTeamsModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-secondary tracking-tight leading-6 sub">
                        Tippe den Namen des Teams dem du die Checklistenvorlage zuweisen möchtest.
                    </div>
                    <div class="mt-6 relative">
                        <div class="my-auto w-full">
                            <input id="userSearch" v-model="team_query" type="text" autocomplete="off"
                                   class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                                   placeholder="placeholder"/>
                            <label for="userSearch"
                                   class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name</label>
                        </div>

                        <transition leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0">
                            <div v-if="team_search_results.length > 0 && team_query.length > 0"
                                 class="absolute z-10 mt-1 w-full max-h-60 bg-primary shadow-lg
                                         text-base ring-1 ring-black ring-opacity-5
                                         overflow-auto focus:outline-none sm:text-sm">
                                <div class="border-gray-200">
                                    <div v-for="(team, index) in team_search_results" :key="index"
                                         class="flex items-center cursor-pointer">
                                        <div class="flex-1 text-sm py-4">
                                            <p @click="addTeamToTeamsArray(team)"
                                               class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                {{ team.name }}
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
                        <span v-for="(team,index) in templateForm.assignedTeams"
                              class="flex mt-4 mr-1 rounded-full items-center font-bold text-primary">
                            <div class="flex items-center">
                                <TeamIconCollection :iconName="team.svg_name"
                                                    class="rounded-full h-11 w-11 object-cover"/>
                                <span class="flex ml-4">
                                {{ team.name }}
                                    </span>
                            </div>
                            <button type="button" @click="deleteTeamFromTemplate(team)">
                                <span class="sr-only">Team aus Checklistenvorlage entfernen</span>
                                <XCircleIcon class="ml-2 mt-1 h-5 w-5 hover:text-error "/>
                            </button>
                        </span>
                    </div>
                    <button @click="closeChangeTeamsModal"
                            class=" inline-flex mt-8 items-center px-12 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                    >Zuweisen
                    </button>
                </div>

            </template>

        </jet-dialog-modal>
    </app-layout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {PencilAltIcon, TrashIcon, XIcon, PlusSmIcon} from "@heroicons/vue/outline";
import {CheckIcon, ChevronDownIcon, DotsVerticalIcon, XCircleIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import JetButton from "@/Jetstream/Button";
import JetDialogModal from "@/Jetstream/DialogModal";
import JetInput from "@/Jetstream/Input";
import JetInputError from "@/Jetstream/InputError";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import draggable from "vuedraggable";
import {Inertia} from "@inertiajs/inertia";
import {useForm} from "@inertiajs/inertia-vue3";

export default {
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
        draggable
    },
    data() {
        return {
            deletingTeam: false,
            showSuccess: false,
            deletingAllMembers: false,
            team_query: "",
            addingTask: false,
            dragging: false,
            showChangeTeamsModal: false,
            team_search_results: [],
            templateForm: this.$inertia.form({
                _method: 'POST',
                name: this.checklist_template.name,
                //user who created the template
                user_id: this.$page.props.user.id,
                task_templates: this.checklist_template.task_templates,
                assignedTeams: this.checklist_template.assignedTeams
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
        },
        deleteTeamFromTemplate(team) {
            this.templateForm.assignedTeams.splice(this.templateForm.assignedTeams.indexOf(team), 1);
        },
        showSuccessButton() {
            this.showSuccess = true;
            setTimeout(() => {
                this.showSuccess = false
            }, 1000)
        },
        editChecklistTemplate() {
            this.templateForm.patch(route('checklist_templates.update'),{checklist_template: this.checklist_template.id});
            this.showSuccessButton();
        },
        addTeamToTeamsArray(team) {
            for (let assignedTeam of this.templateForm.assignedTeams) {
                //if team is already assigned do nothing
                if (team.id === assignedTeam.id) {
                    this.team_query = ""
                    return;
                }
            }
            this.templateForm.assignedTeams.push(team);
            this.team_query = "";
            this.team_search_results = []
        },
        addTaskToTemplate(){
            this.templateForm.task_templates.push({name:this.newTaskName,description:this.newTaskDescription});
            this.newTaskName = "";
            this.newTaskDescription = "";
            this.closeAddTaskModal();
        }
    },
    watch: {
        team_query: {
            handler() {
                if (this.team_query.length > 0) {
                    axios.get('/departments/search', {
                        params: {query: this.team_query}
                    }).then(response => {
                        this.team_search_results = response.data
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
