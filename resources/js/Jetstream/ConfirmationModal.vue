<template>
    <modal :show="show" :max-width="maxWidth" :closeable="closeable" @close="close">
        <div class="bg-surface rounded-t-lg px-4 pt-5 pb-4 sm:p-4">
            <div class="sm:flex sm:items-start">
                <div class="mx-auto shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-danger-surface sm:mx-0 sm:h-10 sm:w-10">
                    <IconAlertTriangle class="h-6 w-6 text-danger" stroke-width="1.5" aria-hidden="true"/>
                </div>

                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                    <h3 class="font-lexend font-semibold text-lg text-text">
                        <slot name="title"></slot>
                    </h3>

                    <div class="mt-2 text-[13px] text-text-muted">
                        <slot name="content"></slot>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-row justify-end gap-x-2 px-4 py-3 bg-surface-sunken border-t border-border-subtle rounded-b-lg text-right">
            <slot name="footer">
            </slot>
        </div>
    </modal>
</template>

<script>
    import { defineComponent } from 'vue'
    import { IconAlertTriangle } from '@tabler/icons-vue'
    import Modal from './Modal.vue'

    export default defineComponent({
        emits: ['close'],

        components: {
            Modal,
            IconAlertTriangle,
        },

        props: {
            show: {
                default: false
            },
            maxWidth: {
                default: '2xl'
            },
            closeable: {
                default: true
            },
        },

        methods: {
            close() {
                this.$emit('close')
            },
        }
    })
</script>
