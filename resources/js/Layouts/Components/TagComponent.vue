<template>
<span class="rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
    {{ displayedText }}
    <button type="button" @click="this.method(property)">
        <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
    </button>
</span>
</template>

<script>
import Button from "@/Jetstream/Button";
import {XIcon} from "@heroicons/vue/outline";

export default {
    name: "TagComponent",
    components: {Button, XIcon},
    props: {
        property: String,
        displayedText: String,
        method: { type: Function},
    }
}
</script>

<style scoped>

</style>
