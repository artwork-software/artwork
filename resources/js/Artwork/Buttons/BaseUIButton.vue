<template>
    <button
        :type="type"
        :class="buttonClasses"
        :disabled="disabled"
        :aria-disabled="disabled ? 'true' : 'false'"
        @click="onClick"
        v-bind="$attrs"
    >
        <!-- Icon -->
        <PropertyIcon
            v-if="typeof iconResolved === 'string'"
            :name="iconResolved"
            :class="iconSizeClass"
            :stroke-width="strokeWidthResolved"
        />
        <component
            v-else
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
    /** Add-Button-Variante */
    isAddButton?: boolean;
    /** Delete-Button-Variante */
    isDeleteButton?: boolean;
    isCancelButton?: boolean;
    /** Kleine Variante */
    isSmall?: boolean;
    type?: string;
}>(), {
    disabled: false,
    icon: undefined,
    strokeWidth: 1,
    label: '',
    useTranslation: true,
    isAddButton: false,
    isDeleteButton: false,
    isCancelButton: false,
    isSmall: false,
    type: 'button',
});

const emit = defineEmits<{
    (e: 'click', evt: MouseEvent): void;
}>();

const strokeWidthResolved = computed(() => props.strokeWidth ?? 1);

const iconResolved = computed(() => {
    // Priorität: custom icon → add icon → delete icon → default icon
    if (props.icon) return props.icon;
    if (props.isAddButton) return 'IconCirclePlus';
    if (props.isDeleteButton) return 'IconTrash';
    if (props.isDeleteButton) return 'IconTrash';
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

const iconSizeClass = computed(() =>
    props.isSmall ? 'size-4 sm:size-5' : 'size-5 sm:size-5'
);

const buttonClasses = computed(() => {
    if (props.isAddButton) {
        return props.isSmall ? 'ui-button-add-small' : 'ui-button-add';
    }
    if (props.isDeleteButton) {
        return props.isSmall ? 'ui-button-delete-small' : 'ui-button-delete';
    }
    return props.isSmall ? 'ui-button-small' : 'ui-button';
});
</script>
