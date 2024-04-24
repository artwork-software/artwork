<script>
import ComponentIcons from "@/Components/Globale/ComponentIcons.vue";
import ToolTipDefault from "@/Components/ToolTips/ToolTipDefault.vue";
import DropComponentsToolTip from "@/Components/ToolTips/DropComponentsToolTip.vue";

export default {
    name: "DragComponentElement",
    components: {DropComponentsToolTip, ToolTipDefault, ComponentIcons},
    props: ['component'],
    methods: {
        onDragStart(event) {
            event.dataTransfer.setData(
                'application/json',
                JSON.stringify( {
                    id: this.component.id,
                    type: this.component.type,
                    name: this.component.name,
                    drop_type: 'component',
                    sidebar_enabled: this.component.sidebar_enabled
                })
            );
        }
    }
}
</script>

<template>
    <DropComponentsToolTip :top="true" :tooltip-text="component.special ? $t(component.name) : component.name">
        <div class="p-3 rounded-lg border mb-3 overflow-auto hover:cursor-grab flex flex-col h-28 w-full justify-center items-center" draggable="true" @dragstart="onDragStart">
            <div class="flex items-center justify-center mb-2">
                <ComponentIcons :type="component.type" />
            </div>
            <div class="text-center text-sm font-bold w-20">
                <div v-if="component.special" class="truncate">
                    {{ $t(component.name) }}
                </div>
                <div v-else class="w-20 truncate">
                    {{ component.name }}
                    <div class="text-[10px] text-gray-500 font-light" v-if="component.data.height">
                        {{ component.data.height }} Pixel <span v-if="component.data.showLine === true">| {{ $t('Show a separator line')}}</span>
                    </div>
                    <div class="text-[10px] text-gray-500 font-light" v-if="component.data.title_size">
                        {{ component.data.title_size }} Pixel
                    </div>
                </div>
            </div>
        </div>
    </DropComponentsToolTip>
</template>

<style scoped>

</style>
