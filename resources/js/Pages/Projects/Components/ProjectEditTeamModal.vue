<template>
    <BaseModal @closed="closeModal" v-if="show" modal-image="/Svgs/Overlays/illu_project_team.svg" modal-size="sm:max-w-4xl">
            <div class="mx-3">
                <div class="font-black font-lexend text-primary text-3xl my-2">
                    {{ $t('Assign project team') }}
                </div>
                <div class="xsLight">
                    {{ $t('Type the name of the users you want to add to the team. The users receive read access to this project. Only the project manager can grant further rights.') }}
                </div>
                <div class="my-6 relative">
                    <div class="my-auto w-full">
                        <BaseInput label="Name" id="departmentSearch" class="w-full" v-model="department_and_user_query" type="text" />

                        <!--<input id="departmentSearch" v-model="department_and_user_query" type="text"
                               autocomplete="off"
                               class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                               placeholder="placeholder"/>
                        <label for="departmentSearch"
                               class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name</label>-->
                    </div>
                    <transition leave-active-class="transition ease-in duration-100"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0">
                        <div
                            v-if="(department_and_user_search_results.users
                                || department_and_user_search_results.departments)
                                 && department_and_user_query.length > 0"
                            class="absolute z-10 mt-1 w-full max-h-60 bg-artwork-navigation-background shadow-lg
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
                <div class="my-6">
                    <div v-for="user in this.users" class="flex justify-between mt-4 mr-1 items-center font-bold text-primary pb-3">
                        <div class="flex items-center w-56">
                            <div class="flex items-center">
                                <img class="flex h-11 w-11 object-cover rounded-full"
                                     :src="user.profile_photo_url"
                                     alt=""/>
                                <span class="flex ml-4">
                                    {{ user.first_name }} {{ user.last_name }}
                                </span>
                            </div>
                            <button type="button" @click="deleteUserFromProjectTeam(user)">
                                <span class="sr-only">{{ $t('Remove user from team') }}</span>
                                <XCircleIcon class="ml-3 text-artwork-buttons-create h-5 w-5 hover:text-error "/>
                            </button>
                        </div>
                        <div class="flex justify-between items-center my-1.5 h-5 w-2xl">
                            <div class="flex items-center justify-between" v-if="checkUserAuth(user)">
                               <div class="flex">
                                    <input v-model="user.pivot_can_write"
                                           type="checkbox"
                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p :class="[user.pivot_can_write ? 'text-primary font-black' : 'text-secondary']"
                                   class="ml-4 my-auto text-sm">{{ $t('Write permission') }}</p>
                               </div>
                                <Dropdown :open="user.openedMenu" align="right" width="60" class="text-right">
                                    <template #trigger>
                                        <span class="inline-flex">
                                            <button @click="user.openedMenu = !user.openedMenu" type="button"
                                                    class="text-sm flex items-center ml-14 my-auto text-primary font-semibold focus:outline-none transition">
                                                {{ $t('Further rights') }}
                                            </button>
                                        </span>
                                    </template>
                                    <template #content>
                                        <div class="w-44 p-4">
                                            <div class="flex">
                                                <input v-model="user.pivot_access_budget"
                                                       type="checkbox"
                                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                            <p class=" ml-4 my-auto text-sm text-secondary">
                                                {{ $t('Budget access') }}
                                            </p>
                                            </div>
                                            <div class="flex mt-4">
                                                <input v-model="user.pivot_delete_permission"
                                                       type="checkbox"
                                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                            <p
                                                class=" ml-4 my-auto text-sm text-secondary">
                                                {{ $t('Permission to delete') }}
                                            </p>
                                            </div>
                                            <div class="flex mt-4" v-if="user.project_management">
                                                <input v-model="user.pivot_is_manager"
                                                       type="checkbox"
                                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                            <p
                                                class="ml-4 my-auto text-sm text-secondary">{{ $t('Project management') }}</p>
                                            </div>
                                        </div>
                                    </template>
                                </Dropdown>

                                <Dropdown :open="user.openedMenuRoles" align="right" width="56" class="text-right">
                                    <template #trigger>
                                        <span class="inline-flex">
                                            <button @click="user.openedMenuRoles = !user.openedMenuRoles" type="button"
                                                    class="text-sm flex items-center ml-14 my-auto text-primary font-semibold focus:outline-none transition">
                                                {{ $t('Project Roles') }}
                                            </button>
                                        </span>
                                    </template>
                                    <template #content>
                                        <div class="flex flex-col p-4">
                                            <div v-if="projectRoles.length > 0"
                                                 v-for="role in projectRoles"
                                                 class="flex mb-2">
                                                <input :id="role.id" :name="role.name" type="checkbox" :checked="user?.pivot_roles?.includes(role.id)" @change="addRoleToUser(user, role)" class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                                <p class=" ml-4 my-auto text-sm text-secondary">
                                                    {{ role.name }}
                                                </p>
                                            </div>
                                            <div v-else>
                                                <Link v-if="hasAdminRole()"
                                                      class="linkText"
                                                      :href="route('project-roles.index')">
                                                    {{ $t('No project roles created yet') }}
                                                </Link>
                                                <span v-else class="xxsLight">
                                                    {{ $t('No project roles created yet') }}
                                                </span>
                                            </div>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
                    </div>
                    <span v-for="department in this.departments"
                          class="flex mt-4 mr-1 rounded-full items-center font-bold text-primary">
                        <div class="flex items-center">
                            <TeamIconCollection :iconName="department.svg_name" :alt="department.name"
                                                class="rounded-full h-11 w-11 object-cover"/>
                            <span class="flex ml-4">
                                {{ department.name }}
                            </span>
                        </div>
                        <button type="button" @click="deleteDepartmentFromProjectTeam(department)">
                            <span class="sr-only">{{ $t('Remove team from the project') }}</span>
                            <XCircleIcon class="ml-2 h-5 w-5 hover:text-error "/>
                        </button>
                    </span>
                </div>
                <div class="w-full items-center text-center">
                    <FormButton @click="editProjectTeam" :disabled="form.processing"
                               :text="$t('Save')"
                    />
                </div>
            </div>
    </BaseModal>
