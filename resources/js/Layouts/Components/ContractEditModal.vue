<template>
    <BaseModal @closed="closeModal" v-if="show" modal-image="/Svgs/Overlays/illu_project_edit.svg">
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{ $t('Edit contract')}}
                </div>
                <div class="text-secondary text-sm my-6">
                    {{ $t('Upload documents that relate exclusively to the budget. These can only be viewed by users with the appropriate authorization.')}}
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
                <div v-if="this.file !== null" class="mb-6">
                    <div class="group flex"><span v-if="this.file.name">{{ this.file.name }}</span><span
                        v-else>{{ contract.name }}</span>
                        <IconCircleX
                            stroke-width="1.5" @click="this.file = null"
                            class="ml-2 group-hover:cursor-pointer my-auto hidden group-hover:block h-5 w-5 flex-shrink-0 text-error"
                            aria-hidden="true"/>
                    </div>
                </div>
                <div>
                    <div class="py-1">
                        <input type="text"
                               v-model="this.contractPartner"
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
                                    {{$t('Legal form')}}
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
                                <span class="block truncate items-center flex">
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
                                    class="absolute w-[88%] z-10 mt-12 bg-artwork-navigation-background shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                    <ListboxOption as="template" class="max-h-8"
                                                   v-for="contractType in this.contractTypes"
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
                               v-model="this.contractAmount"
                               :placeholder="$t('Amount* (fee, co-production contribution, etc.)')"
                               class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        <Listbox as="div" class="flex h-12 w-24" v-model="selectedCurrency"
                                 id="eventType">
                            <ListboxButton
                                class="pl-1 h-12 inputMain w-full bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm flex items-center">
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
                                        <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                            <div class="flex">
                                            <span
                                                :class="[selected ? 'xsWhiteBold' :  'font-normal', 'ml-4 block truncate']">
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
                            <input id="hasGroup" type="checkbox" v-model="this.kskLiable"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <label for="hasGroup" :class="this.kskLiable ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                   class="ml-2">
                                {{ $t('KSK-liable')}}
                            </label>
                        </div>

                        <div class="flex items-center mb-2">
                            <input id="hasGroup" type="checkbox" v-model="this.isAbroad"
                                   @click="this.hasPowerOfAttorney = false; this.isFreed = false"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <label for="hasGroup" :class="this.isAbroad ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                   class="ml-2">
                                {{ $t('Resident abroad')}}
                            </label>
                        </div>
                        <div class="ml-4" v-if="this.isAbroad">
                            <div class="flex items-center mb-2">
                                <input id="hasGroup" type="checkbox" v-model="this.hasPowerOfAttorney"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <label for="hasGroup"
                                       :class="this.hasPowerOfAttorney ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                       class="ml-2">
                                    {{$t('Power of attorney is available')}}
                                </label>
                            </div>
                            <div class="flex items-center mb-2">
                                <input id="hasGroup" type="checkbox" v-model="this.isFreed"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <label for="hasGroup" :class="this.isFreed ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                       class="ml-2">
                                    {{ $t('Liberated at home')}}
                                </label>
                            </div>
                        </div>
                    </div>
                    <textarea :placeholder="$t('Comment / Note')"
                              id="comment"
                              v-model="comment"
                              rows="5"
                              class="inputMain resize-none xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                    <div class="my-1">
                        <div class="relative w-full">
                            <div class="w-full">
                                <input id="userSearch" v-model="user_query" type="text" autocomplete="off"
                                       :placeholder="$t('Document access for') + '*'"
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
                                                 :src="user?.profile_photo_url"
                                                 alt=""/>
                                            <span class="flex ml-4 sDark">
                                            {{ user.first_name }} {{ user.last_name }}
                                            </span>
                                            <button type="button" @click="deleteUserFromContractUserArray(index)">
                                                <span class="sr-only">{{$t('Remove user from contract')}}</span>
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
                                    <input id="hasGroup" type="checkbox" v-model="task.done"
                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                    <label for="hasGroup"
                                           :class="task.checked ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                           class="ml-2">
                                        {{ task.name }}
                                    </label>
                                </div>
                            </div>

                            <ContractTaskForm :show="creatingNewTask" :users="usersWithAccess" ref="task_form" @add-task="addTask" @showError="showError"/>

                            <div class="flex justify-between">
                                <button v-if="!creatingNewTask" type="button"
                                        @click="[creatingNewTask = !creatingNewTask]"
                                        class="flex py-3 px-3 mt-1 items-center border border-2 mt-6 border-artwork-buttons-create bg-backgroundGray hover:bg-gray-200 rounded-full shadow-sm text-artwork-buttons-create hover:shadow-artwork-buttons-create focus:outline-none">
                                    <IconCirclePlus stroke-width="1.5" class="h-6 w-6 mr-2" aria-hidden="true"/>
                                    <p class="text-sm">{{ tasks.length === 0 ? 'Neue Aufgabe' : 'Weitere Aufgabe' }}</p>
                                </button>

                                <button class="flex text-sm py-3 px-8 mt-1 items-center border border-2 mt-6 border-success bg-backgroundGray hover:bg-green-50 rounded-full shadow-sm text-success hover:shadow-v-if focus:outline-none" v-if="creatingNewTask" @click="$refs.task_form.saveTask(); this.errorText === null ? creatingNewTask = false : null">
                                    {{ $t('Save task in contract')}}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="w-full mb-4 mt-8 -px-12">
                    <div v-for="comment in comments">
                        <div class="flex items-center">
                            <img :src="comment.user?.profile_photo_url" alt="profile_photo"
                                 class="h-5 w-5 mr-2 rounded-2xl"/>
                            <div class="text-secondary text-sm">{{comment.created_at}}</div>
                        </div>
                        <div class="mt-2 mb-4">
                            {{comment.text}}
                        </div>
                    </div>
                </div>

                <div class="justify-center flex w-full my-6">
                    <button class="flex p-2 px-8 mt-1 items-center border border-transparent rounded-full shadow-sm  focus:outline-none" :class="(this.file === null || this.contractAmount === '' || this.contractPartner === '')? 'bg-secondary text-white' : 'text-white bg-artwork-buttons-create hover:shadow-artwork-buttons-create hover:bg-artwork-buttons-hover'" :disabled="this.file === null || this.contractAmount === '' || this.contractPartner === ''"
                            @click="updateContract">{{ $t('Save') }}</button>
                </div>
            </div>
    </BaseModal>
