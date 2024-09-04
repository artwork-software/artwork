<template>
    <label :for="this.for" class="input-label" :class="[isSmall ? ' peer-placeholder-shown:top-3' : ' peer-placeholder-shown:top-4', isDark ? '!text-gray-600' : '']">
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
        },
        isDark: {
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
