<script>
import SelectTabsModal from "@/Pages/Settings/Components/SelectTabsModal.vue";

export default {
    name: "DropNewComponent",
    components: {SelectTabsModal},
    props: ['tab', 'order', 'isSidebar', 'allTabs'],
    emits: ['tabOpened'],
    data() {
        return {
            dropOver: false,
            showSelectTabsModal: false,
            componentData: null,
        }
    },
    methods: {
        onDragOver(event) {
            this.dropOver = true;
            event.preventDefault();
        },
        onDrop(event) {
            event.preventDefault();

            // add check if JSON is valid
            if (!event.dataTransfer?.getData('application/json')) {
                this.dropOver = false;
                return;
            }

            const data = JSON?.parse(event.dataTransfer?.getData('application/json'));


            if(!this.isSidebar){
                if(data.type === 'ProjectDocumentsComponent' || data.type === 'CommentTab' || data.type === 'ChecklistComponent') {
                    this.componentData = data;
                    this.showSelectTabsModal = true;
                    this.dropOver = false;
                    return;
                }
            }

            if(this.isSidebar && !data.sidebar_enabled) {
                this.dropOver = false;
                return;
            }

            if(data.drop_type !== 'component') {
                this.dropOver = false;
                return;
            }

            if(this.isSidebar) {
                this.$inertia.post(route('tab.add.component.sidebar', {projectTabSidebarTab: this.tab.id}), {
                    component_id: data.id,
                    order: this.order
                }, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        this.dropOver = false;
                    }
                });
            } else {
                this.$inertia.post(route('tab.add.component', {projectTab: this.tab.id}), {
                    component_id: data.id,
                    order: this.order
                }, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        this.dropOver = false;
                    }
                });
            }
            this.openTab(this.tab.id)
        },
        openTab(tabId){
            this.$emit('tabOpened', tabId);
        }
    }
}
</script>

<template>
    <div class="flex items-center h-4 min-h-4 hover:bg-gray-50/40 rounded cursor-pointer" @dragleave="dropOver = false" @dragover="onDragOver" @drop="onDrop">
        <span v-if="dropOver" class="text-xs text-gray-300 w-full flex items-center justify-center pointer-events-none">
            Zum hinzufügen hier loslassen
        </span>
    </div>
    <SelectTabsModal :tabs="allTabs" v-if="showSelectTabsModal" :position="order" :component-data="componentData" :current-tab="tab" @close="showSelectTabsModal = false" @open-tab="openTab" />
</template>

<style scoped>

</style>
