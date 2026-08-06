<template>
    <div class="w-full">
        <!-- Label über dem Feld -->
        <label v-if="label" :for="id" class="mb-1 block font-lexend text-xs font-medium text-[#3F424A]">
            <span class="block truncate">
                {{ withoutTranslation ? label : $t(label) }}
                <span v-if="required" class="text-danger">*</span>
            </span>
        </label>

        <div class="relative">
            <input
                ref="inputEl"
                :id="id"
                :type="effectiveType"
                v-model="model"
                :placeholder="effectivePlaceholder"
                :disabled="disabled"
                :required="required"
                :step="effectiveType === 'number' ? step : undefined"
                :inputmode="isTimeProxy ? 'tel' : undefined"
                :pattern="isTimeProxy ? timePattern : undefined"
                :autocomplete="isTimeProxy ? 'off' : undefined"
                :aria-invalid="String(Boolean(error))"
                :aria-required="String(required)"
                :aria-describedby="error ? errorId : undefined"
                :class="[
                    inputClasses,
                    inputBaseClass,
                    density.field,
                    error ? 'border-danger-border' : 'border-border',
                    disabled
                        ? 'bg-surface-sunken text-text-subtle border-border-subtle cursor-not-allowed'
                        : 'bg-surface text-text',
                    hasRightAffordance ? density.rightPadding : '',
                    effectiveType === 'number'
                      ? 'appearance-none [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none'
                      : ''
                ]"
                @input="isTimeProxy ? onTimeProxyInput($event) : undefined"
                @blur="($event) => { maybeAutofillTime($event); emit('focusout', $event) }"
                @change="maybeAutofillTime"
                @keydown.enter="handleEnter"
            />

            <!-- Clear Button -->
            <div v-if="isClearable" :class="['absolute top-0 bottom-0 flex items-center', density.affordanceRight]">
                <button
                    type="button"
                    @click="handleClear"
                    tabindex="-1"
                    class="text-text-subtle hover:text-danger transition duration-200 ease-in-out"
                    :aria-label="$t ? $t('Clear input') : 'Clear input'"
                >
                    <PropertyIcon name="IconX" :class="density.iconSize" />
                </button>
            </div>

            <!-- Loading Spinner -->
            <div v-if="isLoadingIcon" :class="['absolute top-0 bottom-0 flex items-center', density.affordanceRight]">
                <div class="animate-spin">
                    <PropertyIcon name="IconLoader2" :class="['text-text-muted', density.iconSize]" />
                </div>
            </div>
        </div>

        <!-- Error -->
        <p v-if="error" :id="errorId" class="mt-1 text-[11.5px] text-danger">
            {{ error }}
        </p>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

const emit = defineEmits(['focusout'])

/** v-model */
const model = defineModel({ default: '' })

const props = defineProps({
    label: { type: String, default: '' },
    type: { type: String, default: 'text' },
    id: { type: String, required: true },
    placeholder: { type: String, default: '' },

    disabled: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
    isSmall: { type: Boolean, default: false },
    /** NEU: große Variante (40px Feldhöhe) */
    isLarge: { type: Boolean, default: false },
    withoutTranslation: { type: Boolean, default: false },

    step: { type: Number, default: 1 },
    showLoading: { type: Boolean, default: false },
    inputClasses: { type: [String, Array, Object], default: '' },
    error: { type: String, default: '' },

    /** aktiviert den Text-Proxy für time-Autofill */
    enableTimeAutofill: { type: Boolean, default: true }
})

/** Refs */
const inputEl = ref(null)

/**
 * Ermöglicht Parent-Komponenten (z.B. ToolbarHeader) den Fokus auf das echte <input> zu setzen.
 * Vue-Refs auf Komponenten zeigen sonst nur auf die Component-Instanz.
 */
function focus() {
    inputEl.value?.focus?.()
}

function select() {
    inputEl.value?.select?.()
}

defineExpose({ focus, select })

/** Dichte: sm = 28px, md = 32px (Default), lg = 40px */
const density = computed(() => {
    if (props.isSmall) {
        return {
            field: 'h-7 px-2.5 text-xs',
            affordanceRight: 'right-1 pr-1',
            rightPadding: 'pr-7',
            iconSize: 'size-3.5'
        }
    }
    if (props.isLarge) {
        return {
            field: 'h-10 px-3.5 text-sm',
            affordanceRight: 'right-2 pr-2',
            rightPadding: 'pr-10',
            iconSize: 'size-4'
        }
    }
    return {
        field: 'h-8 px-3 text-sm',
        affordanceRight: 'right-1.5 pr-1.5',
        rightPadding: 'pr-8',
        iconSize: 'size-4'
    }
})

/** Klassen */
const inputBaseClass = [
    'block w-full rounded-md border',
    'placeholder:text-text-subtle',
    'focus:border-accent-600',
    'transition-[border-color,background-color] duration-150 ease-in-out'
].join(' ')

/** Text-Proxy für time-Felder */
const isTime = computed(() => props.type === 'time')
const isTimeProxy = computed(() => isTime.value && props.enableTimeAutofill)
const effectiveType = computed(() => (isTimeProxy.value ? 'text' : props.type))

/** Echter Placeholder (kein Floating-Label mehr) */
const effectivePlaceholder = computed(() => props.placeholder || '')

