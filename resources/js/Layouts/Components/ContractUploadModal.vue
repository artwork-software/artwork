<template>
    <ArtworkBaseModal
        v-if="show"
        title="Contract upload"
        description="Upload documents that relate exclusively to the budget. These can only be viewed by users with the appropriate authorization."
        modal-size="sm:max-w-3xl"
        @close="closeModal"
    >
        <div class="space-y-5">
            <div class="space-y-3">
                <input
                    id="contractUploadFileInput"
                    ref="module_files"
                    type="file"
                    class="sr-only"
                    @change="validateType"
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
                    @drop.stop.prevent="validateType($event)"
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
                    v-if="file"
                    class="flex items-center justify-between gap-x-3 rounded-lg border border-border-subtle px-3 py-2 text-sm text-text"
                >
                    <div class="flex min-w-0 items-center gap-x-2">
                        <PropertyIcon name="IconFile" class="size-4 shrink-0 text-text-subtle" stroke-width="1.5" aria-hidden="true"/>
                        <span class="truncate">{{ file.name }}</span>
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
                <!-- Projektzuordnung (nur ohne festes Projekt) -->
                <div v-if="!projectId" class="col-span-full">
                    <p class="mb-1 font-lexend text-xs font-medium text-text-muted">
                        {{ $t('Project assignment') }}
                        <span class="text-danger">*</span>
                    </p>
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

                <div>
                    <BaseInput
                        id="contractUploadPartner"
                        v-model="contractForm.contract_partner"
                        label="Contract partner"
                        required
                    />
                    <!-- CRM-Kontakt verknüpfen -->
                    <div class="mt-2">
                        <div
                            v-if="selectedCrmContact"
                            class="flex items-center gap-2 rounded-md border border-border-subtle bg-surface-sunken px-3 py-2"
                        >
                            <img
                                v-if="selectedCrmContact.profile_photo_url"
                                :src="selectedCrmContact.profile_photo_url"
                                alt=""
                                class="size-6 rounded-full object-cover"
                            />
                            <span class="truncate text-sm text-text">{{ selectedCrmContact.display_name }}</span>
                            <span v-if="selectedCrmContact.contact_type" class="text-xs text-text-muted">({{ selectedCrmContact.contact_type.name }})</span>
                            <a
                                :href="route('crm.contacts.show', selectedCrmContact.id)"
                                class="ml-auto text-xs text-accent-600 hover:text-accent-700 hover:underline"
                            >
                                {{ $t('View in CRM') }}
                            </a>
                            <button
                                type="button"
                                class="inline-flex size-6 shrink-0 cursor-pointer items-center justify-center rounded text-text-subtle transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover hover:text-text"
                                :aria-label="$t('Show CRM details')"
                                :aria-expanded="showCrmDetails"
                                v-tooltip.top="{ value: $t('Show CRM details'), class: 'aw-tooltip' }"
                                @click="toggleCrmDetails"
                            >
                                <PropertyIcon :name="showCrmDetails ? 'IconChevronUp' : 'IconChevronDown'" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                            </button>
                            <button
                                type="button"
                                class="inline-flex size-6 shrink-0 cursor-pointer items-center justify-center rounded text-text-subtle transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover hover:text-danger"
                                :aria-label="$t('Remove link')"
                                @click="removeCrmContact"
                            >
                                <PropertyIcon name="IconX" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                            </button>
                        </div>
                        <button
                            v-else
                            type="button"
                            class="cursor-pointer text-xs text-accent-600 hover:text-accent-700 hover:underline"
                            @click="showCrmSearch = true"
                        >
                            {{ $t('Link CRM contact') }}
                        </button>
                    </div>
                </div>
                <div class="flex items-end gap-x-2 self-start">
                    <ArtworkBaseListbox
                        v-model="selectedLegalForm"
                        class="min-w-0 flex-1"
                        :items="companyTypes ?? []"
                        by="id"
                        option-label="name"
                        label="Legal form"
                    />
                    <button
                        v-if="selectedLegalForm"
                        type="button"
                        class="inline-flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-md text-text-subtle transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover hover:text-danger"
                        :aria-label="$t('Clear selection')"
                        @click="selectedLegalForm = null"
                    >
                        <PropertyIcon name="IconX" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                    </button>
                </div>
                <!-- Aufklappbare CRM-Details (volle Breite) -->
                <div
                    v-if="selectedCrmContact && showCrmDetails"
                    class="col-span-full overflow-hidden rounded-md border border-border-subtle bg-surface-sunken px-3 py-3"
                >
                    <p v-if="loadingCrmDetails" class="py-2 text-center text-sm text-text-muted">
                        {{ $t('Loading...') }}
                    </p>
                    <div v-else-if="crmContactData && crmVisibleGroups.length > 0" class="space-y-3">
                        <CrmPropertyGroupSection
                            v-for="group in crmVisibleGroups"
                            :key="group.id"
                            :group="group"
                            :contact="crmContactData.contact"
                            :editing="false"
                        />
                    </div>
                    <p v-else class="py-2 text-sm text-text-muted">
                        {{ $t('No CRM data available.') }}
                    </p>
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
                        v-if="selectedContractType"
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
                        id="contractUploadAmount"
                        v-model="contractForm.amount"
                        class="min-w-0 flex-1"
                        type="number"
                        label="Amount"
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
                    <BaseCheckbox id="contractUploadKskLiable" v-model="contractForm.ksk_liable" :label="$t('KSK-liable')"/>
                    <BaseInput
                        v-if="contractForm.ksk_liable"
                        id="contractUploadKskAmount"
                        v-model="contractForm.ksk_amount"
                        type="number"
                        step="0.01"
                        label="KSK Amount"
                    />
                    <BaseTextarea
                        v-else
                        id="contractUploadKskReason"
                        v-model="contractForm.ksk_reason"
                        label="KSK Reason"
                        :rows="2"
                    />
                    <div v-if="contractForm.resident_abroad" class="space-y-2 pl-4">
                        <BaseCheckbox
                            id="contractUploadPowerOfAttorney"
                            v-model="contractForm.has_power_of_attorney"
                            :label="$t('Power of attorney is available')"
                        />
                        <BaseCheckbox
                            id="contractUploadIsFreed"
                            v-model="contractForm.is_freed"
                            :label="$t('Liberated at home')"
                        />
                    </div>
                    <BaseCheckbox id="contractUploadForeignTax" v-model="contractForm.foreign_tax" :label="$t('Foreign tax')"/>
                    <div v-if="contractForm.foreign_tax" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <BaseInput
                            id="contractUploadForeignTaxAmount"
                            v-model="contractForm.foreign_tax_amount"
                            type="number"
                            step="0.01"
                            label="Foreign tax amount"
                        />
                        <div class="hidden md:block"></div>
                        <BaseInput id="contractUploadForeignTaxCity" v-model="contractForm.foreign_tax_city" label="City"/>
                        <BaseInput id="contractUploadForeignTaxCountry" v-model="contractForm.foreign_tax_country" label="Country"/>
                    </div>
                    <BaseTextarea
                        v-else
                        id="contractUploadForeignTaxReason"
                        v-model="contractForm.foreign_tax_reason"
                        label="Foreign tax reason"
                        :rows="2"
                    />
                </div>

                <BaseInput
                    id="contractUploadReverseChargeAmount"
                    v-model="contractForm.reverse_charge_amount"
                    type="number"
                    step="0.01"
                    label="Reverse Charge Amount"
                />
                <BaseInput
                    id="contractUploadDeadlineDate"
                    v-model="contractForm.deadline_date"
                    type="date"
                    label="Deadline date"
                />
                <BaseTextarea
                    id="contractUploadDescription"
                    v-model="description"
                    class="col-span-full"
                    label="Comment / Note"
                    :rows="5"
                />
                <BaseInput
                    id="contractUploadState"
                    v-model="contractForm.contract_state"
                    label="Contract status"
                />
                <BaseTextarea
                    id="contractUploadStateComment"
                    v-model="contractForm.contract_state_comment"
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
                                <img class="size-6 shrink-0 rounded-full object-cover" :src="user.profile_photo_url" alt=""/>
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
                            id="contractUploadDepartmentSearch"
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
                    <ul v-if="tasks.length > 0" class="space-y-1">
                        <li v-for="(task, index) in tasks" :key="'task-' + index" class="flex items-center gap-x-2 text-sm text-text">
                            <PropertyIcon name="IconChecklist" class="size-4 shrink-0 text-text-subtle" stroke-width="1.5" aria-hidden="true"/>
                            <span class="truncate">{{ task.name }}</span>
                        </li>
                    </ul>
                    <ContractTaskForm
                        ref="task_form"
                        :show="creatingNewTask"
                        :users="budgetAccess"
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
        </div>
        <!-- CRM-Kontaktsuche -->
        <CrmContactSearchModal
            v-if="showCrmSearch"
            :contact-types="crmContactTypes"
            @close="showCrmSearch = false"
            @contact-selected="onCrmContactSelected"
        />
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="contractForm.processing"
                @click="closeModal"
            />
            <BaseUIButton
                label="Upload contract"
                variant="primary"
                icon="IconUpload"
                :processing="contractForm.processing"
                :disabled="!canSubmit"
                @click="storeContract"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script>
