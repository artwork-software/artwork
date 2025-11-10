<template>
    <div class="group">
        <!-- Dropzone, wenn kein Key-Visual vorhanden -->
        <div
            v-if="!hasKeyVisual"
            class="flex col-span-2 w-full justify-center border-2 bg-stone-50 border-gray-300 cursor-pointer border-dashed rounded-md p-2"
            @dragover.prevent
            @drop.stop.prevent="uploadDraggedKeyVisual($event)"
            @click="selectNewKeyVisual"
        >
            <div class="space-y-1 text-center">
                <div class="xsLight flex my-auto h-40 items-center">
                    <span v-html="$t('Drag your key visual here')"></span>
                    <input
                        id="keyVisual-upload"
                        ref="keyVisual"
                        name="file-upload"
                        type="file"
                        class="sr-only"
                        accept=".jpg,.jpeg,.png,.gif,.svg"
                        @change="updateKeyVisual"
                    />
                </div>
            </div>
        </div>

        <!-- Bild + Aktionen, wenn vorhanden -->
        <div v-else class="flex items-center justify-center relative w-full">
            <!-- Hover-Actions -->
            <div
                class="absolute w-full text-center items-center justify-center hidden group-hover:flex space-x-4"
            >
                <button
                    v-if="originalKeyVisualNotDefault"
                    @click="downloadKeyVisual"
                    type="button"
                    class="ui-button bg-white hover:text-orange-500"
                >
                    <IconDownload class="h-5 w-5" aria-hidden="true" />
                </button>

                <button
                    @click="selectNewKeyVisual"
                    type="button"
                    class="ui-button bg-white hover:text-blue-500"
                >
                    <IconEdit class="h-5 w-5" aria-hidden="true" />
                </button>

                <button
                    @click="deleteKeyVisual"
                    type="button"
                    class="ui-button bg-white hover:text-red-500"
                >
                    <IconX class="h-5 w-5" aria-hidden="true" />
                </button>
            </div>

            <!-- Bild -->
            <div class="text-center">
                <div class="cursor-pointer">
                    <img
                        :key="cacheBuster"
                        :src="currentPreviewSrc"
                        alt="Aktuelles Key-Visual"
                        @error="(e) => (e.target.src = page.props.big_logo)"
                        class="rounded-md w-full h-48 object-cover"
                    />
                    <input
                        id="keyVisual-upload"
                        ref="keyVisual"
                        name="file-upload"
                        type="file"
                        class="sr-only"
                        accept=".jpg,.jpeg,.png,.gif,.svg"
                        @change="updateKeyVisual"
                    />
                </div>
            </div>
        </div>

        <JetInputError :message="uploadKeyVisualFeedback" />
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, usePage, router } from '@inertiajs/vue3'
import JetInputError from '@/Jetstream/InputError.vue'
import { IconDownload, IconEdit, IconX } from '@tabler/icons-vue'

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
})

const emit = defineEmits(['updated'])

const page = usePage()

// --- Lokaler State ---
const keyVisual = ref(null)
const uploadKeyVisualFeedback = ref('')
const cacheBuster = ref(Date.now())

// wichtig neu:
const tempPreviewUrl = ref(null)
// → das ist eine Blob-URL (URL.createObjectURL) für das neu hochgeladene Bild
// die nutzen wir sofort nach Upload, ohne auf Server zu warten

// wir behalten den (vom Server gelieferten) Pfad separat
const serverKeyVisualPath = ref(props.project?.key_visual_path ?? null)

// wenn Parent mal doch updated (z.B. nach reload)
watch(
    () => props.project?.key_visual_path,
    (newVal) => {
        serverKeyVisualPath.value = newVal ?? null
        // sobald vom Server was Neues kommt, entwerten wir die tempPreviewUrl
        // damit wieder der echte Pfad angezeigt wird
        tempPreviewUrl.value = null
        cacheBuster.value = Date.now()
    }
)

