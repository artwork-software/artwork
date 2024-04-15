<script>


import IconLib from "@/mixins/IconLib.vue";
import draggable from "vuedraggable";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import ComponentIcons from "@/Components/Globale/ComponentIcons.vue";
import DropNewComponent from "@/Pages/Settings/Components/DropNewComponent.vue";
import AddEditSidebarTab from "@/Pages/Settings/Components/Sidebar/AddEditSidebarTab.vue";

export default {
    name: "SingleSidebarElement",
    mixins: [IconLib],
    components: {
        AddEditSidebarTab,
        DropNewComponent, ComponentIcons,
        draggable,
        Menu,
        MenuButton,
        MenuItems,
        MenuItem,
    },
    props: ['tab', 'sidebarTab'],
    data() {
        return {
            tabClosed: false,
            dragging: false,
            showMenu: null,
            showAddEditModal: false,
        }
    },
    methods: {
        onDragStart(event) {
            event.dataTransfer.setData(
                'application/json',
                JSON.stringify( this.sidebarTab )
            );
        },
        removeComponentFromSidebar(id) {
            this.$inertia.delete(route('sidebar.component.remove', {sidebarTabComponent: id}), {
                preserveState: true,
                preserveScroll: true,
            })
        },
        updateComponentOrder(components) {
            components.map((component, index) => {
                component.order = index + 1
            })

            this.$inertia.post(route('sidebar.tab.update.component.order', {projectTabSidebarTab: this.sidebarTab.id}), {
                components: components,
            }, {
                preserveScroll: true
            });
        },
        openTab(){
            this.tabClosed = false;
        },
        editTab(){
            this.showAddEditModal = true;
        },
    }
}
</script>

<template>
    <div class="mb-3" draggable="true" @dragstart="onDragStart">
        <div class="flex items-center justify-between hover:cursor-grab">
            <div class="flex items-center gap-2 cursor-pointer" @click="tabClosed = !tabClosed">
                <h3 class="headline3">{{ sidebarTab.name }}</h3>
                <IconChevronDown v-if="tabClosed" class="h-5 w-5 text-gray-600" />
                <IconChevronUp v-else class="h-5 w-5 text-gray-600" />
            </div>
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
                                <a href="#" @click="editTab"
                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                    <IconEdit stroke-width="1.5"
                                              class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                              aria-hidden="true"/>
                                    {{ $t('Edit') }}
                                </a>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                                <a href="#" @click="removeTab"
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
        <DropNewComponent :is-sidebar="true" :all-tabs="null" :tab="sidebarTab" :order="1" @tab-opened="openTab" />
        <div v-if="!tabClosed">
            <draggable ghost-class="opacity-50" key="draggableKey" item-key="id" :list="sidebarTab.components_in_sidebar" @start="dragging=true" @end="dragging=false" @change="updateComponentOrder(sidebarTab.components_in_sidebar)">
                <template #item="{element}" :key="element.id">
                    <div v-show="!element.temporary" class="" @mouseover="showMenu = element.id" :key="element.id" @mouseout="showMenu = null">
                        <div class="flex group w-full items-center">
                            <div class="flex items-center bg-artwork-project-background py-5 px-4 my-1 rounded-lg flex-wrap w-full" :key="element.id" :class="dragging? 'cursor-grabbing' : 'cursor-grab'">
                                <div class="flex justify-between w-full items-center">
                                    <div class="w-full">
                                        <div class="grid gird-cols-1 md:grid-cols-12">
                                            <div class="col-span-6 flex items-center gap-x-3">
                                                <ComponentIcons :type="element.component.type" />
                                                <div class="">
                                                    {{element.component.name }}
                                                    <div class="text-[10px] text-gray-500 font-light" v-if="element.component.data.height">
                                                        {{ element.component.data.height }} Pixel <span v-if="element.component.data.showLine === true">| {{ $t('Show a separator line')}}</span>
                                                    </div>
                                                </div>

                                            </div>
                                            <div class="col-span-2 text-xs flex items-center">
                                                {{ $t(element.component.type)}}
                                            </div>
                                        </div>
                                    </div>
                                    <IconDragDrop class="xsDark h-5 w-5 hidden group-hover:block"/>
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
                                                            <a href="#" @click="removeComponentFromSidebar(element.id)"
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
                        <DropNewComponent :is-sidebar="true" :all-tabs="null" :tab="sidebarTab" :order="element.order + 1" @tab-opened="openTab" />
                    </div>
                </template>
            </draggable>
        </div>
    </div>

    <AddEditSidebarTab :tab="null" :tab-to-edit="sidebarTab" v-if="showAddEditModal" @close="showAddEditModal = false" />

</template>

<style scoped>

</style>
