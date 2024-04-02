<script>
import {IconCopy, IconDotsVertical, IconDragDrop, IconEdit, IconTrash} from "@tabler/icons-vue";
import draggable from "vuedraggable";
import DropNewComponent from "@/Pages/Settings/Components/DropNewComponent.vue";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";

export default {
    name: "SingleTabComponent",
    components: {
        IconTrash,
        IconCopy,
        SvgCollection,
        IconEdit,
        IconDotsVertical,
        DropNewComponent, draggable, IconDragDrop,
        Menu,
        MenuButton,
        MenuItems,
        MenuItem,
    },
    props: ['tab'],
    data() {
        return {
            dragging: false,
            showMenu: null,
        }
    },

    methods: {
        updateComponentOrder(components) {
            components.map((component, index) => {
                component.order = index + 1
            })

            this.$inertia.post(route('tab.update.component.order', {projectTab: this.tab.id}), {
                components: components,
            });
        },
        removeComponentFromTab(componentId) {
            this.$inertia.delete(route('tab.remove.component', {projectTab: this.tab.id}),
                {
                    data: {
                        component_id: componentId
                    }
                }
            );
        }
    }
}
</script>

<template>
    <h3 class="headline3">{{ tab.name }}</h3>
    <div v-if="tab.components.length > 0">
        <DropNewComponent :tab="tab" :order="1" />
        <draggable ghost-class="opacity-50" key="draggableKey" item-key="id" :list="tab.components" @start="dragging=true" @end="dragging=false" @change="updateComponentOrder(tab.components)">
            <template #item="{element}" :key="element.id">
                <div v-show="!element.temporary" class="" @mouseover="showMenu = element.id" :key="element.id" @mouseout="showMenu = null">
                    <div class="flex group w-full">
                        <div class="flex bg-artwork-project-background py-5 px-4 my-1 rounded-lg flex-wrap w-full" :key="element.id" :class="dragging? 'cursor-grabbing' : 'cursor-grab'">
                            <div class="flex justify-between w-full">
                                <div class="flex">
                                    <IconDragDrop class="my-auto xsDark h-5 w-5 hidden group-hover:block"/>
                                    {{element.component.name }}
                                </div>
                                <div  class="hidden group-hover:block">
                                    <Menu as="div" class="my-auto relative">
                                        <div class="flex">
                                            <MenuButton
                                                class="flex">
                                                <IconDotsVertical stroke-width="1.5"
                                                                  class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
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
                    <DropNewComponent :tab="tab" :order="element.order + 1" />
                </div>
            </template>
        </draggable>
    </div>
    <div v-else>
        <DropNewComponent :tab="tab" :order="1"/>
    </div>
</template>

<style scoped>

</style>
