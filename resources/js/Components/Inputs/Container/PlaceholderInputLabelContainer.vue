<template>
    <div :class="noMarginTop ? '' : 'mt-5'" class="flex flex-col relative w-full h-full">
        <slot/>
    </div>
</template>

<script>
import {defineComponent} from "vue";

export default defineComponent({
    props: {
        noMarginTop: {
            type: Boolean,
            default: false
        }
    }
});
</script>
