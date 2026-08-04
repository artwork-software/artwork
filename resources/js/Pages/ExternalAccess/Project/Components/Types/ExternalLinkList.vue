<template>
    <div class="rounded-2xl border border-border-subtle bg-white p-5">
        <div class="flex items-center justify-between mb-3">
            <label class="block text-sm font-medium text-text-muted">{{ label }}</label>
            <button
                v-if="editable && links.length < maxItems"
                type="button"
                class="text-xs font-medium text-artwork-buttons-create"
                @click="addLink"
            >
                + {{ $t('Add') }}
            </button>
        </div>

        <div v-if="links.length === 0" class="text-xs text-text-subtle">
            {{ $t('No content') }}
        </div>

        <ul class="space-y-3">
            <li v-for="(link, index) in links" :key="index" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <input
                    v-model="link.label"
                    type="text"
                    :placeholder="schema.placeholder_label"
                    :disabled="!editable"
                    class="block w-full rounded-lg border-border text-sm disabled:bg-surface-sunken disabled:text-text-subtle"
                    @blur="saveLinks"
                />
                <input
                    v-model="link.url"
                    type="url"
                    :placeholder="schema.placeholder_url"
                    :disabled="!editable"
                    class="block w-full rounded-lg border-border text-sm disabled:bg-surface-sunken disabled:text-text-subtle"
                    @blur="saveLinks"
                />
                <button
                    v-if="editable"
                    type="button"
                    class="shrink-0 text-xs text-danger"
                    @click="removeLink(index)"
                >
                    {{ $t('Remove') }}
                </button>
            </li>
        </ul>

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

const schema = computed(() => ({
    label: '',
    placeholder_label: '',
    placeholder_url: 'https://…',
    max_items: 20,
    ...(props.component.data_schema || {}),
}))
const label = computed(() => schema.value.label || props.component.name)
const maxItems = computed(() => Number(schema.value.max_items) || 20)
const editable = computed(() => props.component.is_writable && props.scope.access_type === 'write')

function normalize(raw) {
    if (!Array.isArray(raw)) return []
    return raw.map((l) => ({ label: l?.label ?? '', url: l?.url ?? '' }))
}

const links = ref(normalize(props.component.value?.links))

const { status, save } = useExternalComponentSave(props.projectId, props.tabId, props.component.component_id)

watch(() => props.component.value?.links, (newLinks) => {
    if (newLinks !== undefined) {
        links.value = normalize(newLinks)
    }
})

function addLink() {
    if (links.value.length >= maxItems.value) return
    links.value.push({ label: '', url: '' })
}

function removeLink(index) {
    links.value.splice(index, 1)
    saveLinks()
}

function saveLinks() {
    if (!editable.value) return
    save({ links: links.value })
}
</script>
