<template>
    <jet-dialog-modal :show="show" @close="closeModal(false)">
        <template #content>
            <img src="/Svgs/Overlays/illu_project_team.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-3">
                <div class="font-black font-lexend text-primary text-3xl my-2">
                    Projektteam zuweisen
                </div>
                <XIcon @click="closeModal(false)"
                       class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                       aria-hidden="true"/>
                <div class="xsLight">
                    Tippe den Namen der Nutzer*innen, die du zum Team hinzufügen möchtest.
                    Die Nutzer*innen erhalten Lesezugriff auf dieses Projekt.
                    Weiterreichende Rechte kann nur die Projektleitung vergeben.
                </div>
                <div class="mt-6 relative">
                    <div class="my-auto w-full">
                        <input id="departmentSearch" v-model="department_and_user_query" type="text"
                               autocomplete="off"
                               class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                               placeholder="placeholder"/>
                        <label for="departmentSearch"
                               class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name</label>
                    </div>

                    <transition leave-active-class="transition ease-in duration-100"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0">
                        <div
                            v-if="(department_and_user_search_results.users
                                || department_and_user_search_results.departments)
                                 && department_and_user_query.length > 0"
                            class="absolute z-10 mt-1 w-full max-h-60 bg-primary shadow-lg
                                         text-base ring-1 ring-black ring-opacity-5
                                         overflow-auto focus:outline-none sm:text-sm">
                            <div class="border-gray-200">
                                <div v-for="(user, index) in department_and_user_search_results.users" :key="index"
                                     class="flex items-center cursor-pointer">
                                    <div class="flex-1 text-sm py-4">
                                        <p @click="addUserToProjectTeamArray(user)"
                                           class="font-bold px-4 flex text-white items-center hover:border-l-4 hover:border-l-success">
                                            <img :src="user.profile_photo_url" :alt="user.name"
                                                 class="rounded-full h-8 w-8 object-cover"/>
                                            <span class="ml-2 truncate">
                                                    {{ user.first_name }} {{ user.last_name }}
                                                </span>
                                        </p>
                                    </div>
                                </div>
                                <div v-for="(department, index) in department_and_user_search_results.departments"
                                     :key="index"
                                     class="flex items-center cursor-pointer">
                                    <div class="flex-1 text-sm py-4">
                                        <p @click="addDepartmentToProjectTeamArray(department)"
                                           class="font-bold flex items-center px-4 text-white hover:border-l-4 hover:border-l-success">
                                            <TeamIconCollection :iconName="department.svg_name"
                                                                :alt="department.name"
                                                                class="rounded-full h-8 w-8 object-cover"/>
                                            <span class="ml-2">
                                                    {{ department.name }}
                                                </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>
                <div class="mt-4">
                        <span v-for="user in users"
                              class="flex justify-between mt-4 mr-1 items-center font-bold text-primary border-1 border-b pb-3">
                            <div class="flex items-center w-64">
                                <div class="flex items-center">
                                    <img class="flex h-11 w-11 rounded-full"
                                         :src="user.profile_photo_url"
                                         alt=""/>
                                    <span class="flex ml-4">
                                        {{ user.first_name }} {{ user.last_name }}
                                    </span>
                                </div>
                                <button type="button" @click="deleteUserFromProjectTeam(user)">
                                    <span class="sr-only">User aus Team entfernen</span>
                                    <XCircleIcon class="ml-3 text-buttonBlue h-5 w-5 hover:text-error "/>
                                </button>
                            </div>
                            <div class="flex justify-between items-center my-1.5 h-5 w-80">
                                <div class="flex items-center justify-between" v-if="checkUserAuth(user)">
                                   <div class="flex">
                                        <input v-model="user.can_write"
                                               type="checkbox"
                                               class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                    <p :class="[user.can_write ? 'text-primary font-black' : 'text-secondary']"
                                       class="ml-4 my-auto text-sm">Schreibrecht</p>
                                   </div>
                                    <Dropdown :open="user.openedMenu" align="right" width="60" class="text-right">
                                        <template #trigger>
                                            <span class="inline-flex">
                                                <button @click="user.openedMenu = !user.openedMenu" type="button"
                                                        class="text-sm flex items-center ml-14 my-auto text-primary font-semibold focus:outline-none transition">
                                                    Weitere Rechte
                                                </button>
                                            </span>
                                        </template>

                                        <template #content>
                                            <div class="w-44 p-4">
                                                <div class="flex">
                                                    <input v-model="user.access_budget"
                                                           type="checkbox"
                                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                                <p
                                                    class=" ml-4 my-auto text-sm text-secondary">Budgetzugriff</p>
                                                </div>
                                                <div class="flex mt-4">
                                                    <input v-model="user.delete_permission"
                                                           type="checkbox"
                                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                                <p
                                                    class=" ml-4 my-auto text-sm text-secondary">Löschrecht</p>
                                                </div>
                                                <div class="flex mt-4" v-if="user.project_management">
                                                    <input v-model="user.is_manager"
                                                           type="checkbox"
                                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                                <p
                                                    class="ml-4 my-auto text-sm text-secondary">Projektleitung</p>
                                                </div>
                                            </div>
                                        </template>
                                    </Dropdown>
                                </div>
                            </div>
                        </span>
                    <span v-for="department in assignedDepartments"
                          class="flex mt-4 mr-1 rounded-full items-center font-bold text-primary">
                            <div class="flex items-center">
                                <TeamIconCollection :iconName="department.svg_name" :alt="department.name"
                                                    class="rounded-full h-11 w-11 object-cover"/>
                                                <span class="flex ml-4">
                                                    {{ department.name }}
                                                </span>
                            </div>
                            <button type="button" @click="deleteDepartmentFromProjectTeam(department)">
                                <span class="sr-only">Team aus dem Projekt entfernen</span>
                                <XCircleIcon class="ml-2 h-5 w-5 hover:text-error "/>
                            </button>
                        </span>
                </div>
                <div class="w-full items-center text-center">
                    <AddButton @click="editProjectTeam" text="Speichern" mode="modal"
                               class=" inline-flex mt-8 items-center px-12 py-3 border bg-buttonBlue hover:bg-buttonHover focus:outline-none border-transparent text-base font-bold tracking-wider text-lg shadow-sm text-secondaryHover"
                    />
                </div>
            </div>
        </template>

    </jet-dialog-modal>
