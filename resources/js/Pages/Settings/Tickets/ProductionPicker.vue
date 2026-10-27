<template>
    <div class="rounded-md border border-border-subtle">
        <div class="relative border-b border-border-hairline">
            <IconSearch class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-text-subtle" />
            <input v-model="search" type="search" :placeholder="$t('Search productions')" :aria-label="$t('Search productions')"
                   class="h-9 w-full rounded-t-md border-0 bg-surface pl-8 pr-3 text-sm text-text focus:ring-0" />
        </div>
        <ul v-if="shown.length > 0" class="max-h-56 overflow-y-auto divide-y divide-border-hairline">
            <li v-for="production in shown" :key="production.id" class="flex items-center justify-between gap-3 px-3 py-2">
                <BaseCheckbox :id="`${idPrefix}-${production.id}`" :label="production.title"
                              :disabled="fixedIds.includes(production.id) || production.id in coveredBy"
                              :model-value="modelValue.includes(production.id) || production.id in coveredBy"
                              @update:model-value="(on) => pick(production.id, on)" />
                <span v-if="production.id in coveredBy" class="shrink-0 text-xs text-text-subtle">
                    {{ $t('via {groups}', { groups: coveredBy[production.id] }) }}
                </span>
            </li>
        </ul>
        <p v-else class="px-3 py-4 text-center text-xs text-text-subtle">{{ $t('No results') }}</p>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { IconSearch } from '@tabler/icons-vue'
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue'

/* The productions of Artwork-Tickets as a searchable checklist. Picked ones come first, as they
   were when the picker opened, so ticking one doesn't make it jump. */
const props = defineProps({
    productions: { type: Array, required: true },
    modelValue: { type: Array, required: true },
    /** Picks that can't be taken off again (something of it is sold). */
    fixedIds: { type: Array, default: () => [] },
    /** Productions already counted through a group: id → the group names, shown ticked and locked. */
    coveredBy: { type: Object, default: () => ({}) },
    idPrefix: { type: String, required: true },
})

const emit = defineEmits(['update:modelValue'])

const search = ref('')
const pickedOnOpen = new Set(props.modelValue)
const ordered = [...props.productions].sort((a, b) => Number(pickedOnOpen.has(b.id)) - Number(pickedOnOpen.has(a.id)))
const shown = computed(() => {
    const needle = search.value.trim().toLocaleLowerCase()
    return needle === '' ? ordered : ordered.filter((production) => production.title.toLocaleLowerCase().includes(needle))
})

function pick(id, on) {
    emit('update:modelValue', on ? [...props.modelValue, id] : props.modelValue.filter((picked) => picked !== id))
}
</script>
