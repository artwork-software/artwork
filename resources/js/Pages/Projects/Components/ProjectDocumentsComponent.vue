<script setup lang="ts">
import { ref, reactive, computed, onMounted, getCurrentInstance, onBeforeUnmount, watch } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import axios from 'axios'
import TinyPageHeadline from '@/Components/Headlines/TinyPageHeadline.vue'
import MultiAlertComponent from '@/Components/Alerts/MultiAlertComponent.vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'
import InfoButtonComponent from '@/Pages/Projects/Tab/Components/InfoButtonComponent.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import { useProjectDocumentListener } from '@/Composeables/Listener/useProjectDocumentListener.js'
import { VuePDF, usePDF } from '@tato30/vue-pdf'
import FilePreview from "@/Artwork/Files/FilePreview.vue";
import { IconFileUpload, IconFileText } from '@tabler/icons-vue'
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";

interface ProjectFile {
    id?: number | string
    name: string
    file_size?: string
    created_at?: string
    mime_type?: string | null
    url?: string | null
}

const props = defineProps<{
    project: { id: number | string; project_files_tab?: ProjectFile[] }
    projectWriteIds?: Array<number | string>
    projectManagerIds?: Array<number | string>
    tab_id?: number | string | null
    canEditComponent?: boolean
    component?: any
}>()

const initialDocuments = props.project?.project_files_tab ?? []
const documents = ref<ProjectFile[]>([...initialDocuments])
const documentForm = reactive<{ errors: Record<string, string[] | string> }>({ errors: {} })
const uploadDocumentFeedback = ref('')
const deletingFile = ref(false)
const selectedFile = ref<ProjectFile | null>(null)
const isUploading = ref(false)
const uploadedCount = ref(0)
const totalToUpload = ref(0)
const isDragging = ref(false)
const fileInputEl = ref<HTMLInputElement | null>(null)

const page = usePage()
const userId = computed(() => (page.props as any)?.auth?.user?.id ?? null)
const { appContext } = getCurrentInstance()!
const $role = (appContext?.config?.globalProperties as any)?.$role as ((name: string) => boolean) | undefined
const remoteProjectWriteIds = ref<Array<number | string>>(props.projectWriteIds ?? [])
const remoteProjectManagerIds = ref<Array<number | string>>(props.projectManagerIds ?? [])
const effectiveProjectWriteIds = computed(
    () => (remoteProjectWriteIds.value.length ? remoteProjectWriteIds.value : (props.projectWriteIds ?? []))
)
const effectiveProjectManagerIds = computed(
    () => (remoteProjectManagerIds.value.length ? remoteProjectManagerIds.value : (props.projectManagerIds ?? []))
)

const canEdit = computed(() =>
    !!props.canEditComponent ||
    !!$role?.('artwork admin') ||
    (effectiveProjectWriteIds.value?.includes(userId.value ?? -1) ?? false)
)
const canEditFull = computed(() =>
    canEdit.value || (effectiveProjectManagerIds.value?.includes(userId.value ?? -1) ?? false)
)

const isLoadingDocuments = ref(false)
const loadDocumentsError = ref('')

onMounted(() => {
    if (props.project?.id) {
        useProjectDocumentListener(documents.value, props.project.id).init()
    }

    window.addEventListener('keydown', onKey);
})

watch(
    () => [props.project?.id, props.component?.id],
    ([projectId, componentId]) => {
        // Only fetch when we have both a valid project and component ID
        if (projectId && componentId) {
            fetchDocuments()
        }
    },
    { immediate: true }
)

