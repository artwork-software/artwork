<template>
    <fieldset>
        <legend class="block text-sm font-semibold text-text mb-1">
            {{ label }}
        </legend>

        <div class="space-y-1.5">
            <label
                v-for="(option, index) in options"
                :key="option"
                :for="`${inputId}-${index}`"
                class="flex items-start gap-2 text-sm text-text"
            >
                <input
                    :id="`${inputId}-${index}`"
                    type="checkbox"
                    class="input-checklist mt-0.5"
                    :checked="selected.includes(option)"
                    :disabled="!editable"
                    @change="toggle(option, $event.target.checked)"
                />
                <span class="break-words">{{ option }}</span>
            </label>
        </div>

        <p v-if="status === 'saved'" class="mt-1 text-xs text-success">{{ $t('Saved') }}</p>
        <p v-if="status === 'error'" class="mt-1 text-xs text-danger">{{ $t('Could not save. Try again.') }}</p>
    </fieldset>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useExternalComponentSave } from '../useExternalComponentSave.js'
import { selectedValues, selectionOptions } from '@/Helper/ComponentSelection.js'

// Mehrfachauswahl: CheckboxList und DropDown mit "multiple" (extern einheitlich als Checkboxen)
const props = defineProps({
    component: { type: Object, required: true },
    projectId: { type: Number, required: true },
    tabId: { type: Number, required: true },
    scope: { type: Object, required: true },
})

const schema = computed(() => props.component.data_schema || {})
const label = computed(() => schema.value.label || props.component.name)
const options = computed(() => selectionOptions(schema.value))
const editable = computed(() => props.component.is_writable && props.scope.access_type === 'write')
const inputId = computed(() => `comp-${props.component.component_id}`)

const selected = ref(selectedValues(props.component.value ? props.component.value.selected : schema.value.selected))

const { status, save } = useExternalComponentSave(props.projectId, props.tabId, props.component.component_id)

watch(() => props.component.value?.selected, (newSelected) => {
    if (newSelected !== undefined) {
        selected.value = selectedValues(newSelected)
    }
})

function toggle(option, checked) {
    if (!editable.value) return
    const next = checked ? [...selected.value, option] : selected.value.filter((value) => value !== option)
    selected.value = options.value.filter((value) => next.includes(value))
    save({ selected: selected.value })
}
</script>
