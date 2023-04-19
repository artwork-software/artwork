<template>
    <jet-dialog-modal :show="show" @close="closeModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_project_edit.svg" class="-ml-6 -mt-8 mb-4" alt="artwork"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    Vertrags-Upload
                </div>
                <XIcon @click="closeModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="text-secondary text-sm my-6">
                    Lade Dokumente hoch, die ausschließlich das Budget betreffen. Diese können nur User mit
                    entsprechender Berechtigung einsehen.
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
                        border-buttonBlue border-dotted border-2 h-32 bg-colorOfAction p-2 cursor-pointer">
                        <p class="text-buttonBlue font-bold text-center">Dokument zum Upload hierher ziehen
                            <br>oder ins Feld klicken
                        </p>
                    </div>
                    <jet-input-error :message="uploadDocumentFeedback"/>
                </div>
                <div class="mb-2">
                    <div class="group flex" v-if="this.file">{{ this.file.name }}
                        <XCircleIcon
                            @click="this.file = null"
                            class="ml-2 group-hover:cursor-pointer my-auto hidden group-hover:block h-5 w-5 flex-shrink-0 text-error"
                            aria-hidden="true"/>
                    </div>
                </div>
                <div>
                    <div class="py-1">
                        <input type="text"
                               v-model="this.contractPartner"
                               id="eventTitle"
                               placeholder="Vertragspartner*"
                               class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                    </div>
                    <div class="py-1">
                        <Listbox as="div" class="flex h-12" v-model="selectedLegalForm"
                                 id="eventType">
                            <ListboxButton v-if="selectedLegalForm !== null"
                                           class="pl-3 h-12 inputMain w-full bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm flex items-center">
                                <div class="flex items-center my-auto">
                                <span class="block truncate items-center flex">
                                            <span>{{ selectedLegalForm.name }}</span>
                                </span>
                                    <span
                                        class="ml-2 right-0 absolute inset-y-0 flex items-center pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </span>
                                </div>
                            </ListboxButton>
                            <ListboxButton v-else
                                           class="pl-3 h-12 inputMain w-full bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm flex items-center">
                                <div class="flex flex-grow xsLight text-left subpixel-antialiased">
                                    Rechtsform
                                </div>
                                <span
                                    class="ml-2 right-0 absolute inset-y-0 flex items-center pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
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
                                                      <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
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
                                     <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </span>
                                </div>
                            </ListboxButton>
                            <ListboxButton v-else
                                           class="pl-3 h-12 inputMain w-full bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm flex items-center">
                                <div class="flex flex-grow xsLight text-left subpixel-antialiased">
                                    Vertragsart
                                </div>
                                <span
                                    class="ml-2 right-0 absolute inset-y-0 flex items-center pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
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
                                                      <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
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
                               placeholder="Betrag* (Gage, Co-Produktionsbeitrag, etc.)"
                               class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        <Listbox as="div" class="flex h-12 w-24" v-model="selectedCurrency"
                                 id="eventType">
                            <ListboxButton
                                class="pl-1 truncate h-12 inputMain w-full bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm flex items-center">
                                <div class="flex items-center my-auto">
                                <span class="block w-12 truncate items-center ml-3 flex">
                                            <span>{{ selectedCurrency.name }}</span>
                                </span>
                                    <span
                                        class="ml-2 right-0 absolute inset-y-0 flex items-center pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
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
                                                      <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
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
                                KSK-pflichtig
                            </label>
                        </div>

                        <div class="flex items-center mb-2">
                            <input id="hasGroup" type="checkbox" v-model="this.isAbroad"
                                   @click="this.hasPowerOfAttorney = false; this.isFreed = false"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <label for="hasGroup" :class="this.isAbroad ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                   class="ml-2">
                                Im Ausland ansässig
                            </label>
                        </div>
                        <div class="ml-4" v-if="this.isAbroad">
                            <div class="flex items-center mb-2">
                                <input id="hasGroup" type="checkbox" v-model="this.hasPowerOfAttorney"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <label for="hasGroup"
                                       :class="this.hasPowerOfAttorney ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                       class="ml-2">
                                    Vollmacht liegt vor
                                </label>
                            </div>
                            <div class="flex items-center mb-2">
                                <input id="hasGroup" type="checkbox" v-model="this.isFreed"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <label for="hasGroup" :class="this.isFreed ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                       class="ml-2">
                                    Im Heimatland befreit
                                </label>
                            </div>
                        </div>
                    </div>
                    <textarea placeholder="Kommentar / Notiz"
                              id="description"
                              v-model="description"
                              rows="5"
                              class="inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                    <div class="my-1">
                        <div class="relative w-full">
                            <div class="w-full">
                                <input id="userSearch" v-model="user_query" type="text" autocomplete="off"
                                       placeholder="Dokumentzugriff für*"
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
                                            <div class="flex-1 text-sm py-4"  v-if="budgetAccess.includes(user.id)">
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
                                                <span class="sr-only">User aus Vertrag entfernen</span>
                                                <XIcon
                                                    class="ml-2 h-4 w-4 p-0.5 hover:text-error rounded-full bg-buttonBlue text-white border-0 "/>
                                            </button>
                                        </div>

                                        </span>
                        </div>
                    </div>
                </div>
                <div class="bg-backgroundGray -mx-12 pt-6 pb-12 mt-6">
                    <div class="px-12 w-full">
                        <div class="xxsDarkBold flex items-center cursor-pointer"
                             @click="showExtraSettings = !showExtraSettings">
                            Weitere Angaben oder Aufgabe hinzufügen
                            <ChevronUpIcon v-if="showExtraSettings"
                                           class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></ChevronUpIcon>
                            <ChevronDownIcon v-else class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></ChevronDownIcon>
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

                            <ContractTaskForm :show="creatingNewTask" :users="usersWithAccess" ref="task_form"
                                              @add-task="addTask" @showError="showError"/>
                            <div class="flex justify-between">
                                <button v-if="!creatingNewTask" type="button"
                                        @click="[creatingNewTask = !creatingNewTask]"
                                        class="flex py-3 px-8 mt-1 items-center border border-2 mt-6 border-buttonBlue bg-backgroundGray hover:bg-gray-200 rounded-full shadow-sm text-buttonBlue hover:shadow-blueButton focus:outline-none">
                                    <PlusCircleIcon class="h-6 w-6 mr-2" aria-hidden="true"/>
                                    <p class="text-sm">{{ tasks.length === 0 ? 'Neue Aufgabe' : 'Weitere Aufgabe' }}</p>
                                </button>

                                <button class="flex text-sm py-3 px-8 mt-1 items-center border border-2 mt-6 border-success bg-backgroundGray hover:bg-green-50 rounded-full shadow-sm text-success hover:shadow-blueButton focus:outline-none" v-if="creatingNewTask" @click="$refs.task_form.saveTask(); this.errorText === null ? creatingNewTask = false : null">
                                    Aufgabe im Vertrag speichern
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="justify-center flex w-full my-6">
                    <button class="flex p-2 px-8 mt-1 items-center border border-transparent rounded-full shadow-sm  focus:outline-none" :class="(this.file === null || this.contractAmount === '' || this.contractPartner === '')? 'bg-secondary text-white' : 'text-white bg-buttonBlue hover:shadow-blueButton hover:bg-buttonHover'" :disabled="this.file === null || this.contractAmount === '' || this.contractPartner === ''"
                               @click="storeContract">Vertrag hochladen</button>
                </div>
                </div>

        </template>

    </jet-dialog-modal>
