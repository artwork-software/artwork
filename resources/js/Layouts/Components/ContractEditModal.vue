<template>
    <ArtworkBaseModal
        v-if="show"
        title="Edit contract"
        description="Upload documents that relate exclusively to the budget. These can only be viewed by users with the appropriate authorization."
        modal-size="sm:max-w-3xl"
        @close="closeModal"
    >
        <div class="space-y-5">
            <div class="space-y-3">
                <input
                    id="contractEditFileInput"
                    ref="module_files"
                    type="file"
                    class="sr-only"
                    @change="upload"
                />
                <div
                    role="button"
                    tabindex="0"
                    class="flex min-h-28 w-full cursor-pointer flex-col items-center justify-center gap-y-2 rounded-lg border-2 border-dashed p-4 text-center transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-600"
                    :class="isDragging ? 'border-accent-600 bg-accent-50' : 'border-border'"
                    @click="selectNewFiles"
                    @keydown.enter.prevent="selectNewFiles"
                    @keydown.space.prevent="selectNewFiles"
                    @dragenter.prevent="isDragging = true"
                    @dragover.prevent="isDragging = true"
                    @dragleave="isDragging = $event.currentTarget.contains($event.relatedTarget)"
                    @drop.stop.prevent="uploadDraggedDocuments($event)"
                >
                    <PropertyIcon name="IconUpload" class="size-6 text-text-subtle" stroke-width="1.5" aria-hidden="true"/>
                    <p class="text-sm text-text-muted">
                        <template v-if="isDragging">{{ $t('Drop here') }}</template>
                        <template v-else>
                            <span class="font-semibold text-accent-600">{{ $t('Choose file') }}</span>
                            {{ $t('or drag and drop') }}
                        </template>
                    </p>
                </div>
                <div
                    v-if="fileDisplayName"
                    class="flex items-center justify-between gap-x-3 rounded-lg border border-border-subtle px-3 py-2 text-sm text-text"
                >
                    <div class="flex min-w-0 items-center gap-x-2">
                        <PropertyIcon name="IconFile" class="size-4 shrink-0 text-text-subtle" stroke-width="1.5" aria-hidden="true"/>
                        <span class="truncate">{{ fileDisplayName }}</span>
                    </div>
                    <button
                        type="button"
                        class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-text-subtle transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover hover:text-danger"
                        :aria-label="$t('Remove file')"
                        @click="file = null"
                    >
                        <PropertyIcon name="IconX" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                    </button>
                </div>
                <BaseAlertComponent
                    v-if="uploadDocumentFeedback"
                    type="error"
                    class="mb-0!"
                    :message="uploadDocumentFeedback"
                />
                <MultiAlertComponent
                    v-if="Object.keys(contractForm.errors).length > 0"
                    :errors="contractForm.errors"
                    :error-count="Object.keys(contractForm.errors).length"
                />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <!-- Projektzuordnung -->
                <div class="col-span-full">
                    <p class="mb-1 font-lexend text-xs font-medium text-text-muted">{{ $t('Project assignment') }}</p>
                    <div
                        v-if="selectedProject"
                        class="flex items-center gap-2 rounded-md border border-border-subtle bg-surface-sunken px-3 py-2"
                    >
                        <span class="text-sm text-text-muted">{{ $t('Connected project:') }}</span>
                        <a
                            v-if="selectedProject?.id && first_project_calendar_tab_id"
                            :href="route('projects.tab', {project: selectedProject.id, projectTab: first_project_calendar_tab_id})"
                            class="truncate text-sm font-medium text-accent-600 hover:underline"
                        >
                            {{ selectedProject?.name }}
                        </a>
                        <span v-else class="truncate text-sm font-medium text-text">{{ selectedProject?.name }}</span>
                        <button
                            type="button"
                            class="ml-auto inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-text-subtle transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover hover:text-danger"
                            :aria-label="$t('Remove project')"
                            @click="removeProject"
                        >
                            <PropertyIcon name="IconX" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                        </button>
                    </div>
                    <template v-else>
                        <ProjectSearch
                            label="Search for projects"
                            @project-selected="selectProject"
                        />
                        <LastedProjects
                            :limit="10"
                            @select="selectProject"
                        />
                    </template>
                </div>

                <BaseInput
                    id="contractEditPartner"
                    v-model="contractPartner"
                    label="Contract partner"
                    required
                />
                <div class="flex items-end gap-x-2">
                    <ArtworkBaseListbox
                        v-model="selectedLegalForm"
                        class="min-w-0 flex-1"
                        :items="companyTypes ?? []"
                        by="id"
                        option-label="name"
                        label="Legal form"
                    />
                    <button
                        v-if="selectedLegalForm !== null"
                        type="button"
                        class="inline-flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-md text-text-subtle transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover hover:text-danger"
                        :aria-label="$t('Clear selection')"
                        @click="selectedLegalForm = null"
                    >
                        <PropertyIcon name="IconX" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                    </button>
                </div>
                <div class="col-span-full flex items-end gap-x-2">
                    <ArtworkBaseListbox
                        v-model="selectedContractType"
                        class="min-w-0 flex-1"
                        :items="contractTypes ?? []"
                        by="id"
                        option-label="name"
                        label="Contract type"
                    />
                    <button
                        v-if="selectedContractType !== null"
                        type="button"
                        class="inline-flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-md text-text-subtle transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover hover:text-danger"
                        :aria-label="$t('Clear selection')"
                        @click="selectedContractType = null"
                    >
                        <PropertyIcon name="IconX" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                    </button>
                </div>
                <div class="col-span-full flex items-end gap-x-2">
                    <BaseInput
                        id="contractEditAmount"
                        v-model="contractAmount"
                        class="min-w-0 flex-1"
                        type="number"
                        label="Amount (fee, co-production contribution, etc.)"
                    />
                    <ArtworkBaseListbox
                        v-model="selectedCurrency"
                        class="w-28 shrink-0"
                        :items="currencies ?? []"
                        by="id"
                        option-label="name"
                        label="Currency"
                        :enable-search="false"
                    />
                </div>

                <div class="col-span-full space-y-3">
                    <BaseCheckbox id="contractEditKskLiable" v-model="kskLiable" :label="$t('KSK-liable')"/>
                    <BaseInput
                        v-if="kskLiable"
                        id="contractEditKskAmount"
                        v-model="kskAmount"
                        type="number"
                        step="0.01"
                        label="KSK Amount"
                    />
                    <BaseTextarea
                        v-else
                        id="contractEditKskReason"
                        v-model="kskReason"
                        label="KSK Reason"
                        :rows="2"
                    />
                    <div v-if="isAbroad" class="space-y-2 pl-4">
                        <BaseCheckbox
                            id="contractEditPowerOfAttorney"
                            v-model="hasPowerOfAttorney"
                            :label="$t('Power of attorney is available')"
                        />
                        <BaseCheckbox id="contractEditIsFreed" v-model="isFreed" :label="$t('Liberated at home')"/>
                    </div>
                    <BaseCheckbox id="contractEditForeignTax" v-model="foreignTax" :label="$t('Foreign tax')"/>
                    <div v-if="foreignTax" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <BaseInput
                            id="contractEditForeignTaxAmount"
                            v-model="foreignTaxAmount"
                            type="number"
                            step="0.01"
                            label="Foreign tax amount"
                        />
                        <div class="hidden md:block"></div>
                        <BaseInput id="contractEditForeignTaxCity" v-model="foreignTaxCity" label="City"/>
                        <BaseInput id="contractEditForeignTaxCountry" v-model="foreignTaxCountry" label="Country"/>
                    </div>
                    <BaseTextarea
                        v-else
                        id="contractEditForeignTaxReason"
                        v-model="foreignTaxReason"
                        label="Foreign tax reason"
                        :rows="2"
                    />
                </div>

                <BaseInput
                    id="contractEditReverseChargeAmount"
                    v-model="reverseChargeAmount"
                    type="number"
                    step="0.01"
                    label="Reverse Charge Amount"
                />
                <BaseInput
                    id="contractEditDeadlineDate"
                    v-model="deadlineDate"
                    type="date"
                    label="Deadline date"
                />
                <BaseTextarea
                    id="contractEditDescription"
                    v-model="description"
                    class="col-span-full"
                    label="Comment / Note"
                    :rows="5"
                />
                <BaseInput
                    id="contractEditState"
                    v-model="contractState"
                    label="Contract status"
                />
                <BaseTextarea
                    id="contractEditStateComment"
                    v-model="contractStateComment"
                    class="col-span-full"
                    label="Contract status comment"
                    :rows="3"
                />

                <!-- Dokumentzugriff -->
                <div class="col-span-full space-y-4 rounded-lg border border-border-subtle bg-surface-sunken p-4">
                    <div>
                        <UserSearch label="Document access for" @user-selected="addUserToContractUserArray"/>
                        <ul v-if="usersWithAccess.length > 0" class="mt-2 flex flex-wrap gap-2">
                            <li
                                v-for="(user, index) in usersWithAccess"
                                :key="'user-' + user.id"
                                class="inline-flex items-center gap-x-2 rounded-md border border-border-subtle bg-surface py-1 pl-1 pr-1 text-sm text-text"
                            >
                                <img class="size-6 shrink-0 rounded-full object-cover" :src="user?.profile_photo_url" alt=""/>
                                <span class="truncate font-medium">{{ user.first_name }} {{ user.last_name }}</span>
                                <button
                                    type="button"
                                    class="inline-flex size-6 shrink-0 cursor-pointer items-center justify-center rounded text-text-subtle transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover hover:text-danger"
                                    :aria-label="$t('Remove user from contract')"
                                    @click="deleteUserFromContractUserArray(index)"
                                >
                                    <PropertyIcon name="IconX" class="size-3.5" stroke-width="1.5" aria-hidden="true"/>
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <BaseInput
                            id="contractEditDepartmentSearch"
                            v-model="department_query"
                            label="Or select teams, who should get access"
                            :placeholder="$t('Search for teams')"
                            autocomplete="off"
                        />
                        <ul
                            v-if="department_search_results.length > 0"
                            class="mt-2 max-h-60 divide-y divide-border-subtle overflow-y-auto rounded-lg border border-border bg-surface"
                        >
                            <li v-for="department in department_search_results" :key="department.id">
                                <button
                                    type="button"
                                    class="flex w-full cursor-pointer items-center gap-x-2 px-3 py-2 text-left text-sm font-medium text-text transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover focus-visible:bg-surface-hover"
                                    @click="addDepartmentToContractArray(department)"
                                >
                                    <TeamIconCollection :iconName="department.svg_name" class="size-6 shrink-0 rounded-full"/>
                                    <span class="truncate">{{ department.name }}</span>
                                </button>
                            </li>
                        </ul>
                        <ul v-if="departmentsWithAccess.length > 0" class="mt-2 flex flex-wrap gap-2">
                            <li
                                v-for="(department, index) in departmentsWithAccess"
                                :key="'department-' + department.id"
                                class="inline-flex items-center gap-x-2 rounded-md border border-border-subtle bg-surface py-1 pl-1 pr-1 text-sm text-text"
                            >
                                <TeamIconCollection :iconName="department.svg_name" class="size-6 shrink-0 rounded-full"/>
                                <span class="truncate font-medium">{{ department.name }}</span>
                                <button
                                    type="button"
                                    class="inline-flex size-6 shrink-0 cursor-pointer items-center justify-center rounded text-text-subtle transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover hover:text-danger"
                                    :aria-label="$t('Remove team from contract')"
                                    @click="deleteDepartmentFromContractArray(index)"
                                >
                                    <PropertyIcon name="IconX" class="size-3.5" stroke-width="1.5" aria-hidden="true"/>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Weitere Angaben und Aufgaben -->
            <div>
                <button
                    type="button"
                    class="inline-flex cursor-pointer items-center gap-x-1 rounded-md text-xs font-semibold text-text transition-colors duration-150 motion-reduce:transition-none hover:text-accent-600"
                    :aria-expanded="showExtraSettings"
                    @click="showExtraSettings = !showExtraSettings"
                >
                    {{ $t('Add further details or task') }}
                    <PropertyIcon
                        :name="showExtraSettings ? 'IconChevronUp' : 'IconChevronDown'"
                        class="size-4 shrink-0"
                        stroke-width="1.5"
                        aria-hidden="true"
                    />
                </button>
                <div v-if="showExtraSettings" class="mt-3 space-y-4">
                    <div v-if="tasks.length > 0" class="space-y-2">
                        <BaseCheckbox
                            v-for="(task, index) in tasks"
                            :id="'contractEditTask-' + index"
                            :key="task.id ?? 'new-' + index"
                            v-model="task.done"
                            :label="task.name"
                        />
                    </div>
                    <ContractTaskForm
                        ref="task_form"
                        :show="creatingNewTask"
                        :users="usersWithAccess"
                        @add-task="addTask"
                        @show-error="showError"
                    />
                    <BaseUIButton
                        v-if="!creatingNewTask"
                        variant="secondary"
                        size="sm"
                        icon="IconCirclePlus"
                        :label="tasks.length === 0 ? 'New task' : 'Further task'"
                        @click="creatingNewTask = true"
                    />
                    <BaseUIButton
                        v-else
                        variant="secondary"
                        size="sm"
                        icon="IconDeviceFloppy"
                        label="Save task in contract"
                        @click="saveTaskInContract"
                    />
                </div>
            </div>

            <ul v-if="comments && comments.length > 0" class="divide-y divide-border-subtle rounded-lg border border-border-subtle">
                <li v-for="(commentItem, index) in comments" :key="commentItem.id ?? index" class="px-3 py-2">
                    <div class="flex items-center gap-x-2">
                        <img :src="commentItem.user?.profile_photo_url" alt="" class="size-5 rounded-full object-cover"/>
                        <span class="text-xs text-text-muted">{{ commentItem.created_at }}</span>
                    </div>
                    <p class="mt-1 whitespace-pre-line text-sm text-text">{{ commentItem.text }}</p>
                </li>
            </ul>
        </div>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="contractForm.processing"
                @click="closeModal"
            />
            <BaseUIButton
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="contractForm.processing"
                :disabled="!canSubmit"
                @click="updateContract"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script>
