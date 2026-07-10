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
            draggedComponent: dragBus.draggedComponent,
        }
    },
    computed: {
        // Grund, warum die aktuell gezogene Komponente hier nicht abgelegt werden darf (null = erlaubt)
        invalidDropReason() {
            const data = this.draggedComponent;
            if (!data || !this.isSidebar) return null;

            if (data.type === 'DisclosureComponent') {
                return this.$t('Folder components cannot be placed in the sidebar');
            }
            if (data.sidebar_enabled === false || data.sidebar_enabled === 0 || data.sidebar_enabled === "0") {
                return this.$t('This component cannot be placed in the sidebar');
            }
            return null;
        },
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
            const jsonData = event.dataTransfer?.getData('application/json');
            if (!jsonData) {
                this.dropOver = false;
                return;
            }

            let data;
            try {
                data = JSON.parse(jsonData);
            } catch (e) {
                console.error('❌ JSON Parse Error:', e);
                this.dropOver = false;
                return;
            }

            // Prüfen ob es eine Komponente ist
            if(data.drop_type !== 'component') {
                this.dropOver = false;
                return;
            }

            // Für normale Tabs: Spezielle Komponenten brauchen Tab-Auswahl
            if(!this.isSidebar){
                if(data.type === 'ProjectDocumentsComponent' || data.type === 'CommentTab' || data.type === 'ChecklistComponent') {
                    this.componentData = data;
                    this.showSelectTabsModal = true;
                    this.dropOver = false;
                    return;
                }
            }

            // Für Sidebar: Prüfen ob Komponente Sidebar-fähig ist
            // (der Grund wird bereits während des Draggings in der Zone angezeigt)
            if(this.isSidebar) {
                if(data.type === 'DisclosureComponent'
                    || data.sidebar_enabled === false || data.sidebar_enabled === 0 || data.sidebar_enabled === "0") {
                    this.dropOver = false;
                    return;
                }
            }

            // Komponente zur Sidebar oder Tab hinzufügen
            if(this.isSidebar) {
                this.$inertia.post(route('tab.add.component.sidebar', {projectTabSidebarTab: this.tab.id}), {
                    component_id: data.id,
                    order: this.order
                }, {
                    preserveScroll: true,
                    onStart: () => {
                        // Request gestartet
                    },
                    onSuccess: (page) => {
                        this.dropOver = false;
                        // Reload page data to get fresh sidebar tabs
                        this.$inertia.reload({ only: ['tabs'] });
                    },
                    onError: (errors) => {
                        console.error('❌ Fehler beim Hinzufügen zur Sidebar:', errors);
                        this.dropOver = false;
                    },
                    onFinish: () => {
                        // Request abgeschlossen
                    }
                });
            } else {
                this.$inertia.post(route('tab.add.component', {projectTab: this.tab.id}), {
                    component_id: data.id,
                    order: this.order
                }, {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.dropOver = false;
                    },
                    onError: (errors) => {
                        console.error('❌ Fehler beim Hinzufügen zum Tab:', errors);
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
            'flex items-center h-4 min-h-4 rounded transition',
            isDragging ? 'border-2 border-dashed' : 'hover:bg-gray-50/40',
            isDragging && invalidDropReason
                ? 'border-red-200 bg-red-50/40 opacity-70 cursor-not-allowed'
                : (isDragging && dropOver
                    ? 'border-emerald-400 bg-emerald-50/60 ring-2 ring-emerald-400/30 cursor-pointer'
                    : (isDragging ? 'border-zinc-300 bg-zinc-50/40 cursor-pointer' : 'cursor-pointer'))
        ]"
        :aria-hidden="!isDragging"
        :aria-dropeffect="isDragging && !invalidDropReason ? 'copy' : undefined"
    >
        <div v-if="isDragging && invalidDropReason" class="h-full w-full flex items-center justify-center gap-2 text-xs pointer-events-none">
            <span class="font-medium text-red-600">{{ invalidDropReason }}</span>
        </div>
        <div v-else-if="isDragging" class="h-full w-full flex items-center justify-center gap-2 text-xs text-zinc-600 pointer-events-none">
            <span class="font-medium" :class="dropOver ? 'text-emerald-700' : ''">{{ $t('Drop component here') }}</span>
        </div>
        <span v-else-if="dropOver" class="text-xs text-gray-300 w-full flex items-center justify-center pointer-events-none">
            {{ $t('Release to add') }}
        </span>
    </div>
    <SelectTabsModal :tabs="allTabs" v-if="showSelectTabsModal" :position="order" :component-data="componentData" :current-tab="tab" @close="showSelectTabsModal = false" @open-tab="openTab" />
</template>

<style scoped>

</style>
