<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative" :style="{ 'z-index': isInShiftPlan ? '999999': zIndex }" @close="closeModal">
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
                                     leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                     @after-enter="makeContainerDraggable">
                        <DialogPanel class="draggableModal w-full text-left bg-surface border border-border rounded-lg shadow-overlay" :class="[modalSize]" ref="containerRef">
                            <div class="flex items-center justify-end gap-x-1 px-4 pt-3">
                                <button
                                    type="button"
                                    class="inline-flex size-7 cursor-pointer items-center justify-center rounded-md text-text-muted transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-sunken hover:text-text"
                                    :aria-label="showBackdrop ? $t('Remove Backdrop') : $t('Show Backdrop')"
                                    v-tooltip.bottom="{ value: showBackdrop ? $t('Remove Backdrop') : $t('Show Backdrop'), class: 'aw-tooltip' }"
                                    @click="toggleBackdrop"
                                >
                                    <PropertyIcon name="IconBackground" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                                </button>
                                <div ref="dragHandleRef" class="dragHandle">
                                    <button
                                        type="button"
                                        class="inline-flex size-7 cursor-grab items-center justify-center rounded-md text-text-muted transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-sunken hover:text-text"
                                        :aria-label="$t('Hold here to move')"
                                        v-tooltip.bottom="{ value: $t('Hold here to move'), class: 'aw-tooltip' }"
                                    >
                                        <PropertyIcon name="IconDragDrop" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                                    </button>
                                </div>
                                <button
                                    type="button"
                                    class="inline-flex size-7 cursor-pointer items-center justify-center rounded-md text-text-muted transition-colors duration-150 motion-reduce:transition-none hover:bg-danger-surface hover:text-danger"
                                    :aria-label="$t('Close Window')"
                                    v-tooltip.bottom="{ value: $t('Close Window'), class: 'aw-tooltip' }"
                                    @click="closeModal"
                                >
                                    <PropertyIcon name="IconX" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                                </button>
                            </div>
                            <div class="p-4">
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
import axios from 'axios';
import {Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot} from '@headlessui/vue'
import Permissions from "@/Mixins/Permissions.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

export default {
    name: "BaseModal",
    mixins: [Permissions],
    components: {
        PropertyIcon,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        DialogPanel
    },
    data() {
        return {
            open: true,
            showBackdrop: this.$page.props.auth.user?.show_modal_backdrop ?? true,
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
        toggleBackdrop() {
            this.showBackdrop = !this.showBackdrop

            const user = this.$page.props.auth.user
            if (!user) {
                return
            }

            // Keep the shared page props in sync so modals opened later in the
            // same page visit start with the new preference without a reload.
            user.show_modal_backdrop = this.showBackdrop

            axios.patch(route('user.modal.backdrop.update', {user: user.id}), {
                show_modal_backdrop: this.showBackdrop
            })
        },
        makeContainerDraggable(){
            const container = this.$refs.containerRef?.$el || this.$refs.containerRef

            const dragHandle = this.$refs.dragHandleRef?.$el || this.$refs.dragHandleRef;

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
