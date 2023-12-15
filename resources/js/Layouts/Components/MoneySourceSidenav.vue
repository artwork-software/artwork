<template>
    <div class="w-full mt-36">
        <div class="w-full flex-grow items-center mb-4">
            <div class="text-secondary flex justify-between text-md font-semibold">
                Freigegeben für
                <div v-if="$role('artwork admin') || writeAccess.includes($page.props.user.id) || competent.includes($page.props.user.id)"
                    class="bg-gray-500 h-6 w-6 flex items-center justify-center rounded-full hover:bg-gray-900 cursor-pointer transition-all"
                    @click="openEditUsersModal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="10.918" height="10.918" viewBox="0 0 10.918 10.918">
                        <g id="Icon_feather-edit" data-name="Icon feather-edit" transform="translate(0.5 0.5)">
                            <path id="Pfad_1013" data-name="Pfad 1013"
                                  d="M7.436,6H3.986A.986.986,0,0,0,3,6.986v6.9a.986.986,0,0,0,.986.986h6.9a.986.986,0,0,0,.986-.986v-3.45"
                                  transform="translate(-3 -4.954)" fill="none" stroke="#fcfcfb" stroke-linecap="round"
                                  stroke-linejoin="round" stroke-width="1"/>
                            <path id="Pfad_1014" data-name="Pfad 1014"
                                  d="M17.176,3.124A1.046,1.046,0,0,1,18.654,4.6L13.972,9.286,12,9.779l.493-1.972Z"
                                  transform="translate(-9.043 -2.818)" fill="none" stroke="#fcfcfb"
                                  stroke-linecap="round" stroke-linejoin="round" stroke-width="1"/>
                        </g>
                    </svg>
                </div>
            </div>
            <div class="flex flex-wrap">
                <div class="flex w-full xsLight mt-1">
                    Zuständig
                </div>
                <div class="ml-3 flex">
                    <div v-for="user in users">
                        <div class="-ml-3"  v-if="user.pivot?.competent">
                            <NewUserToolTip :type="1" :user="user" height="10" width="10" :id="user.id"/>
                        </div>
                    </div>
                </div>
                <div class="flex w-full xsLight mt-2">
                    Zugriff
                </div>
                <div class="ml-3 flex">
                    <div  v-for="user in users">
                        <div class="-ml-3" v-if="user.pivot?.write_access && !user.pivot?.competent">
                            <NewUserToolTip :user="user" height="10" width="10" :id="user.id"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="border-t-2 my-5 w-full border-secondary border-opacity-30" />
        <div class="w-full items-center mb-4">
            <div class="text-secondary flex items-center justify-between text-md font-semibold my-2">
                <div class="flex items-center">
                    Quellenkategorien
                    <ChevronDownIcon class="w-4 h-4 ml-4" :class="[ showMoneySourceCategories ? 'rotate-180' : '']"
                                     @click="showMoneySourceCategories = !showMoneySourceCategories"/>
                </div>

                <div class="bg-gray-500 h-6 w-6 flex items-center justify-center rounded-full hover:bg-gray-900 cursor-pointer transition-all"
                     @click="openMoneySourceCategoriesModal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="10.918" height="10.918" viewBox="0 0 10.918 10.918">
                        <g id="Icon_feather-edit" data-name="Icon feather-edit" transform="translate(0.5 0.5)">
                            <path id="Pfad_1013" data-name="Pfad 1013"
                                  d="M7.436,6H3.986A.986.986,0,0,0,3,6.986v6.9a.986.986,0,0,0,.986.986h6.9a.986.986,0,0,0,.986-.986v-3.45"
                                  transform="translate(-3 -4.954)" fill="none" stroke="#fcfcfb" stroke-linecap="round"
                                  stroke-linejoin="round" stroke-width="1"/>
                            <path id="Pfad_1014" data-name="Pfad 1014"
                                  d="M17.176,3.124A1.046,1.046,0,0,1,18.654,4.6L13.972,9.286,12,9.779l.493-1.972Z"
                                  transform="translate(-9.043 -2.818)" fill="none" stroke="#fcfcfb"
                                  stroke-linecap="round" stroke-linejoin="round" stroke-width="1"/>
                        </g>
                    </svg>
                </div>
            </div>
            <div v-if="showMoneySourceCategories" class="flex flex-row flex-wrap">
                <TagComponent v-if="money_source.categories.length > 0"
                              v-for="category in money_source.categories"
                              :key="category.id"
                              :displayed-text="category.name"
                              type="gray"
                              hide-x="true"/>
            </div>
        </div>
        <div class="border-t-2 my-5 w-full border-secondary border-opacity-30" />
        <div class="w-full items-center mb-4">
            <div class="text-secondary flex items-center justify-between text-md font-semibold my-2">
                <div class="flex items-center">
                Zugriff für
                    <ChevronDownIcon class="w-4 h-4 ml-4" :class="[ showLinkedProjects ? 'rotate-180' : '']"
                                     @click="showLinkedProjects = !showLinkedProjects"/>
                </div>

                <div v-if="$role('artwork admin') || writeAccess.includes($page.props.user.id) || competent.includes($page.props.user.id)"
                    class="bg-gray-500 h-6 w-6 flex items-center justify-center rounded-full hover:bg-gray-900 cursor-pointer transition-all"
                    @click="openLinkProjectsModal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="10.918" height="10.918" viewBox="0 0 10.918 10.918">
                        <g id="Icon_feather-edit" data-name="Icon feather-edit" transform="translate(0.5 0.5)">
                            <path id="Pfad_1013" data-name="Pfad 1013"
                                  d="M7.436,6H3.986A.986.986,0,0,0,3,6.986v6.9a.986.986,0,0,0,.986.986h6.9a.986.986,0,0,0,.986-.986v-3.45"
                                  transform="translate(-3 -4.954)" fill="none" stroke="#fcfcfb" stroke-linecap="round"
                                  stroke-linejoin="round" stroke-width="1"/>
                            <path id="Pfad_1014" data-name="Pfad 1014"
                                  d="M17.176,3.124A1.046,1.046,0,0,1,18.654,4.6L13.972,9.286,12,9.779l.493-1.972Z"
                                  transform="translate(-9.043 -2.818)" fill="none" stroke="#fcfcfb"
                                  stroke-linecap="round" stroke-linejoin="round" stroke-width="1"/>
                        </g>
                    </svg>
                </div>
            </div>
            <div v-if="showLinkedProjects" class="text-secondary text-sm" v-for="linkedProject in linkedProjects">
                {{ linkedProject.name }}
            </div>
        </div>
        <div class="border-t-2 my-5 w-full border-secondary border-opacity-30" />
        <div class="w-full flex items-center mb-4">
            <div class="text-secondary text-md font-semibold">
                Dokumente
            </div>
            <ChevronDownIcon class="w-4 h-4 ml-4" :class="[ showMoneySourceFiles ? 'rotate-180' : '']"
                             @click="showMoneySourceFiles = !showMoneySourceFiles"/>
            <UploadIcon v-if="$role('artwork admin') || writeAccess.includes($page.props.user.id) || competent.includes($page.props.user.id)" class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                        @click="openFileUploadModal"/>
        </div>
        <div v-if="showMoneySourceFiles">
            <div v-if="moneySourceFiles?.data.length > 0">
                <div v-for="moneySourceFile in moneySourceFiles.data">
                    <div class="flex items-center w-full mb-2 cursor-pointer text-secondary hover:text-white" v-if="$role('artwork admin') || writeAccess.includes($page.props.user.id) || competent.includes($page.props.user.id)">
                        <DownloadIcon class="w-4 h-4 mr-2" @click="downloadMoneySourceFile(moneySourceFile)"/>
                        <div @click="openFileEditModal(moneySourceFile)">{{ moneySourceFile.name }}</div>
                        <XCircleIcon class="w-4 h-4 ml-auto bg-error rounded-full text-white" @click="openFileDeleteModal(moneySourceFile)"/>
                    </div>
                    <div v-else class="flex items-center w-full mb-2 cursor-pointer text-secondary hover:text-white">
                        {{ moneySourceFile.name }}
                    </div>
                </div>
            </div>
            <div v-else>
                <div class="text-secondary text-sm my-2">Keine Dokumente vorhanden</div>
            </div>
        </div>
        <div class="border-t-2 my-5 w-full border-secondary border-opacity-30" />
        <div class="w-full flex-grow items-center mb-4">
            <div class="text-secondary text-md font-semibold mb-3 flex justify-between">
                Aufgaben
                <div v-if="$role('artwork admin') || writeAccess.includes($page.props.user.id) || competent.includes($page.props.user.id)"
                    class="bg-gray-500 h-6 w-6 flex items-center justify-center rounded-full hover:bg-gray-900 cursor-pointer transition-all"
                    @click="openAddMoneySourceTask">
                    <svg xmlns="http://www.w3.org/2000/svg" width="10.918" height="10.918" viewBox="0 0 10.918 10.918">
                        <g id="Icon_feather-edit" data-name="Icon feather-edit" transform="translate(0.5 0.5)">
                            <path id="Pfad_1013" data-name="Pfad 1013"
                                  d="M7.436,6H3.986A.986.986,0,0,0,3,6.986v6.9a.986.986,0,0,0,.986.986h6.9a.986.986,0,0,0,.986-.986v-3.45"
                                  transform="translate(-3 -4.954)" fill="none" stroke="#fcfcfb" stroke-linecap="round"
                                  stroke-linejoin="round" stroke-width="1"/>
                            <path id="Pfad_1014" data-name="Pfad 1014"
                                  d="M17.176,3.124A1.046,1.046,0,0,1,18.654,4.6L13.972,9.286,12,9.779l.493-1.972Z"
                                  transform="translate(-9.043 -2.818)" fill="none" stroke="#fcfcfb"
                                  stroke-linecap="round" stroke-linejoin="round" stroke-width="1"/>
                        </g>
                    </svg>
                </div>
            </div>
            <ul>
                <li v-for="task in tasks" class="mb-4 border-b border-gray-400 pb-3 flex items-start">
                    <div class="mr-2" v-if="$role('artwork admin') || writeAccess.includes($page.props.user.id) || competent.includes($page.props.user.id)">
                        <input @click="updateTask(task)"
                               type="checkbox"
                                 :checked="task.done"
                               class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                    </div>
                    <div :class="task.done ? 'line-through' : ''">
                        <div class="xsWhiteBold flex items-center gap-2">{{ task.name }} <span
                            class="text-red-500 xxsLight"
                            :class="Date.parse(task.deadline) < new Date().getTime()? 'text-error subpixel-antialiased' : ''">bis {{
                                formatDate(task.deadline)
                            }}</span>
                            <div class="flex">
                                <span class="-ml-1" v-for="(user,index) in task.money_source_task_users">
                                    <NewUserToolTip :height="7" :width="7" v-if="user"
                                                    :user="user" :id="index" :class="index === 1  ? '-ml-3' : ''"/>
                                </span>
                            </div>
                        </div>
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
    <EditMoneySourceUsersModal
        v-if="showEditUsersModal"
        @closed="onCloseEditUsersModal()"
        :moneySource="money_source"
    />
    <link-projects-to-money-sources-component
        v-if="showLinkProjectsModal"
        @closed="onCloseLinkProjectsModal()"
        :moneySource="money_source"
        :linkedProjects="linkedProjects"
    />

    <MoneySourceFileUploadModal :show="showFileUploadModal" :close-modal="closeFileUploadModal"
                                :money-source-id="money_source.id"/>

    <MoneySourceFileEditModal :show="showFileEditModal" :close-modal="closeFileEditModal"
                              :file="moneySourceFileToEdit"/>

    <MoneySourceFileDeleteModal :show="showFileDeleteModal" :money-source-id="money_source.id"
                                :close-modal="closeFileDeleteModal"
                                :file="moneySourceFileToDelete"/>

    <MoneySourceCategoriesModal :show="showMoneySourceCategoriesModal"
                                :money-source-id="money_source.id"
                                :money-source-categories="moneySourceCategories"
                                :money-source-current-categories="money_source.categories"
                                :close-modal="closeMoneySourceCategoriesModal"
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
import CreateMoneySourceTask from "@/Layouts/Components/CreateMoneySourceTask.vue";
import {Inertia} from "@inertiajs/inertia";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import MoneySourceFileUploadModal from "@/Layouts/Components/MoneySourceFileUploadModal.vue";
import MoneySourceFileEditModal from "@/Layouts/Components/MoneySourceFileEditModal.vue";
import MoneySourceFileDeleteModal from "@/Layouts/Components/MoneySourceFileDeleteModal.vue";
import MoneySourceCategoriesModal from "@/Layouts/Components/MoneySourceCategoriesModal.vue";
import {ChevronDownIcon} from "@heroicons/vue/solid";
import LinkProjectsToMoneySourcesComponent from "@/Layouts/Components/LinkProjectsToMoneySourcesComponent.vue";
import EditMoneySourceUsersModal from "@/Layouts/Components/EditMoneySourceUsersModal.vue";
import Permissions from "@/mixins/Permissions.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";

