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
                        @change="validateType"
                        class="hidden"
                        ref="module_files"
                        id="file"
                        type="file"
                    />
                    <div @click="selectNewFiles" @dragover.prevent
                         @drop.stop.prevent="validateType($event)" class="mb-4 w-full flex rounded-lg justify-center items-center
                        border-artwork-buttons-create border-dotted border-2 h-32 bg-colorOfAction p-2 cursor-pointer">
                        <p class="text-artwork-buttons-create font-bold text-center" v-html="$t('Drag document here to upload or click in the field')"></p>
                    </div>
                    <jet-input-error :message="uploadDocumentFeedback"/>
                </div>
                <div class="mb-3">
                    <MultiAlertComponent :errors="contractForm.errors" v-show="Object.keys(contractForm.errors).length > 0"  :error-count="Object.keys(contractForm.errors).length" />
                </div>
                <div class="mb-2">
                    <div class="group flex" v-if="file">
                        {{ file.name }}
                        <IconCircleX
                            stroke-width="1.5" @click="this.file = null"
                            class="ml-2 group-hover:cursor-pointer my-auto hidden group-hover:block h-5 w-5 flex-shrink-0 text-error"
                            aria-hidden="true"/>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex xsDark items-center col-span-full" v-if="selectedProject">
                        {{ $t('Currently assigned to:')}}
                        <a v-if="this.selectedProject?.id"
                           :href="route('projects.tab', {project: selectedProject.id, projectTab: this.first_project_calendar_tab_id})"
                           class="ml-3 flex xsDark">
                            {{ this.selectedProject?.name }}
                        </a>
                    </div>
                    <div class="col-span-full relative" v-if="!this.projectId">
                        <BaseInput
                            :label="$t('Save in which project?*')"
                            v-model="project_query"
                            id="project_query"
                        />
                        <div v-if="project_search_results.length > 0" class="absolute bg-primary rounded-lg truncate sm:text-sm z-20 w-full top-16">
                            <div v-for="(project, index) in project_search_results" :key="index"
                                 @click="selectProject(project)"
                                 class="p-4 text-white border-l-4 hover:border-l-success border-l-primary cursor-pointer w-[88%]">
                                {{ project.name }}
                            </div>
                        </div>
                    </div>
                    <div class="">
                        <BaseInput
                            v-model="contractForm.contract_partner"
                            id="eventTitle"
                            :label="$t('Contract partner*')"
                        />
                    </div>
                    <div class="">
                        <Listbox as="div" class="flex relative" v-model="selectedLegalForm" id="eventType">
                            <ListboxButton v-if="selectedLegalForm !== null" class="menu-button">
                                <div>{{ selectedLegalForm.name }}</div>
                                <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                            </ListboxButton>
                            <ListboxButton v-else class="menu-button">
                                <span>{{ $t('Legal form')}}</span>
                                <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                            </ListboxButton>
                            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions class="absolute w-full z-10 mt-16 bg-primary rounded-lg shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                    <ListboxOption as="template" class="max-h-8" v-for="legalForm in companyTypes" :key="legalForm" :value="legalForm" v-slot="{ active, selected }">
                                        <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                            <div class="flex">
                                                <span :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate']">
                                                    {{ legalForm.name }}
                                                </span>
                                            </div>
                                            <span :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 flex text-success" aria-hidden="true"/>
                                            </span>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </transition>
                        </Listbox>
                    </div>
                    <div class="col-span-full">
                        <Listbox as="div" class="flex relative" v-model="selectedContractType" id="eventType">
                            <ListboxButton v-if="selectedContractType !== null" class="menu-button">
                                <div class="flex items-center justify-between w-full">
                                    <span class="truncate items-center flex">
                                        <span>{{ selectedContractType.name }}</span>
                                    </span>
                                    <span class="pointer-events-none">
                                        <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                    </span>
                                </div>
                            </ListboxButton>
                            <ListboxButton v-else class="menu-button">
                                <div class="flex flex-grow xsLight text-left subpixel-antialiased">
                                    {{ $t('Contract type')}}
                                </div>
                                <span class="pointer-events-none">
                                     <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </span>
                            </ListboxButton>
                            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions class="absolute w-full z-10 mt-16 rounded-lg bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                    <ListboxOption as="template" class="max-h-8" v-for="contractType in contractTypes" :key="contractType" :value="contractType" v-slot="{ active, selected }">
                                        <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                            <div class="flex">
                                                <span :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate']">
                                                    {{ contractType.name }}
                                                </span>
                                            </div>
                                            <span :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 flex text-success" aria-hidden="true"/>
                                            </span>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </transition>
                        </Listbox>
                    </div>
                    <div class="col-span-full">
                        <div class="flex">
                            <BaseInput
                                type="number"
                                id="amount"
                                v-model="contractForm.amount"
                                :label="$t('Amount* (fee, co-production contribution, etc.)')"
                            />
                            <Listbox as="div" class="flex w-28 relative" v-model="selectedCurrency" id="eventType">
                                <ListboxButton class="menu-button">
                                    <span>{{ selectedCurrency.name }}</span>
                                    <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </ListboxButton>
                                <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions class="absolute w-full z-10 mt-16 rounded-lg bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
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
                    </div>
                    <div class="col-span-full">
                        <div class="flex items-center mb-2">
                            <input id="hasGroup" type="checkbox" v-model="contractForm.ksk_liable"
                                   class="input-checklist"/>
                            <label for="hasGroup" :class="contractForm.ksk_liable ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                   class="ml-2">
                                {{ $t('KSK-liable')}}
                            </label>
                        </div>

                        <div class="flex items-center mb-2">
                            <input id="hasGroup" type="checkbox" v-model="contractForm.resident_abroad"
                                   @click="contractForm.has_power_of_attorney = false; contractForm.is_freed = false"
                                   class="input-checklist"/>
                            <label for="hasGroup" :class="contractForm.resident_abroad ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                   class="ml-2">
                                {{ $t('Resident abroad')}}
                            </label>
                        </div>
                        <div class="ml-4" v-if="contractForm.resident_abroad">
                            <div class="flex items-center mb-2">
                                <input id="hasGroup" type="checkbox" v-model="contractForm.has_power_of_attorney"
                                       class="input-checklist"/>
                                <label for="hasGroup"
                                       :class="contractForm.has_power_of_attorney ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                       class="ml-2">
                                    {{ $t('Power of attorney is available')}}
                                </label>
                            </div>
                            <div class="flex items-center mb-2">
                                <input id="hasGroup" type="checkbox" v-model="contractForm.is_freed"
                                       class="input-checklist"/>
                                <label for="hasGroup" :class="contractForm.is_freed ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                       class="ml-2">
                                    {{ $t('Liberated at home')}}
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-span-full">
                        <BaseTextarea
                            :label="$t('Comment / Note')"
                            id="description"
                            v-model="description"
                            rows="5"
                        />
                    </div>

                    <div class="-mx-10 bg-lightBackgroundGray px-10 py-5 col-span-full border-b border-dashed border-gray-300">
                        <div class="relative w-full">
                            <UserSearch v-model="user_query" @userSelected="addUserToContractUserArray"/>
                        </div>
                        <div v-if="usersWithAccess.length > 0" class="mt-2 mb-4 flex items-center">
                            <div v-for="(user,index) in usersWithAccess" class="flex mr-5 rounded-full items-center font-bold text-primary">
                                <div class="flex items-center">
                                    <img class="flex h-11 w-11 rounded-full object-cover" :src="user.profile_photo_url" alt=""/>
                                    <span class="flex ml-4 sDark">
                                        {{ user.first_name }} {{ user.last_name }}
                                    </span>
                                    <button type="button" @click="deleteUserFromContractUserArray(index)">
                                        <span class="sr-only">{{ $t('Remove user from contract')}}</span>
                                        <IconX stroke-width="1.5" class="ml-2 h-4 w-4 p-0.5 hover:text-error rounded-full text-primary border-0 "/>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-backgroundGray -mx-10 pt-6 pb-12">
                    <div class="px-12 w-full">
                        <div class="xxsDarkBold flex items-center cursor-pointer"
                             @click="showExtraSettings = !showExtraSettings">
                            {{ $t('Add further details or task')}}
                            <IconChevronUp stroke-width="1.5" v-if="showExtraSettings" class=" ml-1 mr-3 flex-shrink-0 h-4 w-4"></IconChevronUp>
                            <IconChevronDown stroke-width="1.5" v-else class=" ml-1 mr-3 flex-shrink-0h-4 w-4"></IconChevronDown>
                        </div>
                        <div v-if="showExtraSettings">
                            <div class="items-center mb-2">
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
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import NumberInputComponent from "@/Components/Inputs/NumberInputComponent.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import MultiAlertComponent from "@/Components/Alerts/MultiAlertComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";

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
        BaseTextarea,
        BaseInput,
        MultiAlertComponent,
        UserSearch,
        TextareaComponent,
        NumberInputComponent,
        TextInputComponent,
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
        validateType(file) {
            this.file = file.target.files[0]
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
