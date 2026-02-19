<template>
    <div>
        <h3 class="text-[11px] font-semibold uppercase tracking-wide text-secondary mb-2">{{ $t('artist management') }}</h3>
        <div v-if="residencies?.length > 0">
            <table class="min-w-full divide-y divide-gray-300 text-sm">
                <thead>
                    <tr>
                        <th scope="col" class="py-2 pr-3 text-left font-semibold text-gray-900">{{ $t('Name') }}</th>
                        <th scope="col" class="px-3 py-2 text-left font-semibold text-gray-900">{{ $t('Position') }}</th>
                        <th scope="col" class="px-3 py-2 text-left font-semibold text-gray-900">{{ $t('Arrival date') }}</th>
                        <th scope="col" class="px-3 py-2 text-left font-semibold text-gray-900">{{ $t('Date departure') }}</th>
                        <th scope="col" class="px-3 py-2 text-left font-semibold text-gray-900">{{ $t('Accommodation') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="residency in residencies" :key="residency.id">
                        <td class="whitespace-nowrap py-2 pr-3 text-gray-900">{{ residency.artist_name || '-' }}</td>
                        <td class="whitespace-nowrap px-3 py-2 text-gray-500">{{ residency.position || '-' }}</td>
                        <td class="whitespace-nowrap px-3 py-2 text-gray-500">{{ formatDate(residency.arrival_date) }}</td>
                        <td class="whitespace-nowrap px-3 py-2 text-gray-500">{{ formatDate(residency.departure_date) }}</td>
                        <td class="whitespace-nowrap px-3 py-2 text-gray-500">{{ residency.accommodation?.name || '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-else class="text-sm text-secondary">
            {{ $t('No entries') }}
        </div>
    </div>
</template>
<script setup>
import { computed } from 'vue'

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    component: {
        type: Object,
        required: false,
    },
})

const residencies = computed(() => props.project?.artist_residencies || [])

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
}
</script>
<style scoped>
</style>