async function fetchDocuments() {
    const projectId = props.project?.id
    const componentInTabId = props.component?.id ?? props.component?.component_in_tab_id

    if (!projectId || !componentInTabId) {
        return
    }

    isLoadingDocuments.value = true
    loadDocumentsError.value = ''

    try {
        const { data } = await axios.get(
            route('projects.tabs.documents', { project: projectId, componentInTab: componentInTabId })
        )
        const fetchedDocuments = data?.documents ?? []
        documents.value.splice(0, documents.value.length, ...fetchedDocuments)

        if (Array.isArray(data?.projectWriteIds)) {
            remoteProjectWriteIds.value = data.projectWriteIds
        }

        if (Array.isArray(data?.projectManagerIds)) {
            remoteProjectManagerIds.value = data.projectManagerIds
        }
    } catch (error) {
        console.error(error)
        loadDocumentsError.value = (page.props as any)?.errors?.documents
            ?? 'Unable to load project documents.'
    } finally {
        isLoadingDocuments.value = false
    }
}

function selectNewFiles() { fileInputEl.value?.click() }
async function uploadChosenDocuments(e: Event) {
    const files = Array.from((e.target as HTMLInputElement).files ?? [])
    await validateTypeAndUpload(files)
    if (fileInputEl.value) fileInputEl.value.value = ''
}
async function onDrop(e: DragEvent) {
    isDragging.value = false
    const files = Array.from(e.dataTransfer?.files ?? [])
    await validateTypeAndUpload(files)
}
function onDragEnter() { isDragging.value = true }
function onDragOver()  { isDragging.value = true }
function onDragLeave() { isDragging.value = false }

