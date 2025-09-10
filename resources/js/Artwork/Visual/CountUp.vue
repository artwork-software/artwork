<template>
    <!-- Alle Klassen/Attribute vom Parent werden via v-bind="attrs" durchgereicht -->
    <span v-bind="attrs">{{ prefix }}{{ formatted }}{{ suffix }}</span>
</template>

<script setup>
import { ref, watch, computed, onBeforeUnmount, useAttrs } from 'vue'

const props = defineProps({
    /** Zielwert, zu dem hochgezählt wird */
    value: { type: Number, required: true },
    /** Dauer der Animation in ms */
    duration: { type: Number, default: 600 },
    /** Dezimalstellen für die Anzeige */
    decimals: { type: Number, default: 0 },
    /** Optionales Präfix/Suffix (z.B. "€") */
    prefix: { type: String, default: '' },
    suffix: { type: String, default: '' },
    /** Easing aktivieren (easeOutCubic) */
    easing: { type: Boolean, default: true },
    /** Optional: Locale für Zahlformatierung (z.B. 'de-DE'); undefined = Browser-Default */
    locale: { type: String, default: undefined },
})

const attrs = useAttrs()

const display = ref(0)
let raf = 0
let startTs = 0
let startVal = 0
let targetVal = 0

const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3)

const formatNumber = (n) =>
    (props.locale
            ? new Intl.NumberFormat(props.locale, {
                minimumFractionDigits: props.decimals,
                maximumFractionDigits: props.decimals,
            })
            : new Intl.NumberFormat(undefined, {
                minimumFractionDigits: props.decimals,
                maximumFractionDigits: props.decimals,
            })
    ).format(n)

const formatted = computed(() => {
    const safe = Number.isFinite(display.value) ? display.value : 0
    return formatNumber(safe)
})

function animateTo(next) {
    cancelAnimationFrame(raf)
    startTs = 0
    startVal = Number.isFinite(display.value) ? display.value : 0
    targetVal = Number.isFinite(next) ? next : 0

    if (props.duration <= 0 || Math.abs(targetVal - startVal) < 1e-6) {
        display.value = targetVal
        return
    }

    const step = (ts) => {
        if (!startTs) startTs = ts
        const p = Math.min(1, (ts - startTs) / props.duration)
        const k = props.easing ? easeOutCubic(p) : p
        display.value = startVal + (targetVal - startVal) * k
        if (p < 1) raf = requestAnimationFrame(step)
    }
    raf = requestAnimationFrame(step)
}

watch(
    () => props.value,
    (v) => animateTo(Number(v)),
    { immediate: true }
)

onBeforeUnmount(() => cancelAnimationFrame(raf))
</script>