</template>

<script>
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import JetInputError from '@/Jetstream/InputError.vue'
import {PlusCircleIcon, XIcon} from "@heroicons/vue/outline";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";
import {CheckIcon, ChevronDownIcon, ChevronUpIcon, XCircleIcon} from "@heroicons/vue/solid";
import {useForm} from "@inertiajs/vue3";
import ContractTaskForm from "@/Layouts/Components/ContractTaskForm.vue";
import Permissions from "@/Mixins/Permissions.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
export default {
    name: "ContractEditModal",
    mixins: [Permissions, IconLib],
    props: {
        show: Boolean,
        closeModal: Function,
        contract: Object,
        currencies: Array,
        companyTypes: Array,
        contractTypes: Array,
    },
    components: {
        BaseModal,
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
                        this.user_search_results = response.data
                    })
                }
            },
            deep: true
        },
    },
    mounted() {
        this.contract?.tasks?.forEach(task => {
            this.tasks.push(task)
        })
    },
    methods: {
        showError(){
            this.errorText = 'Du musst die Aufgabe einer Person mit Dokumentenzugriff zuweisen'
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
        storeFile() {
            this.$inertia.post(route('contracts.store.file'), {
                file: this.file,
                contract: this.contract.id
            }, {
                preserveState: true,
                preserveScroll: true,

            })
        },
        validateType(file) {
            this.uploadDocumentFeedback = "";
            const forbiddenTypes = [
                "application/vnd.microsoft.portable-executable",
                "application/x-apple-diskimage",
            ]
            if (forbiddenTypes.includes(file[0].type) || file[0].type.match('video.*') || file[0].type === "") {
                this.uploadDocumentFeedback = "Videos, .exe und .dmg Dateien werden nicht unterstützt"
            } else {
                const fileSize = file[0].size;
                if (fileSize > 2097152) {
                    this.uploadDocumentFeedback = "Dateien, welche größer als 2MB sind, können nicht hochgeladen werden."
                } else {
                    this.file = file[0];
                }
            }
        },
        storeFiles() {
            this.storeFile(this.file)
            this.comment = null
            this.closeModal()
        },
        addTask(task) {
            if(this.tasks) {
                this.tasks.push(task)
            }
            else {
                this.tasks = []
                this.tasks.push(task)
            }
        },
        updateContract() {
            this.contractForm.file = this.file;
            this.contractForm.contract_partner = this.contractPartner;
            this.contractForm.company_type_id = this.selectedLegalForm?.id;
            this.contractForm.contract_type_id = this.selectedContractType?.id;
            this.contractForm.amount = this.contractAmount;
            this.contractForm.ksk_liable = this.kskLiable;
            this.contractForm.resident_abroad = this.isAbroad;
            this.contractForm.has_power_of_attorney = this.hasPowerOfAttorney;
            this.contractForm.is_freed = this.isFreed;
            this.contractForm.comment = this.comment;
            this.contractForm.currency_id = this.selectedCurrency.id;
            const userIds = [];
            this.usersWithAccess.forEach((user) => {
                 userIds.push(user.id);
            })

            this.contractForm.accessibleUsers = userIds;
            this.contractForm.tasks = this.tasks
            this.contractForm.patch(this.route('contracts.update', this.contract.id), {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    this.closeModal()
                },
            });

           // this.closeModal()
        },
    },
    data() {
        return {
            creatingNewTask: false,
            uploadDocumentFeedback: "",
            file: this.contract?.basename,
            comment: null,
            comments: this.contract?.comments,
            description: this.contract?.description,
            contractPartner: this.contract?.partner,
            selectedLegalForm: this.contract?.company_type,
            selectedContractType: this.contract?.contract_type,
            selectedCurrency: this.contract?.currency,
            user_search_results: [],
            user_query: '',
            usersWithAccess: this.contract?.accessibleUsers ? this.contract.accessibleUsers : [],
            kskLiable: this.contract?.ksk_liable,
            isAbroad: this.contract?.resident_abroad,
            hasPowerOfAttorney: this.contract?.has_power_of_attorney,
            isFreed: this.contract?.is_freed,
            tasks: [],
            errorText:null,
            showExtraSettings: false,

            contractAmount: this.contract?.amount,
            contractForm: useForm({
                file: this.contract?.basename,
                contract_partner: this.contract?.partner,
                company_type_id: this.contract?.company_type?.id,
                contract_type_id: this.contract?.contract_type?.id,
                currency_id: this.contract?.currency?.id,
                amount: this.contract?.amount,
                ksk_liable: this.contract?.ksk_liable,
                resident_abroad: this.contract?.resident_abroad,
                has_power_of_attorney: this.contract?.has_power_of_attorney,
                is_freed: this.contract?.is_freed,
                description: this.contract?.description,
                accessibleUsers: this.contract ? this.contract.accessibleUsers : [],
                tasks: this.contract?.tasks,
                comment: this.comment
            }),
        }
    }
}
</script>

<style scoped>

</style>