export default {
    mixins: [Permissions],
    name: "MoneySourceSidenav",
    props: [
        'users',
        'tasks',
        'money_source',
        'moneySourceFiles',
        'linkedProjects',
        'competent',
        'writeAccess',
        'moneySourceCategories'
    ],
    components: {
        TagComponent,
        LinkProjectsToMoneySourcesComponent,
        MoneySourceFileDeleteModal,
        MoneySourceFileEditModal,
        MoneySourceFileUploadModal,
        MoneySourceCategoriesModal,
        NewUserToolTip,
        ContractModuleDeleteModal,
        DownloadIcon,
        UploadIcon,
        XCircleIcon,
        UserTooltip,
        CreateMoneySourceTask,
        ChevronDownIcon,
        EditMoneySourceUsersModal
    },
    data() {
        return {
            showMoneySourceTaskModal: false,
            showFileUploadModal: false,
            showFileEditModal: false,
            showFileDeleteModal: false,
            showMoneySourceFiles: false,
            moneySourceFileToEdit: null,
            moneySourceFileToDelete: null,
            showLinkProjectsModal: false,
            showEditUsersModal: false,
            showLinkedProjects: false,
            showMoneySourceCategories: false,
            showMoneySourceCategoriesModal: false,
        }
    },
    methods: {
        openFileUploadModal() {
            this.showFileUploadModal = true
        },
        openEditUsersModal() {
            this.showEditUsersModal = true;
        },
        onCloseEditUsersModal() {
            this.showEditUsersModal = false;
        },
        openLinkProjectsModal() {
            this.showLinkProjectsModal = true;
        },
        openMoneySourceCategoriesModal() {
            this.showMoneySourceCategoriesModal = true;
        },
        onCloseLinkProjectsModal() {
            this.showLinkProjectsModal = false;
        },
        openFileEditModal(file) {
            this.moneySourceFileToEdit = file;
            this.showFileEditModal = true
        },
        openFileDeleteModal(file) {
            this.moneySourceFileToDelete = file
            this.showFileDeleteModal = true;
        },
        closeFileUploadModal() {
            this.showFileUploadModal = false;
        },
        closeFileEditModal() {
            this.showFileEditModal = false;
            this.moneySourceFileToEdit = null;
        },
        closeFileDeleteModal() {
            this.showFileDeleteModal = false;
            this.moneySourceFileToDelete = null;
        },
        closeMoneySourceCategoriesModal() {
            this.showMoneySourceCategoriesModal = false;
        },
        downloadMoneySourceFile(file) {
            let link = document.createElement('a');
            link.href = route('money_sources_download_file', {money_source_file: file});
            link.target = '_blank';
            link.click();
        },

        updateTask(task) {
            if (!task.done) {
                Inertia.patch(route('money_source.task.done', {moneySourceTask: task.id}), {}, {preserveState: true});
            } else {
                Inertia.patch(route('money_source.task.undone', {moneySourceTask: task.id}), {}, {preserveState: true});
            }

        },
        openAddMoneySourceTask() {
            this.showMoneySourceTaskModal = true
        },
        onCreateMoneySourceTask() {
            this.showMoneySourceTaskModal = false;
        },
        formatDate(date) {
            const dateFormate = new Date(date);
            return dateFormate.toLocaleString('de-de', {
                year: 'numeric',
                month: 'numeric',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }
}
</script>

<style scoped>

</style>
