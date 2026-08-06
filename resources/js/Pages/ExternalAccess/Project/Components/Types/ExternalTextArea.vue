<template>
    <div class="rounded-2xl border border-border-subtle bg-white p-5">
        <label :for="inputId" class="block text-sm font-medium text-text-muted mb-1">
            {{ label }}
        </label>

        <textarea
            :id="inputId"
            v-model="text"
            rows="4"
            :placeholder="schema.placeholder"
            :disabled="!editable"
            class="block w-full rounded-lg border-border text-sm disabled:bg-surface-sunken disabled:text-text-subtle"
            @blur="saveIfChanged"
        />

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

const schema = computed(() => ({ label: '', placeholder: '', text: '', ...(props.component.data_schema || {}) }))
const label = computed(() => schema.value.label || props.component.name)
const editable = computed(() => props.component.is_writable && props.scope.access_type === 'write')
const inputId = computed(() => `comp-${props.component.component_id}`)

// The internal flow stores text with nl2br applied; show it with breaks turned
// back into newlines so the textarea stays editable.
function fromStored(value) {
    return (value ?? '').replace(/<br\s*\/?>/gi, '\n')
}

const initial = fromStored(props.component.value?.text ?? schema.value.text)
const text = ref(initial)
const lastSaved = ref(initial)

const { status, save } = useExternalComponentSave(props.projectId, props.tabId, props.component.component_id)

watch(() => props.component.value?.text, (newText) => {
    if (newText !== undefined) {
        const normalized = fromStored(newText)
        if (normalized !== text.value) {
            text.value = normalized
            lastSaved.value = normalized
        }
    }
})

async function saveIfChanged() {
    if (!editable.value || text.value === lastSaved.value) return
    if (await save({ text: text.value })) {
        lastSaved.value = text.value
    }
}
</script>
