<template>
    <ArtworkBaseModal
        title="Details Internal material issue"
        description="Detailed information on the planned internal material issue - including time period, room, inventory and special items."
        @close="$emit('close')"
        modal-size="max-w-4xl"
    >
        <div class="mt-6 space-y-6 text-sm text-gray-800">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-500">{{ $t('Name')}}</p>
                    <p class="font-medium">{{ issue.name }}</p>
                </div>
                <div v-if="issue.room">
                    <p class="text-gray-500">{{ $t('Room')}}</p>
                    <p class="font-medium">{{ issue.room.name }}</p>
                </div>
                <div v-if="issue.project">
                    <p class="text-gray-500">{{ $t('Project')}}</p>
                    <p class="font-medium">{{ issue.project.name }}</p>
                </div>
                <div>
                    <p class="text-gray-500">{{ $t('Period')}}</p>
                    <p class="font-medium">
                        {{ issue.start_date_time }} - {{ issue.end_date_time }}
                    </p>
                </div>
                <div>
                    <p class="text-gray-500">{{ $t('Responsible persons') }}</p>
                    <div class="flex items-center print:hidden">
                        <div class="flex -space-x-2 overflow-hidden items-center">
                            <UserPopoverTooltip v-for="user in issue.responsible_users" :user="user" width="8" height="8" classes="border-2 border-white rounded-full" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notizen -->
            <div v-if="issue.notes">
                <p class="text-gray-500">{{ $t('Notes')}}</p>
                <p class="whitespace-pre-line font-medium">{{ issue.notes }}</p>
            </div>

            <!-- Dateien -->
            <div v-if="issue.files?.length">
                <p class="text-gray-500">{{ $t('Files')}}</p>
                <ul class="list-disc list-inside space-y-1">
                    <li v-for="file in issue.files" :key="file.id">
                        <a :href="'/storage/' + file.file_path" target="_blank" download class="text-blue-600 hover:underline">
                            {{ file.original_name }}
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Artikel -->
            <div>
                <p class="text-gray-500">{{ $t('Articles')}}</p>
                <div v-if="issue.articles?.length">
                    <ul class="divide-y border rounded overflow-hidden">
                        <li v-for="a in issue.articles" :key="a.id" class="p-3 flex justify-between items-center">
                            <span class="font-medium">{{ a.name }}</span>
                            <span class="text-sm text-gray-500">{{ $t('Quantity')}}: {{ a.pivot.quantity }}</span>
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
                        <span class="text-sm text-gray-500">{{ $t('Quantity')}}: {{ s.quantity }}</span>
                    </li>
                </ul>
            </div>

            <!-- Status Hinweis -->
            <div v-if="issue.special_items?.length && !issue.special_items_done" class="bg-yellow-100 border-l-4 border-yellow-400 text-yellow-800 p-3 text-sm rounded">
                ⚠ {{ $t('There are unmarked special items in this material issue.') }}
            </div>

            <!-- Footer Buttons -->
            <div class="flex justify-end mt-4">
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