</template>

<script>
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import {XCircleIcon, XIcon} from "@heroicons/vue/solid";
import {Link, useForm} from "@inertiajs/vue3";
import Dropdown from "@/Jetstream/Dropdown.vue";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

export default {
    mixins: [Permissions, IconLib],
    name: "ProjectEditTeamModal",
    components: {
        BaseInput,
        BaseModal,
        FormButton,
        Dropdown,
        JetDialogModal,
        TeamIconCollection,
        XCircleIcon,
        XIcon,
        Link
    },
    props: [
        'show',
        'assignedUsers',
        'assignedDepartments',
        'userIsProjectManager',
        'projectId',
        'projectRoles'
    ],
    data(){
        return {
            department_and_user_query: "",
            department_search_results: [],
            department_and_user_search_results: [],
            form: useForm({
                assigned_user_ids: {},
                assigned_departments: [],
            }),
            //remove reference to original object by destructuring to new array
            users: [...this.assignedUsers],
            //remove reference to original object by destructuring to new array
            departments: [...this.assignedDepartments]
        }
    },
    methods: {
        closeModal(bool) {
            this.$emit('closed', bool);
        },
        addDepartmentToProjectTeamArray(departmentToAdd) {
            for (let assignedDepartment of this.departments) {
                if (departmentToAdd.id === assignedDepartment.id) {
                    this.department_and_user_query = ""
                    return;
                }
            }

            this.department_and_user_query = ""
            this.departments.push(departmentToAdd);
        },
        deleteDepartmentFromProjectTeam(department) {
            this.departments.splice(this.departments.indexOf(department), 1);
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
        deleteUserFromProjectTeam(user) {
            if (this.users.includes(user)) {
                this.users.splice(this.users.indexOf(user), 1);
            }
        },
        editProjectTeam() {
            this.form.assigned_user_ids = {};
            this.users.forEach(user => {
                this.form.assigned_user_ids[user.id] = {
                    access_budget: user.pivot_access_budget,
                    is_manager: user.pivot_is_manager,
                    can_write: user.pivot_can_write,
                    delete_permission: user.pivot_delete_permission,
                    roles: user.pivot_roles
                };
            })
            this.form.assigned_departments = [];
            this.departments.forEach(department => {
                this.form.assigned_departments.push(department);
            })
            this.form.patch(route('projects.update_team', {project: this.projectId}));
            this.closeModal(true);
        },
        checkUserAuth(user) {
            if (this.userIsProjectManager) {
                return true;
            }
            if (this.$page.props.auth.user.id === user.id && user.project_management) {
                return true;
            }

            return this.hasAdminRole();
        },
        addRoleToUser(user, role) {
            if (user.pivot_roles?.includes(role.id)) {
                user.pivot_roles.splice(user.pivot_roles.indexOf(role.id), 1);
                return;
            }
            user.pivot_roles.push(role.id);
        }
    },
    watch: {
        department_and_user_query: {
            handler() {
                if (this.department_and_user_query.length > 0) {
                    axios.get(
                        route('users_departments.search'),
                        {
                            params: {
                                query: this.department_and_user_query
                            }
                        }
                    ).then(
                        response => {
                            this.department_and_user_search_results = response.data
                        }
                    )
                }
            },
            deep: true
        },
    },
}
</script>
