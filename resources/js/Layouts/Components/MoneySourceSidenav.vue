<template>
        <div class="w-full mt-36">
            <div class="w-full flex-grow items-center mb-4 h-36">
                <div class="text-secondary text-md font-semibold">
                    Freigegeben für
                </div>
                <div class="flex">
                    <div class="-ml-3 first:ml-0" v-for="user in users">
                        <img v-if="user"
                             :data-tooltip-target="user?.id"
                             :src="user?.profile_photo_url"
                             :alt="user?.name"
                             class="mt-3 rounded-full h-10 w-10 object-cover"/>
                        <UserTooltip v-if="user" :user="user" />
                    </div>
                </div>
            </div>
            <div class="w-full flex items-center mb-4 h-36">
                <div class="text-secondary text-md font-semibold">
                    Zugriff für
                </div>
            </div>
            <div class="w-full flex items-center mb-4 h-36">
                <div class="text-secondary text-md font-semibold">
                    Dokumente
                </div>
            </div>
            <div class="w-full flex-grow items-center mb-4">
                <div class="text-secondary text-md font-semibold mb-3 flex justify-between">
                    Aufgaben
                    <div class="bg-gray-500 h-6 w-6 flex items-center justify-center rounded-full hover:bg-gray-900 cursor-pointer transition-all" @click="openAddMoneySourceTask">
                        <svg xmlns="http://www.w3.org/2000/svg" width="10.918" height="10.918" viewBox="0 0 10.918 10.918">
                            <g id="Icon_feather-edit" data-name="Icon feather-edit" transform="translate(0.5 0.5)">
                                <path id="Pfad_1013" data-name="Pfad 1013" d="M7.436,6H3.986A.986.986,0,0,0,3,6.986v6.9a.986.986,0,0,0,.986.986h6.9a.986.986,0,0,0,.986-.986v-3.45" transform="translate(-3 -4.954)" fill="none" stroke="#fcfcfb" stroke-linecap="round" stroke-linejoin="round" stroke-width="1"/>
                                <path id="Pfad_1014" data-name="Pfad 1014" d="M17.176,3.124A1.046,1.046,0,0,1,18.654,4.6L13.972,9.286,12,9.779l.493-1.972Z" transform="translate(-9.043 -2.818)" fill="none" stroke="#fcfcfb" stroke-linecap="round" stroke-linejoin="round" stroke-width="1"/>
                            </g>
                        </svg>
                    </div>
                </div>
                <ul>
                    <li v-for="task in tasks" class="mb-4 border-b border-gray-400 pb-3 flex items-start">
                        <div class="mr-2">
                            <input @click="updateTask(task)"
                                   v-model="task.done"
                                   type="checkbox"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                        </div>
                        <div :class="task.done ? 'line-through' : ''">
                            <div class="xsWhiteBold flex items-center gap-2">{{ task.name }} <span class="text-red-500 xxsLight" :class="Date.parse(task.deadline) < new Date().getTime()? 'text-error subpixel-antialiased' : ''">bis {{ formatDate(task.deadline) }}</span><div class="flex">
                                <span class="-ml-1 first:ml-0 -mt-3 " v-for="user in task.money_source_task_users">
                                    <img v-if="user"
                                         :data-tooltip-target="user?.id"
                                         :src="user?.profile_photo_url"
                                         :alt="user?.name"
                                         class="mt-3 rounded-full h-5 w-5 object-cover"/>
                                    <UserTooltip v-if="user" :user="user" />
                                </span>
                            </div></div>
                            <p>{{ task.description }}</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>


    <create-money-source-task
        v-if="showMoneySourceTaskModal"
        @closed="onCreateMoneySourceTask()"
        :money_source_id="money_source.id"
    />

</template>

<script>
import {
    DownloadIcon,
    UploadIcon,
    XCircleIcon
} from '@heroicons/vue/outline';
import ContractModuleDeleteModal from "@/Layouts/Components/ContractModuleDeleteModal";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import {useForm} from "@inertiajs/inertia-vue3";
import CreateMoneySourceTask from "@/Layouts/Components/CreateMoneySourceTask.vue";


export default {
    name: "MoneySourceSidenav",
    props: ['users', 'tasks', 'money_source'],
    components: {
        ContractModuleDeleteModal,
        DownloadIcon,
        UploadIcon,
        XCircleIcon,
        UserTooltip,
        CreateMoneySourceTask
    },
    data() {
        return {
            showMoneySourceTaskModal: false
        }
    },
    methods: {
        updateTask(task){

            const taskForm = useForm({
                task
            })
            taskForm.patch(route('money_source.task.update', {moneySourceTask: task}), {preserveState: true} );
        },
        openAddMoneySourceTask(){
            this.showMoneySourceTaskModal = true
        },
        onCreateMoneySourceTask(){
            this.showMoneySourceTaskModal = false;
        },
        formatDate(date) {
            const dateFormate = new Date(date);
            return dateFormate.toLocaleString('de-de',{year: 'numeric', month: 'numeric', day: 'numeric', hour: '2-digit', minute: '2-digit'});
        }
    }
}
</script>

<style scoped>

</style>