/** Clear/Loading nur für textartige Eingaben ODER Time-Proxy */
const isTextLike = computed(() =>
    ['text', 'email', 'password', 'search', 'tel', 'url'].includes(effectiveType.value)
)
const canShowAffordances = computed(() =>
    !props.disabled && (isTextLike.value || isTimeProxy.value)
)

const hasValue = computed(() => String(model.value ?? '').length > 0)
const isClearable = computed(() => canShowAffordances.value && hasValue.value && !props.showLoading)
const isLoadingIcon = computed(() => canShowAffordances.value && hasValue.value && props.showLoading)
const hasRightAffordance = computed(() => isClearable.value || isLoadingIcon.value)

const errorId = computed(() => `${props.id}-error`)

/** Format-Utilities */
const timePattern = '^\\d{1,2}(:\\d{0,2})?$'
const two = (n) => String(n).padStart(2, '0')
const clampHour = (v) => {
    let n = parseInt(v, 10)
    if (Number.isNaN(n)) n = 0
    if (n < 0) n = 0
    if (n > 23) n = 23
    return n
}
const clampMinute = (v) => {
    let n = parseInt(v, 10)
    if (Number.isNaN(n)) n = 0
    if (n < 0) n = 0
    if (n > 59) n = 59
    return n
}


/** Live-Säuberung im Text-Proxy: erlaubt nur Ziffern+":" und begrenzt Länge sinnvoll */
function onTimeProxyInput(e) {
    const el = e?.target
    if (!el) return

    const prev = el.value
    const cursor = el.selectionStart

    // Nur Ziffern und ":" beibehalten
    let raw = prev.replace(/[^\d:]/g, '')

    // Wenn kein ":" vorhanden:
    if (!raw.includes(':')) {
        const digits = raw.replace(/\D/g, '')

        if (digits.length >= 3) {
            // z.B. "1515" oder "915" -> HH:MM (3–4 Ziffern)
            const hh = digits.slice(0, 2)
            const mm = digits.slice(2, 4)
            raw = mm ? `${hh}:${mm}` : `${hh}:`
        } else if (digits.length === 2) {
            // nach zwei Ziffern Doppelpunkt automatisch setzen: "15" -> "15:"
            raw = `${digits}:`
        } else {
            // 0–1 Ziffer: Roh belassen
            raw = digits
        }
    } else {
        // Es gibt schon ":", auf max. 5 Zeichen begrenzen
        const [hh, mm = ''] = raw.split(':')
        raw = `${hh.slice(0, 2)}:${mm.slice(0, 2)}`
    }

    // Gesamtlänge hart begrenzen
    if (raw.length > 5) raw = raw.slice(0, 5)

    // Wert anwenden
    el.value = raw
    model.value = raw

    // Cursor halbwegs stabil halten (einfacher Ansatz)
    // Wenn wir gerade ":" automatisch eingefügt haben, rücken wir 1 vor
    if (cursor != null) {
        const addedColon = !prev.includes(':') && raw.includes(':') && raw.indexOf(':') === 2
        const nextPos = Math.min(raw.length, addedColon ? cursor + 1 : cursor)
        try { el.setSelectionRange(nextPos, nextPos) } catch {}
    }
}

/** Finalisierung beim Blur/Enter/Change */
function normalizeToTime(raw) {
    const s = String(raw ?? '').trim()
    if (!s) return s
    const two = (n) => String(n).padStart(2, '0')
    const clampHour = (v) => Math.max(0, Math.min(23, parseInt(v || '0',10) || 0))
    const clampMinute = (v) => Math.max(0, Math.min(59, parseInt(v || '0',10) || 0))

    const onlyH = s.match(/^(\d{1,2})$/)
    if (onlyH) return `${two(clampHour(onlyH[1]))}:00`

    const hNoM = s.match(/^(\d{1,2})[:h.]$/i)
    if (hNoM) return `${two(clampHour(hNoM[1]))}:00`

    const hM = s.match(/^(\d{1,2})[:h.](\d{1,2})$/i)
    if (hM) return `${two(clampHour(hM[1]))}:${two(clampMinute(hM[2]))}`

    const ok = s.match(/^(\d{1,2}):(\d{1,2})$/)
    if (ok) return `${two(clampHour(ok[1]))}:${two(clampMinute(ok[2]))}`

    return s
}

function maybeAutofillTime(e) {
    if (!isTime.value) return
    const current = isTimeProxy.value ? (e?.target?.value ?? model.value ?? '') : (model.value ?? '')
    const next = normalizeToTime(current)
    if (next && next !== current) {
        if (e?.target) e.target.value = next
        if (inputEl?.value) inputEl.value.value = next
        model.value = next
    }
}

function handleEnter(e) {
    // Nur bei Time-Inputs Enter abfangen, um zuerst zu normalisieren.
    // Bei normalen Inputs soll Enter das native Form-Submit auslösen können.
    if (!isTime.value) return
    e?.preventDefault?.()
    maybeAutofillTime(e)
}

/** Clear-Handler: Wert leeren und blur explizit auslösen (Firefox-Fix) */
function handleClear() {
    model.value = ''
    // Blur explizit auslösen und focusout-Event manuell dispatchen (Firefox-Fix)
    if (inputEl?.value) {
        const element = inputEl.value
        element.blur()
        // Focusout-Event manuell dispatchen, da Firefox blur() nicht immer in focusout übersetzt
        const focusoutEvent = new FocusEvent('focusout', {
            bubbles: true,
            cancelable: true,
            relatedTarget: null
        })
        element.dispatchEvent(focusoutEvent)
    }
}
</script>
