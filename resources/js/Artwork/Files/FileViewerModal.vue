<template>
    <!-- Wiederverwendbarer Bild-/PDF-Viewer (Muster aus den Projekt-Dokumenten,
         Abnahme MAT-03 Ref. 1.16): Vorschau im Browser, Download bleibt erhalten. -->
    <teleport to="body">
        <div
            class="fixed inset-0 z-[1000] flex items-center justify-center bg-black/60 p-4"
            @click.self="$emit('close')"
        >
            <div class="relative w-[92vw] max-w-5xl rounded-2xl bg-white p-3 shadow-xl">
                <button
                    type="button"
                    class="absolute right-3 top-3 rounded-full p-1.5 text-text-muted hover:text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-600"
                    :aria-label="$t('Close')"
                    @click="$emit('close')"
                >
                    ✕
                </button>

                <div class="mb-2 pr-10 truncate text-sm font-medium text-text">{{ name }}</div>

                <!-- Bild groß -->
                <img
                    v-if="type === 'image'"
                    :src="src"
                    :alt="name"
                    class="mx-auto max-h-[78vh] w-auto object-contain"
                    loading="eager"
                />

                <!-- PDF groß mit Seiten-Navigation -->
                <div v-else-if="type === 'pdf'" class="max-h-[78vh] overflow-auto">
                    <div class="mb-3 flex items-center justify-center gap-2">
                        <button
                            type="button"
                            class="rounded-lg px-2 py-1 text-sm font-medium ring-1 ring-inset ring-border disabled:cursor-not-allowed disabled:text-text-subtle focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-600"
                            :disabled="currentPage <= 1"
                            @click="currentPage--"
                        >
                            ‹ {{ $t('Prev') }}
                        </button>

                        <span class="text-sm text-text-muted">{{ currentPage }} / {{ pages || 1 }}</span>

                        <button
                            type="button"
                            class="rounded-lg px-2 py-1 text-sm font-medium ring-1 ring-inset ring-border disabled:cursor-not-allowed disabled:text-text-subtle focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-600"
                            :disabled="currentPage >= (pages || 1)"
                            @click="currentPage++"
                        >
                            {{ $t('Next') }} ›
                        </button>
                    </div>

                    <VuePDF :pdf="pdf" :page="currentPage" fit-parent class="mx-auto" />
                </div>

                <!-- Download bleibt erhalten -->
                <div class="mt-3 flex justify-end">
                    <a
                        :href="src"
                        target="_blank"
                        :download="name"
                        class="rounded-lg px-2 py-1 text-sm font-medium text-text ring-1 ring-inset ring-border hover:bg-surface-sunken focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-600"
                    >
                        {{ $t('Download') }}
                    </a>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { VuePDF, usePDF } from '@tato30/vue-pdf'
import { GlobalWorkerOptions } from 'pdfjs-dist'

// pdf.js-Worker lazy konfigurieren (gleiches Muster wie FilePreview.vue)
if (!GlobalWorkerOptions.workerSrc) {
    GlobalWorkerOptions.workerSrc = new URL(
        'pdfjs-dist/build/pdf.worker.min.mjs',
        import.meta.url
    ).toString()
}

const props = defineProps<{
    src: string
    name: string
    type: 'pdf' | 'image'
}>()

const emit = defineEmits(['close'])

const pdfSrc = ref<string | null>(null)
watch(
    () => props.src,
    (val) => { pdfSrc.value = props.type === 'pdf' ? val : null },
    { immediate: true }
)

const { pdf, pages } = usePDF(pdfSrc as any)
const currentPage = ref(1)
watch(() => props.src, () => { currentPage.value = 1 })

function onKey(e: KeyboardEvent) {
    if (e.key === 'Escape') emit('close')
    if (props.type !== 'pdf') return
    if (e.key === 'ArrowRight' && currentPage.value < (pages.value || 1)) currentPage.value++
    if (e.key === 'ArrowLeft' && currentPage.value > 1) currentPage.value--
}

onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))
</script>
