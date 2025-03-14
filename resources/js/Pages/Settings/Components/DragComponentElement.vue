<script>
import ComponentIcons from "@/Components/Globale/ComponentIcons.vue";
import ToolTipDefault from "@/Components/ToolTips/ToolTipDefault.vue";
import DropComponentsToolTip from "@/Components/ToolTips/DropComponentsToolTip.vue";
import {EventListenerForDragging} from "@/Composeables/EventListenerForDragging.js";
const { dispatchEventStart, dispatchEventEnd } = EventListenerForDragging();
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
                    sidebar_enabled: this.component.sidebar_enabled,
                    special: this.component.special,
                })
            );
            dispatchEventStart()
        },
        onDragEnd() {
            dispatchEventEnd()
        }
    }
}
</script>

<template>
    <DropComponentsToolTip :top="true" :tooltip-text="component.special ? $t(component.name) : component.name">
        <div class="flex p-3 rounded-lg border mb-3 hover:cursor-grab h-16 w-full items-center gap-2" draggable="true"  @dragend="onDragEnd" @dragstart="onDragStart">
            <div class="flex items-center justify-center">
                <ComponentIcons :type="component.type" />
            </div>
            <div class="text-sm font-bold">
                <div class="w-full">
                    {{ $t(component.name) }}
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
