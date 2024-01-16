<template>
    <div class="flex justify-between items-center my-1.5 h-5">
        <div class="flex items-center justify-start">
            <input :key="item.name" v-model="item.checked" type="checkbox"
                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <p :class="[item.checked ? 'xsDark' : 'xsLight']"
               class="ml-4 my-auto text-sm" v-if="!item.name_de">{{ item.name }}</p>
            <p :class="[item.checked ? 'xsDark' : 'xsLight']"
               class="ml-4 my-auto text-sm" v-else>{{item.name_de}}</p>
        </div>
        <div v-if="item.showIcon !== false">
            <TextToolTip :id="item.name" :height="6" :width="6" :tooltip-text="item.tooltipText" />
        </div>
    </div>
</template>

<script>
import {InformationCircleIcon} from "@heroicons/vue/outline";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import TextToolTip from "@/Layouts/Components/TextToolTip.vue";
import Permissions from "@/mixins/Permissions.vue";

export default {
    name: "Checkbox",
    components: {
        TextToolTip,
        InformationCircleIcon,
        SvgCollection
    },
    mixins: [Permissions],
    props: ['item'],
    methods: {
    }
}
</script>

<style scoped>

</style>