const hasKeyVisual = computed(() => {
    // wir haben ein Bild entweder vom Server ODER gerade frisch hochgeladen (tempPreviewUrl)
    return !!tempPreviewUrl.value || !!serverKeyVisualPath.value
})

const originalKeyVisualNotDefault = computed(() => {
    return (
        serverKeyVisualPath.value &&
        serverKeyVisualPath.value !== 'default_keyVisual.png'
    )
})

// Was zeigen wir im <img> aktuell an?
const currentPreviewSrc = computed(() => {
    // Priorität 1: frisch hochgeladene Preview (Blob)
    if (tempPreviewUrl.value) {
        return tempPreviewUrl.value
    }

    // Priorität 2: Server-Bild
    if (serverKeyVisualPath.value) {
        return `/storage/keyVisual/${serverKeyVisualPath.value}?cb=${cacheBuster.value}`
    }

    // Fallback Logo
    return page.props.big_logo
})

// Upload-Form für Inertia-POST
const keyVisualForm = useForm({ keyVisual: null })

const selectNewKeyVisual = () => {
    keyVisual.value?.click()
}

const updateKeyVisual = () => {
    const file = keyVisual.value?.files?.[0]
    if (!file) return
    handleFileUpload(file)

    // Input resetten, damit derselbe Dateiname nochmal geht
    keyVisual.value.value = null
}

const uploadDraggedKeyVisual = (event) => {
    const file = event?.dataTransfer?.files?.[0]
    if (!file) return
    handleFileUpload(file)
}

// zentrale Upload-Logik
function handleFileUpload(file) {
    uploadKeyVisualFeedback.value = ''

    const allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/svg+xml',
    ]

    if (!allowedTypes.includes(file.type)) {
        uploadKeyVisualFeedback.value = $t(
            'Only logos and illustrations of the type .jpeg, .svg, .png and .gif are accepted.'
        )
        return
    }

    // SOFORT lokale Preview setzen (direkt sichtbar im Modal)
    tempPreviewUrl.value = URL.createObjectURL(file)
    cacheBuster.value = Date.now()

    // jetzt Anfrage an den Server rausschicken
    keyVisualForm.keyVisual = file

    keyVisualForm.post(
        route('projects_key_visual.update', { project: props.project.id }),
        {
            preserveState: true,
            onSuccess: () => {
                // UI ist eh schon umgesprungen dank tempPreviewUrl,
                // aber wir versuchen trotzdem sauber nachzuladen,
                // falls andere Komponenten den neuen Pfad brauchen:
                emit('updated')

                router.reload({
                    only: ['project'],
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        cacheBuster.value = Date.now()
                        // wenn der Server jetzt einen neuen Pfad liefert,
                        // wird unser watcher oben anspringen → tempPreviewUrl = null
                    },
                })
            },
            onError: (error) => {
                uploadKeyVisualFeedback.value =
                    error?.keyVisual ?? $t('Upload failed.')

                // Falls Upload schief geht → wieder zurückrollen auf altes Bild
                tempPreviewUrl.value = null
                cacheBuster.value = Date.now()
            },
        }
    )
}

const downloadKeyVisual = () => {
    const link = document.createElement('a')
    link.href = route('project.download.keyVisual', props.project.id)
    link.target = '_blank'
    link.click()
}

const deleteKeyVisual = () => {
    router.delete(route('project.delete.keyVisual', props.project.id), {
        preserveState: true,
        onSuccess: () => {
            // Sofort lokal entfernen
            tempPreviewUrl.value = null
            serverKeyVisualPath.value = null
            cacheBuster.value = Date.now()

            emit('updated')

            router.reload({
                only: ['project'],
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    cacheBuster.value = Date.now()
                },
            })
        },
        onError: (error) => {
            uploadKeyVisualFeedback.value =
                error?.keyVisual ?? $t('Deletion failed.')
        },
    })
}
</script>
