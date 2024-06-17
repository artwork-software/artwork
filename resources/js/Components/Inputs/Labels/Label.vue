<template>
    <label :for="this.for"
           class="cursor-text text-xs text-black subpixel-antialiased">
        {{ this.label }}
    </label>
</template>

<script>
import {defineComponent} from "vue";

export default defineComponent({
    props: [
        'label',
        'for'
    ]
})
</script>
