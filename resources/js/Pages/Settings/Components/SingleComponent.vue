<script>
import ComponentIcons from "@/Components/Globale/ComponentIcons.vue";
import {IconDotsVertical, IconDragDrop, IconTrash} from "@tabler/icons-vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";

export default {
    name: "SingleComponent",
    components: {
        BaseMenu, IconTrash, IconDragDrop, IconDotsVertical, ComponentIcons,
        Menu,
        MenuButton,
        MenuItems,
        MenuItem,},
    data(){
        return {
            dragging: false,
        }
    },
    props: ['element', 'tab'],
    methods: {
        removeComponentFromTab() {
            this.$inertia.delete(route('tab.remove.component', {projectTab: this.tab.id}),
                {
                    data: {
                        component_id: this.element.id
                    }
                }
            );
        },
    }
}
</script>

<template>
    <div class="flex group w-full items-center">
        <div class="flex items-center bg-artwork-project-background py-5 px-4 my-1 rounded-lg flex-wrap w-full" :key="element.id" :class="dragging? 'cursor-grabbing' : 'cursor-grab'">
            <div class="flex justify-between w-full items-center">
                <div class="w-full">
                    <div class="flex">
                        <div class="col-span-6 flex items-center gap-x-3">
                            <ComponentIcons :type="element.component.type" />
                            <div class="">
                                <div class="flex items-center gap-4">
                                    {{element.component.name }}
                                    <div class="text-[10px] text-gray-500 font-light" v-if="element.component.data.height">
                                        {{ element.component.data.height }} Pixel <span v-if="element.component.data.showLine === true">| {{ $t('Show a separator line')}}</span>
                                    </div>
                                    <div class="text-[10px] text-gray-500 font-light truncate" v-if="element.component.data.title_size">
                                        {{ element.component.data.title_size }} Pixel
                                    </div>
                                </div>


                                <div class="col-span-2 text-xs flex items-center">
                                    {{ $t(element.component.type)}}
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
                <IconDragDrop class="xsDark h-5 w-5 hidden group-hover:block cursor-pointer relative z-100"/>
                <div  class="invisible group-hover:visible">
                    <BaseMenu has-no-offset>
                        <MenuItem v-slot="{ active }">
                            <a href="#" @click="removeComponentFromTab(element.id)"
                               :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                <IconTrash stroke-width="1.5"
                                           class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                           aria-hidden="true"/>
                                {{ $t('Delete') }}
                            </a>
                        </MenuItem>
                    </BaseMenu>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
