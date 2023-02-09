<template>
    <jet-dialog-modal :show="editingChecklistTeams" @close="emitClose">
        <template #content>

            <img alt="" src="/Svgs/Overlays/illu_checklist_team_assign.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-3">
                <div class="font-bold font-lexend text-primary text-2xl my-2">
                    Teams zuweisen
                </div>

                <XIcon @click="emitClose"
                    class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                    aria-hidden="true"/>

                <div class="text-secondary tracking-tight leading-6 sub">
                    Tippe den Namen des Teams ein, dem du die Checkliste zuweisen möchtest.
                </div>
                <div class="mt-10">
                    <!--   Search for Departments    -->
                    <div class="my-auto w-full">
                        <input id="departmentSearch" placeholder="Name"
                            v-model="departmentQuery"
                            type="text"
                            autocomplete="off"
                            class="pl-2 h-12 w-10/12 focus:border-b-primary border-b-2 border-gray-300 text-primary focus:outline-none focus:ring-0 placeholder-secondary border-0"/>
                    </div>

                    <!--    Department Search Results    -->
                    <div class="absolute max-h-60 bg-primary shadow-lg text-sm flex flex-col w-9/12">
                        <button v-for="(department, index) in searchedDepartments"
                            :key="index"
                            class="cursor-pointer p-4 font-bold text-white hover:border-l-4 hover:border-l-success border-l-4 border-l-primary"
                            @click="addDepartment(department)">
                            {{ department.name }}
                        </button>
                        <div v-if="departmentQuery && (searchedDepartments.length === 0)"
                            key="no-item"
                            class="p-4 font-bold text-white">
                            Keine Ergebnisse gefunden
                        </div>
                    </div>
                </div>

                <!--    Team list    -->
                <div v-for="(department,index) in selectedDepartments"
                    class="mt-4 font-bold text-primary flex"
                    :key="index">
                    <div class="flex items-center">
                        <TeamIconCollection :iconName="department.svg_name" class="rounded-full h-11 w-11 object-cover"/>
                        <div class="pl-3 pt-1">{{ department.name }}</div>
                    </div>
                    <button type="button" @click="removeDepartment(department)">
                        <span class="sr-only">Team aus Checkliste entfernen</span>
                        <XCircleIcon class="ml-2 mt-1 h-5 w-5 hover:text-error text-white bg-primary rounded-full"/>
                    </button>
                </div>

                <AddButton @click="submitDepartments"
                           text="Zuweisen"
                           mode="modal"
                    class="mt-8 px-12 py-3" />

                <!-- <p v-if="error" class="text-red-800 text-xs">{{ error }}</p> -->
            </div>
        </template>
    </jet-dialog-modal>
</template>

<script>

import {XCircleIcon, XIcon} from '@heroicons/vue/outline';
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import JetDialogModal from "@/Jetstream/DialogModal";
import AddButton from "@/Layouts/Components/AddButton";

export default {
    name: 'ChecklistTeamComponent',

    components: {
        XIcon,
        XCircleIcon,
        TeamIconCollection,
        JetDialogModal,
        AddButton
    },

    emits: ['closed'],

    props: ['checklistId', 'departments', 'editingChecklistTeams'],

    data() {
        return {
            selectedDepartments: [],
            searchedDepartments: [],
            departmentQuery: null,
            error: null,
        }
    },

    methods: {
        addDepartment(department) {
            if (!this.selectedDepartments.find((selected) => selected.id === department.id)) {
                this.selectedDepartments.push(department);
            }
            this.departmentQuery = null;
            this.searchedDepartments = [];
        },

        removeDepartment(department) {
            this.selectedDepartments.splice(this.selectedDepartments.indexOf(department),1);
        },

        async submitDepartments() {
            await axios
                .patch(`/checklists/${this.checklistId}`, {
                    assigned_department_ids: this.selectedDepartments.map((department) => department.id)
                })
                .then(response => this.emitClose())
                // .catch(error => this.error = error.response.data.errors);
                .catch(error => this.emitClose());
        },

        emitClose() {
            this.$emit('closed')
        },
    },

    watch: {
        departmentQuery: {
            handler() {
                if (!this.departmentQuery) {
                    return
                }
                axios.get('/departments/search', {params: {query: this.departmentQuery}
                }).then(response => {
                    this.searchedDepartments = response.data
                })
            },
        },
        departments: {
            handler() {
                this.selectedDepartments = this.departments
            },
            deep: true
        },
    },
}
</script>

<style scoped>
</style>
