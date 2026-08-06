<template>
    <ArtworkBaseModal @close="closeModal" v-if="show" :title="$t('Document request details')" :description="$t('View the document request details.')">
        <div class="space-y-6">
            <!-- Status Badge -->
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-text-subtle">{{ $t('Status') }}</span>
                <span :class="getStatusClass(documentRequest?.status)" class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium">
                    {{ getStatusLabel(documentRequest?.status) }}
                </span>
            </div>

            <!-- Requester & Requested -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-sm font-medium text-text-subtle">{{ $t('Requested by') }}</span>
                    <div v-if="documentRequest?.requester" class="mt-2 flex items-center">
                        <img :src="documentRequest.requester.profile_photo_url" alt="" class="size-10 rounded-full object-cover" />
                        <div class="ml-3">
                            <div class="text-sm font-medium text-text">
                                {{ documentRequest.requester.first_name }} {{ documentRequest.requester.last_name }}
                            </div>
                            <div class="text-xs text-text-subtle">{{ documentRequest.requester.email }}</div>
                        </div>
                    </div>
                </div>
                <div>
                    <span class="text-sm font-medium text-text-subtle">{{ $t('Assigned to') }}</span>
                    <div v-if="documentRequest?.requested" class="mt-2 flex items-center">
                        <img :src="documentRequest.requested.profile_photo_url" alt="" class="size-10 rounded-full object-cover" />
                        <div class="ml-3">
                            <div class="text-sm font-medium text-text">
                                {{ documentRequest.requested.first_name }} {{ documentRequest.requested.last_name }}
                            </div>
                            <div class="text-xs text-text-subtle">{{ documentRequest.requested.email }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Project -->
            <div v-if="documentRequest?.project">
                <span class="text-sm font-medium text-text-subtle">{{ $t('Project') }}</span>
                <p class="mt-1 text-sm text-text">{{ documentRequest.project.name }}</p>
            </div>

            <!-- Uploaded Contract -->
            <div v-if="documentRequest?.contract">
                <span class="text-sm font-medium text-text-subtle">{{ $t('Uploaded document') }}</span>
                <a :href="route('contracts.download', documentRequest.contract.id)" class="mt-1 flex items-center text-sm text-accent-600 hover:text-accent-700">
                    <PropertyIcon name="IconFile" stroke-width="1.5" class="h-5 w-5 mr-2" />
                    {{ documentRequest.contract.name }}
                </a>
            </div>

            <!-- CRM Contact Profile -->
            <div v-if="documentRequest?.crm_contact_id">
                <span class="text-sm font-medium text-text-subtle mb-2 block">{{ $t('Linked CRM contact') }}</span>
                <CrmContactProfileCard
                    :document-request-id="documentRequest.id"
                    :crm-contact="documentRequest.crm_contact"
                    :readonly="true"
                />
            </div>

            <hr class="border-border-subtle">

            <!-- Metadata Section -->
            <div class="text-sm font-medium text-text-muted mb-2">{{ $t('Document metadata') }}</div>

            <div class="grid grid-cols-2 gap-4 text-sm">
                <div v-if="documentRequest?.contract_partner">
                    <span class="font-medium text-text-subtle">{{ $t('Contract partner') }}</span>
                    <p class="text-text">{{ documentRequest.contract_partner }}</p>
                </div>

                <div v-if="documentRequest?.contract_value">
                    <span class="font-medium text-text-subtle">{{ $t('Contract value') }}</span>
                    <p class="text-text">{{ formatCurrency(documentRequest.contract_value) }}</p>
                </div>

                <div v-if="documentRequest?.contract_type">
                    <span class="font-medium text-text-subtle">{{ $t('Contract type') }}</span>
                    <p class="text-text">{{ documentRequest.contract_type.name }}</p>
                </div>

                <div v-if="documentRequest?.company_type">
                    <span class="font-medium text-text-subtle">{{ $t('Legal form') }}</span>
                    <p class="text-text">{{ documentRequest.company_type.name }}</p>
                </div>

                <div v-if="documentRequest?.deadline_date">
                    <span class="font-medium text-text-subtle">{{ $t('Deadline date') }}</span>
                    <p class="text-text">{{ formatDate(documentRequest.deadline_date) }}</p>
                </div>
            </div>

            <!-- KSK Info -->
            <div v-if="documentRequest?.ksk_liable" class="bg-surface-sunken rounded-lg p-4">
                <div class="flex items-center mb-2">
                    <PropertyIcon name="IconCheck" stroke-width="1.5" class="h-5 w-5 text-success mr-2" />
                    <span class="font-medium text-text">{{ $t('KSK-liable') }}</span>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm ml-7">
                    <div v-if="documentRequest?.ksk_amount">
                        <span class="text-text-subtle">{{ $t('Amount') }}:</span>
                        <span class="ml-1 text-text">{{ formatCurrency(documentRequest.ksk_amount) }}</span>
                    </div>
                    <div v-if="documentRequest?.ksk_reason">
                        <span class="text-text-subtle">{{ $t('Reason') }}:</span>
                        <span class="ml-1 text-text">{{ documentRequest.ksk_reason }}</span>
                    </div>
                </div>
            </div>

            <!-- Foreign Tax Info -->
            <div v-if="documentRequest?.foreign_tax" class="bg-surface-sunken rounded-lg p-4">
                <div class="flex items-center mb-2">
                    <PropertyIcon name="IconCheck" stroke-width="1.5" class="h-5 w-5 text-success mr-2" />
                    <span class="font-medium text-text">{{ $t('Foreign tax') }}</span>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm ml-7">
                    <div v-if="documentRequest?.foreign_tax_amount">
                        <span class="text-text-subtle">{{ $t('Amount') }}:</span>
                        <span class="ml-1 text-text">{{ formatCurrency(documentRequest.foreign_tax_amount) }}</span>
                    </div>
                    <div v-if="documentRequest?.foreign_tax_city">
                        <span class="text-text-subtle">{{ $t('City') }}:</span>
                        <span class="ml-1 text-text">{{ documentRequest.foreign_tax_city }}</span>
                    </div>
                    <div v-if="documentRequest?.foreign_tax_country">
                        <span class="text-text-subtle">{{ $t('Country') }}:</span>
                        <span class="ml-1 text-text">{{ documentRequest.foreign_tax_country }}</span>
                    </div>
                    <div v-if="documentRequest?.foreign_tax_reason">
                        <span class="text-text-subtle">{{ $t('Reason') }}:</span>
                        <span class="ml-1 text-text">{{ documentRequest.foreign_tax_reason }}</span>
                    </div>
                </div>
            </div>

            <!-- Reverse Charge -->
            <div v-if="documentRequest?.reverse_charge_amount" class="text-sm">
                <span class="font-medium text-text-subtle">{{ $t('Reverse Charge Amount') }}</span>
                <p class="text-text">{{ formatCurrency(documentRequest.reverse_charge_amount) }}</p>
            </div>

            <!-- Comment -->
            <div v-if="documentRequest?.comment">
                <span class="text-sm font-medium text-text-subtle">{{ $t('Comment') }}</span>
                <p class="mt-1 text-sm text-text">{{ documentRequest.comment }}</p>
            </div>

            <!-- Contract State -->
            <div v-if="documentRequest?.contract_state || documentRequest?.contract_state_comment" class="bg-surface-sunken rounded-lg p-4">
                <div class="text-sm font-medium text-text-muted mb-2">{{ $t('Contract status') }}</div>
                <div class="grid grid-cols-1 gap-2 text-sm">
                    <div v-if="documentRequest?.contract_state">
                        <span class="text-text-subtle">{{ $t('Status') }}:</span>
                        <span class="ml-1 text-text">{{ documentRequest.contract_state }}</span>
                    </div>
                    <div v-if="documentRequest?.contract_state_comment">
                        <span class="text-text-subtle">{{ $t('Comment') }}:</span>
                        <span class="ml-1 text-text">{{ documentRequest.contract_state_comment }}</span>
                    </div>
                </div>
            </div>

            <!-- Timestamps -->
            <div class="text-xs text-text-subtle pt-4 border-t border-border-subtle">
                <p>{{ $t('Created at') }}: {{ documentRequest?.created_at }}</p>
                <p>{{ $t('Updated at') }}: {{ documentRequest?.updated_at }}</p>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import CrmContactProfileCard from "./CrmContactProfileCard.vue";

export default {
    name: "DocumentRequestDetailModal",
    emits: ['close'],
    props: {
        show: Boolean,
        documentRequest: Object,
    },
    components: {
        CrmContactProfileCard,
        PropertyIcon,
        ArtworkBaseModal,
    },
    methods: {
        closeModal() {
            this.$emit('close');
        },
        getStatusClass(status) {
            switch (status) {
                case 'open':
                    return 'bg-warning-surface text-warning'
                case 'in_progress':
                    return 'bg-accent-100 text-accent-700'
                case 'completed':
                    return 'bg-success-surface text-success'
                default:
                    return 'bg-surface-sunken text-text'
            }
        },
        getStatusLabel(status) {
            switch (status) {
                case 'open':
                    return this.$t('Open')
                case 'in_progress':
                    return this.$t('In Progress')
                case 'completed':
                    return this.$t('Completed')
                default:
                    return status
            }
        },
        formatCurrency(value) {
            if (!value) return '-';
            return new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(value);
        },
        formatDate(value) {
            if (!value) return '-';
            const date = new Date(value);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}.${month}.${year}`;
        }
    },
}
</script>
