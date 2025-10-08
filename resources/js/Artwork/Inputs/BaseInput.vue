<template>
    <div class="relative w-full">
        <input
            :id="id"
            :type="type"
            v-model="model"
            :placeholder="' '"
            :disabled="disabled"
            :required="required"
            :step="type === 'number' ? step : undefined"
            :aria-invalid="String(Boolean(error))"
            :aria-required="String(required)"
            :aria-describedby="error ? errorId : undefined"
            :class="[
        inputBaseClass,
        density.inputPadding,
        disabled ? 'bg-gray-100 cursor-not-allowed' : 'bg-white',
        hasRightAffordance ? density.rightPadding : '',
        type === 'number'
          ? 'appearance-none [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none'
          : ''
      ]"
        />

        <!-- Clear Button -->
        <div
            v-if="isClearable"
            :class="['absolute top-0 bottom-0 flex items-center', density.affordanceRight]"
        >
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
        <div
            v-if="isLoadingIcon"
            :class="['absolute top-0 bottom-0 flex items-center', density.affordanceRight]"
        >
            <div class="animate-spin">
                <IconLoader2 :class="['text-gray-500', density.iconSize]" />
            </div>
        </div>

        <!-- Floating Label -->
        <label
            v-if="label"
            :for="id"
            :class="[
        labelBaseClass,
        density.labelPositionFloated,
        density.labelTransitions
      ]"
        >
      <span class="block truncate">
        {{ withoutTranslation ? label : $t(label) }}
      </span>
        </label>

        <!-- Optional Error-Text -->
        <p v-if="error" :id="errorId" class="mt-1 text-xs text-artwork-messages-error">
            {{ error }}
        </p>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { IconX, IconLoader2 } from '@tabler/icons-vue'

// v-model (Composition API)
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

    error: { type: String, default: '' }
})

/**
 * Dichte-/Größen-System
 * - MD (default): bequem beschriftet mit größerem Puffer
 * - SM (isSmall): kompakt, geringere Höhe, feinere Typografie
 * Beide Varianten:
 * - reservieren Platz rechts, wenn Icons angezeigt werden (pr-8)
 * - harmonisierte Label-Animation (Scale + Translate)
 */
const density = computed(() => {
    if (props.isSmall) {
        return {
            // kompakte Eingabe (min-h-9 ≈ 36px), kleinere Typo
            inputPadding: 'px-3 pt-4 pb-1 text-xs leading-5 min-h-9 peer',
            // Label: default floated (oben/klein), bei leer runter + größer
            labelPositionFloated: 'left-3 top-1',
            labelTransitions:
            // floated: scale-90 + translate-y-0
            // leer (placeholder-shown): scale-100 + translate-y to center
                'origin-left text-[10px] ' +
                'peer-focus:translate-y-0 peer-focus:scale-90 peer-focus:text-artwork-buttons-create ' +
                'peer-placeholder-shown:translate-y-[10px] peer-placeholder-shown:scale-100 peer-placeholder-shown:text-[11px] peer-placeholder-shown:text-gray-500',
            // Icon-Block rechts enger anlegen
            affordanceRight: 'right-1 pr-1',
            // Platz im Input für Icons
            rightPadding: 'pr-8',
            // Icon-Größe klein
            iconSize: 'size-3.5'
        }
    }
    // Default (mittel)
    return {
        inputPadding: 'px-4 pt-6 pb-2 text-sm leading-6 min-h-11 peer',
        labelPositionFloated: 'left-4 top-1.5',
        labelTransitions:
            'origin-left text-[11px] ' +
            'peer-focus:translate-y-0 peer-focus:scale-90 peer-focus:text-artwork-buttons-create ' +
            'peer-placeholder-shown:translate-y-[14px] peer-placeholder-shown:scale-100 peer-placeholder-shown:text-xs peer-placeholder-shown:text-gray-500',
        affordanceRight: 'right-2 pr-2',
        rightPadding: 'pr-10',
        iconSize: 'size-4'
    }
})

const inputBaseClass = [
    'block w-full rounded-md border border-gray-200 shadow-sm',
    'focus:outline-none focus:ring-1 focus:ring-artwork-buttons-create focus:border-artwork-buttons-create',
    'transition-[box-shadow,border-color] duration-150 ease-in-out'
].join(' ')

const labelBaseClass = [
    'absolute pointer-events-none',
    'text-gray-500 transition-all duration-200'
].join(' ')

/** Affordances (Clear/Loading) nur bei textbasierten Inputs und wenn nicht disabled */
const canShowAffordances = computed(() =>
    !props.disabled &&
    ['text', 'email', 'password', 'search', 'tel', 'url'].includes(props.type)
)

const hasValue = computed(() => {
    return !!(typeof model === 'string' ? model : (model?.value ?? '')).toString().length
})

const isClearable = computed(() => canShowAffordances.value && hasValue.value && !props.showLoading)
const isLoadingIcon = computed(() => canShowAffordances.value && hasValue.value && props.showLoading)
const hasRightAffordance = computed(() => isClearable.value || isLoadingIcon.value)

const errorId = computed(() => `${props.id}-error`)
</script>
