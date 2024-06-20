<template>
    <label :for="this.for" class="cursor-text absolute left-0 -top-5 xsLight peer-placeholder-shown:text-xs transition-all subpixel-antialiased focus:outline-none peer-placeholder-shown:text-secondary peer-placeholder-shown:left-2 pointer-events-none" :class="isSmall ? ' peer-placeholder-shown:top-3' : ' peer-placeholder-shown:top-4'">
        {{ this.label }}
    </label>
</template>

<script>
import {defineComponent} from "vue";

export default defineComponent({
    props: {
        for: {
            type: String,
            required: false
        },
        label: {
            type: String,
            required: true
        },
        isSmall: {
            type: Boolean,
            default: false
        }
    },
    methods: {
        focusInput() {
            document.getElementById(this.for).focus();
        }
    }
})
</script>
