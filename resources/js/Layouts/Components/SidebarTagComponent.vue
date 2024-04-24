<template>
<span class="rounded-full items-center font-medium text-gray-300 border bg-gray-600 border-gray-500 px-3 text-sm mr-1 mb-1 h-8 inline-flex">
    <img v-if="icon === 'audience'" src="/Svgs/IconSvgs/icon_public_buttonBlue.svg" class=" h-6 w-6 mx-2" alt="audienceIcon"/>
    {{ displayedText }}
    <button v-if="!hideX" type="button" @click="this.method(property)">
        <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
    </button>
</span>
</template>

<script>
import Button from "@/Jetstream/Button";
import {XIcon} from "@heroicons/vue/outline";
import Permissions from "@/Mixins/Permissions.vue";

export default {
    name: "SidebarTagComponent",
    components: {Button, XIcon},
    mixins: [Permissions],
    props: {
        property: String,
        displayedText: String,
        method: { type: Function},
        hideX: false,
        icon: String,
    }
}
</script>

<style scoped>

</style>
