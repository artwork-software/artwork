<template>
    <label :for="this.for"
           class="cursor-text absolute left-0 -top-3.5 text-sm text-black transition-all subpixel-antialiased focus:outline-none peer-placeholder-shown:text-secondary peer-placeholder-shown:left-2.5 peer-placeholder-shown:top-2.5">
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