</template>

<script>
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import AddButton from "@/Layouts/Components/AddButton.vue";
import {XCircleIcon, XIcon} from "@heroicons/vue/solid";
import {useForm} from "@inertiajs/inertia-vue3";
import Dropdown from "@/Jetstream/Dropdown.vue";

export default {
    name: "ProjectEditTeamModal",
    components: {
        Dropdown,
        JetDialogModal,
        TeamIconCollection,
        AddButton,
        XCircleIcon,
        XIcon
    },
    props: ['editingTeam', 'assignedUsers', 'projectManagerIds', 'departments', 'projectId'],
    data(){
        return {
            department_and_user_query: "",
            department_search_results: [],
            department_and_user_search_results: [],
            form: useForm({
                assigned_user_ids: {},
                assigned_departments: [],
            }),
            users: this.assignedUsers,
            assignedDepartments: this.departments ? this.departments : [],
            show: this.editingTeam ? this.editingTeam : false
        }
    },
    methods: {
        closeModal(bool) {
            this.$emit('closed', bool);
        },
        closeEditProjectTeamModal(){
            this.editingTeam = false;
        },
        deleteDepartmentFromProjectTeam(department) {
            this.assignedDepartments.splice(this.assignedDepartments.indexOf(department), 1);
        },
        addUserToProjectTeamArray(userToAdd) {
            for (let assignedUser of this.users) {
                if (userToAdd.id === assignedUser.id) {
                    this.department_and_user_query = ""
                    return;
                }
            }

            this.users.push(userToAdd);
            this.department_and_user_query = ""
        },
        addDepartmentToProjectTeamArray(departmentToAdd) {
            if (this.assignedDepartments !== []) {
                for (let assignedDepartment of this.assignedDepartments) {
                    if (departmentToAdd.id === assignedDepartment.id) {
                        this.department_and_user_query = ""
                        return;
                    }
                }
            } else {
                this.assignedDepartments = [departmentToAdd];
            }
            this.department_and_user_query = ""
            this.assignedDepartments.push(departmentToAdd);

        },
        deleteUserFromProjectTeam(user) {
            if (this.users.includes(user)) {
                this.users.splice(this.users.indexOf(user), 1);
            }
        },
        editProjectTeam() {
            this.form.assigned_user_ids = {};
            this.users.forEach(user => {
                this.form.assigned_user_ids[user.id] = {
                    access_budget: user.access_budget,
                    is_manager: user.is_manager,
                    can_write: user.can_write,
                    delete_permission: user.delete_permission
                };
            })
            this.form.assigned_departments = [];
            this.assignedDepartments.forEach(department => {
                this.form.assigned_departments.push(department);
            })
            this.form.patch(route('projects.update_team', {project: this.projectId}));
            this.closeModal(true);
        },
        checkUserAuth(user) {
            if (this.projectManagerIds.includes(this.$page.props.user.id)) {
                return true;
            }
            if (this.$page.props.user.id === user.id && user.project_management) {
                return true;
            }
            if ($role('artwork admin')) {
                return true;
            }
            return false;
        }
    },
    watch: {
        department_and_user_query: {
            handler() {
                if (this.department_and_user_query.length > 0) {
                    axios.get('/projects/users_departments/search', {
                        params: {query: this.department_and_user_query}
                    }).then(response => {
                        this.department_and_user_search_results = response.data
                    })
                }
            },
            deep: true
        },
    },
}
</script>

<style scoped>

</style>
