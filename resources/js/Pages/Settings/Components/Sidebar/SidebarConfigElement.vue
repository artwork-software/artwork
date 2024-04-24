<script>
import SingleSidebarElement from "@/Pages/Settings/Components/Sidebar/SingleSidebarElement.vue";
import IconLib from "@/mixins/IconLib.vue";
import AddEditSidebarTab from "@/Pages/Settings/Components/Sidebar/AddEditSidebarTab.vue";
import draggable from "vuedraggable";


export default {
    name: "SidebarConfigElement",
    mixins: [IconLib],
    components: {
        draggable,
        AddEditSidebarTab,
        SingleSidebarElement
    },
    props: ['tab'],
    data() {
        return {
            showAddEditSidebarTabModal: false,
            dragging: false,
        }
    },
    methods: {
        updateComponentOrder(components) {
            components.map((component, index) => {
                component.order = index + 1
            })

            this.$inertia.post(route('sidebar.tab.reorder'), {
                components: components,
            }, {
                preserveScroll: true
            });
        },
    }
}
</script>

<template>
    <div>
        <div class="flex items-center justify-between mr-3">
            <h3 class="text-base font-bold my-3">Sidebar</h3>
            <IconCirclePlus class="h-5 w-5 text-gray-600 cursor-pointer" @click="showAddEditSidebarTabModal = true"/>
        </div>
        <draggable ghost-class="opacity-50" key="draggableKey" item-key="id" :list="tab.sidebar_tabs" @start="dragging=true" @end="dragging=false" @change="updateComponentOrder(tab.sidebar_tabs)">
            <template #item="{element}" :key="element.id">
                <div class="">
                    <div class="" :key="element.id" :class="dragging? 'cursor-grabbing' : 'cursor-grab'">
                        <SingleSidebarElement :tab="tab" :sidebar-tab="element" />
                    </div>
                </div>
            </template>
        </draggable>

    </div>

    <AddEditSidebarTab :tab-to-edit="null" :tab="tab" v-if="showAddEditSidebarTabModal" @close="showAddEditSidebarTabModal = false" />
</template>

<style scoped>

</style>
