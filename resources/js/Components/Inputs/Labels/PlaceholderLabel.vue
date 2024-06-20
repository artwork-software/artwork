<template>
    <label :for="this.for" class="cursor-text absolute left-0 -top-4 text-xs text-black transition-all subpixel-antialiased focus:outline-none peer-placeholder-shown:text-secondary peer-placeholder-shown:left-2 peer-placeholder-shown:top-4 pointer-events-none">
        {{ this.label }}
    </label>
</template>

<script>
import {defineComponent} from "vue";

export default defineComponent({
    props: [
        'label',
        'for'
    ],
    methods: {
        focusInput() {
            document.getElementById(this.for).focus();
        }
    }
})
</script>
