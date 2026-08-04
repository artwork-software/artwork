<template>
    <div class="rounded-2xl border border-border-subtle bg-white p-5">
        <label :for="inputId" class="block text-sm font-medium text-text-muted mb-1">
            {{ label }}
        </label>

        <input
            :id="inputId"
            v-model="url"
            type="url"
            :placeholder="schema.placeholder || 'https://…'"
            :disabled="!editable"
            class="block w-full rounded-lg border-border text-sm disabled:bg-surface-sunken disabled:text-text-subtle"
            @blur="saveIfChanged"
        />

        <a
            v-if="!editable && url"
            :href="url"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-2 inline-block text-sm text-accent-600 underline break-all"
        >
            {{ url }}
        </a>

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

const initial = props.component.value?.text ?? schema.value.text ?? ''
const url = ref(initial)
const lastSaved = ref(initial)

const { status, save } = useExternalComponentSave(props.projectId, props.tabId, props.component.component_id)

watch(() => props.component.value?.text, (newText) => {
    if (newText !== undefined && newText !== url.value) {
        url.value = newText
        lastSaved.value = newText
    }
})

async function saveIfChanged() {
    if (!editable.value || url.value === lastSaved.value) return
    if (await save({ text: url.value })) {
        lastSaved.value = url.value
    }
}
</script>
