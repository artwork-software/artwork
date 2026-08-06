<script>
import Button from "@/Jetstream/Button.vue";

export default {
    name: "BaseButton",
    components: {Button},
    props: {
        text: String,
        disabled: Boolean,
        horizontalPadding: {
            type: String,
            default: "px-4"
        },
        verticalPadding: {
            type: String,
            default: "py-2"
        },
        textColor: {
            type: String,
            default: "text-white"
        },
        borderWidth: {
            type: String,
            default: "border"
        },
        borderColor: {
            type: String,
            default: "border-transparent"
        },
        backgroundColor: {
            type: String,
            default: "bg-accent-600"
        },
        classes: {
            type: String,
            default: ""
        },
        type: {
            type: String,
            default: "button"
        }
    },
    computed: {
        buttonStyle(){
            switch (this.type) {
                case 'button':
                    return ''
                case 'submit':
                    return ''
                case 'reset':
                    return ''
                case 'cancel':
                    return 'bg-border hover:bg-border-strong !text-text'
                default:
                    return ''
            }
        }
    }
}

</script>

<template>
    <button
       :type="type"
       :disabled="disabled"
       :class="[disabled ? 'bg-text-subtle' : `${backgroundColor} hover:bg-accent-700`,
       $props.horizontalPadding, textColor, borderWidth, borderColor, verticalPadding, classes, buttonStyle]"
       class="flex items-center rounded-lg shadow-sm focus:outline-none transition-all duration-150 ease-in-out "
    >
        <!-- slot for the icon -->
        <slot></slot>
        <p class="text-sm">{{ text }}</p>
    </button>
</template>

<style scoped>

</style>
