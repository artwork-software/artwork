<template>
    <div v-show="message">
        <p class="text-xs text-error subpixel-antialiased">
            {{ message }}
        </p>
    </div>
</template>

<script>
    import { defineComponent } from 'vue'

    export default defineComponent({
        props: ['message']
    })
</script>
