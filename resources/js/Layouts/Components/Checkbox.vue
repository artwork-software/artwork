<template>
    <div>
        <div class="flex justify-between items-center my-1.5 h-5">
            <div class="flex items-center justify-start">
                <input :key="item.name" v-model="item.checked" type="checkbox"
                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p :class="[item.checked ? 'xsDark' : 'xsLight']"
                   class="ml-4 my-auto text-sm" v-if="!item.name_de">{{ item.name }}</p>
                <p :class="[item.checked ? 'xsDark' : 'xsLight']"
                   class="ml-4 my-auto text-sm" v-else>{{item.name_de}}</p>
            </div>
            <div v-if="item.showIcon !== false" :data-tooltip-target="item.name">
            <InformationCircleIcon  class="h-7 w-7 flex text-gray-400"
                                   aria-hidden="true"/>
            </div>
            <div :id="item.name" role="tooltip" class="inline-block bg-primary absolute invisible z-10 py-2 px-3 text-sm font-medium text-secondary bg-primary rounded-lg shadow-md opacity-0 transition-opacity duration-300 tooltip">
                {{item.tooltipText}}
                <div class="tooltip-arrow" data-popper-arrow></div>
            </div>
        </div>


    </div>
</template>

<script>
import {InformationCircleIcon} from "@heroicons/vue/outline";
import SvgCollection from "@/Layouts/Components/SvgCollection";

export default {
    name: "Checkbox",
    components: {
        InformationCircleIcon,
        SvgCollection
    },
    props: ['item'],
    methods: {
    }
}
</script>

<style scoped>

</style>
