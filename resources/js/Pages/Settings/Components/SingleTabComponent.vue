<template>
    <!-- DropTabComponent -->
    <div class="border rounded-md px-4 py-5 bg-gray-50/50">
        <div class="flex items-center justify-between hover:cursor-grab pb-3 border-b border-dashed border-gray-300">
            <div class="flex items-center gap-2 cursor-pointer" @click="tabClosed = !tabClosed">
                <h3 class="headline3">{{ tab.name }}</h3>
                <IconChevronDown v-if="tabClosed" class="h-5 w-5 text-gray-600"/>
                <IconChevronUp v-else class="h-5 w-5 text-gray-600"/>
            </div>
            <div class="flex items-center gap-x-2">
                <div class="flex items-center gap-x-2">
                    <Switch v-model="tab.default" @click="updateDefaultTab" :class="[tab.default ? 'bg-artwork-buttons-create' : 'bg-gray-200', 'relative inline-flex h-4 w-8 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-artwork-buttons-create focus:ring-offset-2']">
                    <span :class="[tab.default ? 'translate-x-4' : 'translate-x-0', 'pointer-events-none relative inline-block size-3 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                      <span :class="[tab.default ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in', 'absolute inset-0 flex size-full items-center justify-center transition-opacity']" aria-hidden="true">
                        <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 12 12">
                          <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                      </span>
                      <span :class="[tab.default? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex size-full items-center justify-center transition-opacity']" aria-hidden="true">
                        <svg class="size-3 text-artwork-buttons-create" fill="currentColor" viewBox="0 0 12 12">
                          <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                        </svg>
                      </span>
                    </span>
                    </Switch>
                    <div>
                        <p class="xxsDark">{{ $t('Should this tab be set as the default tab?') }}</p>
                    </div>
                </div>
                <BaseMenu has-no-offset>
                    <MenuItem v-slot="{ active }">
                        <a href="#" @click="editTab"
                           :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                            <IconEdit stroke-width="1.5"
                                      class="mr-3 h-5 w-5 text-primaryText group-hover:text-artwork-buttons-hover"
                                      aria-hidden="true"/>
                            {{ $t('Edit') }}
                        </a>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                        <a href="#" @click="removeTab"
                           :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                            <IconTrash stroke-width="1.5"
                                       class="mr-3 h-5 w-5 text-primaryText group-hover:text-artwork-buttons-hover"
                                       aria-hidden="true"/>
                            {{ $t('Delete') }}
                        </a>
                    </MenuItem>
                </BaseMenu>
            </div>
        </div>
        <div v-if="!tabClosed">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h3 class="text-base font-bold my-3">Komponenten</h3>
                    <DropNewComponent :is-sidebar="false" :all-tabs="allTabs" :tab="tab" :order="1"
                                      @tab-opened="openTab"/>
                    <draggable ghost-class="opacity-50" key="draggableKey" item-key="id" :list="tab.components"
                               @start="dragging=true" @end="dragging=false"
                               @change="updateComponentOrder(tab.components)">
                        <template #item="{element}" :key="element.id">
                            <div v-show="!element.temporary" class="" @mouseover="showMenu = element.id"
                                 :key="element.id" @mouseout="showMenu = null">
                                <SingleComponent :element="element" :tab="tab"/>

                                <DropNewComponent :is-sidebar="false" :all-tabs="allTabs" :tab="tab"
                                                  :order="element.order + 1" @tab-opened="openTab"/>
                            </div>
                        </template>
                    </draggable>
                </div>
                <SidebarConfigElement :tab="tab"/>
            </div>
        </div>
        <div v-else>
            <DropNewComponent :all-tabs="allTabs" :is-sidebar="false" :tab="tab" :order="lastComponentOrder"
                              @tab-opened="openTab"/>
        </div>
    </div>
    <AddEditTabModal @close="showAddEditModal = false" v-if="showAddEditModal" :tab-to-edit="tab"/>
    <error-component v-if="showComponentTabCannotBeDeletedModal"
                     :titel="$t('An error has occurred')"
                     :description="$t('At least one project tab must be available')"
                     :confirm="$t('Close message')"
                     @closed="showComponentTabCannotBeDeletedModal = false;"
    />
</template>

<script>
import draggable from "vuedraggable";
import DropNewComponent from "@/Pages/Settings/Components/DropNewComponent.vue";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import {Menu, MenuButton, MenuItem, MenuItems, Switch} from "@headlessui/vue";
import IconLib from "@/Mixins/IconLib.vue";
import AddEditTabModal from "@/Pages/Settings/Components/AddEditTabModal.vue";
import ComponentIcons from "@/Components/Globale/ComponentIcons.vue";
import SingleSidebarElement from "@/Pages/Settings/Components/Sidebar/SingleSidebarElement.vue";
import AddEditSidebarTab from "@/Pages/Settings/Components/Sidebar/AddEditSidebarTab.vue";
import SidebarConfigElement from "@/Pages/Settings/Components/Sidebar/SidebarConfigElement.vue";
import SingleComponent from "@/Pages/Settings/Components/SingleComponent.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import ErrorComponent from "@/Layouts/Components/ErrorComponent.vue";
import {router, usePage} from "@inertiajs/vue3";

export default {
    name: "SingleTabComponent",
    mixins: [IconLib],
    components: {
        Switch,
        ErrorComponent,
        BaseMenu,
        SingleComponent,
        SidebarConfigElement,
        AddEditSidebarTab,
        SingleSidebarElement,
        ComponentIcons,
        AddEditTabModal,
        SvgCollection,
        DropNewComponent,
        draggable,
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
            sidebarTabToEdit: null,
            showComponentTabCannotBeDeletedModal: false
        }
    },
    computed: {
        lastComponentOrder() {
            return this.tab.components.length + 1;
        }
    },
    methods: {
        usePage,
        updateComponentOrder(components) {
            // Update local order
            components.map((component, index) => {
                component.order = index + 1
            })

            // Create a minimal payload with only necessary data (id and order)
            const minimalComponents = components.map(component => ({
                id: component.id,
                order: component.order
            }));

            this.$inertia.post(route('tab.update.component.order', {projectTab: this.tab.id}), {
                components: minimalComponents,
            }, {
                preserveScroll: true
            });
        },
        removeTab() {
            if (this.allTabs.length === 1) {
                this.showComponentTabCannotBeDeletedModal = true;
                return false;
            }

            this.$inertia.delete(route('tab.destroy', {projectTab: this.tab.id}));
        },
        editTab() {
            this.showAddEditModal = true;
        },
        openTab() {
            this.tabClosed = false;
        },
        updateDefaultTab() {
            router.patch(route('tab.update.default', {projectTab: this.tab.id}), {
                default: !this.tab.default
            });
        }
    }
}
</script>
