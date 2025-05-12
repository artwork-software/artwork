<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative" :style="{ 'z-index': isInShiftPlan ? '999999': zIndex }" @close="closeModal">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100"
                             leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-opacity-75 transition-opacity" :class="showBackdrop ? 'bg-gray-500' : ''"/>

            </TransitionChild>
            <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300"
                                     enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                     enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200"
                                     leave-from="opacity-100 translate-y-0 sm:scale-100"
                                     leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                     @after-enter="makeContainerDraggable">
                        <DialogPanel class="flex h-fit w-full grow flex-col rounded-lg bg-gradient-to-br  text-left shadow-glass backdrop-blur-2xl p-gap-3xl border draggableModal" :class="[modalSize, showBackdrop ? 'border-gray-300 from-slate-50/80 to-sky-100/50' : 'border-gray-100 from-slate-50/70 to-sky-100/20']"  ref="containerRef">
                            <div class="flex items-center justify-end px-5 pt-5 pb-2">
                                <div class="flex items-center gap-x-3">
                                    <div class="text-gray-700 hover:text-artwork-buttons-hover transition-all duration-150 ease-in-out cursor-pointer">
                                        <div @click="showBackdrop = !showBackdrop">
                                            <ToolTipDefault bottom show-background-icon :tooltip-text="showBackdrop ? $t('Remove Backdrop') : $t('Show Backdrop')"/>
                                        </div>
                                    </div>
                                    <div ref="dragHandleRef" class=" hover:text-yellow-600 transition-all duration-150 ease-in-out cursor-grab dragHandle">
                                        <div>
                                            <ToolTipDefault bottom show-draggable :tooltip-text="$t('Hold here to move')"/>
                                        </div>
                                    </div>
                                    <div class="text-gray-700 hover:text-artwork-messages-error transition-all duration-150 ease-in-out cursor-pointer">
                                        <div @click="closeModal">
                                            <ToolTipDefault bottom show-x-icon :tooltip-text="$t('Close Window')"/>
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
</template>
<script>
import {Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot} from '@headlessui/vue'
import {XIcon} from "@heroicons/vue/solid";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import {IconBackground} from "@tabler/icons-vue";
import ToolTipDefault from "@/Components/ToolTips/ToolTipDefault.vue";

export default {
    name: "BaseModal",
    mixins: [Permissions, IconLib],
    components: {
        ToolTipDefault,
        FormButton,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        XIcon, DialogPanel, IconBackground
    },
    data() {
        return {
            open: true,
            showBackdrop: true,
            isDragging: false
        }
    },
    props: {
        modalSize: {
            type: String,
            default: 'sm:max-w-2xl'
        },
        fullModal: {
            type: Boolean,
            default: false
        },
        isInShiftPlan: {
            type: Boolean,
            default: false
        },
        zIndex: {
            type: Number,
            default: 150
        }
    },
    mounted() {

    },
    emits: ['closed'],
    methods: {
        closeModal(bool) {
            this.$emit('closed', bool)
        },
        makeContainerDraggable(){
            const container = this.$refs.containerRef?.$el || this.$refs.containerRef
            const dragHandle = this.$refs.dragHandle;

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
        },
    }
}
</script>

<style scoped>
.no-select {
    user-select: none; /* Disable text selection */
    -webkit-user-select: none; /* Safari */
    -moz-user-select: none; /* Firefox */
    -ms-user-select: none; /* Internet Explorer/Edge */
}
</style>
