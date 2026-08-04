<template>
    <div v-if="type === 'gray'"
         class="rounded-full items-center font-medium text-text-subtle border bg-tagBgGray border-border-strong px-3 text-sm mr-1 mb-1 min-h-8 inline-flex">
        <img v-if="icon === 'audience'" src="/Svgs/IconSvgs/icon_public.svg" class=" h-6 w-6 mx-2"
             alt="audienceIcon"/>
        {{ displayedText }}
        <button v-if="!hideX" type="button" @click="this.method(property)">
            <IconX class="ml-1 h-4 w-4 hover:text-danger "/>
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
            <IconX class="ml-1 h-4 w-4 hover:text-danger "/>
        </button>
    </div>
</template>

<script>
import {IconX} from "@tabler/icons-vue";

import {useColorHelper} from "@/Composeables/UseColorHelper.js";

export default {
    name: "TagComponent",
    components: {IconX},
    setup() {
        const {backgroundColorWithOpacityOld: backgroundColorWithOpacity, TextColorWithDarken} = useColorHelper();
        return {backgroundColorWithOpacity, TextColorWithDarken};
    },
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
