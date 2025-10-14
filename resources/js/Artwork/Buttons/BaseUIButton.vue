<template>
    <button
        type="button"
        :class="isAddButton ? isSmall ? 'ui-button-add-small' : 'ui-button-add' : isSmall ? 'ui-button-small' : 'ui-button'"
        :disabled="disabled"
        :aria-disabled="String(!!disabled)"
        @click="onClick"
        v-bind="$attrs"
    >
        <PropertyIcon
            :name="iconResolved"
            :class="isSmall ? 'size-4 sm:size-5' : 'size-5 sm:size-6'"
            :stroke-width="strokeWidthResolved"
        />
        {{ useTranslation ? $t(label) : label }}
    </button>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { IconCirclePlus } from "@tabler/icons-vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

defineOptions({ name: "BaseUIButton" });

const props = defineProps<{
    /** Button deaktivieren */
    disabled?: boolean;
    /** Icon-Komponente oder Name (z. B. 'IconCirclePlus') */
    icon?: unknown;
    /** Strichstärke für Tabler-Icons */
    strokeWidth?: number | string;
    /** Fallback-Label, falls kein Slot genutzt wird */
    label?: string;
    /** useTranslation default true */
    useTranslation?: boolean;
    isAddButton?: boolean;
    isSmall?: boolean;
}>();

const emit = defineEmits<{
    (e: "click", evt: MouseEvent): void;
}>();

const iconResolved = computed(() => props.icon ?? IconCirclePlus);
const strokeWidthResolved = computed(() => props.strokeWidth ?? 1);

function onClick(evt: MouseEvent) {
    if (props.disabled) return;
    emit("click", evt);
}
</script>
