<template>
    <Transition
        enter-active-class="transition ease-out duration-150"
        enter-from-class="opacity-0 translate-y-1"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="status !== 'idle'"
            class="fixed bottom-6 right-6 z-50 flex items-center gap-2 rounded-full px-4 py-2 text-sm shadow-lg print:hidden"
            :class="pillClass"
        >
            <svg
                v-if="status === 'saving'"
                class="size-4 animate-spin"
                viewBox="0 0 24 24"
                fill="none"
            >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
            </svg>
            <IconCheck v-else-if="status === 'saved'" class="size-4" />
            <IconAlertTriangle v-else class="size-4" />
            <span class="max-w-md">{{ label }}</span>
            <!-- Fehler bleiben stehen, bis man sie gelesen hat — deshalb schließbar -->
            <button
                v-if="status === 'error'"
                type="button"
                class="ml-1 -mr-1 inline-flex size-5 items-center justify-center rounded-full hover:bg-white/20"
                :aria-label="$t('Close')"
                @click="$emit('dismiss')"
            >
                <IconX class="size-3.5" />
            </button>
        </div>
    </Transition>
</template>

<script setup>
import { computed } from 'vue';
import { IconCheck, IconAlertTriangle, IconX } from '@tabler/icons-vue';
import { useTranslation } from '@/Composeables/Translation.js';

const t = useTranslation();

const props = defineProps({
    // idle | saving | saved | error
    status: { type: String, required: true },
    // Konkreter Grund (Validierungstext) — ersetzt den generischen Fehlertext
    errorMessage: { type: String, default: null },
});

defineEmits(['dismiss']);

const pillClass = computed(() => ({
    saving: 'bg-surface-inverse/90 text-text-inverse',
    saved: 'bg-success text-white',
    error: 'bg-danger text-white',
}[props.status] ?? 'bg-surface-inverse/90 text-text-inverse'));

const label = computed(() => ({
    saving: t('Saving...'),
    saved: t('Saved'),
    error: props.errorMessage
        ? `${t('Not saved')}: ${props.errorMessage}`
        : t('Saving failed — the entry was not stored.'),
}[props.status] ?? ''));
</script>
