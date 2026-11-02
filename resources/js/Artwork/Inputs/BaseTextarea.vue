<template>
    <div class="w-full" v-bind="$attrs">
        <!-- Label über dem Feld -->
        <label v-if="hasLabel" :for="id" class="mb-1 block font-lexend text-xs font-medium text-[#3F424A]">
            <span class="block truncate">
                {{ $t(label) }}
                <span v-if="required" class="text-danger">*</span>
            </span>
        </label>

        <textarea
            ref="textareaRef"
            :id="id"
            :name="name || undefined"
            :value="modelValue"
            :placeholder="placeholder"
            @input="onInput"
            @blur="($event) => emit('focusout', $event)"
            :disabled="disabled"
            :readonly="readonly"
            :required="required"
            :maxlength="maxlength ?? undefined"
            :rows="rows"
            :aria-invalid="String(Boolean(error))"
            :aria-required="String(required)"
            :aria-describedby="error ? errorId : undefined"
            class="block w-full rounded-md border px-3 py-2 text-sm resize-none placeholder:text-text-subtle focus:border-accent-600 transition-[border-color,background-color] duration-150 ease-in-out"
            :class="[
                error ? 'border-danger-border' : 'border-border',
                disabled ? 'bg-surface-sunken text-text-subtle border-border-subtle cursor-not-allowed' : bgColor
            ]"
        />

        <!-- Zeichenzähler (nur mit maxlength), damit das Limit beim Einfügen sichtbar ist -->
        <div v-if="showCounter && maxlength" class="mt-1 text-xs text-end text-text-muted">
            {{ (modelValue ?? '').length }} / {{ maxlength }}
        </div>

        <!-- Error -->
        <p v-if="error" :id="errorId" class="mt-1 text-[11.5px] text-danger">
            {{ error }}
        </p>
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue'

defineOptions({
    inheritAttrs: false
})

const props = defineProps({
    modelValue: String,
    label: { type: String, default: '' },
    id: {
        type: String,
        required: true
    },
    placeholder: {
        type: String,
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    },
    readonly: {
        type: Boolean,
        default: false
    },
    /** Zeichenlimit am <textarea> (Wrapper-Attribute erreichen das Feld wegen inheritAttrs: false nicht) */
    maxlength: {
        type: [Number, String],
        default: null
    },
    name: {
        type: String,
        default: ''
    },
    /** Zeichenzähler „n / maxlength“ unter dem Feld anzeigen */
    showCounter: {
        type: Boolean,
        default: false
    },
    rows: {
        type: [Number, String],
        default: 4
    },
    required: {
        type: Boolean,
        default: false
    },
    bgColor: {
        type: String,
        default: 'bg-surface'
    },
    /** Höhe wächst mit dem Inhalt (ab `rows`) bis `maxRows`, erst danach scrollt das Feld */
    autoGrow: {
        type: Boolean,
        default: false
    },
    maxRows: {
        type: Number,
        default: 30
    },
    /** NEU: optionale Fehlermeldung (danger-Rahmen + Text unter dem Feld) */
    error: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:modelValue', 'focusout'])

const hasLabel = computed(() => typeof props.label === 'string' && props.label.trim().length > 0)

const errorId = computed(() => `${props.id}-error`)

const textareaRef = ref(null)

function onInput(event) {
    emit('update:modelValue', event.target.value)
    resizeToContent()
}

function resizeToContent() {
    const textarea = textareaRef.value
    if (!props.autoGrow || !textarea) {
        return
    }
    const style = window.getComputedStyle(textarea)
    const lineHeight = parseFloat(style.lineHeight) || 20
    const verticalChrome = parseFloat(style.paddingTop) + parseFloat(style.paddingBottom)
        + parseFloat(style.borderTopWidth) + parseFloat(style.borderBottomWidth)
    const minHeight = lineHeight * Number(props.rows) + verticalChrome
    const maxHeight = lineHeight * props.maxRows + verticalChrome

    // Auf Mindesthöhe statt 'auto' messen: das Feld fällt nie kleiner zusammen, die Seite springt beim Tippen nicht
    textarea.style.height = `${minHeight}px`
    const contentHeight = textarea.scrollHeight + parseFloat(style.borderTopWidth) + parseFloat(style.borderBottomWidth)
    textarea.style.height = `${Math.min(Math.max(contentHeight, minHeight), maxHeight)}px`
    textarea.style.overflowY = contentHeight > maxHeight ? 'auto' : 'hidden'
}

// Externe Wertänderungen (Live-Update, Zurücksetzen) passen die Höhe ebenfalls an
watch(() => props.modelValue, () => nextTick(resizeToContent))
onMounted(resizeToContent)

/** Fokus für Eltern über ref (z. B. ShiftNoteComponent beim Öffnen des Felds) */
function focus(options) {
    textareaRef.value?.focus(options)
}

defineExpose({ focus })
</script>
