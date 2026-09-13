<template>
    <div class="artwork">
        <TransitionRoot as="template" :show="open">
            <Dialog as="div" class="relative" :style="{ 'z-index': isInShiftPlan ? '999999': zIndex }" @close="$emit('close')">
                <TransitionChild as="template" enter="ease-out duration-200 motion-reduce:transition-none" enter-from="opacity-0" enter-to="opacity-100"
                                 leave="ease-in duration-150 motion-reduce:transition-none" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 transition-opacity" :class="showBackdrop ? 'bg-[#1C1F24]/45' : ''"/>
                </TransitionChild>
                <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
                    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-200 motion-reduce:transition-none"
                                         enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                         enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-150 motion-reduce:transition-none"
                                         leave-from="opacity-100 translate-y-0 sm:scale-100"
                                         @after-enter="initDraggable"
                                         leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel class="draggableModal w-full text-left bg-surface rounded-lg shadow-overlay" :class="modalSize" ref="containerRef">
                                <div class="flex items-start justify-between gap-x-6 rounded-t-lg bg-surface-inverse px-5 py-3">
                                    <div class="min-w-0">
                                        <h2 class="font-lexend font-bold text-[16px] text-text-inverse">{{ $t(props.title) }}</h2>
                                        <p v-if="props.description" class="mt-0.5 text-[13px] text-text-inverse-muted tabular-nums">
                                            {{ $t(props.description) }}
                                        </p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-x-1">
                                        <slot name="headerActions"/>
                                        <button
                                            type="button"
                                            class="inline-flex size-7 cursor-pointer items-center justify-center rounded-md bg-white/8 text-text-inverse transition-colors duration-150 motion-reduce:transition-none hover:bg-white/16"
                                            :aria-label="showBackdrop ? $t('Remove Backdrop') : $t('Show Backdrop')"
                                            v-tooltip.bottom="{ value: showBackdrop ? $t('Remove Backdrop') : $t('Show Backdrop'), class: 'aw-tooltip' }"
                                            @click="toggleBackdrop"
                                        >
                                            <PropertyIcon name="IconTexture" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                                        </button>
                                        <div ref="dragHandleRef">
                                            <button
                                                type="button"
                                                class="inline-flex size-7 cursor-grab items-center justify-center rounded-md bg-white/8 text-text-inverse transition-colors duration-150 motion-reduce:transition-none hover:bg-white/16"
                                                :aria-label="$t('Hold here to move')"
                                                v-tooltip.bottom="{ value: $t('Hold here to move'), class: 'aw-tooltip' }"
                                            >
                                                <PropertyIcon name="IconDragDrop" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                                            </button>
                                        </div>
                                        <button
                                            type="button"
                                            class="inline-flex size-7 cursor-pointer items-center justify-center rounded-md bg-white/8 text-text-inverse transition-colors duration-150 motion-reduce:transition-none hover:bg-white/16"
                                            :aria-label="$t('Close Window')"
                                            v-tooltip.bottom="{ value: $t('Close Window'), class: 'aw-tooltip' }"
                                            @click="$emit('close')"
                                        >
                                            <PropertyIcon name="IconX" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                                        </button>
                                    </div>
                                </div>
                                <div class="p-5">
                                    <slot/>
                                </div>
                                <div v-if="$slots.footer" class="sticky bottom-0 flex items-center justify-end gap-x-2 rounded-b-lg border-t border-border-subtle bg-surface-sunken px-5 py-3">
                                    <slot name="footer"/>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </div>
</template>

<script setup>

import axios from "axios";
import {nextTick, ref} from "vue";
import {usePage} from "@inertiajs/vue3";
import {Dialog, DialogPanel, TransitionChild, TransitionRoot} from "@headlessui/vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

//define options for the modal
defineOptions({
    name: 'ArtworkBaseModal'
})

const props = defineProps({
    modalSize: {
        type: String,
        default: 'sm:max-w-2xl'
    },
    isInShiftPlan: {
        type: Boolean,
        default: false
    },
    fullModal: {
        type: Boolean,
        default: false
    },
    zIndex: {
        type: String,
        default: '100'
    },
    title: {
        type: String,
        default: 'Artwork Modal'
    },
    description: {
        type: String,
        default: ''
    },
    showBackdrop: {
        type: Boolean,
        default: true
    },
    classesInWhiteBackground: {
        type: String,
        default: ''
    }
})
const open = ref(true)
// An explicitly disabled prop wins, otherwise the per-user preference decides.
const showBackdrop = ref(
    props.showBackdrop === false
        ? false
        : (usePage().props.auth.user?.show_modal_backdrop ?? true)
)

function toggleBackdrop() {
    showBackdrop.value = !showBackdrop.value

    const user = usePage().props.auth.user
    if (!user) {
        return
    }

    // Keep the shared page props in sync so modals opened later in the
    // same page visit start with the new preference without a reload.
    user.show_modal_backdrop = showBackdrop.value

    axios.patch(route('user.modal.backdrop.update', {user: user.id}), {
        show_modal_backdrop: showBackdrop.value
    })
}

const emits = defineEmits(['close'])
const containerRef = ref(null)
const dragHandleRef = ref(null)
function initDraggable() {
    nextTick(() => {
        const container = containerRef.value?.$el || containerRef.value
        const dragHandle = dragHandleRef?.value.$el || dragHandleRef.value;

        let isDragging = false;
        let offsetX = 0;
        let offsetY = 0;
        let animationFrameId = null;

        dragHandle.addEventListener('mousedown', (event) => {
            isDragging = true;
            offsetX = event.clientX - container.offsetLeft;
            offsetY = event.clientY - container.offsetTop;
        });

        document.addEventListener('mousemove', (event) => {
            if (isDragging) {
                if (animationFrameId !== null) {
                    cancelAnimationFrame(animationFrameId);
                }

                animationFrameId = requestAnimationFrame(() => {
                    container.style.position = 'absolute';
                    container.style.left = `${event.clientX - offsetX}px`;
                    container.style.top = `${event.clientY - offsetY}px`;
                    // Prevent text selection while dragging
                    document.body.classList.add('select-none');
                });
            }
        });

        document.addEventListener('mouseup', () => {
            isDragging = false;
            if (animationFrameId !== null) {
                cancelAnimationFrame(animationFrameId);
            }
            // Remove no-select class when dragging stops
            document.body.classList.remove('select-none');
        });
    })
}
</script>

<style scoped>
.draggableModal {
    transition: transform 0.15s ease-out;
    will-change: transform;
}
@media (prefers-reduced-motion: reduce) {
    .draggableModal {
        transition: none;
    }
}
</style>
