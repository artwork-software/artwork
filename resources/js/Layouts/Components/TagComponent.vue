<template>
    <div v-if="type === 'gray'"
         class="rounded-full items-center font-medium text-secondary border bg-tagBgGray border-gray-500 px-3 text-sm mr-1 mb-1 min-h-8 inline-flex">
        <img v-if="icon === 'audience'" src="/Svgs/IconSvgs/icon_public.svg" class=" h-6 w-6 mx-2"
             alt="audienceIcon"/>
        {{ displayedText }}
        <button v-if="!hideX" type="button" @click="this.method(property)">
            <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
        </button>
    </div>
    <div v-else
         class="rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex"
         :style="[property?.color ? { backgroundColor: backgroundColorWithOpacity(property?.color), color: TextColorWithDarken(property?.color), borderColor: TextColorWithDarken(property?.color) } : {}]"
    >
        <img v-if="icon === 'audience'" src="/Svgs/IconSvgs/icon_public.svg" class=" h-6 w-6 mx-2"
             alt="audienceIcon"/>
        {{ displayedText }}
        <button v-if="!hideX" type="button" @click="this.method(property)">
            <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
        </button>
    </div>
</template>

<script>

import {XIcon} from "@heroicons/vue/outline";
import ColorHelper from "@/Mixins/ColorHelper.vue";

export default {
    name: "TagComponent",
    components: {XIcon},
    mixins: [ColorHelper],
    props: {
        property: {
            type: [Object, String, Number],
            required: false,
            default: null
        },
        displayedText: String,
        method: {type: Function},
        hideX: false,
        type: String,
        icon: String,
    }
}
</script>

<style scoped>

</style>
