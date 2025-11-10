<template>
    <button
        type="button"
        class="group relative block overflow-hidden rounded-lg ring-1 ring-zinc-200 bg-white/70"
        :class="sizeClass"
        @click="$emit('open')"
        :aria-label="`Preview ${name}`"
    >
        <!-- IMAGE -->
        <img
            v-if="type === 'image'"
            :src="src"
            :alt="name"
            class="h-full w-full object-cover"
            loading="lazy"
            decoding="async"
        />

        <!-- PDF (erste Seite) -->
        <VuePDF
            v-else-if="type === 'pdf' && pdf"
            :pdf="pdf"
            :page="1"
            :width="thumbWidth"
            class="mx-auto"
        />

        <!-- Fallback/Icon -->
        <div v-else class="flex h-full w-full items-center justify-center text-zinc-400">
            <svg viewBox="0 0 24 24" class="h-6 w-6" fill="currentColor" aria-hidden="true">
                <path d="M14 2H6a2 2 0 0 0-2 2v16l4-4h10a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>
            </svg>
        </div>

        <!-- Fokus-Ring -->
        <span class="pointer-events-none absolute inset-0 rounded-lg ring-1 ring-black/5 group-focus-visible:ring-2 group-focus-visible:ring-indigo-500" />
    </button>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { VuePDF, usePDF } from '@tato30/vue-pdf'

const props = withDefaults(defineProps<{
    src: string
    name: string
    type: 'pdf' | 'image'
    size?: 'sm' | 'md'
}>(), {
    size: 'sm'
})

const sizeClass = computed(() => props.size === 'sm' ? 'h-12 w-12' : 'h-20 w-20')
const thumbWidth = computed(() => props.size === 'sm' ? 48 : 96)

/** PDF nur laden, wenn auch wirklich PDF */
const pdfSrc = ref<string | null>(null)
watch(
    () => props.src,
    (val) => { pdfSrc.value = props.type === 'pdf' ? val : null },
    { immediate: true }
)

const { pdf } = usePDF(pdfSrc as any)
</script>
