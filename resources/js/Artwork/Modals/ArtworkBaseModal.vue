<template>
    <div class="artwork">
        <TransitionRoot as="template" :show="open">
            <Dialog as="div" class="relative" :style="{ 'z-index': isInShiftPlan ? '999999': zIndex }" @close="$emit('close')">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100"
                                 leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-opacity-50 transition-opacity" :class="showBackdrop ? 'bg-gray-500/40 backdrop-blur-xs' : ''"/>
                </TransitionChild>
                <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
                    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300"
                                         enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                         enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200"
                                         leave-from="opacity-100 translate-y-0 sm:scale-100"
                                         @after-enter="initDraggable"
                                         leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel class="card glassy draggableModal w-full" :class="[modalSize, showBackdrop ? '' : '!border-gray-200 shadow-glass']"  ref="containerRef">

                                <div class="p-5">
                                    <div class="card white p-5 relative">
                                        <div class="flex items-start gap-x-8 justify-between mb-8 bg-zinc-50 p-6 -mx-5 -mt-5 rounded-t-lg">
                                            <div class="text-left">
                                                <h3 class="font-medium text-lg subpixel-antialiased">{{ $t(props.title) }}</h3>
                                                <p class="text-xs text-zinc-500 subpixel-antialiased mt-0.5">
                                                    {{ $t(props.description) }}
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-x-3">
                                                <div class="ui-button" @click="showBackdrop = !showBackdrop">
                                                    <div>
                                                        <ToolTipComponent :icon="IconTexture" :tooltip-text="showBackdrop ? $t('Remove Backdrop') : $t('Show Backdrop')"/>
                                                    </div>
                                                </div>
                                                <div ref="dragHandleRef" class="ui-button hover:!bg-yellow-50">
                                                    <div>
                                                        <ToolTipComponent :icon="IconDragDrop" :tooltip-text="$t('Hold here to move')"/>
                                                    </div>
                                                </div>
                                                <div class="ui-button hover:!bg-red-50 !text-red-500" @click="$emit('close')">
                                                    <div>
                                                        <ToolTipComponent :icon="IconX" :tooltip-text="$t('Close Window')" classes="!text-red-500"/>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <slot/>
                                    </div>
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

import ToolTipDefault from "@/Components/ToolTips/ToolTipDefault.vue";
import {nextTick, onMounted, ref} from "vue";
import {Dialog, DialogPanel, TransitionChild, TransitionRoot} from "@headlessui/vue";
import { createDraggable } from 'animejs';
import CardHeadline from "@/Artwork/Cards/CardHeadline.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import {IconDragDrop, IconTexture, IconX} from "@tabler/icons-vue";

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
        default: 'This is a description'
    },
    showBackdrop: {
        type: Boolean,
        default: true
    }
})
const open = ref(true)
const showBackdrop = ref(props.showBackdrop)

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
</style>
