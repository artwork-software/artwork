<template>
    <modal :show="show" :max-width="maxWidth" :closeable="closeable" @close="close">
        <div class="p-4 text-left">
            <div class="font-lexend font-semibold text-lg text-text">
                <slot name="title">
                </slot>
            </div>

            <div class="mt-4">
                <slot name="content">
                </slot>
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
    import Modal from './Modal.vue'

    export default defineComponent({
        emits: ['close'],

        components: {
            Modal,
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
