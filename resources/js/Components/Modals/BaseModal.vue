<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-50" @close="closeModal">
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
                                     leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="modal" :class="[modalSize, fullModal ? '' : 'sm:p-6 px-4 pt-5 pb-4', showBackdrop ? '' : 'border border-gray-300']"  ref="containerRef">
                            <div class="absolute top-0 right-0 pt-4 pr-4 hidden sm:block z-50">
                                <div class="flex items-center gap-x-3">
                                    <div class="text-gray-400 hover:text-artwork-buttons-hover transition-all duration-150 ease-in-out cursor-pointer">
                                        <div @click="showBackdrop = !showBackdrop">
                                            <ToolTipDefault top show-background-icon :tooltip-text="showBackdrop ? $t('Remove Backdrop') : $t('Show Backdrop')"/>
                                        </div>
                                    </div>
                                    <div ref="dragHandle" class=" hover:text-artwork-messages-waring transition-all duration-150 ease-in-out cursor-grab" :class="isDragging ? 'text-artwork-messages-waring' : 'text-gray-400' ">
                                        <div>
                                            <ToolTipDefault top show-draggable :tooltip-text="$t('Hold here to move')"/>
                                        </div>
                                    </div>
                                    <div class="text-gray-400 hover:text-artwork-messages-error transition-all duration-150 ease-in-out cursor-pointer">
                                        <div @click="closeModal(false)">
                                            <ToolTipDefault top show-x-icon :tooltip-text="$t('Close Window')"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <slot/>
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
        //@todo: deprecated, remove
        modalImage: {
            type: String,
            default: '/Svgs/Overlays/illu_appointment_edit.svg'
        },
        //@todo: deprecated, remove
        showImage: {
            type: Boolean,
            default: true
        },
        modalSize: {
            type: String,
            default: 'sm:max-w-2xl'
        },
        fullModal: {
            type: Boolean,
            default: false
        }
    },
    mounted() {
        this.$nextTick(() => {
            const container = this.$refs.containerRef?.$el || this.$refs.containerRef;
            if (container && container instanceof HTMLElement) {
                this.makeContainerDraggable();
            } else {
                console.error('containerRef is not a valid DOM element');
            }
        });
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
            /*const container = this.$refs.containerRef?.$el || this.$refs.containerRef;
            const dragHandle = this.$refs.dragHandle;

            if (!container || !(container instanceof HTMLElement) || !dragHandle) {
                console.error('containerRef or dragHandle is not a valid DOM element');
                return;
            }

            this.isDragging = false;
            let offsetX, offsetY;

            const onMouseDown = (event) => {
                event.preventDefault();
                this.isDragging = true;

                // Berechne den Offset zwischen Mausposition und der aktuellen Position des Modals
                const rect = container.getBoundingClientRect();
                offsetX = event.clientX - rect.left;
                offsetY = event.clientY - rect.top;

                // Füge Event-Listener für Mousemove und Mouseup hinzu
                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            };

            const onMouseMove = (event) => {
                if (!this.isDragging) return;

                // Berechne die neue Position basierend auf der aktuellen Mausposition minus dem Offset
                const newX = event.clientX - offsetX;
                const newY = event.clientY - offsetY;

                // Setze die neue Position des Modals sofort
                container.style.position = 'absolute';
                container.style.left = `${newX}px`;
                container.style.top = `${newY}px`;
            };

            const onMouseUp = () => {
                if (!this.isDragging) return;

                this.isDragging = false;

                // Entferne die Event-Listener
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
            };

            // Füge den mousedown Event-Listener nur am Drag-Handle hinzu
            dragHandle.addEventListener('mousedown', onMouseDown);
             */
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
