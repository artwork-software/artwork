<template>
    <ArtworkBaseModal @close="closeModal" v-if="show" :title="$t('Create document request')" :description="$t('Create a new document request and assign it to a user.')">
        <div class="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Assign to user -->
                <div class="col-span-full relative">
                    <UserSearch v-model="user_query" @userSelected="selectUser" :label="$t('Assign to user*')" />
                    <div v-if="selectedUser" class="mt-2 flex items-center">
                        <img class="h-8 w-8 rounded-full object-cover" :src="selectedUser.profile_photo_url" alt="" />
                        <span class="ml-3 text-sm font-medium text-gray-900">
                            {{ selectedUser.first_name }} {{ selectedUser.last_name }}
                        </span>
                        <button type="button" @click="selectedUser = null" class="ml-2">
                            <PropertyIcon name="IconX" stroke-width="1.5" class="h-4 w-4 text-gray-400 hover:text-red-500" />
                        </button>
                    </div>
                </div>

                <!-- Project -->
                <div class="col-span-full relative">
                    <div class="text-sm font-medium text-gray-700 mb-2">{{ $t('Project assignment') }}</div>

                    <!-- Selected Project Chip -->
                    <div v-if="selectedProject" class="flex items-center gap-2 rounded-md border border-zinc-200 bg-zinc-50 px-3 py-3 mb-2">
                        <span class="text-sm text-zinc-700">{{ $t('Connected project:') }}</span>
                        <span class="font-medium text-zinc-900">{{ selectedProject.name }}</span>
                        <button type="button" class="ml-auto text-zinc-400 hover:text-rose-600 transition" @click="selectedProject = null">
                            <PropertyIcon name="IconCircleX" stroke-width="1.5" class="h-5 w-5" aria-hidden="true"/>
                        </button>
                    </div>

                    <!-- Project Search (only when no project selected) -->
                    <template v-if="!selectedProject">
                        <ProjectSearch
                            :label="$t('Search for projects')"
                            @project-selected="selectProject"
                        />
                        <LastedProjects
                            :limit="10"
                            @select="selectProject"
                        />
                    </template>
                </div>

                <hr class="col-span-full border-gray-200">

                <div class="col-span-full text-sm font-medium text-gray-700 mb-2">
                    {{ $t('Pre-filled document metadata (optional)') }}
                </div>

                <!-- Contract Partner -->
                <div class="">
                    <BaseInput
                        v-model="form.contract_partner"
                        id="contractPartner"
                        :label="$t('Contract partner')"
                    />
                </div>

                <!-- Contract Value -->
                <div class="">
                    <BaseInput
                        type="number"
                        step="0.01"
                        v-model="form.contract_value"
                        id="contractValue"
                        :label="$t('Contract value')"
                    />
                </div>

                <!-- Legal Form -->
                <div class="">
                    <div class="flex relative">
                        <Listbox as="div" class="flex-1 relative" v-model="selectedLegalForm">
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
                                            <span :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate']">
                                                {{ legalForm.name }}
                                            </span>
                                            <PropertyIcon name="IconCheck" stroke-width="1.5" v-if="selected" class="h-5 w-5 flex text-success" aria-hidden="true"/>
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

                <!-- Contract Type -->
                <div class="">
                    <div class="flex relative">
                        <Listbox as="div" class="flex-1 relative" v-model="selectedContractType">
                            <ListboxButton v-if="selectedContractType !== null" class="menu-button">
                                <div class="flex items-center justify-between w-full">
                                    <span>{{ selectedContractType.name }}</span>
                                    <PropertyIcon name="IconChevronDown" stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </div>
                            </ListboxButton>
                            <ListboxButton v-else class="menu-button">
                                <span>{{ $t('Contract type')}}</span>
                                <PropertyIcon name="IconChevronDown" stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                            </ListboxButton>
                            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions class="absolute w-full z-10 mt-16 rounded-lg bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                    <ListboxOption as="template" class="max-h-8" v-for="contractType in contractTypes" :key="contractType.id" :value="contractType" v-slot="{ active, selected }">
                                        <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                            <span :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate']">
                                                {{ contractType.name }}
                                            </span>
                                            <PropertyIcon name="IconCheck" stroke-width="1.5" v-if="selected" class="h-5 w-5 flex text-success" aria-hidden="true"/>
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

                <!-- KSK -->
                <div class="col-span-full">
                    <div class="flex items-center mb-2">
                        <input id="kskLiable" type="checkbox" v-model="form.ksk_liable" class="input-checklist"/>
                        <label for="kskLiable" :class="form.ksk_liable ? 'xsDark' : 'xsLight subpixel-antialiased'" class="ml-2">
                            {{ $t('KSK-liable')}}
                        </label>
                    </div>
                    <div class="grid grid-cols-1 gap-4 mb-4" >
                        <BaseInput
                            type="number"
                            v-if="form.ksk_liable"
                            step="0.01"
                            id="kskAmount"
                            v-model="form.ksk_amount"
                            :label="$t('KSK Amount')"
                        />
                        <BaseTextarea
                            :label="$t('KSK Reason')"
                            id="kskReason"
                            v-model="form.ksk_reason"
                            rows="2"
                        />
                    </div>
                </div>

                <!-- Foreign Tax -->
                <div class="col-span-full">
                    <div class="flex items-center mb-2">
                        <input id="foreignTax" type="checkbox" v-model="form.foreign_tax" class="input-checklist"/>
                        <label for="foreignTax" :class="form.foreign_tax ? 'xsDark' : 'xsLight subpixel-antialiased'" class="ml-2">
                            {{ $t('Foreign tax')}}
                        </label>
                    </div>
                    <div class="grid grid-cols-1 gap-4 mb-4" >
                        <BaseInput
                            type="number"
                            step="0.01"
                            v-if="form.foreign_tax"
                            id="foreignTaxAmount"
                            v-model="form.foreign_tax_amount"
                            :label="$t('Foreign tax amount')"
                        />
                        <BaseTextarea
                            :label="$t('Foreign tax reason')"
                            id="foreignTaxReason"
                            v-model="form.foreign_tax_reason"
                            rows="2"
                        />
                    </div>
                </div>

                <!-- Reverse Charge -->
                <div class="">
                    <BaseInput
                        type="number"
                        step="0.01"
                        id="reverseChargeAmount"
                        v-model="form.reverse_charge_amount"
                        :label="$t('Reverse Charge Amount')"
                    />
                </div>

                <!-- Deadline Date -->
                <div class="">
                    <BaseInput
                        type="date"
                        id="deadlineDate"
                        v-model="form.deadline_date"
                        :label="$t('Deadline date')"
                    />
                </div>

                <!-- Comment -->
                <div class="col-span-full">
                    <BaseTextarea
                        :label="$t('Comment / Note')"
                        id="comment"
                        v-model="form.comment"
                        rows="3"
                    />
                </div>
            </div>

            <div class="justify-end flex w-full my-6">
                <BaseUIButton
                    :label="$t('Create document request')"
                    is-add-button
                    :disabled="!selectedUser"
                    @click="storeRequest"
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script>
import { useForm } from "@inertiajs/vue3";
import { Listbox, ListboxButton, ListboxOption, ListboxOptions } from "@headlessui/vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import ProjectSearch from "@/Components/SearchBars/ProjectSearch.vue";
import LastedProjects from "@/Artwork/LastedProjects.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

