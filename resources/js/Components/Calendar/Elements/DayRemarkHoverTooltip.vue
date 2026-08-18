<template>
    <!-- Teleport + fixed: entkommt overflow-hidden/sticky der Kalenderzellen
         (gleiches Muster wie der Inline-Editor in DayRemarkCell) -->
    <Teleport to="body">
        <div
            v-if="visible"
            ref="tooltipRef"
            class="fixed z-[999] pointer-events-none max-w-[320px] rounded-lg border border-warning-border bg-surface shadow-overlay px-2.5 py-2"
            :style="positionStyle"
        >
            <p class="text-[11px] leading-[15px] text-text whitespace-pre-line break-words">
                {{ remark?.text }}
            </p>
            <p v-if="remark?.updated_by" class="mt-1.5 text-[10px] text-text-subtle">
                {{ $t('Last edited by {0} on {1}', [remark.updated_by, remark.updated_at]) }}
            </p>
        </div>
    </Teleport>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'

// Zeigt den vollen Bemerkungstext beim Hover — aber nur, wenn die Anzeige im
// Anker tatsächlich abgeschnitten ist (line-clamp/truncate), sonst wäre der
// Tooltip reines Rauschen. Der Parent steuert die Sichtbarkeit per v-if
// (mouseenter/mouseleave); diese Komponente misst, verzögert und positioniert.
const props = defineProps({
    remark: { type: Object, default: null },
    anchor: { type: Object, default: null }, // HTMLElement der gerenderten (geclampten) Anzeige
    openDelay: { type: Number, default: 250 },
})

const TOOLTIP_MAX_WIDTH = 320

const visible = ref(false)
const tooltipRef = ref(null)
const positionStyle = ref({})
let openTimer = null

const isTruncated = (el) =>
    el.scrollHeight > el.clientHeight + 1 || el.scrollWidth > el.clientWidth + 1

// Container mit overflow-hidden melden selbst keinen Overflow, wenn ein
// geclamptes Kind ihn schluckt — deshalb auch die Nachfahren prüfen
const anchorTruncated = () => {
    const root = props.anchor
    if (!root) {
        return false
    }
    return isTruncated(root) || Array.from(root.querySelectorAll('*')).some(isTruncated)
}

const open = async () => {
    const rect = props.anchor?.getBoundingClientRect()
    if (!rect) {
        return
    }
    // Erst unsichtbar rendern und die echte Höhe messen — die Heuristik
    // "oben oder unten?" ohne Messung schob lange Texte aus dem Viewport
    positionStyle.value = { left: '-9999px', top: '0px' }
    visible.value = true
    await nextTick()
    const height = tooltipRef.value?.offsetHeight ?? 0
    const left = Math.max(8, Math.min(rect.left, window.innerWidth - TOOLTIP_MAX_WIDTH - 12))
    let top = rect.bottom + 6
    if (top + height > window.innerHeight - 8) {
        top = rect.top - height - 6
    }
    // Passt weder unter noch über den Anker komplett → im Viewport festklemmen
    top = Math.max(8, Math.min(top, window.innerHeight - height - 8))
    positionStyle.value = { left: `${left}px`, top: `${top}px` }
}

// Scrollen verschiebt den Anker unter dem festen Tooltip weg — dann ausblenden
const hide = () => {
    visible.value = false
    clearTimeout(openTimer)
}

onMounted(() => {
    if (!props.remark?.text || !anchorTruncated()) {
        return
    }
    openTimer = setTimeout(open, props.openDelay)
    window.addEventListener('scroll', hide, true)
})

onBeforeUnmount(() => {
    clearTimeout(openTimer)
    window.removeEventListener('scroll', hide, true)
})
</script>
