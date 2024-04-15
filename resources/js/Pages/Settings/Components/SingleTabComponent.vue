<script>
import draggable from "vuedraggable";
import DropNewComponent from "@/Pages/Settings/Components/DropNewComponent.vue";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import IconLib from "@/mixins/IconLib.vue";
import AddEditTabModal from "@/Pages/Settings/Components/AddEditTabModal.vue";
import ComponentIcons from "@/Components/Globale/ComponentIcons.vue";
import SingleSidebarElement from "@/Pages/Settings/Components/Sidebar/SingleSidebarElement.vue";
import AddEditSidebarTab from "@/Pages/Settings/Components/Sidebar/AddEditSidebarTab.vue";
import SidebarConfigElement from "@/Pages/Settings/Components/Sidebar/SidebarConfigElement.vue";
import SingleComponent from "@/Pages/Settings/Components/SingleComponent.vue";

export default {
    name: "SingleTabComponent",
    mixins: [IconLib],
    components: {
        SingleComponent,
        SidebarConfigElement,
        AddEditSidebarTab,
        SingleSidebarElement,
        ComponentIcons,
        AddEditTabModal,
        SvgCollection,
        DropNewComponent, draggable,
        Menu,
        MenuButton,
        MenuItems,
        MenuItem,
    },
    props: ['tab', 'allTabs'],
    data() {
        return {
            dragging: false,
            showMenu: null,
            showAddEditModal: false,
            tabClosed: true,
            showAddEditSidebarTabModal: false,
            sidebarTabToEdit: null
        }
    },
    computed: {
        lastComponentOrder() {
            return this.tab.components.length + 1;
        }
    },
    methods: {
        updateComponentOrder(components) {
            components.map((component, index) => {
                component.order = index + 1
            })

            this.$inertia.post(route('tab.update.component.order', {projectTab: this.tab.id}), {
                components: components,
            }, {
                preserveScroll: true
            });
        },

        removeTab() {
            this.$inertia.delete(route('tab.destroy', {projectTab: this.tab.id}));
        },
        editTab(){
            this.showAddEditModal = true;
        },

        openTab(){
            this.tabClosed = false;
        }
    }
}
</script>

<template>
    <!-- DropTabComponent -->
<div class="border rounded-md px-4 py-5 bg-gray-50/50">
    <div class="flex items-center justify-between hover:cursor-grab pb-3 border-b border-dashed border-gray-300">
        <div class="flex items-center gap-2 cursor-pointer" @click="tabClosed = !tabClosed">
            <h3 class="headline3">{{ tab.name }}</h3>
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
    <div v-if="!tabClosed">
       <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
           <div>
               <h3 class="text-base font-bold my-3">Komponenten</h3>
               <DropNewComponent :is-sidebar="false" :all-tabs="allTabs" :tab="tab" :order="1" @tab-opened="openTab" />
               <draggable ghost-class="opacity-50" key="draggableKey" item-key="id" :list="tab.components" @start="dragging=true" @end="dragging=false" @change="updateComponentOrder(tab.components)">
                   <template #item="{element}" :key="element.id">
                       <div v-show="!element.temporary" class="" @mouseover="showMenu = element.id" :key="element.id" @mouseout="showMenu = null">
                            <SingleComponent :element="element" :tab="tab" />

                           <DropNewComponent :is-sidebar="false" :all-tabs="allTabs" :tab="tab" :order="element.order + 1" @tab-opened="openTab" />
                       </div>
                   </template>
               </draggable>
           </div>
            <SidebarConfigElement :tab="tab" />
       </div>
    </div>
    <div v-else>
        <DropNewComponent :all-tabs="allTabs" :is-sidebar="false" :tab="tab" :order="lastComponentOrder" @tab-opened="openTab"/>
    </div>
</div>


    <AddEditTabModal @close="showAddEditModal = false" v-if="showAddEditModal" :tab-to-edit="tab" />
</template>

<style scoped>

</style>