import {useForm} from "@inertiajs/vue3";
import ContractTaskForm from "@/Layouts/Components/ContractTaskForm.vue";
import Permissions from "@/Mixins/Permissions.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import ProjectSearch from "@/Components/SearchBars/ProjectSearch.vue";
import MultiAlertComponent from "@/Components/Alerts/MultiAlertComponent.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import LastedProjects from "@/Artwork/LastedProjects.vue";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import CrmContactSearchModal from "@/Pages/DocumentRequests/Components/CrmContactSearchModal.vue";
import CrmPropertyGroupSection from "@/Pages/CRM/Components/CrmPropertyGroupSection.vue";

const DEFAULT_CURRENCY = {id: 1, name: '€'};

export default {
    name: "ContractUploadModal",
    mixins: [Permissions],
    emits: ['closeModal'],
    props: [
        'show',
        'projectId',
        'budgetAccess',
        'contractTypes',
        'companyTypes',
        'currencies',
        'first_project_calendar_tab_id',
        'documentRequest',
        'crmContactTypes'
    ],
    components: {
        CrmPropertyGroupSection,
        CrmContactSearchModal,
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
        // Bei einer Dokumentenanfrage bekommt die anfragende Person Zugriff
        if (this.documentRequest?.requester) {
            this.usersWithAccess.push(this.documentRequest.requester);
        }
    },
    data() {
        return {
            selectedProject: this.documentRequest?.project || null,
            showCrmSearch: false,
            selectedCrmContact: this.documentRequest?.crm_contact || null,
            showCrmDetails: false,
            loadingCrmDetails: false,
            crmContactData: null,

            errorText: null,
            creatingNewTask: false,
            tasks: [],
            uploadDocumentFeedback: "",
            isDragging: false,
            file: null,
            description: this.documentRequest?.comment || "",
            selectedLegalForm: this.documentRequest?.company_type_id ? (this.companyTypes?.find(ct => ct.id === this.documentRequest.company_type_id) ?? null) : null,
            selectedContractType: this.documentRequest?.contract_type_id ? (this.contractTypes?.find(ct => ct.id === this.documentRequest.contract_type_id) ?? null) : null,
            selectedCurrency: this.documentRequest?.currency_id ? (this.currencies?.find(c => c.id === this.documentRequest.currency_id) || DEFAULT_CURRENCY) : DEFAULT_CURRENCY,
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
                foreign_tax_city: this.documentRequest?.foreign_tax_city || '',
                foreign_tax_country: this.documentRequest?.foreign_tax_country || '',
                foreign_tax_reason: this.documentRequest?.foreign_tax_reason || '',
                reverse_charge_amount: this.documentRequest?.reverse_charge_amount || null,
                deadline_date: this.documentRequest?.deadline_date || null,
                has_power_of_attorney: this.documentRequest?.has_power_of_attorney || false,
                is_freed: this.documentRequest?.is_freed || false,
                description: this.documentRequest?.comment || '',
                contract_state: this.documentRequest?.contract_state || '',
                contract_state_comment: this.documentRequest?.contract_state_comment || '',
                accessibleUsers: [],
                accessibleDepartments: [],
                tasks: [],
                document_request_id: this.documentRequest?.id || null,
            }),
        }
    },
    computed: {
        canSubmit() {
            return this.file !== null
                && String(this.contractForm.contract_partner ?? '').trim() !== ''
                && !this.contractForm.processing;
        },
        crmVisibleGroups() {
            if (!this.crmContactData) return [];

            const typePropertyIds = new Set(
                (this.crmContactData.contact.contact_type?.properties ?? []).map(p => p.id)
            );

            const pivotMap = {};
            for (const p of (this.crmContactData.contact.contact_type?.properties ?? [])) {
                pivotMap[p.id] = p.pivot ?? {};
            }

            return (this.crmContactData.propertyGroups ?? [])
                .map(group => ({
                    ...group,
                    properties: (group.properties ?? [])
                        .filter(p => typePropertyIds.has(p.id))
                        .map(p => ({ ...p, pivot: pivotMap[p.id] ?? {} })),
                }))
                .filter(group => group.properties.length > 0);
        },
    },
    methods: {
        onCrmContactSelected(contact) {
            this.selectedCrmContact = contact;
            this.contractForm.contract_partner = contact.display_name;
            this.showCrmSearch = false;
            this.showCrmDetails = false;
            this.crmContactData = null;
        },
        removeCrmContact() {
            this.selectedCrmContact = null;
            this.showCrmDetails = false;
            this.crmContactData = null;
        },
        toggleCrmDetails() {
            this.showCrmDetails = !this.showCrmDetails;
            if (this.showCrmDetails && !this.crmContactData) {
                this.loadCrmDetails();
            }
        },
        loadCrmDetails() {
            this.loadingCrmDetails = true;
            axios.get(this.route('crm.contacts.data', this.selectedCrmContact.id))
                .then(response => {
                    this.crmContactData = response.data;
                })
                .catch(() => {
                    this.crmContactData = null;
                })
                .finally(() => {
                    this.loadingCrmDetails = false;
                });
        },
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
            if (this.uploadDocumentFeedback === this.$t('Please select a project')) {
                this.uploadDocumentFeedback = '';
            }

            // Budget-Stichtag des Projekts als Frist vorschlagen
            if (project?.id) {
                axios.get(`/projects/${project.id}/basic`)
                    .then(response => {
                        if (response.data?.budget_deadline) {
                            this.contractForm.deadline_date = response.data.budget_deadline;
                        }
                    })
                    .catch(() => {
                        // Frist lässt sich auch von Hand setzen
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
        validateType(event) {
            this.isDragging = false;
            const files = event.dataTransfer?.files ?? event.target?.files;
            if (files && files.length > 0) {
                this.file = files[0];
                this.uploadDocumentFeedback = '';
            }
            if (event.target?.files) {
                // Gleiche Datei erneut wählbar machen
                event.target.value = '';
            }
        },
        resetModal() {
            this.contractForm.reset();
            this.contractForm.clearErrors();
            this.selectedProject = null;
            this.selectedLegalForm = null;
            this.selectedContractType = null;
            this.selectedCurrency = DEFAULT_CURRENCY;
            this.selectedCrmContact = null;
            this.showCrmDetails = false;
            this.crmContactData = null;
            this.file = null;
            this.description = "";
            this.usersWithAccess = [];
            this.department_query = '';
            this.departmentsWithAccess = [];
            this.department_search_results = [];
            this.showExtraSettings = false;
            this.creatingNewTask = false;
            this.tasks = [];
            this.uploadDocumentFeedback = "";
            this.errorText = null;
        },
        closeModal() {
            if (this.contractForm.processing) {
                return;
            }
            this.resetModal();
            this.$emit('closeModal', true)
        },
        storeContract() {
            if (!this.canSubmit) {
                return;
            }
            const projectIdToUse = this.projectId || this.selectedProject?.id;
            if (!projectIdToUse) {
                this.uploadDocumentFeedback = this.$t('Please select a project');
                return;
            }

            this.contractForm.file = this.file;
            this.contractForm.company_type_id = this.selectedLegalForm?.id ?? null;
            this.contractForm.contract_type_id = this.selectedContractType?.id ?? null;
            // Früher nie übernommen: Währungsauswahl und Kommentar gingen beim Hochladen verloren
            this.contractForm.currency_id = this.selectedCurrency?.id ?? DEFAULT_CURRENCY.id;
            this.contractForm.description = this.description;
            this.contractForm.accessibleUsers = this.usersWithAccess.map((user) => user.id);
            this.contractForm.accessibleDepartments = this.departmentsWithAccess.map((department) => department.id);
            this.contractForm.tasks = this.tasks
            // Mit festem Projekt oder aus einer Dokumentenanfrage nach dem Speichern auf der Seite bleiben
            this.contractForm.redirect_back = !!(this.projectId || this.documentRequest);
            this.contractForm.post(this.route('contracts.store', projectIdToUse), {
                forceFormData: true,
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    this.resetModal();
                    this.$emit('closeModal', true);
                },
            });
        }
    },
}
</script>
