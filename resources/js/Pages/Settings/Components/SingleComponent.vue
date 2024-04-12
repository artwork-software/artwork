<script>
import ComponentIcons from "@/Components/Globale/ComponentIcons.vue";
import {IconDotsVertical, IconDragDrop, IconTrash} from "@tabler/icons-vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";

export default {
    name: "SingleComponent",
    components: {IconTrash, IconDragDrop, IconDotsVertical, ComponentIcons,
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
                    <div class="grid gird-cols-1 md:grid-cols-12">
                        <div class="col-span-6 flex items-center gap-x-3">
                            <ComponentIcons :type="element.component.type" />
                            <div class="">
                                <span v-if="element.component.special">
                                    {{ $t(element.component.name) }}
                                </span>
                                <span v-else>
                                    {{element.component.name }}
                                </span>
                                <div class="text-[10px] text-gray-500 font-light" v-if="element.component.data.height">
                                    {{ element.component.data.height }} Pixel <span v-if="element.component.data.showLine === true">| {{ $t('Show a separator line')}}</span>
                                </div>
                                <div class="text-[10px] text-gray-500 font-light truncate" v-if="element.component.data.title_size">
                                    {{ element.component.data.title_size }} Pixel
                                </div>
                            </div>

                        </div>
                        <div class="col-span-2 text-xs flex items-center">
                            {{ $t(element.component.type)}}
                        </div>
                    </div>
                </div>
                <IconDragDrop class="xsDark h-5 w-5 hidden group-hover:block cursor-pointer relative z-100"/>
                <div  class="hidden group-hover:block">
                    <Menu as="div" class="my-auto relative">
                        <div class="flex">
                            <MenuButton
                                class="flex">
                                <IconDotsVertical stroke-width="1.5"
                                                  class="flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                  aria-hidden="true"/>
                            </MenuButton>
                        </div>
                        <transition enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95">
                            <MenuItems
                                class="origin-top-right absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                <div class="py-1">
                                    <MenuItem v-slot="{ active }">
                                        <a href="#" @click="removeComponentFromTab(element.id)"
                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <IconTrash stroke-width="1.5"
                                                       class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                       aria-hidden="true"/>
                                            {{ $t('Delete') }}
                                        </a>
                                    </MenuItem>
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
