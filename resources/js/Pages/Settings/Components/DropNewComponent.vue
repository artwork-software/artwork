<script>
import SelectTabsModal from "@/Pages/Settings/Components/SelectTabsModal.vue";
import { EventListenerForDragging } from "@/Composeables/EventListenerForDragging.js";

const dragBus = EventListenerForDragging();

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
            listeners: null,
            isDragging: dragBus.isDragging,
        }
    },
    mounted() {
        this.listeners = dragBus.addEventListenerForDraggingStart();
    },
    beforeUnmount() {
        dragBus.removeEventListenerForDraggingStart(this.listeners);
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
    <div
        @dragleave="dropOver = false"
        @dragover="onDragOver"
        @drop="onDrop"
        :class="[
            'flex items-center h-4 min-h-4 rounded cursor-pointer transition',
            isDragging ? 'border-2 border-dashed' : 'hover:bg-gray-50/40',
            isDragging && dropOver
                ? 'border-emerald-400 bg-emerald-50/60 ring-2 ring-emerald-400/30'
                : (isDragging ? 'border-zinc-300 bg-zinc-50/40' : '')
        ]"
        :aria-hidden="!isDragging"
        :aria-dropeffect="isDragging ? 'copy' : undefined"
    >
        <div v-if="isDragging" class="h-full w-full flex items-center justify-center gap-2 text-xs text-zinc-600 pointer-events-none">
            <span class="font-medium" :class="dropOver ? 'text-emerald-700' : ''">Komponente hier ablegen</span>
        </div>
        <span v-else-if="dropOver" class="text-xs text-gray-300 w-full flex items-center justify-center pointer-events-none">
            Zum hinzufügen hier loslassen
        </span>
    </div>
    <SelectTabsModal :tabs="allTabs" v-if="showSelectTabsModal" :position="order" :component-data="componentData" :current-tab="tab" @close="showSelectTabsModal = false" @open-tab="openTab" />
</template>

<style scoped>

</style>