async function validateTypeAndUpload(files: File[]) {
    uploadDocumentFeedback.value = ''
    documentForm.errors = {}
    if (!files.length) return
    isUploading.value = true
    uploadedCount.value = 0
    totalToUpload.value = files.length
    for (const f of files) {
        try { await uploadDocumentToProject(f); uploadedCount.value++ } catch {}
    }
    isUploading.value = false
    await fetchDocuments()
}
async function uploadDocumentToProject(file: File) {
    const formData = new FormData()
    formData.append('file', file)
    if (props.tab_id) formData.append('tabId', String(props.tab_id))
    try {
        await axios.post(route('project_files.store', { project: props.project.id }), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
    } catch (error: any) {
        if (error?.response?.data?.errors) documentForm.errors = error.response.data.errors
        else uploadDocumentFeedback.value = 'Upload failed'
        throw error
    }
}

function downloadFile(file: ProjectFile) {
    const a = document.createElement('a')
    a.href = fileUrl(file)
    a.target = '_blank'
    a.click()
}

function openConfirmDeleteModal(file: ProjectFile) { selectedFile.value = file; deletingFile.value = true }
function closeConfirmDeleteModal() { deletingFile.value = false; selectedFile.value = null }
function deleteFile() {
    if (!selectedFile.value?.id) return
    router.delete(route('project_files.destroy', { project_file: selectedFile.value.id }), {
        preserveScroll: true,
        preserveState: true,
        onFinish: closeConfirmDeleteModal,
        onSuccess: fetchDocuments
    })
}

const errorCount = computed(() => Object.keys(documentForm.errors ?? {}).length)
function formatDate(iso?: string) {
    if (!iso) return ''
    try { return new Date(iso).toLocaleDateString((page.props as any)?.locale ?? 'de-DE', { year: 'numeric', month: '2-digit', day: '2-digit' }) }
    catch { return iso }
}

/** ------- Preview-Logik ------- */
function fileExt(name?: string) {
    const n = (name || '').toLowerCase()
    return n.includes('.') ? n.split('.').pop()! : ''
}
function isImage(file: ProjectFile) {
    const m = (file.mime_type || '').toLowerCase()
    const ext = fileExt(file.name)
    return m.startsWith('image/') || ['png','jpg','jpeg','gif','webp','avif','bmp','svg'].includes(ext)
}
function isPdf(file: ProjectFile) {
    const m = (file.mime_type || '').toLowerCase()
    const ext = fileExt(file.name)
    return m === 'application/pdf' || ext === 'pdf'
}
function isPreviewable(file: ProjectFile) { return isImage(file) || isPdf(file) }

function fileUrl(file: ProjectFile) {
    // Falls dein Download-Endpoint 'attachment' erzwingt, erlaube serverseitig ?inline=1 o. ä.
    // Hier nehmen wir denselben Route-Helper wie beim Download.
    return file.url || route('download_file', { project_file: file as any })
}

/* Lightbox State */
const lightboxOpen = ref(false)
const lightboxType = ref<'pdf'|'image'|null>(null)
const lightboxName = ref<string>('')
const lightboxSrc = ref<string>('')

const { pdf: lightboxPdf, pages } = usePDF(lightboxSrc) // pages = Ref<number | undefined>
const currentPage = ref(1)

const totalPages = computed(() => pages?.value ?? 1)
const canPrev = computed(() => currentPage.value > 1)
const canNext = computed(() => currentPage.value < totalPages.value)

// Bei Öffnen oder Source-Wechsel immer auf Seite 1
watch([lightboxOpen, lightboxSrc], () => { currentPage.value = 1 })

// Clamp, falls PDF neu lädt oder weniger Seiten hat
watch(pages, (n) => {
    if (!n) return
    if (currentPage.value > n) currentPage.value = n
})

// Navigation
function nextPage() { if (canNext.value) currentPage.value += 1 }
function prevPage() { if (canPrev.value) currentPage.value -= 1 }

// Keyboard (→ / ← / Esc)
function onKey(e: KeyboardEvent) {
    if (!lightboxOpen.value || lightboxType.value !== 'pdf') return
    if (e.key === 'ArrowRight') nextPage()
    if (e.key === 'ArrowLeft')  prevPage()
    if (e.key === 'Escape')     closePreview()
}

onBeforeUnmount(() => window.removeEventListener('keydown', onKey))

function openPreview(file: ProjectFile) {
    if (!isPreviewable(file)) return
    lightboxType.value = isPdf(file) ? 'pdf' : 'image'
    lightboxName.value = file.name
    lightboxSrc.value = fileUrl(file)
    lightboxOpen.value = true
}
function closePreview() {
    lightboxOpen.value = false
    lightboxSrc.value = '' // entlädt PDF
}
</script>

<template>
    <div class="my-6 space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <BasePageTitle title="Documents" description="Here you can upload and download documents for the project." />
            <InfoButtonComponent :component="component" />
        </div>
        <div v-if="loadDocumentsError" class="text-xs text-rose-600">
            {{ loadDocumentsError }}
        </div>
        <div v-else-if="isLoadingDocuments" class="text-xs text-secondary">
            {{ $t('Loading data...') }}
        </div>

        <!-- Errors -->
        <div v-if="errorCount > 0" class="mb-2">
            <MultiAlertComponent :errors="documentForm.errors" :error-count="errorCount" />
        </div>

        <!-- Dropzone -->
        <div v-if="canEdit" class="relative">
            <div role="button"
                tabindex="0"
                @click="selectNewFiles"
                @keydown.enter.prevent="selectNewFiles"
                @keydown.space.prevent="selectNewFiles"
                @dragenter.prevent="onDragEnter"
                @dragover.prevent="onDragOver"
                @dragleave.prevent="onDragLeave"
                @drop.prevent="onDrop"
                :class="[
                      'relative block w-full rounded-2xl border-2 border-dashed p-10 text-center transition',
                      isDragging ? 'border-indigo-400 bg-indigo-50/60' : 'border-zinc-300 hover:border-zinc-400',
                      isUploading ? 'opacity-60 cursor-progress' : 'cursor-pointer'
                    ]">
                <component :is="IconFileUpload" class="mx-auto size-12 text-zinc-400" aria-hidden="true" />
                <div class="mt-2 text-sm font-medium text-zinc-900">
                    {{ $t('Drag document here to upload or click in the field') }}
                </div>
                <div v-if="isUploading" class="mt-3 text-xs text-zinc-600">
                    {{ $t('Uploading...') }} ({{ uploadedCount }}/{{ totalToUpload }})
                </div>
                <span class="pointer-events-none absolute inset-0 rounded-2xl ring-1 ring-inset ring-black/5" />
            </div>
            <input ref="fileInputEl" type="file" multiple class="hidden" @change="uploadChosenDocuments" />
            <jet-input-error :message="uploadDocumentFeedback" />
        </div>

        <!-- File list -->
        <div>
            <ul v-if="documents.length > 0"
                role="list"
                class="divide-y divide-zinc-100 rounded-2xl border border-zinc-200 bg-white/60 backdrop-blur">
                <li v-for="file in documents" :key="file.id ?? file.name" class="group flex items-center justify-between gap-4 px-4 py-3">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <!-- Thumbnail -->
                        <FilePreview
                            v-if="isPreviewable(file)"
                            :src="fileUrl(file)"
                            :name="file.name"
                            :type="isPdf(file) ? 'pdf' : 'image'"
                            size="sm"
                            @open="openPreview(file)"
                        />
                        <component v-else :is="IconFileText" class="size-5 shrink-0 text-zinc-400" aria-hidden="true" />
                        <!-- Name + Meta -->
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium text-zinc-900">{{ file.name }}</div>
                            <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                                <span>{{ file.file_size }}</span>
                                <span v-if="file.created_at" class="inline-flex items-center gap-1">
                                  • <span>{{ $t('Uploaded') }}:</span>
                                  <time :datetime="file.created_at">{{ formatDate(file.created_at) }}</time>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="shrink-0 flex items-center gap-3">
                        <button type="button" class="rounded-lg px-2 py-1 text-sm font-medium text-zinc-800 ring-1 ring-inset ring-zinc-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500" @click="downloadFile(file)">
                            {{ $t('Download') }}
                        </button>
                        <button v-if="canEditFull" type="button" class="rounded-lg px-2 py-1 text-sm font-medium text-rose-700 ring-1 ring-inset ring-rose-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500" @click="openConfirmDeleteModal(file)">
                            {{ $t('Löschen') }}
                        </button>
                    </div>
                </li>
            </ul>

            <div v-else class="rounded-2xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500">
                {{ $t('No files available') }}
            </div>
        </div>

        <!-- Delete modal -->
        <ConfirmDeleteModal
            v-if="deletingFile"
            :title="$t('Delete file')"
            :description="$t('Are you sure you want to delete the selected file from the project?')"
            @closed="closeConfirmDeleteModal"
            @delete="deleteFile"
        />

        <!-- Lightbox Preview -->
        <teleport to="body">
            <div v-if="lightboxOpen"
                class="fixed inset-0 z-[1000] flex items-center justify-center bg-black/60 p-4"
                @click.self="closePreview">
                <div class="relative w-[92vw] max-w-5xl rounded-2xl bg-white p-3 shadow-xl">
                    <button type="button"
                        class="absolute right-3 top-3 rounded-full p-1.5 text-zinc-600 hover:text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                        @click="closePreview"
                        aria-label="Close preview">
                        ✕
                    </button>

                    <!-- Bild groß -->
                    <img v-if="lightboxType === 'image'"
                        :src="lightboxSrc"
                        :alt="lightboxName"
                        class="mx-auto max-h-[80vh] w-auto object-contain"
                        loading="eager" />

                    <!-- PDF groß -->
                    <div v-else-if="lightboxType === 'pdf'" class="max-h-[80vh] overflow-auto">
                        <div class="mb-3 flex items-center justify-center gap-2">
                            <button type="button"
                                class="rounded-lg px-2 py-1 text-sm font-medium ring-1 ring-inset ring-zinc-300 disabled:opacity-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                :disabled="!canPrev"
                                @click="prevPage">
                                ‹ {{ $t('Prev') }}
                            </button>

                            <span class="text-sm text-zinc-600">
                              {{ currentPage }} / {{ totalPages }}
                            </span>

                            <button type="button"
                                class="rounded-lg px-2 py-1 text-sm font-medium ring-1 ring-inset ring-zinc-300 disabled:opacity-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                :disabled="!canNext"
                                @click="nextPage">
                                {{ $t('Next') }} ›
                            </button>
                        </div>

                        <VuePDF :pdf="lightboxPdf" :page="currentPage" fit-parent class="mx-auto" />
                    </div>
                </div>
            </div>
        </teleport>
    </div>
</template>
