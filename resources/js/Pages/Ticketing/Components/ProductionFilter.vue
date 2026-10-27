<template>
    <Popover class="relative">
        <PopoverButton class="inline-flex h-8 items-center gap-1.5 rounded-md border bg-surface pl-2.5 pr-2 text-xs text-text transition-colors hover:bg-surface-sunken focus:outline-none"
                       :class="modelValue.length ? 'border-accent-600/50' : 'border-border'"
                       @click="startEditing">
            <IconMasksTheater class="size-3.5 text-text-subtle" />
            <span class="max-w-[220px] truncate">{{ summary }}</span>
            <IconChevronDown class="size-3.5 text-text-subtle" />
        </PopoverButton>

        <transition enter-active-class="transition duration-100 ease-out" enter-from-class="opacity-0 -translate-y-1"
                    leave-active-class="transition duration-75 ease-in" leave-to-class="opacity-0">
            <PopoverPanel v-slot="{ close }" class="absolute right-0 z-50 mt-1.5 flex w-80 flex-col rounded-xl border border-border-subtle bg-surface shadow-overlay">
                <div class="relative border-b border-border-hairline p-2">
                    <IconSearch class="pointer-events-none absolute left-4 top-1/2 size-4 -translate-y-1/2 text-text-subtle" />
                    <input v-model="query" type="search" :placeholder="$t('Search productions')" :aria-label="$t('Search productions')" autofocus
                           class="h-8 w-full rounded-md border border-border bg-surface pl-8 pr-2.5 text-[13px] text-text focus:ring-0"
                           @keydown.enter.prevent="apply(close)" />
                </div>

                <p v-if="visible.length === 0" class="px-4 py-6 text-center text-[13px] text-text-subtle">{{ $t('No results') }}</p>
                <ul v-else class="max-h-72 overflow-y-auto p-1.5">
                    <li v-for="production in visible" :key="production.id" class="rounded-md px-2 py-1.5 hover:bg-surface-sunken">
                        <BaseCheckbox :id="`production-filter-${production.id}`" :model-value="draft.includes(production.id)"
                                      :label="production.title" :description="statusLabel[production.status]"
                                      @update:model-value="(checked) => toggle(production.id, checked)" />
                    </li>
                </ul>

                <div class="flex items-center justify-between gap-2 border-t border-border-hairline px-3 py-2.5">
                    <button type="button" class="text-xs text-text-subtle hover:text-text disabled:opacity-50" :disabled="draft.length === 0" @click="draft = []">
                        {{ $t('Clear selection') }}
                    </button>
                    <button type="button" class="ui-button-add-small" @click="apply(close)">{{ $t('Apply') }}</button>
                </div>
            </PopoverPanel>
        </transition>
    </Popover>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
import { useI18n } from 'vue-i18n'
import { IconChevronDown, IconMasksTheater, IconSearch } from '@tabler/icons-vue'
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue'

/* Several productions to narrow the sales to; the choice is staged and only applied on "Anwenden",
   so ticking five boxes asks Artwork-Tickets once rather than five times. */
const props = defineProps({
    modelValue: { type: Array, required: true },
    productions: { type: Array, required: true },
})

const emit = defineEmits(['update:modelValue'])

const { t } = useI18n()

const statusLabel = { draft: t('Draft'), archived: t('Archived') }

const query = ref('')
const draft = ref([])
const order = ref([])

const summary = computed(() => {
    const titles = props.productions.filter((production) => props.modelValue.includes(production.id)).map((production) => production.title)
    if (titles.length === 0) return t('All productions')
    return titles.length === 1 ? titles[0] : t('{count} productions', { count: titles.length }, titles.length)
})

const visible = computed(() => {
    const needle = query.value.trim().toLocaleLowerCase()
    return order.value.filter((production) => production.title.toLocaleLowerCase().includes(needle))
})

/** Opening starts from what is applied, with the chosen productions on top — sorted once, so rows do not jump while ticking. */
function startEditing() {
    query.value = ''
    draft.value = [...props.modelValue]
    order.value = [
        ...props.productions.filter((production) => props.modelValue.includes(production.id)),
        ...props.productions.filter((production) => !props.modelValue.includes(production.id)),
    ]
}

function toggle(id, checked) {
    draft.value = checked ? [...draft.value, id] : draft.value.filter((candidate) => candidate !== id)
}

function apply(close) {
    close()
    const changed = draft.value.length !== props.modelValue.length || draft.value.some((id) => !props.modelValue.includes(id))
    if (changed) emit('update:modelValue', draft.value)
}
</script>
