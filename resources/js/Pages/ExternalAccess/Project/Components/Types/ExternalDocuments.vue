<template>
    <div class="rounded-2xl border border-border-subtle bg-white p-5">
        <div class="flex items-center justify-between mb-3">
            <div>
                <label class="block text-sm font-medium text-text-muted">{{ label }}</label>
                <p v-if="editable" class="text-xs text-text-subtle mt-0.5">
                    {{ $t('Upload documents such as PDFs, images or logos. You can remove your own uploads.') }}
                </p>
            </div>
        </div>

        <div v-if="editable" class="mb-4">
            <label
                class="flex cursor-pointer items-center justify-center rounded-xl border-2 border-dashed border-border px-4 py-6 text-sm text-text-muted hover:border-border-strong"
                :class="uploading ? 'opacity-60 cursor-progress' : ''"
            >
                <span v-if="!uploading">{{ $t('Click to select files to upload') }}</span>
                <span v-else>{{ $t('Uploading...') }} ({{ uploadedCount }}/{{ totalToUpload }})</span>
                <input type="file" multiple class="sr-only" :disabled="uploading" @change="onFilesChosen" />
            </label>
            <p v-if="uploadError" class="mt-1 text-xs text-danger">{{ uploadError }}</p>
        </div>

        <p v-if="loading" class="text-xs text-text-subtle">{{ $t('Loading data...') }}</p>
        <p v-else-if="loadError" class="text-xs text-danger">{{ loadError }}</p>
        <div v-else-if="documents.length === 0" class="text-xs text-text-subtle">
            {{ $t('No files available') }}
        </div>
        <ul v-else class="divide-y divide-border-subtle rounded-xl border border-border-subtle">
            <li v-for="file in documents" :key="file.id" class="flex items-center justify-between gap-3 px-3 py-2">
                <div class="min-w-0">
                    <div class="truncate text-sm font-medium text-text">{{ file.name }}</div>
                    <div class="text-xs text-text-subtle">
                        <span v-if="file.file_size">{{ file.file_size }}</span>
                        <span v-if="file.created_at"> · {{ formatDate(file.created_at) }}</span>
                        <span v-if="file.uploaded_by_me"> · {{ $t('uploaded by you') }}</span>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <a
                        v-if="file.storage_available !== false"
                        :href="downloadUrl(file)"
                        target="_blank"
                        rel="noopener"
                        class="rounded-lg px-2 py-1 text-xs font-medium text-text ring-1 ring-inset ring-border"
                    >
                        {{ $t('Download') }}
                    </a>
                    <button
                        v-if="editable && file.uploaded_by_me"
                        type="button"
                        class="rounded-lg px-2 py-1 text-xs font-medium text-danger ring-1 ring-inset ring-danger-border"
                        @click="removeFile(file)"
                    >
                        {{ $t('Remove') }}
                    </button>
                </div>
            </li>
        </ul>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import { useTranslation } from '@/Composeables/Translation.js'

const props = defineProps({
    component: { type: Object, required: true },
    projectId: { type: Number, required: true },
    tabId: { type: Number, required: true },
    scope: { type: Object, required: true },
})

const $t = useTranslation()

const label = computed(() => props.component.name || $t('Documents'))
const editable = computed(() => props.component.is_writable && props.scope.access_type === 'write')

const documents = ref([])
const loading = ref(true)
const loadError = ref('')
const uploading = ref(false)
const uploadedCount = ref(0)
const totalToUpload = ref(0)
const uploadError = ref('')

const routeParams = () => ({ project: props.projectId, tab: props.tabId, component: props.component.component_id })

async function fetchDocuments() {
    loading.value = true
    loadError.value = ''
    try {
        const { data } = await axios.get(route('external.project.tab.documents.index', routeParams()))
        documents.value = data.documents ?? []
    } catch (e) {
        loadError.value = $t('Could not load documents.')
    } finally {
        loading.value = false
    }
}

async function onFilesChosen(event) {
    const files = Array.from(event.target.files ?? [])
    event.target.value = ''
    if (!files.length || !editable.value) return

    uploadError.value = ''
    uploading.value = true
    uploadedCount.value = 0
    totalToUpload.value = files.length
    for (const file of files) {
        const formData = new FormData()
        formData.append('file', file)
        try {
            await axios.post(route('external.project.tab.documents.store', routeParams()), formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
            uploadedCount.value++
        } catch (error) {
            const errors = error?.response?.data?.errors
            uploadError.value = errors
                ? Object.values(errors).flat().filter(Boolean).join(' ')
                : (error?.response?.data?.message ?? $t('Could not save. Try again.'))
        }
    }
    uploading.value = false
    await fetchDocuments()
}

async function removeFile(file) {
    if (!window.confirm($t('Remove') + ` „${file.name}“?`)) return
    try {
        await axios.delete(route('external.project.tab.documents.destroy', { project: props.projectId, tab: props.tabId, file: file.id }))
        documents.value = documents.value.filter((d) => d.id !== file.id)
    } catch (e) {
        uploadError.value = $t('Could not save. Try again.')
    }
}

function downloadUrl(file) {
    return route('external.project.tab.documents.download', { project: props.projectId, tab: props.tabId, file: file.id })
}

function formatDate(iso) {
    return iso ? new Date(iso).toLocaleDateString() : ''
}

onMounted(fetchDocuments)
</script>
