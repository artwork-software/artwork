<template>
    <div class="py-2 font-lexend" :class="stacked ? '' : 'flex items-center justify-between gap-4'">
        <div class="text-text-subtle shrink-0">
            {{ property.name }}
        </div>
        <div class="min-w-0" :class="stacked ? 'mt-0.5 text-text' : 'text-right'">
            <template v-if="property.type === 'file'">
                <a v-if="property.file"
                   :href="route('inventory-management.articles.property-file.download', { path: property.file.path })"
                   :title="property.file.name"
                   class="text-accent-600 hover:text-accent-700 underline cursor-pointer break-all"
                   :class="stacked ? 'line-clamp-3' : ''">
                    {{ property.file.name }}
                </a>
                <span v-else>-</span>
            </template>
            <PropertyDiffTooltip
                v-else-if="property.varied"
                :values="property.distinctValues"
                :heading="$t('Values')"
                class="text-text-subtle"
            >
                {{ property.text }}
            </PropertyDiffTooltip>
            <span v-else-if="property.empty">-</span>
            <span
                v-else
                ref="valueEl"
                class="break-words whitespace-pre-line"
                :class="stacked ? 'line-clamp-3' : ''"
                @mouseenter="onEnter"
                @mouseleave="hide"
            >
                {{ property.text }}
            </span>
        </div>

        <Teleport to="body">
            <div
                v-if="tooltipVisible"
                class="fixed z-[10000] max-w-[18rem] rounded-md bg-black/90 px-2.5 py-2 text-xs leading-snug text-white shadow-lg pointer-events-none font-lexend break-words whitespace-pre-line"
                :style="tooltipStyle"
            >
                {{ property.text }}
                <div class="absolute left-1/2 top-full h-2 w-2 -translate-x-1/2 -translate-y-1/2 rotate-45 bg-black/90"></div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useTranslation } from '@/Composeables/Translation.js'
import PropertyDiffTooltip from '@/Pages/Inventory/Components/PropertyDiffTooltip.vue'

const $t = useTranslation()

const props = defineProps({
    // Display descriptor from useInventoryPropertyDisplay (name, type, text, empty, varied, file, distinctValues).
    property: {
        type: Object,
        required: true,
    },
})

// Text the row will actually render — decides between the side-by-side and stacked layout.
const displayedText = computed(() => {
    if (props.property.type === 'file') return props.property.file?.name ?? ''
    if (props.property.varied || props.property.empty) return ''
    return props.property.text ?? ''
})

// Long values get their own line below the label instead of squeezing next to it.
const STACK_THRESHOLD = 24
const stacked = computed(
    () => displayedText.value.length > STACK_THRESHOLD || displayedText.value.includes('\n')
)

const valueEl = ref(null)
const tooltipVisible = ref(false)
const coords = ref({ top: 0, left: 0 })

const tooltipStyle = computed(() => ({
    top: coords.value.top + 'px',
    left: coords.value.left + 'px',
    transform: 'translate(-50%, calc(-100% - 10px))',
}))

// Show the full-text tooltip only when the clamp actually cut something off.
const onEnter = () => {
    const el = valueEl.value
    if (!el || el.scrollHeight <= el.clientHeight + 1) return
    const rect = el.getBoundingClientRect()
    coords.value = { top: rect.top, left: rect.left + rect.width / 2 }
    tooltipVisible.value = true
}

const hide = () => {
    tooltipVisible.value = false
}
</script>
