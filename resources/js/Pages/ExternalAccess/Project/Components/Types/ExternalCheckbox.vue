<template>
    <div class="rounded-2xl border border-border-subtle bg-white p-5">
        <label :for="inputId" class="inline-flex items-center gap-3 text-sm font-medium text-text-muted">
            <input
                :id="inputId"
                v-model="checked"
                type="checkbox"
                :disabled="!editable"
                class="rounded border-border disabled:text-text-subtle disabled:cursor-not-allowed"
                @change="saveChange"
            />
            <span>{{ label }}</span>
        </label>

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

const schema = computed(() => ({ label: '', checked: false, ...(props.component.data_schema || {}) }))
const label = computed(() => schema.value.label || props.component.name)
const editable = computed(() => props.component.is_writable && props.scope.access_type === 'write')
const inputId = computed(() => `comp-${props.component.component_id}`)

const checked = ref(Boolean(props.component.value?.checked ?? schema.value.checked))

const { status, save } = useExternalComponentSave(props.projectId, props.tabId, props.component.component_id)

watch(() => props.component.value?.checked, (newChecked) => {
    if (newChecked !== undefined) {
        checked.value = Boolean(newChecked)
    }
})

function saveChange() {
    if (!editable.value) return
    save({ checked: checked.value })
}
</script>
