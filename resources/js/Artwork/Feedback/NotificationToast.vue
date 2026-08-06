<template>
    <div
        aria-live="assertive"
        class="pointer-events-none fixed inset-0 flex items-end px-4 py-6 sm:items-start sm:p-6 z-50"
    >
        <div class="flex w-full flex-col items-center space-y-4 sm:items-end">
            <transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg bg-surface border border-border-subtle shadow-overlay" v-if="visible">
                    <div class="p-4">
                        <div class="flex items-stretch">
                            <div class="p-1 rounded-lg" :class="{'bg-success': type === 'success', 'bg-danger': type === 'danger', 'bg-warning': type === 'warning'}"></div>
                            <div class="ml-3 w-0 flex-1 pt-0.5">
                                <p class="text-sm font-medium text-text font-lexend">{{ $t(title) }}</p>
                                <p v-if="description" class="mt-1 text-[13px] text-text-muted">{{ $t(description) }}</p>
                            </div>
                            <div class="ml-4 flex shrink-0">
                                <button
                                    type="button"
                                    @click="visible = false"
                                    class="inline-flex rounded-md bg-surface text-text-subtle hover:text-text-muted">
                                    <span class="sr-only">{{ $t('Close') }}</span>
                                    <PropertyIcon name="IconX" class="size-5" :stroke-width="1.5" aria-hidden="true" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

const props = defineProps({
    show: Boolean,
    title: {
        type: String,
        default: 'Erfolgreich gespeichert!'
    },
    description: {
        type: String,
        default: ''
    },
    duration: {
        type: Number,
        default: 5000
    },
    type: {
        type: String,
        default: 'success'
    }
})

const emit = defineEmits(['update:show'])

const visible = ref(false)

watch(
    () => props.show,
    (val) => {
        if (val) {
            visible.value = true
            setTimeout(() => {
                visible.value = false
                emit('update:show', false)
            }, props.duration)
        }
    }
)
</script>
