<template>
    <div class="space-y-2">
        <div v-if="contracts.length > 0" class="space-y-1.5">
            <div v-for="contract in contracts" :key="contract.id" class="text-sm text-primaryText border-b border-border-subtle pb-1">
                <div class="font-medium">{{ contract.name || '-' }}</div>
                <div v-if="contract.contract_type" class="text-xs text-text-subtle">{{ contract.contract_type.name }}</div>
            </div>
        </div>
        <div v-else class="text-sm text-text-subtle">
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
        required: true,
    },
})

const contracts = computed(() => props.project.contracts_documents || [])
</script>

<style scoped>
</style>
