<template>
    <div>
        <TransitionRoot as="template" :show="open">
            <Dialog as="div" class="relative" :style="{ 'z-index': isInShiftPlan ? '999999': zIndex }" @close="$emit('close')">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100"
                                 leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-opacity-50 transition-opacity" :class="showBackdrop ? 'bg-gray-700' : ''"/>

                </TransitionChild>
                <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
                    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300"
                                         enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                         enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200"
                                         leave-from="opacity-100 translate-y-0 sm:scale-100"
                                         @after-enter="initDraggable"
                                         leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        >
                            <DialogPanel class="flex h-fit w-full grow flex-col rounded-lg bg-gradient-to-br  text-left shadow-glass backdrop-blur-2xl p-gap-3xl border draggableModal" :class="[modalSize, showBackdrop ? 'border-gray-300 from-slate-50/80 to-sky-100/50' : 'border-gray-100 from-slate-50/70 to-sky-100/20']"  ref="containerRef">
                                <div class="flex items-center justify-between px-5 pt-5 pb-2">
                                    <div class="text-left">
                                        <h3 class="font-lexend font-bold">{{ $t(props.title) }}</h3>
                                        <p class="text-sm xsDark mt-0.5">
                                            {{ $t(props.description) }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-x-3">
                                        <div class="text-gray-700 hover:text-artwork-buttons-hover transition-all duration-150 ease-in-out cursor-pointer">
                                            <div @click="showBackdrop = !showBackdrop">
                                                <ToolTipDefault top show-background-icon :tooltip-text="showBackdrop ? $t('Remove Backdrop') : $t('Show Backdrop')"/>
                                            </div>
                                        </div>
                                        <div ref="dragHandleRef" class=" hover:text-yellow-600 transition-all duration-150 ease-in-out cursor-grab dragHandle">
                                            <div>
                                                <ToolTipDefault top show-draggable :tooltip-text="$t('Hold here to move')"/>
                                            </div>
                                        </div>
                                        <div class="text-gray-700 hover:text-artwork-messages-error transition-all duration-150 ease-in-out cursor-pointer">
                                            <div @click="$emit('close')">
                                                <ToolTipDefault top show-x-icon :tooltip-text="$t('Close Window')"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-5">
                                    <div class="shadow-[0_2px_5px_rgb(0,0,0,0.12)] p-4 bg-white rounded-lg border border-gray-100 w-full relative">
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
})
const open = ref(true)
const showBackdrop = ref(true)

const emits = defineEmits(['close'])
const containerRef = ref(null)
const dragHandleRef = ref(null)
function initDraggable() {
    nextTick(() => {
        const container = containerRef.value?.$el || containerRef.value
        const dragHandle = dragHandleRef.value;

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