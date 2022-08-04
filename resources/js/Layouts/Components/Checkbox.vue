<template>
    <div>
        <div class="flex justify-between items-center my-1.5 h-5">
            <div class="flex items-center justify-start">
                <input @change="changeStyle(item)" :key="item.name" v-model="item.checked" type="checkbox"
                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                <p :class="[checkedStyle ? 'text-primary font-black' : 'text-secondary']"
                   class="ml-4 my-auto text-sm">{{ item.name }}</p>
                <div v-if="type === 'role'">
                    <div v-if="$page.props.can.show_hints" class="flex ml-2 mt-2">
                        <SvgCollection svgName="arrowLeft" class="mt-5 ml-2"/>
                        <span class="font-nanum max-w-xs text-secondary tracking-tight leading-5 text-lg ml-2 my-auto">Administratoren haben im gesamten System <br>Lese- und Schreibrechte - weitere Einstellungen entfallen</span>
                    </div>
                </div>
            </div>
            <div :data-tooltip-target="item.name" v-if="item.showIcon">
            <InformationCircleIcon  class="h-7 w-7 flex text-gray-400"
                                   aria-hidden="true"/>
            </div>
            <div :id="item.name" role="tooltip" class="inline-block absolute invisible z-10 py-2 px-3 text-sm font-medium text-secondary bg-primary rounded-lg shadow-sm opacity-0 transition-opacity duration-300 tooltip">
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
    props: ['item', 'type'],
    data() {
        return {
            checkedStyle: this.item.checked,
        }
    },
    methods: {
        changeStyle() {
            this.checkedStyle = !this.checkedStyle
        }
    }
}
</script>

<style scoped>

</style>
