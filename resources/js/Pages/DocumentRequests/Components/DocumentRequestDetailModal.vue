<template>
    <ArtworkBaseModal @close="closeModal" v-if="show" :title="$t('Document request details')" :description="documentRequest?.title">
        <div class="space-y-6">
            <!-- Status Badge -->
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500">{{ $t('Status') }}</span>
                <span :class="getStatusClass(documentRequest?.status)" class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium">
                    {{ getStatusLabel(documentRequest?.status) }}
                </span>
            </div>

            <!-- Requester & Requested -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-sm font-medium text-gray-500">{{ $t('Requested by') }}</span>
                    <div v-if="documentRequest?.requester" class="mt-2 flex items-center">
                        <img :src="documentRequest.requester.profile_photo_url" alt="" class="size-10 rounded-full object-cover" />
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">
                                {{ documentRequest.requester.first_name }} {{ documentRequest.requester.last_name }}
                            </div>
                            <div class="text-xs text-gray-500">{{ documentRequest.requester.email }}</div>
                        </div>
                    </div>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-500">{{ $t('Assigned to') }}</span>
                    <div v-if="documentRequest?.requested" class="mt-2 flex items-center">
                        <img :src="documentRequest.requested.profile_photo_url" alt="" class="size-10 rounded-full object-cover" />
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">
                                {{ documentRequest.requested.first_name }} {{ documentRequest.requested.last_name }}
                            </div>
                            <div class="text-xs text-gray-500">{{ documentRequest.requested.email }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div v-if="documentRequest?.description">
                <span class="text-sm font-medium text-gray-500">{{ $t('Description') }}</span>
                <p class="mt-1 text-sm text-gray-900">{{ documentRequest.description }}</p>
            </div>

            <!-- Project -->
            <div v-if="documentRequest?.project">
                <span class="text-sm font-medium text-gray-500">{{ $t('Project') }}</span>
                <p class="mt-1 text-sm text-gray-900">{{ documentRequest.project.name }}</p>
            </div>

            <!-- Uploaded Contract -->
            <div v-if="documentRequest?.contract">
                <span class="text-sm font-medium text-gray-500">{{ $t('Uploaded document') }}</span>
                <a :href="route('contracts.download', documentRequest.contract.id)" class="mt-1 flex items-center text-sm text-blue-600 hover:text-blue-800">
                    <PropertyIcon name="IconFile" stroke-width="1.5" class="h-5 w-5 mr-2" />
                    {{ documentRequest.contract.name }}
                </a>
            </div>

            <hr class="border-gray-200">

            <!-- Metadata Section -->
            <div class="text-sm font-medium text-gray-700 mb-2">{{ $t('Document metadata') }}</div>

            <div class="grid grid-cols-2 gap-4 text-sm">
                <div v-if="documentRequest?.contract_partner">
                    <span class="font-medium text-gray-500">{{ $t('Contract partner') }}</span>
                    <p class="text-gray-900">{{ documentRequest.contract_partner }}</p>
                </div>

                <div v-if="documentRequest?.contract_value">
                    <span class="font-medium text-gray-500">{{ $t('Contract value') }}</span>
                    <p class="text-gray-900">{{ formatCurrency(documentRequest.contract_value) }}</p>
                </div>

                <div v-if="documentRequest?.contract_type">
                    <span class="font-medium text-gray-500">{{ $t('Contract type') }}</span>
                    <p class="text-gray-900">{{ documentRequest.contract_type.name }}</p>
                </div>

                <div v-if="documentRequest?.company_type">
                    <span class="font-medium text-gray-500">{{ $t('Legal form') }}</span>
                    <p class="text-gray-900">{{ documentRequest.company_type.name }}</p>
                </div>

                <div v-if="documentRequest?.deadline_date">
                    <span class="font-medium text-gray-500">{{ $t('Deadline date') }}</span>
                    <p class="text-gray-900">{{ documentRequest.deadline_date }}</p>
                </div>
            </div>

            <!-- KSK Info -->
            <div v-if="documentRequest?.ksk_liable" class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-center mb-2">
                    <PropertyIcon name="IconCheck" stroke-width="1.5" class="h-5 w-5 text-green-500 mr-2" />
                    <span class="font-medium text-gray-900">{{ $t('KSK-liable') }}</span>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm ml-7">
                    <div v-if="documentRequest?.ksk_amount">
                        <span class="text-gray-500">{{ $t('Amount') }}:</span>
                        <span class="ml-1 text-gray-900">{{ formatCurrency(documentRequest.ksk_amount) }}</span>
                    </div>
                    <div v-if="documentRequest?.ksk_reason">
                        <span class="text-gray-500">{{ $t('Reason') }}:</span>
                        <span class="ml-1 text-gray-900">{{ documentRequest.ksk_reason }}</span>
                    </div>
                </div>
            </div>

            <!-- Foreign Tax Info -->
            <div v-if="documentRequest?.foreign_tax" class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-center mb-2">
                    <PropertyIcon name="IconCheck" stroke-width="1.5" class="h-5 w-5 text-green-500 mr-2" />
                    <span class="font-medium text-gray-900">{{ $t('Foreign tax') }}</span>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm ml-7">
                    <div v-if="documentRequest?.foreign_tax_amount">
                        <span class="text-gray-500">{{ $t('Amount') }}:</span>
                        <span class="ml-1 text-gray-900">{{ formatCurrency(documentRequest.foreign_tax_amount) }}</span>
                    </div>
                    <div v-if="documentRequest?.foreign_tax_reason">
                        <span class="text-gray-500">{{ $t('Reason') }}:</span>
                        <span class="ml-1 text-gray-900">{{ documentRequest.foreign_tax_reason }}</span>
                    </div>
                </div>
            </div>

            <!-- Reverse Charge -->
            <div v-if="documentRequest?.reverse_charge_amount" class="text-sm">
                <span class="font-medium text-gray-500">{{ $t('Reverse Charge Amount') }}</span>
                <p class="text-gray-900">{{ formatCurrency(documentRequest.reverse_charge_amount) }}</p>
            </div>

            <!-- Comment -->
            <div v-if="documentRequest?.comment">
                <span class="text-sm font-medium text-gray-500">{{ $t('Comment') }}</span>
                <p class="mt-1 text-sm text-gray-900">{{ documentRequest.comment }}</p>
            </div>

            <!-- Timestamps -->
            <div class="text-xs text-gray-400 pt-4 border-t border-gray-200">
                <p>{{ $t('Created at') }}: {{ documentRequest?.created_at }}</p>
                <p>{{ $t('Updated at') }}: {{ documentRequest?.updated_at }}</p>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

export default {
    name: "DocumentRequestDetailModal",
    emits: ['close'],
    props: {
        show: Boolean,
        documentRequest: Object,
    },
    components: {
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
                    return 'bg-yellow-100 text-yellow-800'
                case 'in_progress':
                    return 'bg-blue-100 text-blue-800'
                case 'completed':
                    return 'bg-green-100 text-green-800'
                default:
                    return 'bg-gray-100 text-gray-800'
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
        }
    },
}
</script>
