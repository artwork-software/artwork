<template>
    <ArtworkBaseModal @close="closeModal" v-if="show" :title="$t('Edit document request')" :description="$t('Edit the document request details.')">
        <div class="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Status -->
                <div class="col-span-full">
                    <Listbox as="div" class="flex relative" v-model="selectedStatus">
                        <ListboxButton class="menu-button">
                            <span>{{ getStatusLabel(selectedStatus) }}</span>
                            <PropertyIcon name="IconChevronDown" stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                        </ListboxButton>
                        <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                            <ListboxOptions class="absolute w-full z-10 mt-16 bg-primary rounded-lg shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                <ListboxOption as="template" class="max-h-8" v-for="status in statuses" :key="status.value" :value="status.value" v-slot="{ active, selected }">
                                    <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                        <span :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate']">
                                            {{ status.label }}
                                        </span>
                                        <PropertyIcon name="IconCheck" stroke-width="1.5" v-if="selected" class="h-5 w-5 flex text-success" aria-hidden="true"/>
                                    </li>
                                </ListboxOption>
                            </ListboxOptions>
                        </transition>
                    </Listbox>
                </div>

                <hr class="col-span-full border-gray-200">

                <div class="col-span-full text-sm font-medium text-gray-700 mb-2">
                    {{ $t('Document metadata') }}
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
                    <Listbox as="div" class="flex relative" v-model="selectedLegalForm">
                        <ListboxButton v-if="selectedLegalForm !== null" class="menu-button">
                            <div>{{ selectedLegalForm.name }}</div>
                            <PropertyIcon name="IconChevronDown" stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
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
                </div>

                <!-- Contract Type -->
                <div class="">
                    <Listbox as="div" class="flex relative" v-model="selectedContractType">
                        <ListboxButton v-if="selectedContractType !== null" class="menu-button">
                            <span>{{ selectedContractType.name }}</span>
                            <PropertyIcon name="IconChevronDown" stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
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
                </div>

                <!-- KSK -->
                <div class="col-span-full">
                    <div class="flex items-center mb-2">
                        <input id="kskLiableEdit" type="checkbox" v-model="form.ksk_liable" class="input-checklist"/>
                        <label for="kskLiableEdit" :class="form.ksk_liable ? 'xsDark' : 'xsLight subpixel-antialiased'" class="ml-2">
                            {{ $t('KSK-liable')}}
                        </label>
                    </div>
                    <div class="grid grid-cols-1 gap-4 my-2">
                        <BaseInput
                            v-if="form.ksk_liable"
                            type="number"
                            step="0.01"
                            id="kskAmountEdit"
                            v-model="form.ksk_amount"
                            :label="$t('KSK Amount')"
                        />
                        <BaseTextarea
                            :label="$t('KSK Reason')"
                            id="kskReasonEdit"
                            v-model="form.ksk_reason"
                            rows="2"
                        />
                    </div>
                </div>

                <!-- Foreign Tax -->
                <div class="col-span-full">
                    <div class="flex items-center mb-2">
                        <input id="foreignTaxEdit" type="checkbox" v-model="form.foreign_tax" class="input-checklist"/>
                        <label for="foreignTaxEdit" :class="form.foreign_tax ? 'xsDark' : 'xsLight subpixel-antialiased'" class="ml-2">
                            {{ $t('Foreign tax')}}
                        </label>
                    </div>
                    <div class="grid grid-cols-1 gap-4 my-2">
                        <BaseInput
                            v-if="form.foreign_tax"
                            type="number"
                            step="0.01"
                            id="foreignTaxAmountEdit"
                            v-model="form.foreign_tax_amount"
                            :label="$t('Foreign tax amount')"
                        />
                        <BaseTextarea
                            :label="$t('Foreign tax reason')"
                            id="foreignTaxReasonEdit"
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
                        id="reverseChargeAmountEdit"
                        v-model="form.reverse_charge_amount"
                        :label="$t('Reverse Charge Amount')"
                    />
                </div>

                <!-- Deadline Date -->
                <div class="">
                    <BaseInput
                        type="date"
                        id="deadlineDateEdit"
                        v-model="form.deadline_date"
                        :label="$t('Deadline date')"
                    />
                </div>

                <!-- Comment -->
                <div class="col-span-full">
                    <BaseTextarea
                        :label="$t('Comment / Note')"
                        id="commentEdit"
                        v-model="form.comment"
                        rows="3"
                    />
                </div>
            </div>

            <div class="justify-end flex w-full my-6">
                <BaseUIButton
                    :label="$t('Save changes')"
                    is-add-button
                    :disabled="false"
                    @click="updateRequest"
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
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

export default {
    name: "DocumentRequestEditModal",
    emits: ['close'],
    props: {
        show: Boolean,
        documentRequest: Object,
        contractTypes: {
            type: Array,
            default: () => []
        },
        companyTypes: {
            type: Array,
            default: () => []
        }
    },
    components: {
        PropertyIcon,
        BaseUIButton,
        ArtworkBaseModal,
        BaseTextarea,
        BaseInput,
        Listbox,
        ListboxOption,
        ListboxOptions,
        ListboxButton,
    },
    data() {
        return {
            selectedStatus: this.documentRequest?.status || 'open',
            selectedLegalForm: this.documentRequest?.company_type || null,
            selectedContractType: this.documentRequest?.contract_type || null,
            statuses: [
                { value: 'open', label: this.$t('Open') },
                { value: 'in_progress', label: this.$t('In Progress') },
                { value: 'completed', label: this.$t('Completed') },
            ],
            form: useForm({
                status: this.documentRequest?.status || 'open',
                contract_partner: this.documentRequest?.contract_partner || '',
                contract_value: this.documentRequest?.contract_value || null,
                ksk_liable: this.documentRequest?.ksk_liable || false,
                ksk_amount: this.documentRequest?.ksk_amount || null,
                ksk_reason: this.documentRequest?.ksk_reason || '',
                foreign_tax: this.documentRequest?.foreign_tax || false,
                foreign_tax_amount: this.documentRequest?.foreign_tax_amount || null,
                foreign_tax_reason: this.documentRequest?.foreign_tax_reason || '',
                reverse_charge_amount: this.documentRequest?.reverse_charge_amount || null,
                deadline_date: this.documentRequest?.deadline_date || null,
                contract_type_id: this.documentRequest?.contract_type?.id || null,
                company_type_id: this.documentRequest?.company_type?.id || null,
                comment: this.documentRequest?.comment || '',
            }),
        }
    },
    methods: {
        getStatusLabel(status) {
            const found = this.statuses.find(s => s.value === status);
            return found ? found.label : status;
        },
        closeModal() {
            this.$emit('close');
        },
        updateRequest() {
            this.form.status = this.selectedStatus;
            this.form.company_type_id = this.selectedLegalForm?.id;
            this.form.contract_type_id = this.selectedContractType?.id;

            this.form.patch(this.route('document-requests.update', this.documentRequest.id), {
                preserveScroll: true,
                onSuccess: () => {
                    this.closeModal();
                },
            });
        }
    },
}
</script>
