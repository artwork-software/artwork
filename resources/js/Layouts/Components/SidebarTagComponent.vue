<template>
<span class="rounded-full items-center font-medium border px-3 text-sm mr-1 mb-1 h-8 inline-flex"
      :style="{backgroundColor: backgroundColorWithOpacity(item?.color), color: TextColorWithDarken(item?.color), borderColor: TextColorWithDarken(item?.color)}">
    <img v-if="icon === 'audience'" src="/Svgs/IconSvgs/icon_public_artwork-buttons-create.svg" class=" h-6 w-6 mx-2" alt="audienceIcon"/>
    {{ item?.name }}
    <button v-if="!hideX" type="button" @click="this.method(property)">
        <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
    </button>
</span>

</template>

<script>
import Button from "@/Jetstream/Button.vue";
import {XIcon} from "@heroicons/vue/outline";
import Permissions from "@/Mixins/Permissions.vue";
import ColorHelper from "@/Mixins/ColorHelper.vue";

export default {
    name: "SidebarTagComponent",
    components: {Button, XIcon},
    mixins: [Permissions, ColorHelper],
    props: ['item', 'icon', 'hideX', 'method', 'property'],
}
</script>

<style scoped>

</style>
