<template>
    <BaseModal @closed="closeModal" v-if="show" modal-image="/Svgs/Overlays/illu_project_edit.svg">
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{ $t('Contract upload')}}
                </div>
                <div class="text-secondary text-sm my-6">
                    {{ $t('Upload documents that relate exclusively to the budget. These can only be viewed by users with the appropriate authorization.') }}
                </div>
                <div>
                    <input
                        @change="upload"
                        class="hidden"
                        ref="module_files"
                        id="file"
                        type="file"
                    />
                    <div @click="selectNewFiles" @dragover.prevent
                         @drop.stop.prevent="uploadDraggedDocuments($event)" class="mb-4 w-full flex justify-center items-center
                        border-artwork-buttons-create border-dotted border-2 h-32 bg-colorOfAction p-2 cursor-pointer">
                        <p class="text-artwork-buttons-create font-bold text-center" v-html="$t('Drag document here to upload or click in the field')"></p>
                    </div>
                    <jet-input-error :message="uploadDocumentFeedback"/>
                </div>
                <div class="mb-2">
                    <div class="group flex" v-if="this.file">{{ this.file.name }}
                        <IconCircleX
                            stroke-width="1.5" @click="this.file = null"
                            class="ml-2 group-hover:cursor-pointer my-auto hidden group-hover:block h-5 w-5 flex-shrink-0 text-error"
                            aria-hidden="true"/>
                    </div>
                </div>
                <div>
                    <div class="flex xsDark my-2 items-center" v-if="selectedProject">
                    {{ $t('Currently assigned to:')}}
                    <a v-if="this.selectedProject?.id"
                       :href="route('projects.tab', {project: selectedProject.id, projectTab: this.first_project_calendar_tab_id})"
                       class="ml-3 flex xsDark">
                        {{ this.selectedProject?.name }}
                    </a>
                    </div>
                    <div class="w-full" v-if="!this.projectId">
                        <input type="text"
                               :placeholder="$t('Save in which project?*')"
                               v-model="project_query"
                               class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        <div
                            v-if="project_search_results.length > 0"
                            class="bg-primary truncate sm:text-sm absolute z-20 w-[88%]">
                            <div v-for="(project, index) in project_search_results"
                                 :key="index"
                                 @click="selectProject(project)"
                                 class="p-4 text-white border-l-4 hover:border-l-success border-l-primary cursor-pointer w-[88%]">
                                {{ project.name }}
                            </div>
                        </div>
                    </div>
                    <div class="py-1">
                        <input type="text"
                               v-model="contractForm.contract_partner"
                               id="eventTitle"
                               :placeholder="$t('Contract partner*')"
                               class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                    </div>
                    <div class="py-1">
                        <Listbox as="div" class="flex h-12" v-model="selectedLegalForm"
                                 id="eventType">
                            <ListboxButton v-if="selectedLegalForm !== null"
                                           class="pl-3 h-12 inputMain w-full bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm flex items-center">
                                <div class="flex items-center my-auto">
                                <span class="truncate items-center flex">
                                            <span>{{ selectedLegalForm.name }}</span>
                                </span>
                                    <span
                                        class="ml-2 right-0 absolute inset-y-0 flex items-center pr-2 pointer-events-none">
                                     <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </span>
                                </div>
                            </ListboxButton>
                            <ListboxButton v-else
                                           class="pl-3 h-12 inputMain w-full bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm flex items-center">
                                <div class="flex flex-grow xsLight text-left subpixel-antialiased">
                                    {{ $t('Legal form')}}
                                </div>
                                <span
                                    class="ml-2 right-0 absolute inset-y-0 flex items-center pr-2 pointer-events-none">
                                     <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </span>
                            </ListboxButton>
                            <transition leave-active-class="transition ease-in duration-100"
                                        leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions
                                    class="absolute w-[88%] z-10 mt-12 bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                    <ListboxOption as="template" class="max-h-8"
                                                   v-for="legalForm in companyTypes"
                                                   :key="legalForm"
                                                   :value="legalForm"
                                                   v-slot="{ active, selected }">
                                        <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                            <div class="flex">
                                            <span
                                                :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate']">
                                                        {{ legalForm.name }}
                                                    </span>
                                            </div>
                                            <span
                                                :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                      <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </transition>
                        </Listbox>
                    </div>
                    <div class="py-1">
                        <Listbox as="div" class="flex h-12" v-model="selectedContractType"
                                 id="eventType">
                            <ListboxButton v-if="selectedContractType !== null"
                                           class="pl-3 h-12 inputMain w-full bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm flex items-center">
                                <div class="flex items-center my-auto">
                                <span class="truncate items-center flex">
                                            <span>{{ selectedContractType.name }}</span>
                                </span>
                                    <span
                                        class="ml-2 right-0 absolute inset-y-0 flex items-center pr-2 pointer-events-none">
                                     <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </span>
                                </div>
                            </ListboxButton>
                            <ListboxButton v-else
                                           class="pl-3 h-12 inputMain w-full bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm flex items-center">
                                <div class="flex flex-grow xsLight text-left subpixel-antialiased">
                                    {{ $t('Contract type')}}
                                </div>
                                <span
                                    class="ml-2 right-0 absolute inset-y-0 flex items-center pr-2 pointer-events-none">
                                     <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </span>
                            </ListboxButton>
                            <transition leave-active-class="transition ease-in duration-100"
                                        leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions
                                    class="absolute w-[88%] z-10 mt-12 bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                    <ListboxOption as="template" class="max-h-8"
                                                   v-for="contractType in contractTypes"
                                                   :key="contractType"
                                                   :value="contractType"
                                                   v-slot="{ active, selected }">
                                        <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                            <div class="flex">
                                            <span
                                                :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate']">
                                                        {{ contractType.name }}
                                                    </span>
                                            </div>
                                            <span
                                                :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                      <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </transition>
                        </Listbox>
                    </div>
                    <div class="py-1 w-full flex">
                        <input type="number"
                               v-model="contractForm.amount"
                               :placeholder="$t('Amount* (fee, co-production contribution, etc.)')"
                               class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        <Listbox as="div" class="flex h-12 w-24" v-model="selectedCurrency"
                                 id="eventType">
                            <ListboxButton
                                class="pl-1 truncate h-12 inputMain w-full bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm flex items-center">
                                <div class="flex items-center my-auto">
                                <span class="w-12 truncate items-center ml-3 flex">
                                    <span>{{ selectedCurrency.name }}</span>
                                </span>
                                    <span
                                        class="ml-2 right-0 absolute inset-y-0 flex items-center pr-2 pointer-events-none">
                                     <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </span>
                                </div>
                            </ListboxButton>
                            <transition leave-active-class="transition ease-in duration-100"
                                        leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions
                                    class="absolute w-[12%] z-10 mt-12 bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                    <ListboxOption as="template" class="max-h-8"
                                                   v-for="currency in currencies"
                                                   :key="currency"
                                                   :value="currency"
                                                   v-slot="{ active, selected }">
                                        <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-2 pr-9 text-sm subpixel-antialiased']">
                                            <div class="flex">
                                            <span
                                                :class="[selected ? 'xsWhiteBold' :  'font-normal', 'ml-1 block truncate']">
                                                        {{ currency.name }}
                                                    </span>
                                            </div>
                                            <span
                                                :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                      <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </transition>
                        </Listbox>
                    </div>
                    <div class="my-2 mb-4">
                        <div class="flex items-center mb-2">
                            <input id="hasGroup" type="checkbox" v-model="contractForm.ksk_liable"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <label for="hasGroup" :class="contractForm.ksk_liable ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                   class="ml-2">
                                {{ $t('KSK-liable')}}
                            </label>
                        </div>

                        <div class="flex items-center mb-2">
                            <input id="hasGroup" type="checkbox" v-model="contractForm.resident_abroad"
                                   @click="contractForm.has_power_of_attorney = false; contractForm.is_freed = false"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <label for="hasGroup" :class="contractForm.resident_abroad ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                   class="ml-2">
                                {{ $t('Resident abroad')}}
                            </label>
                        </div>
                        <div class="ml-4" v-if="contractForm.resident_abroad">
                            <div class="flex items-center mb-2">
                                <input id="hasGroup" type="checkbox" v-model="contractForm.has_power_of_attorney"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <label for="hasGroup"
                                       :class="contractForm.has_power_of_attorney ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                       class="ml-2">
                                    {{ $t('Power of attorney is available')}}
                                </label>
                            </div>
                            <div class="flex items-center mb-2">
                                <input id="hasGroup" type="checkbox" v-model="contractForm.is_freed"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <label for="hasGroup" :class="contractForm.is_freed ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                       class="ml-2">
                                    {{ $t('Liberated at home')}}
                                </label>
                            </div>
                        </div>
                    </div>
                    <textarea :placeholder="$t('Comment / Note')"
                              id="description"
                              v-model="description"
                              rows="5"
                              class="inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300"/>

                    <div class="my-1">
                        <div class="relative w-full">
                            <div class="w-full">
                                <input id="userSearch" v-model="user_query" type="text" autocomplete="off"
                                       :placeholder="$t('Document access for')"
                                       class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
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
                                                <p @click="addUserToContractUserArray(user)"
                                                   class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                    {{ user.first_name }} {{ user.last_name }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </transition>
                        </div>
                        <div v-if="usersWithAccess.length > 0" class="mt-2 mb-4 flex items-center">
                                        <span v-for="(user,index) in usersWithAccess"
                                              class="flex mr-5 rounded-full items-center font-bold text-primary">
                                        <div class="flex items-center">
                                            <img class="flex h-11 w-11 rounded-full object-cover"
                                                 :src="user.profile_photo_url"
                                                 alt=""/>
                                            <span class="flex ml-4 sDark">
                                            {{ user.first_name }} {{ user.last_name }}
                                            </span>
                                            <button type="button" @click="deleteUserFromContractUserArray(index)">
                                                <span class="sr-only">{{ $t('Remove user from contract')}}</span>
                                                <IconX stroke-width="1.5"
                                                    class="ml-2 h-4 w-4 p-0.5 hover:text-error rounded-full bg-artwork-buttons-create text-white border-0 "/>
                                            </button>
                                        </div>

                                        </span>
                        </div>
                    </div>
                </div>
                <div class="bg-backgroundGray -mx-10 pt-6 pb-12 mt-6">
                    <div class="px-12 w-full">
                        <div class="xxsDarkBold flex items-center cursor-pointer"
                             @click="showExtraSettings = !showExtraSettings">
                            {{ $t('Add further details or task')}}
                            <IconChevronUp stroke-width="1.5" v-if="showExtraSettings"
                                           class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></IconChevronUp>
                            <IconChevronDown stroke-width="1.5" v-else class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></IconChevronDown>
                        </div>
                        <div v-if="showExtraSettings">
                            <div class="items-center mb-2 mt-4">
                                <div v-for="task in tasks" class="mt-2">
                                    <input id="hasGroup" type="checkbox" v-model="task.checked"
                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                    <label for="hasGroup"
                                           :class="task.checked ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                           class="ml-2">
                                        {{ task.name }}
                                    </label>
                                </div>
                            </div>

                            <ContractTaskForm :show="creatingNewTask" :users="budgetAccess" ref="task_form"
                                              @add-task="addTask" @showError="showError"/>
                            <div class="flex justify-between">
                                <button v-if="!creatingNewTask" type="button"
                                        @click="[creatingNewTask = !creatingNewTask]"
                                        class="flex py-3 px-8 items-center  border-2 mt-6 border-artwork-buttons-create bg-backgroundGray hover:bg-gray-200 rounded-full shadow-sm text-artwork-buttons-create hover:shadow-artwork-buttons-create focus:outline-none">
                                    <IconCirclePlus stroke-width="1.5" class="h-6 w-6 mr-2" aria-hidden="true"/>
                                    <p class="text-sm">{{ tasks.length === 0 ? $t('New task') : $t('Further task') }}</p>
                                </button>

                                <button
                                    class="flex text-sm py-3 px-8 items-center border-2 mt-6 border-success bg-backgroundGray hover:bg-green-50 rounded-full shadow-sm text-success hover:shadow-v-if focus:outline-none"
                                    v-if="creatingNewTask"
                                    @click="$refs.task_form.saveTask(); this.errorText === null ? creatingNewTask = false : null">
                                    {{$t('Save task in contract')}}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="justify-center flex w-full my-6">
                    <button
                        class="flex p-2 px-8 mt-1 items-center border border-transparent rounded-full shadow-sm  focus:outline-none"
                        :class="(file === null || contractForm.amount === '' || contractForm.contract_partner === '') || (!this.projectId && this.selectedProject === null)? 'bg-secondary text-white' : 'text-white bg-artwork-buttons-create hover:shadow-artwork-buttons-create hover:bg-artwork-buttons-hover'"
                        :disabled="file === null || contractForm.amount === '' || contractForm.contract_partner === '' || (!this.projectId && this.selectedProject === null)"
                        @click="storeContract">{{ $t('Upload contract')}}
                    </button>
                </div>
            </div>
        </BaseModal>
</template>

<script>
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import {PlusCircleIcon, XIcon} from "@heroicons/vue/outline";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";
import {CheckIcon, ChevronDownIcon, ChevronUpIcon, XCircleIcon} from "@heroicons/vue/solid";
import {useForm} from "@inertiajs/vue3";
import ContractTaskForm from "@/Layouts/Components/ContractTaskForm.vue";
import Button from "@/Jetstream/Button.vue";
import Permissions from "@/Mixins/Permissions.vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import Input from "@/Jetstream/Input.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    name: "ContractUploadModal",
    mixins: [Permissions, IconLib],
    emits: ['closeModal'],
    props: [
        'show',
        'projectId',
        'budgetAccess',
        'contractTypes',
        'companyTypes',
        'currencies',
        'first_project_calendar_tab_id'
    ],
    components: {
        BaseModal,
        Input,
        InputComponent,
        Button,
        ContractTaskForm,
        JetDialogModal,
        JetInputError,
        XIcon,
        Listbox,
        ListboxOption,
        ListboxOptions,
        ListboxButton,
        ChevronDownIcon,
        ChevronUpIcon,
        CheckIcon,
        PlusCircleIcon,
        XCircleIcon
    },
    watch: {
        user_query: {
            handler() {
                if (this.user_query.length > 0) {
                    axios.get('/users/search', {
                        params: {query: this.user_query}
                    }).then(response => {
                        if(this.budgetAccess){
                            this.user_search_results = response.data.filter(user => this.budgetAccess.some(budgetAccess => budgetAccess.id === user.id))
                        }else{
                            this.user_search_results = response.data
                        }

                    })
                }
            },
            deep: true
        },
        project_query: {
            handler() {
                if (this.project_query.length > 0) {
                    axios.get('/projects/search', {
                        params: {query: this.project_query}
                    }).then(response => {
                        this.project_search_results = response.data
                    })
                }
            },
            deep: true
        },
    },
    data() {
        return {
            selectedProject: null,
            project_query: '',
            project_search_results: [],

            errorText: null,
            creatingNewTask: false,
            tasks: [],
            uploadDocumentFeedback: "",
            file: null,
            description: "",
            contractPartner: '',
            selectedLegalForm: null,
            selectedContractType: null,
            selectedCurrency: {id: 1, name: '€'},
            user_search_results: [],
            user_query: '',
            usersWithAccess: [],
            showExtraSettings: false,
            contractForm: useForm({
                file: null,
                contract_partner: '',
                company_type_id: null,
                contract_type_id: null,
                amount: '',
                currency_id: 1,
                ksk_liable: false,
                resident_abroad: false,
                has_power_of_attorney: false,
                is_freed: false,
                description: '',
                accessibleUsers: [],
                tasks: [],
            }),
        }
    },
    methods: {
        showError() {
            this.errorText = this.$t('You must assign the task to a person with document access')
        },
        selectProject(project) {
            this.selectedProject = project;
            this.project_query = '';
            this.project_search_results = [];
        },
        addTask(task) {
            this.tasks.push(task)
            this.errorText = null;
        },
        addUserToContractUserArray(user) {
            if (!this.usersWithAccess.find(userToAdd => userToAdd.id === user.id)) {
                this.usersWithAccess.push(user);
            }
            this.user_query = '';
        },
        deleteUserFromContractUserArray(index) {
            this.usersWithAccess.splice(index, 1);
        },
        selectNewFiles() {
            this.$refs.module_files.click();
        },
        uploadDraggedDocuments(event) {
            this.validateType([...event.dataTransfer.files])
        },
        upload(event) {
            this.validateType([...event.target.files])
        },
        validateType(files) {
            this.uploadDocumentFeedback = "";
            const forbiddenTypes = [
                "application/vnd.microsoft.portable-executable",
                "application/x-apple-diskimage",
            ]
            for (let file of files) {
                if (forbiddenTypes.includes(file.type) || file.type.match('video.*') || file.type === "") {
                    this.uploadDocumentFeedback = this.$t('Videos, .exe and .dmg files are not supported')
                } else {
                    const fileSize = file.size;
                    if (fileSize > 2097152) {
                        this.uploadDocumentFeedback = this.$t('Files larger than 2MB cannot be uploaded.')
                    } else {
                        this.file = file
                    }
                }
            }
        },
        closeModal(){
            this.contractForm.reset();
            this.selectedProject = null;
            this.selectedLegalForm = null;
            this.selectedContractType = null;
            this.selectedCurrency = {id: 1, name: '€'};
            this.file = null;
            this.description = "";
            this.user_query = '';
            this.usersWithAccess = [];
            this.showExtraSettings = false;
            this.creatingNewTask = false;
            this.tasks = [];
            this.uploadDocumentFeedback = "";
            this.errorText = null;
            this.$emit('closeModal', true)
        },
        clearTaskForm() {
            this.$refs.task_form.clearForm();
        },
        storeContract() {
            this.contractForm.file = this.file;
            this.contractForm.company_type_id = this.selectedLegalForm?.id;
            this.contractForm.contract_type_id = this.selectedContractType?.id;
            const userIds = [];
            this.usersWithAccess.forEach((user) => {
                userIds.push(user.id);
            })
            this.contractForm.accessibleUsers = userIds;
            this.contractForm.tasks = this.tasks
            if(this.projectId){
                this.contractForm.post(this.route('contracts.store', this.projectId), {
                    // TODO: Richtige einbauweise
                    forceFormData: true,
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        this.contractForm.reset()
                        this.closeModal()
                    },
                });
            }else{
                this.contractForm.post(this.route('contracts.store', this.selectedProject.id), {
                    // TODO: Richtige einbauweise
                    forceFormData: true,
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        this.contractForm.reset()
                        this.closeModal()
                    },
                });
            }
        }
    },
}
</script>

<style scoped>

</style>
