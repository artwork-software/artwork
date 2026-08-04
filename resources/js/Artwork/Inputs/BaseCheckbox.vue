<!-- BaseCheckbox.vue -->
<template>
    <div class="flex gap-3 artwork">
        <div class="flex h-5 shrink-0 items-center">
            <div class="group grid size-4 grid-cols-1">
                <input
                    :id="computedId"
                    :name="name || computedId"
                    type="checkbox"
                    class="aw-checklist-input"
                    :checked="modelValue === true"
                    :disabled="disabled"
                    :required="required"
                    :aria-describedby="hasDescription ? descriptionId : undefined"
                    @change="onChange"
                    ref="inputEl"
                />
                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-text-subtle" viewBox="0 0 14 14" fill="none">
                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
        </div>

        <div class="min-w-0">
            <label :for="computedId" class="block font-medium text-[13px]/5 text-text">
                <slot name="label">{{ label }}</slot>
            </label>
            <p v-if="hasDescription" :id="descriptionId" class="text-xs text-text-muted">
                <slot name="description">{{ description }}</slot>
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';

type TriState = boolean | 'indeterminate';

const props = withDefaults(defineProps<{
    modelValue: TriState
    label?: string
    description?: string
    id?: string
    name?: string
    disabled?: boolean
    required?: boolean
}>(), {
    modelValue: false,
    label: '',
    description: undefined,
    disabled: false,
    required: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', v: boolean): void
    (e: 'change', v: boolean): void
}>();

const inputEl = ref<HTMLInputElement | null>(null);

// Fallback-ID, falls keine übergeben wurde
const uid = Math.random().toString(36).slice(2);
const computedId = computed(() => props.id || `chk-${uid}`);
const descriptionId = `desc-${computedId.value}`;
const hasDescription = computed(() => !!props.description);

// Browser-Property für "indeterminate" zuverlässig setzen
const syncIndeterminate = () => {
    if (inputEl.value) {
        inputEl.value.indeterminate = props.modelValue === 'indeterminate';
    }
};

onMounted(syncIndeterminate);
watch(() => props.modelValue, syncIndeterminate);

// Wenn der Nutzer klickt/ändert: echtes boolean weitergeben
const onChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    // Sobald der Nutzer interagiert, ist der Zustand nicht mehr indeterminate
    // → wir senden reines boolean weiter
    emit('update:modelValue', target.checked);
    emit('change', target.checked);
};
</script>
