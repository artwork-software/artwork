<template>
    <draggable
        :model-value="rows"
        item-key="uid"
        handle=".sortable-list-handle"
        ghost-class="opacity-50"
        :class="listClass"
        @start="dragging = true"
        @end="dragging = false"
        @update:model-value="applyOrder"
    >
        <template #item="{ element: row }">
            <div :class="itemClass">
                <div class="flex gap-2" :class="labeled ? 'items-stretch' : 'items-center'">
                    <!-- bei Eingabefeldern mit Label: Griff und Pfeile mittig zum Feld, nicht zum Label -->
                    <div class="flex shrink-0 items-center" :class="{ 'pt-5': labeled }">
                        <IconGripVertical
                            class="sortable-list-handle size-4 text-text-subtle hover:text-text-muted"
                            :class="dragging ? 'cursor-grabbing' : 'cursor-grab'"
                            stroke-width="1.5"
                            :title="$t('Drag to reorder')"
                            :aria-label="$t('Drag to reorder')"
                        />
                    </div>
                    <div class="min-w-0 flex-1">
                        <slot :index="row.index" :item="modelValue[row.index]" :remove="() => remove(row.index)" />
                    </div>
                    <div class="flex shrink-0 items-center" :class="{ 'pt-5': labeled }">
                        <button
                            type="button"
                            class="rounded p-1 text-text-subtle hover:text-text-muted disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="row.index === 0"
                            :title="$t('Move up')"
                            :aria-label="$t('Move up')"
                            @click="move(row.index, -1)"
                        >
                            <IconChevronUp class="size-4" stroke-width="1.5" />
                        </button>
                        <button
                            type="button"
                            class="rounded p-1 text-text-subtle hover:text-text-muted disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="row.index === modelValue.length - 1"
                            :title="$t('Move down')"
                            :aria-label="$t('Move down')"
                            @click="move(row.index, 1)"
                        >
                            <IconChevronDown class="size-4" stroke-width="1.5" />
                        </button>
                        <slot name="actions" :index="row.index" :item="modelValue[row.index]" :remove="() => remove(row.index)" />
                    </div>
                </div>
                <slot name="below" :index="row.index" :item="modelValue[row.index]" :remove="() => remove(row.index)" />
            </div>
        </template>
    </draggable>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import draggable from 'vuedraggable'
import { IconChevronDown, IconChevronUp, IconGripVertical } from '@tabler/icons-vue'
import { useTranslation } from '@/Composeables/Translation.js'

/**
 * Sortierbare Liste (Ziehen am Griff oder Hoch/Runter) für Arrays ohne eigene IDs, z. B. Auswahloptionen.
 * Reihenfolge und Entfernen gehen als neues Array über v-model zurück; Inhalte rendert der Default-Slot.
 */
defineOptions({ name: 'BaseSortableList' })

const props = defineProps({
    modelValue: { type: Array, required: true },
    listClass: { type: [String, Array, Object], default: 'space-y-2' },
    itemClass: { type: [String, Array, Object], default: '' },
    // Inhalt ist ein Eingabefeld mit Label darüber (BaseInput)
    labeled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const $t = useTranslation()
const dragging = ref(false)

// Stabile Schlüssel je Eintrag, damit Eingabefelder beim Umsortieren mitwandern
let nextUid = 0
const uids = ref(props.modelValue.map(() => nextUid++))
watch(() => props.modelValue.length, (length) => {
    while (uids.value.length < length) uids.value.push(nextUid++)
    if (uids.value.length > length) uids.value = uids.value.slice(0, length)
})

const rows = computed(() => props.modelValue.map((_, index) => ({ uid: uids.value[index], index })))

function applyOrder(newRows) {
    uids.value = newRows.map((row) => row.uid)
    emit('update:modelValue', newRows.map((row) => props.modelValue[row.index]))
}

function remove(index) {
    uids.value = uids.value.filter((_, i) => i !== index)
    emit('update:modelValue', props.modelValue.filter((_, i) => i !== index))
}

function move(index, direction) {
    const target = index + direction
    if (target < 0 || target >= props.modelValue.length) return
    const order = rows.value.slice()
    const [row] = order.splice(index, 1)
    order.splice(target, 0, row)
    applyOrder(order)
}
</script>
