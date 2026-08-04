<template>
    <button
        :type="type"
        :class="buttonClasses"
        :disabled="disabled || processing"
        :aria-disabled="disabled ? 'true' : 'false'"
        @click="onClick"
        v-bind="$attrs"
    >
        <!-- Spinner when processing -->
        <svg v-if="processing" class="animate-spin" :class="iconSizeClass" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <!-- Icon -->
        <PropertyIcon
            v-else-if="!hideIcon && typeof iconResolved === 'string'"
            :name="iconResolved"
            :class="iconSizeClass"
            :stroke-width="strokeWidthResolved"
        />
        <component
            v-else-if="!hideIcon"
            :is="iconResolved"
            :class="iconSizeClass"
            :stroke-width="strokeWidthResolved"
        />

        <slot>{{ labelResolved }}</slot>
    </button>
</template>

<script setup lang="ts">
import { computed, type Component } from 'vue';
import { useI18n } from 'vue-i18n';
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue';

defineOptions({ name: 'BaseUIButton' });

const { t } = useI18n();

const props = withDefaults(defineProps<{
    /** Button deaktivieren */
    disabled?: boolean;
    /** Icon-Komponente oder Name (z. B. 'IconCirclePlus' oder tatsächliche Komponente) */
    icon?: string | Component;
    /** Strichstärke für Tabler-Icons */
    strokeWidth?: number | string;
    /** Fallback-Label (wird übersetzt, wenn useTranslation=true) */
    label?: string;
    /** useTranslation default true */
    useTranslation?: boolean;
    /** Semantische Variante — gewinnt gegen die Alt-Boolean-Props */
    variant?: 'primary' | 'secondary' | 'ghost' | 'danger';
    /** Steuerhöhe: sm 28px, md 32px, lg 40px — gewinnt gegen isSmall */
    size?: 'sm' | 'md' | 'lg';
    /** Alt-Prop, entspricht variant="primary" */
    isAddButton?: boolean;
    /** Alt-Prop, entspricht variant="danger" */
    isDeleteButton?: boolean;
    /** Alt-Prop, entspricht variant="secondary" */
    isCancelButton?: boolean;
    /** Alt-Prop, entspricht size="sm" */
    isSmall?: boolean;
    type?: string;
    /** Processing/Loading state */
    processing?: boolean;
    /** Icon komplett ausblenden */
    hideIcon?: boolean;
}>(), {
    disabled: false,
    icon: undefined,
    strokeWidth: 1.5,
    label: '',
    useTranslation: true,
    variant: undefined,
    size: undefined,
    isAddButton: false,
    isDeleteButton: false,
    isCancelButton: false,
    isSmall: false,
    type: 'button',
    processing: false,
    hideIcon: false,
});

const emit = defineEmits<{
    (e: 'click', evt: MouseEvent): void;
}>();

const strokeWidthResolved = computed(() => props.strokeWidth ?? 1.5);

const iconResolved = computed(() => {
    // Priorität: custom icon → add icon → delete icon → default icon
    if (props.icon) return props.icon;
    if (props.isAddButton || props.variant === 'primary') return 'IconCirclePlus';
    if (props.isDeleteButton || props.variant === 'danger') return 'IconTrash';
    if (props.isCancelButton) return 'IconCancel';
    return 'IconCirclePlus';
});

const labelResolved = computed(() => {
    if (!props.label) return '';
    return props.useTranslation ? t(props.label) : props.label;
});

function onClick(evt: MouseEvent) {
    if (props.disabled) return;
    emit('click', evt);
}

/** Alt-Props → neue API */
const variantResolved = computed<'primary' | 'secondary' | 'ghost' | 'danger'>(() => {
    if (props.variant) return props.variant;
    if (props.isAddButton) return 'primary';
    if (props.isDeleteButton) return 'danger';
    if (props.isCancelButton) return 'secondary';
    return 'secondary';
});

const sizeResolved = computed<'sm' | 'md' | 'lg'>(() => {
    if (props.size) return props.size;
    return props.isSmall ? 'sm' : 'md';
});

const iconSizeClass = computed(() =>
    sizeResolved.value === 'sm' ? 'size-4' : 'size-[18px]'
);

/** Varianten-Matrix aus design-basis.md §3 — Zustände nie über Deckkraft */
const VARIANTS: Record<string, string> = {
    primary:
        'bg-accent-600 border border-accent-600 text-white ' +
        'hover:bg-accent-700 hover:border-accent-700 ' +
        'disabled:bg-surface-canvas disabled:border-border-subtle disabled:text-text-subtle',
    secondary:
        'bg-surface border border-border text-text ' +
        'hover:bg-surface-sunken ' +
        'disabled:bg-surface-canvas disabled:border-border-subtle disabled:text-text-subtle',
    ghost:
        'bg-transparent border border-transparent text-accent-600 ' +
        'hover:bg-accent-50 ' +
        'disabled:text-text-subtle disabled:hover:bg-transparent',
    danger:
        'bg-surface border border-danger-border text-danger ' +
        'hover:bg-danger-surface ' +
        'disabled:bg-surface-canvas disabled:border-border-subtle disabled:text-text-subtle',
};

const SIZES: Record<string, string> = {
    sm: 'h-7 px-2.5 text-xs gap-1.5',
    md: 'h-8 px-3 text-[13px] gap-1.5',
    lg: 'h-10 px-4 text-sm gap-1.5',
};

const buttonClasses = computed(() => [
    'inline-flex items-center justify-center select-none whitespace-nowrap font-medium',
    'rounded-md cursor-pointer disabled:cursor-not-allowed',
    'transition-[background-color,border-color] duration-150 ease-out',
    VARIANTS[variantResolved.value],
    SIZES[sizeResolved.value],
]);
</script>
