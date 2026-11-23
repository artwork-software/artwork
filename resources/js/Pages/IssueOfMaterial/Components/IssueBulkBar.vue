<template>
    <!-- Leiste für die Mehrfachauswahl in den Materialausgabe-Listen -->
    <div v-if="count > 0" class="pointer-events-none fixed inset-x-0 bottom-6 z-40 flex justify-center px-4">
        <div class="pointer-events-auto flex flex-wrap items-center gap-x-3 gap-y-2 rounded-xl bg-surface-inverse py-2.5 pl-4 pr-2 text-sm text-white shadow-lg">
            <span class="text-white/70">{{ $t('{count} selected', { count }) }}</span>
            <button type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-white/15 px-3 py-1 font-semibold hover:bg-white/25" @click="$emit('print')">
                <IconPrinter class="size-4" />
                {{ $t('Print') }}
            </button>
            <button type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-white/15 px-3 py-1 font-semibold hover:bg-white/25" @click="$emit('export')">
                <IconFileSpreadsheet class="size-4" />
                {{ $t('Export') }}
            </button>
            <button type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-danger px-3 py-1 font-semibold hover:opacity-90 disabled:opacity-50"
                    :disabled="processing" @click="$emit('delete')">
                <IconTrash class="size-4" />
                {{ $t('Delete') }}
            </button>
            <button type="button" class="rounded-lg p-1.5 hover:bg-white/15" :title="$t('Clear selection')" :aria-label="$t('Clear selection')" @click="$emit('clear')">
                <IconX class="size-4" />
            </button>
        </div>
    </div>
</template>

<script setup>
import { IconFileSpreadsheet, IconPrinter, IconTrash, IconX } from '@tabler/icons-vue'

defineOptions({ name: 'IssueBulkBar' })

defineProps({
    count: { type: Number, default: 0 },
    processing: { type: Boolean, default: false },
})

defineEmits(['print', 'export', 'delete', 'clear'])
</script>
