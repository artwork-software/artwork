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
            console.log('🎯 Drop Event ausgelöst!');

            // add check if JSON is valid
            const jsonData = event.dataTransfer?.getData('application/json');
            if (!jsonData) {
                console.warn('⚠️ Keine JSON-Daten gefunden');
                this.dropOver = false;
                return;
            }

            let data;
            try {
                data = JSON.parse(jsonData);
                console.log('📦 Empfangene Daten:', data);
            } catch (e) {
                console.error('❌ JSON Parse Error:', e);
                this.dropOver = false;
                return;
            }

            // Prüfen ob es eine Komponente ist
            if(data.drop_type !== 'component') {
                console.warn('⚠️ Kein Component-Drop-Type:', data.drop_type);
                this.dropOver = false;
                return;
            }

            // Für normale Tabs: Spezielle Komponenten brauchen Tab-Auswahl
            if(!this.isSidebar){
                if(data.type === 'ProjectDocumentsComponent' || data.type === 'CommentTab' || data.type === 'ChecklistComponent') {
                    console.log('📋 Spezielle Komponente für Tab - Modal öffnen');
                    this.componentData = data;
                    this.showSelectTabsModal = true;
                    this.dropOver = false;
                    return;
                }
            }

            // Für Sidebar: Prüfen ob Komponente Sidebar-fähig ist
            if(this.isSidebar) {
                console.log('🔧 Sidebar-Drop - Validierung läuft...');
                console.log('   - Komplette Daten:', data);
                console.log('   - special:', data.special, '(Type:', typeof data.special, ')');
                console.log('   - sidebar_enabled:', data.sidebar_enabled, '(Type:', typeof data.sidebar_enabled, ')');

                // Debugging: Zeige alle Vergleiche
                console.log('   - data.special === true:', data.special === true);
                console.log('   - data.sidebar_enabled === false:', data.sidebar_enabled === false);
                console.log('   - data.sidebar_enabled === 0:', data.sidebar_enabled === 0);
                console.log('   - data.sidebar_enabled === "0":', data.sidebar_enabled === "0");
                console.log('   - data.sidebar_enabled === true:', data.sidebar_enabled === true);
                console.log('   - data.sidebar_enabled === 1:', data.sidebar_enabled === 1);

                // Spezielle Komponenten können nicht in die Sidebar
                if(data.special === true) {
                    console.warn('⛔ BLOCKIERT: Spezielle Komponente (special === true)');
                    alert('Spezielle Komponenten können nicht in die Sidebar gelegt werden');
                    this.dropOver = false;
                    return;
                }

                // Nur Komponenten mit sidebar_enabled = true können hinzugefügt werden
                // Blockiere nur wenn explizit false oder 0
                if(data.sidebar_enabled === false || data.sidebar_enabled === 0 || data.sidebar_enabled === "0") {
                    console.warn('⛔ BLOCKIERT: sidebar_enabled ist false/0');
                    alert('Diese Komponente kann nicht in die Sidebar gelegt werden');
                    this.dropOver = false;
                    return;
                }

                console.log('✅ Sidebar-Validierung erfolgreich! Komponente wird hinzugefügt.');
            }

            // Komponente zur Sidebar oder Tab hinzufügen
            if(this.isSidebar) {
                console.log('🚀 Sende POST-Request für Sidebar...');
                console.log('   - Route:', route('tab.add.component.sidebar', {projectTabSidebarTab: this.tab.id}));
                console.log('   - component_id:', data.id);
                console.log('   - order:', this.order);

                this.$inertia.post(route('tab.add.component.sidebar', {projectTabSidebarTab: this.tab.id}), {
                    component_id: data.id,
                    order: this.order
                }, {
                    preserveScroll: true,
                    onStart: () => {
                        console.log('⏳ Request gestartet...');
                    },
                    onSuccess: (page) => {
                        console.log('✅ Komponente erfolgreich zur Sidebar hinzugefügt');
                        console.log('📄 Response:', page);
                        this.dropOver = false;
                        // Reload page data to get fresh sidebar tabs
                        this.$inertia.reload({ only: ['tabs'] });
                    },
                    onError: (errors) => {
                        console.error('❌ Fehler beim Hinzufügen zur Sidebar:', errors);
                        this.dropOver = false;
                    },
                    onFinish: () => {
                        console.log('🏁 Request abgeschlossen');
                    }
                });
            } else {
                console.log('🚀 Sende POST-Request für normalen Tab...');
                this.$inertia.post(route('tab.add.component', {projectTab: this.tab.id}), {
                    component_id: data.id,
                    order: this.order
                }, {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.dropOver = false;
                        console.log('✅ Komponente erfolgreich zum Tab hinzugefügt');
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
