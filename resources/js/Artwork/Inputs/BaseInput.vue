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
            :class="[
            inputBaseClass,
            disabled ? 'bg-gray-100 cursor-not-allowed' : 'bg-white',
            inputPaddingClass,
            type === 'number'? 'appearance-none [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none': '']"
        />

        <!-- Clear Button -->
        <div
            v-if="isClearable"
            class="absolute right-1 top-0 bottom-0 flex items-center pr-2"
        >
            <button
                type="button"
                @click="model = ''"
                tabindex="-1"
                class="text-gray-500 hover:text-artwork-messages-error transition duration-200 ease-in-out"
                :aria-label="$t ? $t('Clear input') : 'Clear input'"
            >
                <component :is="IconX" class="size-4" />
            </button>
        </div>

        <!-- Loading Spinner -->
        <div
            v-if="isLoadingIcon"
            class="absolute right-1 top-0 bottom-0 flex items-center pr-2"
        >
            <div class="animate-spin">
                <component :is="IconLoader" class="size-4 text-gray-500" />
            </div>
        </div>

        <!-- Floating Label -->
        <label
            v-if="label"
            :for="id"
            :class="[
        labelBaseClass,
        isSmall ? labelPosSmall : labelPosDefault
      ]"
        >
            {{ withoutTranslation ? label : $t(label) }}
        </label>

        <!-- Optional Error-Text -->
        <p v-if="error" class="mt-1 text-xs text-artwork-messages-error">
            {{ error }}
        </p>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import {IconX} from "@tabler/icons-vue";

// v-model (Composition API)
const model = defineModel({ default: '' })

const props = defineProps({
    label: { type: String, default: '' },
    type: { type: String, default: 'text' },
    id: { type: String, required: true },
    // der sichtbare Placeholder-Text (wir rendern ihn NICHT mehr in das input,
//  damit das Label nicht springt; du kannst ihn optional als title nutzen)
    placeholder: { type: String, default: '' },

    disabled: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
    isSmall: { type: Boolean, default: false },
    withoutTranslation: { type: Boolean, default: false },

    step: { type: Number, default: 1 },
    showLoading: { type: Boolean, default: false },

    // optionaler Fehlertext (neu, für bessere UX)
    error: { type: String, default: '' }
})

// --- Klassen sauber getrennt
const inputBaseClass = [
    'peer block w-full font-lexend shadow-sm',
    'border border-gray-200 rounded-md',
    'focus:outline-none focus:ring-1 focus:ring-artwork-buttons-create focus:border-artwork-buttons-create',
    // nicer transitions
    'transition-[box-shadow,border-color] duration-150 ease-in-out'
].join(' ')

// Padding abhängig davon, ob Label existiert
const inputPaddingClass = computed(() => {
    if (!props.label) return 'px-4 py-3 text-sm'
    return props.isSmall ? 'px-2 pt-3 pb-1 text-xs' : 'px-4 pt-6 pb-2 text-sm'
})

// Label-Basis: default ist FLOATED (klein/oben).
// Wenn das input LEER ist (placeholder-shown), fährt es runter und wird größer.
const labelBaseClass = [
    'absolute font-lexend pointer-events-none',
    'text-gray-500 transition-all duration-200',
    // floated (default)
    'text-[10px] peer-focus:text-artwork-buttons-create',
    // wenn leer -> runterfahren & größer
    'peer-placeholder-shown:text-xs peer-placeholder-shown:text-gray-500'
].join(' ')

const labelPosDefault = [
    // floated position
    'left-4 top-1.5',
    // wenn leer -> runter
    'peer-placeholder-shown:top-[19px]'
].join(' ')

const labelPosSmall = [
    'left-2 top-0',
    'peer-placeholder-shown:top-[7px]'
].join(' ')

// Clear-/Loading-Logik: nur für Texteingaben etc.
const canShowAffordances = computed(() =>
    !props.disabled && ['text', 'email', 'password', 'search', 'tel', 'url'].includes(props.type)
)

const hasValue = computed(() => {
    // Template entpackt Refs automatisch, hier sicherheitshalber:
    return !!(typeof model === 'string' ? model : (model?.value ?? '')).toString().length
})

const isClearable = computed(() => canShowAffordances.value && hasValue.value && !props.showLoading)
const isLoadingIcon = computed(() => canShowAffordances.value && hasValue.value && props.showLoading)
</script>
