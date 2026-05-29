<template>
    <div class="rounded-2xl border border-zinc-200 bg-white p-5">
        <label :for="inputId" class="block text-sm font-medium text-zinc-700 mb-1">
            {{ label }}
        </label>

        <input
            :id="inputId"
            v-model="url"
            type="url"
            :placeholder="schema.placeholder || 'https://…'"
            :disabled="!editable"
            class="block w-full rounded-lg border-zinc-300 text-sm disabled:bg-zinc-50 disabled:text-zinc-500"
            @blur="saveIfChanged"
        />

        <a
            v-if="!editable && url"
            :href="url"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-2 inline-block text-sm text-artwork-buttons-create underline break-all"
        >
            {{ url }}
        </a>

        <p v-if="status === 'saved'" class="mt-1 text-xs text-emerald-600">{{ $t('Saved') }}</p>
        <p v-if="status === 'error'" class="mt-1 text-xs text-red-600">{{ $t('Could not save. Try again.') }}</p>
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
