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
                                <div class="flex items-start justify-between px-5 pt-5 pb-2">
                                    <div class="text-left">
                                        <h3 class="font-lexend font-bold">{{ $t(props.title) }}</h3>
                                        <p class="text-sm xsDark mt-0.5">
                                            {{ $t(props.description) }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-x-3">
                                        <div class="text-gray-800 hover:text-artwork-buttons-hover transition-all duration-150 ease-in-out cursor-pointer">
                                            <div @click="showBackdrop = !showBackdrop">
                                                <ToolTipComponent 
                                                    icon="IconBackground"
                                                    :tooltip-text="showBackdrop ? $t('Remove Backdrop') : $t('Show Backdrop')"
                                                    classes="text-gray-800"
                                                    direction="top"
                                                />
                                            </div>
                                        </div>
                                        <div ref="dragHandleRef" class="text-gray-800 hover:text-yellow-400 transition-all duration-150 ease-in-out cursor-grab dragHandle">
                                            <div>
                                                <ToolTipComponent 
                                                    icon="IconDragDrop"
                                                    :tooltip-text="$t('Hold here to move')"
                                                    classes="text-gray-800"
                                                    direction="top"
                                                />
                                            </div>
                                        </div>
                                        <div class="text-gray-800 hover:text-artwork-error transition-all duration-150 ease-in-out cursor-pointer">
                                            <div @click="$emit('close')">
                                                <ToolTipComponent 
                                                    icon="IconX"
                                                    :tooltip-text="$t('Close Window')"
                                                    classes="text-gray-800"
                                                    direction="top"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-5">
                                    <div class="card white p-5 relative" :class="[classesInWhiteBackground]">
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
    },
    classesInWhiteBackground: {
        type: String,
        default: ''
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