export default {
    name: "DocumentRequestCreateModal",
    emits: ['close'],
    props: {
        show: Boolean,
        contractTypes: {
            type: Array,
            default: () => []
        },
        companyTypes: {
            type: Array,
            default: () => []
        },
        preselectedProject: {
            type: Object,
            default: null
        }
    },
    components: {
        PropertyIcon,
        BaseUIButton,
        ArtworkBaseModal,
        BaseTextarea,
        BaseInput,
        UserSearch,
        ProjectSearch,
        LastedProjects,
        Listbox,
        ListboxOption,
        ListboxOptions,
        ListboxButton,
    },
    data() {
        return {
            selectedUser: null,
            user_query: '',
            selectedProject: this.preselectedProject,
            selectedLegalForm: null,
            selectedContractType: null,
            form: useForm({
                requested_id: null,
                project_id: null,
                contract_partner: '',
                contract_value: null,
                ksk_liable: false,
                ksk_amount: null,
                ksk_reason: '',
                foreign_tax: false,
                foreign_tax_amount: null,
                foreign_tax_reason: '',
                reverse_charge_amount: null,
                deadline_date: null,
                contract_type_id: null,
                company_type_id: null,
                comment: '',
            }),
        }
    },
    methods: {
        selectUser(user) {
            this.selectedUser = user;
            this.user_query = '';
        },
        selectProject(project) {
            this.selectedProject = project;

            // Fetch project details to get budget_deadline
            if (project?.id) {
                axios.get(`/projects/${project.id}/basic`)
                    .then(response => {
                        if (response.data?.budget_deadline) {
                            this.form.deadline_date = response.data.budget_deadline;
                        }
                    })
                    .catch(() => {
                        // Ignore errors, deadline can be set manually
                    });
            }
        },
        closeModal() {
            this.form.reset();
            this.selectedUser = null;
            this.selectedProject = null;
            this.selectedLegalForm = null;
            this.selectedContractType = null;
            this.$emit('close');
        },
        storeRequest() {
            this.form.requested_id = this.selectedUser?.id;
            this.form.project_id = this.selectedProject?.id;
            this.form.company_type_id = this.selectedLegalForm?.id;
            this.form.contract_type_id = this.selectedContractType?.id;
            // If preselectedProject prop is set, we're in a project tab context - stay there after submit
            this.form.redirect_back = !!this.preselectedProject;

            this.form.post(this.route('document-requests.store'), {
                preserveScroll: true,
                onSuccess: () => {
                    this.closeModal();
                },
            });
        }
    },
}
</script>
