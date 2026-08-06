<template>
    <div class="rounded-2xl border border-border-subtle bg-white p-5">
        <label :for="inputId" class="block text-sm font-medium text-text-muted mb-1">
            {{ label }}
        </label>

        <select
            :id="inputId"
            v-model="selected"
            :disabled="!editable"
            class="block w-full rounded-lg border-border text-sm disabled:bg-surface-sunken disabled:text-text-subtle"
            @change="saveChange"
        >
            <option value="">{{ $t('Please select') }}</option>
            <option v-for="(opt, i) in options" :key="i" :value="optionValue(opt)">
                {{ optionValue(opt) }}
            </option>
        </select>

        <p v-if="status === 'saved'" class="mt-1 text-xs text-success">{{ $t('Saved') }}</p>
        <p v-if="status === 'error'" class="mt-1 text-xs text-danger">{{ $t('Could not save. Try again.') }}</p>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useExternalComponentSave } from '../useExternalComponentSave.js'

const props = defineProps({
    component: { type: Object, required: true },
    projectId: { type: Number, required: true },
    tabId: { type: Number, required: true },
    scope: { type: Object, required: true },
})

const schema = computed(() => ({ label: '', options: [], selected: '', ...(props.component.data_schema || {}) }))
const label = computed(() => schema.value.label || props.component.name)
const options = computed(() => schema.value.options || [])
const editable = computed(() => props.component.is_writable && props.scope.access_type === 'write')
const inputId = computed(() => `comp-${props.component.component_id}`)

function optionValue(opt) {
    return typeof opt === 'object' && opt !== null ? opt.value : opt
}

const selected = ref(props.component.value?.selected ?? schema.value.selected ?? '')

const { status, save } = useExternalComponentSave(props.projectId, props.tabId, props.component.component_id)

watch(() => props.component.value?.selected, (newSelected) => {
    if (newSelected !== undefined) {
        selected.value = newSelected
    }
})

function saveChange() {
    if (!editable.value) return
    save({ selected: selected.value })
}
</script>
