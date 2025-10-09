<template>
    <div class="relative w-full">
        <input
            ref="inputEl"
            :id="id"
            :type="effectiveType"
            v-model="model"
            :placeholder="' '"
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
        inputBaseClass,
        density.inputPadding,
        disabled ? 'bg-gray-100 cursor-not-allowed' : 'bg-white',
        hasRightAffordance ? density.rightPadding : '',
        effectiveType === 'number'
          ? 'appearance-none [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none'
          : ''
      ]"
            @input="isTimeProxy ? onTimeProxyInput($event) : undefined"
            @blur="maybeAutofillTime"
            @change="maybeAutofillTime"
            @keydown.enter.prevent="maybeAutofillTime"
        />

        <!-- Clear Button -->
        <div v-if="isClearable" :class="['absolute top-0 bottom-0 flex items-center', density.affordanceRight]">
            <button
                type="button"
                @click="model = ''"
                tabindex="-1"
                class="text-gray-500 hover:text-artwork-messages-error transition duration-200 ease-in-out"
                :aria-label="$t ? $t('Clear input') : 'Clear input'"
            >
                <IconX :class="density.iconSize" />
            </button>
        </div>

        <!-- Loading Spinner -->
        <div v-if="isLoadingIcon" :class="['absolute top-0 bottom-0 flex items-center', density.affordanceRight]">
            <div class="animate-spin">
                <IconLoader2 :class="['text-gray-500', density.iconSize]" />
            </div>
        </div>

        <!-- Floating Label -->
        <label v-if="label" :for="id" :class="[labelBaseClass, density.labelPositionFloated, density.labelTransitions]">
      <span class="block truncate">
        {{ withoutTranslation ? label : $t(label) }}
      </span>
        </label>

        <!-- Error -->
        <p v-if="error" :id="errorId" class="mt-1 text-xs text-artwork-messages-error">
            {{ error }}
        </p>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { IconX, IconLoader2 } from '@tabler/icons-vue'

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
    withoutTranslation: { type: Boolean, default: false },

    step: { type: Number, default: 1 },
    showLoading: { type: Boolean, default: false },

    error: { type: String, default: '' },

    /** NEW: aktiviert den Text-Proxy für time-Autofill */
    enableTimeAutofill: { type: Boolean, default: true }
})

/** Refs */
const inputEl = ref(null)

/** Dichte */
const density = computed(() => {
    if (props.isSmall) {
        return {
            inputPadding: 'px-3 pt-4 pb-1 text-xs leading-5 min-h-9 peer',
            labelPositionFloated: 'left-3 top-1',
            labelTransitions:
                'origin-left text-[10px] peer-focus:translate-y-0 peer-focus:scale-90 peer-focus:text-artwork-buttons-create ' +
                'peer-placeholder-shown:translate-y-[10px] peer-placeholder-shown:scale-100 peer-placeholder-shown:text-[11px] peer-placeholder-shown:text-gray-500',
            affordanceRight: 'right-1 pr-1',
            rightPadding: 'pr-8',
            iconSize: 'size-3.5'
        }
    }
    return {
        inputPadding: 'px-4 pt-6 pb-2 text-sm leading-6 min-h-11 peer',
        labelPositionFloated: 'left-4 top-1.5',
        labelTransitions:
            'origin-left text-[11px] peer-focus:translate-y-0 peer-focus:scale-90 peer-focus:text-artwork-buttons-create ' +
            'peer-placeholder-shown:translate-y-[14px] peer-placeholder-shown:scale-100 peer-placeholder-shown:text-xs peer-placeholder-shown:text-gray-500',
        affordanceRight: 'right-2 pr-2',
        rightPadding: 'pr-10',
        iconSize: 'size-4'
    }
})

/** Klassen */
const inputBaseClass = [
    'block w-full rounded-md border border-gray-200 shadow-sm',
    'focus:outline-none focus:ring-1 focus:ring-artwork-buttons-create focus:border-artwork-buttons-create',
    'transition-[box-shadow,border-color] duration-150 ease-in-out'
].join(' ')

const labelBaseClass = [
    'absolute pointer-events-none',
    'text-gray-500 transition-all duration-200'
].join(' ')

/** Text-Proxy für time-Felder */
const isTime = computed(() => props.type === 'time')
const isTimeProxy = computed(() => isTime.value && props.enableTimeAutofill)
const effectiveType = computed(() => (isTimeProxy.value ? 'text' : props.type))

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
</script>
