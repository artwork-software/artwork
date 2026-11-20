<template>
    <!-- Leiste für die Mehrfachauswahl von Räumen -->
    <div v-if="count > 0" class="pointer-events-none fixed inset-x-0 bottom-6 z-40 flex justify-center px-4">
        <div class="pointer-events-auto flex flex-wrap items-center gap-x-3 gap-y-2 rounded-xl bg-surface-inverse py-2.5 pl-4 pr-2 text-sm text-white shadow-lg">
            <span class="text-white/70 tabular-nums">{{ $t('{count} selected', { count }) }}</span>
            <BaseMenu :show-icon="false" white-icon white-menu-background show-menu-button-text
                      menu-button-text="Room categories" placement="top-start" menu-width="w-64" :classes="menuClass">
                <BaseMenuItem :icon="IconPlus" title="Assign categories" white-menu-background @click="$emit('edit', 'add_categories')" />
                <BaseMenuItem :icon="IconMinus" title="Remove categories" white-menu-background @click="$emit('edit', 'remove_categories')" />
            </BaseMenu>
            <BaseMenu :show-icon="false" white-icon white-menu-background show-menu-button-text
                      menu-button-text="Room properties" placement="top-start" menu-width="w-64" :classes="menuClass">
                <BaseMenuItem :icon="IconPlus" title="Assign properties" white-menu-background @click="$emit('edit', 'add_attributes')" />
                <BaseMenuItem :icon="IconMinus" title="Remove properties" white-menu-background @click="$emit('edit', 'remove_attributes')" />
            </BaseMenu>
            <button type="button" :class="buttonClass" @click="$emit('edit', 'move')">
                <IconArrowsExchange class="size-4" />
                {{ $t('Move to another area') }}
            </button>
            <button type="button" :class="buttonClass" @click="$emit('export')">
                <IconFileSpreadsheet class="size-4" />
                {{ $t('Export') }}
            </button>
            <button type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-danger px-3 py-1 font-semibold hover:opacity-90 disabled:opacity-50"
                    :disabled="processing" @click="$emit('delete')">
                <IconTrash class="size-4" />
                {{ $t('In the recycle bin') }}
            </button>
            <button type="button" class="rounded-lg p-1.5 hover:bg-white/15" :title="$t('Clear selection')" :aria-label="$t('Clear selection')" @click="$emit('clear')">
                <IconX class="size-4" />
            </button>
        </div>
    </div>
</template>

<script setup>
import {
    IconArrowsExchange,
    IconFileSpreadsheet,
    IconMinus,
    IconPlus,
    IconTrash,
    IconX,
} from '@tabler/icons-vue'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import BaseMenuItem from '@/Components/Menu/BaseMenuItem.vue'

defineOptions({ name: 'RoomBulkBar' })

defineProps({
    count: { type: Number, default: 0 },
    processing: { type: Boolean, default: false },
})

defineEmits(['edit', 'export', 'delete', 'clear'])

const menuClass = 'rounded-lg bg-white/15 px-3 py-1 hover:bg-white/25'
const buttonClass = 'inline-flex items-center gap-1.5 rounded-lg bg-white/15 px-3 py-1 font-semibold hover:bg-white/25'
</script>
