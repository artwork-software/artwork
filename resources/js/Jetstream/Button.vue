<template>
    <BaseUIButton variant="primary" :type="type" hide-icon>
        <slot></slot>
    </BaseUIButton>
</template>

<script>
    import { defineComponent } from 'vue'
    import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

    export default defineComponent({
        components: {
            BaseUIButton,
        },
        props: {
            type: {
                type: String,
                default: 'submit',
            },
        }
    })
</script>
