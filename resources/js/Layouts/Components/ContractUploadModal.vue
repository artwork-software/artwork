<template>
    <ArtworkBaseModal @close="closeModal" v-if="show" :title="$t('Contract upload')" :description="$t('Upload documents that relate exclusively to the budget. These can only be viewed by users with the appropriate authorization.')">
            <div class="">
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
                        <PropertyIcon name="IconCircleX"
                            stroke-width="1.5" @click="this.file = null"
                            class="ml-2 group-hover:cursor-pointer my-auto hidden group-hover:block h-5 w-5 flex-shrink-0 text-error"
                            aria-hidden="true"/>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Project Section -->
                    <div class="col-span-full" v-if="!this.projectId">
                        <div class="text-sm font-medium text-gray-700 mb-2">{{ $t('Project assignment') }}</div>

                        <!-- Selected Project Chip -->
                        <div v-if="selectedProject" class="flex items-center gap-2 rounded-md border border-zinc-200 bg-zinc-50 px-3 py-3 mb-2">
                            <span class="text-sm text-zinc-700">{{ $t('Connected project:') }}</span>
                            <a v-if="this.selectedProject?.id && this.first_project_calendar_tab_id"
                               :href="route('projects.tab', {project: selectedProject.id, projectTab: this.first_project_calendar_tab_id})"
                               class="font-medium text-indigo-600 hover:underline">
                                {{ this.selectedProject?.name }}
                            </a>
                            <span v-else class="font-medium text-zinc-900">{{ this.selectedProject?.name }}</span>
                            <button type="button" class="ml-auto text-zinc-400 hover:text-rose-600 transition" @click="removeProject">
                                <PropertyIcon name="IconCircleX" stroke-width="1.5" class="h-5 w-5" aria-hidden="true"/>
                            </button>
                        </div>

                        <!-- Project Search (only when no project selected) -->
                        <template v-if="!selectedProject">
                            <ProjectSearch
                                :label="$t('Search for projects') + '*'"
                                @project-selected="selectProject"
                            />
                            <LastedProjects
                                :limit="10"
                                @select="selectProject"
                            />
                        </template>
                    </div>

                    <div class="">
                        <BaseInput
                            v-model="contractForm.contract_partner"
                            id="eventTitle"
                            :label="$t('Contract partner*')"
                        />
                    </div>
                    <div class="">
                        <div class="flex relative">
                            <Listbox as="div" class="flex-1 relative" v-model="selectedLegalForm" id="legalFormSelect">
                                <ListboxButton v-if="selectedLegalForm !== null" class="menu-button">
                                    <div class="flex items-center justify-between w-full">
                                        <span>{{ selectedLegalForm.name }}</span>
                                        <PropertyIcon name="IconChevronDown" stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                    </div>
                                </ListboxButton>
                                <ListboxButton v-else class="menu-button">
                                    <span>{{ $t('Legal form')}}</span>
                                    <PropertyIcon name="IconChevronDown" stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </ListboxButton>
                                <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions class="absolute w-full z-10 mt-16 bg-primary rounded-lg shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" class="max-h-8" v-for="legalForm in companyTypes" :key="legalForm.id" :value="legalForm" v-slot="{ active, selected }">
                                            <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                <div class="flex">
                                                    <span :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate']">
                                                        {{ legalForm.name }}
                                                    </span>
                                                </div>
                                                <span :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                    <PropertyIcon name="IconCheck" stroke-width="1.5" v-if="selected" class="h-5 w-5 flex text-success" aria-hidden="true"/>
                                                </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </Listbox>
                            <button v-if="selectedLegalForm !== null" type="button" @click="selectedLegalForm = null" class="ml-2 text-zinc-400 hover:text-rose-600 transition self-center">
                                <PropertyIcon name="IconX" stroke-width="1.5" class="h-5 w-5" aria-hidden="true"/>
                            </button>
                        </div>
                    </div>
                    <div class="col-span-full">
                        <div class="flex relative">
                            <Listbox as="div" class="flex-1 relative" v-model="selectedContractType" id="contractTypeSelect">
                                <ListboxButton v-if="selectedContractType !== null" class="menu-button">
                                    <div class="flex items-center justify-between w-full">
                                        <span class="truncate items-center flex">
                                            <span>{{ selectedContractType.name }}</span>
                                        </span>
                                        <span class="pointer-events-none">
                                            <PropertyIcon name="IconChevronDown" stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                        </span>
                                    </div>
                                </ListboxButton>
                                <ListboxButton v-else class="menu-button">
                                    <div class="flex flex-grow xsLight text-left subpixel-antialiased">
                                        {{ $t('Contract type')}}
                                    </div>
                                    <span class="pointer-events-none">
                                         <PropertyIcon name="IconChevronDown" stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                    </span>
                                </ListboxButton>
                                <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions class="absolute w-full z-10 mt-16 rounded-lg bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" class="max-h-8" v-for="contractType in contractTypes" :key="contractType.id" :value="contractType" v-slot="{ active, selected }">
                                            <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                <div class="flex">
                                                    <span :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate']">
                                                        {{ contractType.name }}
                                                    </span>
                                                </div>
                                                <span :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                    <PropertyIcon name="IconCheck" stroke-width="1.5" v-if="selected" class="h-5 w-5 flex text-success" aria-hidden="true"/>
                                                </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </Listbox>
                            <button v-if="selectedContractType !== null" type="button" @click="selectedContractType = null" class="ml-2 text-zinc-400 hover:text-rose-600 transition self-center">
                                <PropertyIcon name="IconX" stroke-width="1.5" class="h-5 w-5" aria-hidden="true"/>
                            </button>
                        </div>
                    </div>
                    <div class="col-span-full">
                        <div class="flex">
                            <BaseInput
                                type="number"
                                id="amount"
                                v-model="contractForm.amount"
                                :label="$t('Amount')"
                            />
                            <Listbox as="div" class="flex w-28 relative" v-model="selectedCurrency" id="currencySelect">
                                <ListboxButton class="menu-button">
                                    <span>{{ selectedCurrency?.name || '€' }}</span>
                                    <PropertyIcon name="IconChevronDown" stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </ListboxButton>
                                <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions class="absolute w-full z-10 mt-16 rounded-lg bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" class="max-h-8"
                                                       v-for="currency in currencies"
                                                       :key="currency.id"
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
                                                      <PropertyIcon name="IconCheck" stroke-width="1.5" v-if="selected" class="h-5 w-5 flex text-success"
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
                            <input id="kskLiable" type="checkbox" v-model="contractForm.ksk_liable"
                                   class="input-checklist"/>
                            <label for="kskLiable" :class="contractForm.ksk_liable ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                   class="ml-2">
                                {{ $t('KSK-liable')}}
                            </label>
                        </div>
                        <div class="grid grid-cols-1 gap-4 mb-4" v-if="contractForm.ksk_liable">
                            <BaseInput
                                type="number"
                                step="0.01"
                                id="kskAmount"
                                v-model="contractForm.ksk_amount"
                                :label="$t('KSK Amount')"
                            />
                        </div>
                        <div class="mb-4">
                            <BaseTextarea
                                :label="$t('KSK Reason')"
                                id="kskReason"
                                v-model="contractForm.ksk_reason"
                                rows="2"
                            />
                        </div>
                        <div class="ml-4" v-if="contractForm.resident_abroad">
                            <div class="flex items-center mb-2">
                                <input id="hasPowerOfAttorney" type="checkbox" v-model="contractForm.has_power_of_attorney"
                                       class="input-checklist"/>
                                <label for="hasPowerOfAttorney"
                                       :class="contractForm.has_power_of_attorney ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                       class="ml-2">
                                    {{ $t('Power of attorney is available')}}
                                </label>
                            </div>
                            <div class="flex items-center mb-2">
                                <input id="isFreed" type="checkbox" v-model="contractForm.is_freed"
                                       class="input-checklist"/>
                                <label for="isFreed" :class="contractForm.is_freed ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                       class="ml-2">
                                    {{ $t('Liberated at home')}}
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center mb-2">
                            <input id="foreignTax" type="checkbox" v-model="contractForm.foreign_tax"
                                   class="input-checklist"/>
                            <label for="foreignTax" :class="contractForm.foreign_tax ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                   class="ml-2">
                                {{ $t('Foreign tax')}}
                            </label>
                        </div>
                        <div class="grid grid-cols-1 gap-4 mb-4" v-if="contractForm.foreign_tax">
                            <BaseInput
                                type="number"
                                step="0.01"
                                id="foreignTaxAmount"
                                v-model="contractForm.foreign_tax_amount"
                                :label="$t('Foreign tax amount')"
                            />
                        </div>
                        <div class="mb-4">
                            <BaseTextarea
                                :label="$t('Foreign tax reason')"
                                id="foreignTaxReason"
                                v-model="contractForm.foreign_tax_reason"
                                rows="2"
                            />
                        </div>
                    </div>
                    <div class="">
                        <BaseInput
                            type="number"
                            step="0.01"
                            id="reverseChargeAmount"
                            v-model="contractForm.reverse_charge_amount"
                            :label="$t('Reverse Charge Amount')"
                        />
                    </div>
                    <div class="">
                        <BaseInput
                            type="date"
                            id="deadlineDate"
                            v-model="contractForm.deadline_date"
                            :label="$t('Deadline date')"
                        />
                    </div>
                    <div class="col-span-full">
                        <BaseTextarea
                            :label="$t('Comment / Note')"
                            id="description"
                            v-model="description"
                            rows="5"
                        />
                    </div>

                    <div class="-mx-5 bg-lightBackgroundGray px-5 py-5 col-span-full border-b border-dashed border-gray-300">
                        <div class="relative w-full">
                            <UserSearch v-model="user_query" @userSelected="addUserToContractUserArray" :label="$t('Document access for')"/>
                        </div>
                        <div v-if="usersWithAccess.length > 0" class="mt-2 mb-4 flex items-center flex-wrap">
                            <div v-for="(user,index) in usersWithAccess" class="flex mr-5 mb-2 rounded-full items-center font-bold text-primary">
                                <div class="flex items-center">
                                    <img class="flex h-11 w-11 rounded-full object-cover" :src="user.profile_photo_url" alt=""/>
                                    <span class="flex ml-4 sDark">
                                        {{ user.first_name }} {{ user.last_name }}
                                    </span>
                                    <button type="button" @click="deleteUserFromContractUserArray(index)">
                                        <span class="sr-only">{{ $t('Remove user from contract')}}</span>
                                        <PropertyIcon name="IconX" stroke-width="1.5" class="ml-2 h-4 w-4 p-0.5 hover:text-error rounded-full text-primary border-0 "/>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Department Search -->
                        <div class="relative w-full mt-4">
                            <BaseInput
                                id="departmentSearch"
                                v-model="department_query"
                                :label="$t('Or select teams, who should get access')"
                                class="w-full"
                                @focus="department_query = ''"
                            />
                            <div v-if="department_search_results.length > 0" class="absolute rounded-lg z-30 w-full max-h-60 bg-artwork-navigation-background shadow-lg text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                                <div class="border-gray-200">
                                    <div v-for="(department, index) in department_search_results" :key="index" class="flex items-center cursor-pointer">
                                        <div class="flex-1 text-sm py-4" @click="addDepartmentToContractArray(department)">
                                            <p class="font-bold px-4 flex text-white items-center hover:border-l-4 hover:border-l-success">
                                                <TeamIconCollection :iconName="department.svg_name" class="rounded-full h-8 w-8 object-cover"/>
                                                <span class="ml-2 truncate">{{ department.name }}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="departmentsWithAccess.length > 0" class="mt-2 mb-4 flex items-center flex-wrap">
                            <div v-for="(department,index) in departmentsWithAccess" class="flex mr-5 mb-2 rounded-full items-center font-bold text-primary">
                                <div class="flex items-center">
                                    <TeamIconCollection :iconName="department.svg_name" class="rounded-full h-11 w-11 object-cover"/>
                                    <span class="flex ml-4 sDark">
                                        {{ department.name }}
                                    </span>
                                    <button type="button" @click="deleteDepartmentFromContractArray(index)">
                                        <span class="sr-only">{{ $t('Remove team from contract')}}</span>
                                        <PropertyIcon name="IconX" stroke-width="1.5" class="ml-2 h-4 w-4 p-0.5 hover:text-error rounded-full text-primary border-0 "/>
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
                            <PropertyIcon name="IconChevronUp" stroke-width="1.5" v-if="showExtraSettings" class=" ml-1 mr-3 flex-shrink-0 h-4 w-4" />
                            <PropertyIcon name="IconChevronDown" stroke-width="1.5" v-else class=" ml-1 mr-3 flex-shrink-0h-4 w-4" />
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
                            <div class="flex justify-between mt-5">
                                <BaseUIButton v-if="!creatingNewTask" type="button"
                                        @click="[creatingNewTask = !creatingNewTask]" is-add-button
                                        :label="tasks.length === 0 ? $t('New task') : $t('Further task')"
                                />

                                <BaseUIButton
                                    :label="$t('Save task in contract')"
                                    is-add-button
                                    v-if="creatingNewTask"
                                    @click="$refs.task_form.saveTask(); this.errorText === null ? creatingNewTask = false : null" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="justify-end flex w-full my-6">
                    <BaseUIButton
                        :label="$t('Upload contract')"
                        is-add-button
                        :disabled="file === null || contractForm.contract_partner === ''"
                        @click="storeContract" />
                </div>
            </div>
        </ArtworkBaseModal>
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
import ProjectSearch from "@/Components/SearchBars/ProjectSearch.vue";
import MultiAlertComponent from "@/Components/Alerts/MultiAlertComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import LastedProjects from "@/Artwork/LastedProjects.vue";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";

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
        'first_project_calendar_tab_id',
        'documentRequest'
    ],
    components: {
        PropertyIcon,
        BaseUIButton,
        ArtworkBaseModal,
        BaseTextarea,
        BaseInput,
        MultiAlertComponent,
        UserSearch,
        ProjectSearch,
        LastedProjects,
        TeamIconCollection,
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
                if (this.project_query.length === 0) {
                    this.project_search_results = [];
                    return;
                }
                axios.get(this.route('projects.search'), {
                    params: {query: this.project_query}
                }).then(response => {
                    this.project_search_results = response.data
                })
            },
            deep: true
        },
        department_query: {
            handler() {
                if (!this.department_query || this.department_query.length === 0) {
                    this.department_search_results = [];
                    return;
                }
                axios.get('/departments/search', {
                    params: {query: this.department_query}
                }).then(response => {
                    this.department_search_results = response.data
                })
            },
            deep: true
        },
    },
    mounted() {
        // Add requester to usersWithAccess if opening from a document request
        if (this.documentRequest?.requester) {
            this.usersWithAccess.push(this.documentRequest.requester);
        }
    },
    data() {
        return {
            selectedProject: this.documentRequest?.project || null,
            project_query: '',
            project_search_results: [],

            errorText: null,
            creatingNewTask: false,
            tasks: [],
            uploadDocumentFeedback: "",
            file: null,
            description: this.documentRequest?.description || "",
            contractPartner: this.documentRequest?.contract_partner || '',
            selectedLegalForm: this.documentRequest?.company_type_id ? this.companyTypes?.find(ct => ct.id === this.documentRequest.company_type_id) : null,
            selectedContractType: this.documentRequest?.contract_type_id ? this.contractTypes?.find(ct => ct.id === this.documentRequest.contract_type_id) : null,
            selectedCurrency: this.documentRequest?.currency_id ? (this.currencies?.find(c => c.id === this.documentRequest.currency_id) || {id: 1, name: '€'}) : {id: 1, name: '€'},
            user_search_results: [],
            user_query: '',
            usersWithAccess: [],
            department_search_results: [],
            department_query: '',
            departmentsWithAccess: [],
            showExtraSettings: false,
            contractForm: useForm({
                file: null,
                contract_partner: this.documentRequest?.contract_partner || '',
                company_type_id: this.documentRequest?.company_type_id || null,
                contract_type_id: this.documentRequest?.contract_type_id || null,
                amount: this.documentRequest?.amount || '',
                currency_id: this.documentRequest?.currency_id || 1,
                ksk_liable: this.documentRequest?.ksk_liable || false,
                ksk_amount: this.documentRequest?.ksk_amount || null,
                ksk_reason: this.documentRequest?.ksk_reason || '',
                resident_abroad: this.documentRequest?.resident_abroad || false,
                foreign_tax: this.documentRequest?.foreign_tax || false,
                foreign_tax_amount: this.documentRequest?.foreign_tax_amount || null,
                foreign_tax_reason: this.documentRequest?.foreign_tax_reason || '',
                reverse_charge_amount: this.documentRequest?.reverse_charge_amount || null,
                deadline_date: this.documentRequest?.deadline_date || null,
                has_power_of_attorney: this.documentRequest?.has_power_of_attorney || false,
                is_freed: this.documentRequest?.is_freed || false,
                description: this.documentRequest?.description || '',
                accessibleUsers: [],
                accessibleDepartments: [],
                tasks: [],
                document_request_id: this.documentRequest?.id || null,
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

            // Fetch project details to get budget_deadline
            if (project?.id) {
                axios.get(`/projects/${project.id}/basic`)
                    .then(response => {
                        if (response.data?.budget_deadline) {
                            this.contractForm.deadline_date = response.data.budget_deadline;
                        }
                    })
                    .catch(() => {
                        // Ignore errors, deadline can be set manually
                    });
            }
        },
        removeProject() {
            this.selectedProject = null;
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
        addDepartmentToContractArray(department) {
            if (!this.departmentsWithAccess.find(d => d.id === department.id)) {
                this.departmentsWithAccess.push(department);
            }
            this.department_query = '';
            this.department_search_results = [];
        },
        deleteDepartmentFromContractArray(index) {
            this.departmentsWithAccess.splice(index, 1);
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
            this.department_query = '';
            this.departmentsWithAccess = [];
            this.department_search_results = [];
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
            const projectIdToUse = this.projectId || this.selectedProject?.id;
            if (!projectIdToUse) {
                this.uploadDocumentFeedback = this.$t('Please select a project');
                return;
            }

            this.contractForm.file = this.file;
            this.contractForm.company_type_id = this.selectedLegalForm?.id;
            this.contractForm.contract_type_id = this.selectedContractType?.id;
            const userIds = [];
            this.usersWithAccess.forEach((user) => {
                userIds.push(user.id);
            })
            this.contractForm.accessibleUsers = userIds;
            const departmentIds = [];
            this.departmentsWithAccess.forEach((department) => {
                departmentIds.push(department.id);
            })
            this.contractForm.accessibleDepartments = departmentIds;
            this.contractForm.tasks = this.tasks
            // If projectId prop is set or documentRequest is set, we're in a project tab context - stay there after submit
            this.contractForm.redirect_back = !!(this.projectId || this.documentRequest);
            this.contractForm.post(this.route('contracts.store', projectIdToUse), {
                forceFormData: true,
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    this.contractForm.reset()
                    this.closeModal()
                },
            });
        }
    },
}
</script>

<style scoped>

</style>
