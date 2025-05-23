<template>
    <ArtworkBaseModal
        title="Details External material issue"
        description="All details about this external loan - including contact, inventory, return status and additional information."
        @close="$emit('close')"
        modal-size="max-w-4xl"
    >
        <div class="mt-6 space-y-6 text-sm text-gray-800">

            <!-- Basisdaten -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-500">{{ $t('Material value')}}</p>
                    <p class="font-medium">{{ Number(issue.material_value).toFixed(2) }} €</p>
                </div>
                <div>
                    <p class="text-gray-500">{{ $t('Period') }}</p>
                    <p class="font-medium">
                        {{ issue.issue_date_formatted }} – {{ issue.return_date_formatted }}
                    </p>
                </div>
                <div>
                    <p class="text-gray-500">{{ $t('Issued By')}}</p>
                    <div class="flex items-start justify-start">
                        <UserPopoverTooltip :user="issue.issued_by" width="8" height="8" classes="border-2 border-white rounded-full" />
                    </div>
                </div>
                <div v-if="issue.received_by">
                    <p class="text-gray-500">{{ $t('Received by') }}</p>
                    <div class="flex items-start justify-start">
                        <UserPopoverTooltip :user="issue.received_by" width="8" height="8" classes="border-2 border-white rounded-full" />
                    </div>
                </div>
            </div>

            <!-- Externe Person -->
            <div>
                <p class="text-gray-500">{{ $t('External contact person / company')}}</p>
                <p class="font-medium">
                    {{ issue.external_name }}<br />
                    <span v-if="issue.external_address">{{ issue.external_address }}</span><br />
                    <span v-if="issue.external_email">{{ issue.external_email }}</span><br />
                    <span v-if="issue.external_phone">{{ issue.external_phone }}</span>
                </p>
            </div>

            <!-- Artikel -->
            <div>
                <p class="text-gray-500">{{ $t('Articles')}}</p>
                <div v-if="issue.articles?.length">
                    <ul class="divide-y border rounded overflow-hidden">
                        <li v-for="a in issue.articles" :key="a.id" class="p-3 flex justify-between items-center">
                            <span class="font-medium">{{ a.name }}</span>
                            <span class="text-sm text-gray-500">{{$t('Quantity')}}: {{ a.pivot.quantity }}</span>
                        </li>
                    </ul>
                </div>
                <p v-else class="text-gray-500 italic">{{ $t('No items assigned.')}}</p>
            </div>

            <!-- Sonderartikel -->
            <div v-if="issue.special_items?.length">
                <p class="text-gray-500">{{ $t('Special article')}}</p>
                <ul class="divide-y border rounded overflow-hidden">
                    <li v-for="s in issue.special_items" :key="s.id" class="p-3 flex justify-between items-center">
                        <span class="font-medium">{{ s.name }}</span>
                        <span class="text-sm text-gray-500">{{$t('Quantity')}}: {{ s.quantity }}</span>
                    </li>
                </ul>
            </div>


            <!-- Mängel -->
            <div v-if="issue.return_remarks">
                <p class="text-gray-500">{{ $t('Defects after return') }}</p>
                <p class="whitespace-pre-line font-medium">{{ issue.return_remarks }}</p>
            </div>

            <!-- Dateien -->
            <div v-if="issue.files?.length">
                <p class="text-gray-500">{{ $t('Files') }}</p>
                <ul class="list-disc list-inside space-y-1">
                    <li v-for="file in issue.files" :key="file.id">
                        <a :href="'/storage/' + file.file_path" target="_blank" download class="text-blue-600 hover:underline">
                            {{ file.original_name }}
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Schließen -->
            <div class="flex justify-end mt-6">
                <ArtworkBaseModalButton type="button" variant="danger" @click="$emit('close')">
                    {{ $t('Close') }}
                </ArtworkBaseModalButton>
            </div>
        </div>

    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";

defineProps({
    issue: {
        type: Object,
        required: true,
    },
});
</script>