</template>

<script>
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import JetInputError from '@/Jetstream/DialogModal.vue'
import AddButton from "@/Layouts/Components/AddButton";
import {PlusCircleIcon, XIcon} from "@heroicons/vue/outline";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";
import {CheckIcon, ChevronDownIcon, ChevronUpIcon, XCircleIcon} from "@heroicons/vue/solid";
import {useForm} from "@inertiajs/inertia-vue3";
import ContractTaskForm from "@/Layouts/Components/ContractTaskForm.vue";
import Button from "@/Jetstream/Button.vue";

export default {
    name: "ContractUploadModal",
    props: {
        show: Boolean,
        closeModal: Function,
        projectId: Number,
        extraSettings: Array,
        budgetAccess: Array
    },
    components: {
        Button,
        ContractTaskForm,
        JetDialogModal,
        JetInputError,
        AddButton,
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
    data() {
        return {
            contractTypes: [],
            companyTypes: [],
            currencies: [],
            errorText: null,
            creatingNewTask: false,
            tasks: [],
            uploadDocumentFeedback: "",
            file: null,
            description: "",
            contractPartner: '',
            selectedLegalForm: null,
            selectedContractType: null,
            selectedCurrency: {id:1, name: '€'},
            user_search_results: [],
            user_query: '',
            usersWithAccess: [],
            kskLiable: false,
            isAbroad: false,
            hasPowerOfAttorney: false,
            isFreed: false,
            showExtraSettings: false,
            contractAmount: '',
            contractForm: useForm({
                file: null,
                contract_partner: this.contractPartner,
                company_type_id: this.selectedLegalForm?.id,
                contract_type_id: this.selectedContractType?.id,
                amount: this.contractAmount,
                currency_id: this.selectedCurrency?.id,
                ksk_liable: this.kskLiable,
                resident_abroad: this.isAbroad,
                has_power_of_attorney: this.hasPowerOfAttorney,
                is_freed: this.isFreed,
                description: this.description,
                accessibleUsers: this.usersWithAccess,
                tasks: this.tasks
            }),
        }
    },
    mounted() {
        axios.get(route('contract_types.index')).then(res => {
            this.contractTypes = res.data
        })
        axios.get(route('company_types.index')).then(res => {
            this.companyTypes = res.data
        })
        axios.get(route('currencies.index')).then(res => {
            this.currencies = res.data
        })
    },
    methods: {
        showError(){
          this.errorText = 'Du musst die Aufgabe einer Person mit Dokumentenzugriff zuweisen'
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
                    this.uploadDocumentFeedback = "Videos, .exe und .dmg Dateien werden nicht unterstützt"
                } else {
                    this.file = file
                }
            }
        },
        clearTaskForm() {
            this.$refs.task_form.clearForm();
        },
        resetValues() {
            this.contractForm.file = null;
            this.contractForm.contract_partner = null;
            this.contractForm.company_type_id = null;
            this.contractForm.contract_type_id = null;
            this.contractForm.amount = '';
            this.contractForm.ksk_liable = false;
            this.contractForm.resident_abroad = false;
            this.contractForm.has_power_of_attorney = false;
            this.contractForm.is_freed = false;
            this.contractForm.description = '';
            this.contractForm.currency_id = 1;
            this.contractForm.accessibleUsers = [];
            this.contractForm.tasks = [];
            this.file = null;
            this.description = "";
            this.contractPartner = '';
            this.selectedLegalForm = null;
            this.selectedContractType = null;
            this.selectedCurrency = {id:1, name: '€'};
            this.user_search_results = [];
            this.user_query = '';
            this.usersWithAccess = [];
            this.kskLiable = false;
            this.isAbroad = false;
            this.hasPowerOfAttorney = false;
            this.isFreed = false;
            this.tasks = [];
            this.contractAmount = '';
        },
        storeContract() {
            this.contractForm.file = this.file;
            this.contractForm.contract_partner = this.contractPartner;
            this.contractForm.company_type_id = this.selectedLegalForm?.id;
            this.contractForm.contract_type_id = this.selectedContractType?.id;
            this.contractForm.amount = this.contractAmount;
            this.contractForm.ksk_liable = this.kskLiable;
            this.contractForm.resident_abroad = this.isAbroad;
            this.contractForm.has_power_of_attorney = this.hasPowerOfAttorney;
            this.contractForm.is_freed = this.isFreed;
            this.contractForm.description = this.description;
            this.contractForm.currency_id = this.selectedCurrency.id;
            const userIds = [];
            this.usersWithAccess.forEach((user) => {
                userIds.push(user.id);
            })
            this.contractForm.accessibleUsers = userIds;
            this.contractForm.tasks = this.tasks
            this.contractForm.post(this.route('contracts.store', this.projectId));
            this.resetValues();
            this.closeModal()
        }
    }
}
</script>

<style scoped>

</style>
