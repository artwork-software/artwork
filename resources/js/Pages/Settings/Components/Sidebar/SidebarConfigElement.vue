<script>
import SingleSidebarElement from "@/Pages/Settings/Components/Sidebar/SingleSidebarElement.vue";
import IconLib from "@/mixins/IconLib.vue";
import AddEditSidebarTab from "@/Pages/Settings/Components/Sidebar/AddEditSidebarTab.vue";


export default {
    name: "SidebarConfigElement",
    mixins: [IconLib],
    components: {
        AddEditSidebarTab,
        SingleSidebarElement
    },
    props: ['tab'],
    data() {
        return {
            showAddEditSidebarTabModal: false
        }
    },
}
</script>

<template>
    <div>
        <div class="flex items-center justify-between mr-3">
            <h3 class="text-base font-bold my-3">Sidebar</h3>
            <IconCirclePlus class="h-5 w-5 text-gray-600 cursor-pointer" @click="showAddEditSidebarTabModal = true"/>
        </div>
        <div v-for="sidebarTab in tab.sidebar_tabs">
            <SingleSidebarElement :tab="tab" :sidebar-tab="sidebarTab" />
        </div>
    </div>

    <AddEditSidebarTab :tab-to-edit="null" :tab="tab" v-if="showAddEditSidebarTabModal" @close="showAddEditSidebarTabModal = false" />
</template>

<style scoped>

</style>