import {useForm} from "@inertiajs/vue3";
import ContractTaskForm from "@/Layouts/Components/ContractTaskForm.vue";
import Permissions from "@/Mixins/Permissions.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import MultiAlertComponent from "@/Components/Alerts/MultiAlertComponent.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import ProjectSearch from "@/Components/SearchBars/ProjectSearch.vue";
import LastedProjects from "@/Artwork/LastedProjects.vue";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import {sharedDepartmentsOf, sharedUsersOf} from "@/Helper/sharedAccess.js";
import {normalizeContractForEdit} from "@/Helper/contractEditForm.js";

export default {
    name: "ContractEditModal",
    mixins: [Permissions],
    emits: ['closeModal'],
    props: {
        show: Boolean,
        contract: Object,
        currencies: Array,
        companyTypes: Array,
        contractTypes: Array,
        budgetAccess: Array,
    },
    components: {
        PropertyIcon,
        BaseUIButton,
        ArtworkBaseModal,
        ArtworkBaseListbox,
        BaseTextarea,
        BaseInput,
        BaseCheckbox,
        BaseAlertComponent,
        MultiAlertComponent,
        UserSearch,
        ProjectSearch,
        LastedProjects,
        TeamIconCollection,
        ContractTaskForm,
    },
    watch: {
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
        },
    },
    mounted() {
        // Kopien, damit das Abhaken nicht die Vertragsdaten der Liste verändert
        this.contract?.tasks?.forEach(task => {
            this.tasks.push({...task, done: Boolean(task.done)})
        })
    },
    data() {
        // Budget-Informationen übergeben das rohe Vertragsmodell, die Listen aufbereitete Arrays: vereinheitlichen,
        // sonst sendet das Speichern leere Werte für Felder, die nur anders heißen (contract_partner, project_id …)
        // Typ, Rechtsform und Währung notfalls über *_id aus den Auswahllisten auflösen (nie still auf € fallen);
        // deadline_date kommt als Kalenderdatum (Y-m-d) für das Datumsfeld
        const source = normalizeContractForEdit(this.contract, null, {
            currencies: this.currencies,
            companyTypes: this.companyTypes,
            contractTypes: this.contractTypes,
        }) ?? {};
        const formattedDeadlineDate = source?.deadline_date ?? null;

        return {
            selectedProject: source?.project || null,
            first_project_calendar_tab_id: source?.project?.calendar_tab_id || null,
            errorText: null,
            creatingNewTask: false,
            tasks: [],
            uploadDocumentFeedback: "",
            isDragging: false,
            // Dateiname des bestehenden Vertrags oder neu gewählte Datei
            file: source?.name ?? null,
            description: source?.description || "",
            contractPartner: source?.partner || '',
            selectedLegalForm: source?.company_type || null,
            selectedContractType: source?.contract_type || null,
            selectedCurrency: source?.currency || {id: 1, name: '€'},
            // accessibleUsers (aufbereitet) oder accessing_users (Modell aus den Budget-Informationen)
            usersWithAccess: sharedUsersOf(this.contract),
            department_search_results: [],
            department_query: '',
            departmentsWithAccess: sharedDepartmentsOf(this.contract),
            showExtraSettings: false,
            contractAmount: source?.amount ?? '',
            kskLiable: source?.ksk_liable || false,
            kskAmount: source?.ksk_amount ?? null,
            kskReason: source?.ksk_reason || '',
            isAbroad: source?.resident_abroad || false,
            foreignTax: source?.foreign_tax || false,
            foreignTaxAmount: source?.foreign_tax_amount ?? null,
            foreignTaxCity: source?.foreign_tax_city || '',
            foreignTaxCountry: source?.foreign_tax_country || '',
            foreignTaxReason: source?.foreign_tax_reason || '',
            contractState: source?.contract_state || '',
            contractStateComment: source?.contract_state_comment || '',
            reverseChargeAmount: source?.reverse_charge_amount ?? null,
            deadlineDate: formattedDeadlineDate,
            hasPowerOfAttorney: source?.has_power_of_attorney || false,
            isFreed: source?.is_freed || false,
            comment: null,
            comments: source?.comments || [],
            contractForm: useForm({
                file: null,
                contract_partner: source?.partner || '',
                company_type_id: source?.company_type?.id || null,
                contract_type_id: source?.contract_type?.id || null,
                amount: source?.amount ?? '',
                currency_id: source?.currency?.id || 1,
                project_id: source?.project?.id || null,
                ksk_liable: source?.ksk_liable || false,
                ksk_amount: source?.ksk_amount ?? null,
                ksk_reason: source?.ksk_reason || '',
                resident_abroad: source?.resident_abroad || false,
                foreign_tax: source?.foreign_tax || false,
                foreign_tax_amount: source?.foreign_tax_amount ?? null,
                foreign_tax_city: source?.foreign_tax_city || '',
                foreign_tax_country: source?.foreign_tax_country || '',
                foreign_tax_reason: source?.foreign_tax_reason || '',
                contract_state: source?.contract_state || '',
                contract_state_comment: source?.contract_state_comment || '',
                reverse_charge_amount: source?.reverse_charge_amount ?? null,
                deadline_date: source?.deadline_date || null,
                has_power_of_attorney: source?.has_power_of_attorney || false,
                is_freed: source?.is_freed || false,
                description: source?.description || '',
                accessibleUsers: [],
                accessibleDepartments: [],
                tasks: [],
                comment: null,
            }),
        }
    },
    computed: {
        fileDisplayName() {
            if (!this.file) {
                return '';
            }
            return typeof this.file === 'string' ? this.file : (this.file.name || this.contract?.name || '');
        },
        canSubmit() {
            return this.file !== null && String(this.contractPartner ?? '').trim() !== '' && !this.contractForm.processing;
        },
    },
    methods: {
        showError() {
            this.errorText = this.$t('You must assign the task to a person with document access')
        },
        saveTaskInContract() {
            // saveTask meldet synchron entweder add-task oder show-error
            this.$refs.task_form?.saveTask();
            if (this.errorText === null) {
                this.creatingNewTask = false;
            }
        },
        selectProject(project) {
            this.selectedProject = project;
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
        uploadDraggedDocuments(event) {
            this.isDragging = false;
            this.validateType([...event.dataTransfer.files])
        },
        upload(event) {
            this.validateType([...event.target.files])
            // Gleiche Datei erneut wählbar machen
            event.target.value = ''
        },
        validateType(files) {
            this.uploadDocumentFeedback = "";
            if (files.length > 0) {
                this.file = files[0];
            }
        },
        closeModal(){
            if (this.contractForm.processing) {
                return;
            }
            this.$emit('closeModal', true)
        },
        updateContract() {
            if (!this.canSubmit) {
                return;
            }
            this.contractForm.file = this.file;
            this.contractForm.contract_partner = this.contractPartner;
            this.contractForm.company_type_id = this.selectedLegalForm?.id;
            this.contractForm.contract_type_id = this.selectedContractType?.id;
            this.contractForm.amount = this.contractAmount;
            this.contractForm.currency_id = this.selectedCurrency?.id;
            this.contractForm.ksk_liable = this.kskLiable;
            this.contractForm.ksk_amount = this.kskAmount;
            this.contractForm.ksk_reason = this.kskReason;
            this.contractForm.resident_abroad = this.isAbroad;
            this.contractForm.foreign_tax = this.foreignTax;
            this.contractForm.foreign_tax_amount = this.foreignTaxAmount;
            this.contractForm.foreign_tax_city = this.foreignTaxCity;
            this.contractForm.foreign_tax_country = this.foreignTaxCountry;
            this.contractForm.foreign_tax_reason = this.foreignTaxReason;
            this.contractForm.contract_state = this.contractState;
            this.contractForm.contract_state_comment = this.contractStateComment;
            this.contractForm.reverse_charge_amount = this.reverseChargeAmount;
            this.contractForm.deadline_date = this.deadlineDate;
            this.contractForm.has_power_of_attorney = this.hasPowerOfAttorney;
            this.contractForm.is_freed = this.isFreed;
            this.contractForm.description = this.description;
            this.contractForm.comment = this.comment;
            this.contractForm.accessibleUsers = this.usersWithAccess.map((user) => user.id);
            this.contractForm.accessibleDepartments = this.departmentsWithAccess.map((department) => department.id);
            this.contractForm.tasks = this.tasks
            this.contractForm.project_id = this.selectedProject?.id || null;
            this.contractForm.patch(this.route('contracts.update', this.contract.id), {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    this.$emit('closeModal', true)
                },
            });
        }
    },
}
</script>
