<template>
    <BaseModal v-if="true" full-modal @closed="$emit('closed')">
        <div class="mb-5 px-6">
            <h1 class="headline1 mb-4">
                <!-- Headline Text für MultiEdit -->
                {{ $t('Book inventory for multiple events') }}
            </h1>
            <p class="xsLight">
                <!-- Beschreibungstext für MultiEdit -->
                {{ $t('Select the number of inventory items to be booked for each event.')}}
            </p>
        </div>

        <div class="bg-gray-100 py-4 px-6">
            <div class="flex items-center justify-between">
                <AlertComponent classes="!text-artwork-buttons-create cursor-pointer" show-icon icon-size="h-5 w-5" :text="showInfoText ? $t('Hide help') : $t('Show help')" @click="openCloseShowInfoText" />
                <div v-if="autoCloseInfoText < 15">
                    <p class="xxsLight">
                        {{ $t('The help is hidden in {0} seconds.', [autoCloseInfoText])}}
                    </p>
                </div>
            </div>
            <div class="mt-2" v-if="showInfoText">
                <p class="text-xs">
                    {{ $t('You can switch between events by clicking on "Next event" or "Previous event". The number of inventory items is saved separately for each event. You can finalise the booking when you have gone through all the appointments. If you do not need an inventory item for an appointment, you can set the number to 0 or simply leave the field empty.')}}
                </p>
            </div>
        </div>

        <div class="overflow-y-scroll px-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <div v-for="tab in addItemsToEvent" :key="tab.name" @click="makeCurrent(tab)" :class="[tab.id === currentTab.id ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700', 'whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium cursor-pointer']" :aria-current="tab.current ? 'page' : undefined">
                        {{ tab.name }}
                        <p class="text-xs">
                            {{ tab.project?.name }}
                        </p>
                    </div>
                </nav>
            </div>
        </div>

        <div class="my-10 divide-y-2 divide-dashed px-6" >
            <div class="py-2" v-for="items in currentTab.groupedItems">
                <h3 class="headline3 pb-2">{{ items.craft }}</h3>
                <NumberInputComponent
                    class="pb-2"
                    v-for="item in items.items"
                    :key="item.id"
                    :id="item.name"
                    v-model="item.count"
                    :label="generateNameByItem(item)"
                    @update:count="updateItemCount(currentTab.id, item.id, $event)"
                />
            </div>
        </div>

        <div class="flex items-center justify-between p-6">
            <div>
                <AddButtonSmall v-if="!checkIfFirstEvent()" @click="previousEvent" no-icon :text="$t('Previous Event')" />
            </div>
            <div>
                <AddButtonSmall v-if="!checkIfLastEvent()" @click="nextEvent" no-icon :text="$t('Next Event')" />
                <AddButtonSmall v-else @click="bookInventory" no-icon :text="$t('Book inventory')" />
            </div>
        </div>
    </BaseModal>
</template>

<script setup>
import { computed, ref, reactive } from 'vue';
import BaseModal from "@/Components/Modals/BaseModal.vue";
import NumberInputComponent from "@/Components/Inputs/NumberInputComponent.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import {router} from "@inertiajs/vue3";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";

const props = defineProps({
    events: {
        type: Array,
        required: true
    },
    selectedItems: {
        type: Array,
        required: true
    },
});

const emits = defineEmits(['closed']);
const showInfoText = ref(false);
const autoCloseInfoText = ref(15);
// Beispiel für initialisierte itemCounts, kann je nach Bedarf angepasst werden
const itemCounts = reactive({});

const openCloseShowInfoText = () => {
    showInfoText.value = !showInfoText.value;
    // auto close info text after autoCloseInfoText seconds
    const interval = setInterval(() => {
        if(showInfoText.value) {
            autoCloseInfoText.value--;
            if (autoCloseInfoText.value === 0) {
                showInfoText.value = false;
                autoCloseInfoText.value = 15;
                clearInterval(interval);
            }
        } else {
            autoCloseInfoText.value = 15;
            clearInterval(interval);
        }
    }, 1000);
}

const addItemsToEvent = computed(() => {
    // Funktion zum Gruppieren der Items nach ihrem 'craft'
    const groupByCraft = (items) => {
        return items.reduce((acc, item) => {
            if (!acc[item.craft]) {
                acc[item.craft] = [];
            }
            acc[item.craft].push({...item});
            return acc;
        }, {});
    };

    // Gruppiere die ausgewählten Items nach ihrem 'craft'
    const groupedItems = groupByCraft(props.selectedItems);

    // Erstelle ein neues Array von Events mit den gruppierten Items und der Anzahl
    return props.events.map(event => {
        const itemsWithCount = Object.keys(groupedItems).map(craft => {
            return {
                craft: craft,
                items: groupedItems[craft].map(item => ({
                    ...item,
                    count: itemCounts[event.id]?.[item.id] || '' // Verwende die spezifische Anzahl für dieses Event und Item
                }))
            };
        });

        return {
            id: event.id,
            name: event.eventName ?? event.title,
            project: event.project,
            groupedItems: itemsWithCount
        };
    });
});

// Beispiel für eine Funktion zum Setzen der Anzahl eines Items für ein spezifisches Event
const setItemCount = (eventId, itemId, count) => {
    if (!itemCounts[eventId]) {
        itemCounts[eventId] = {};
    }
    itemCounts[eventId][itemId] = count;
};

// Methode zum Aktualisieren der Item-Anzahl
const updateItemCount = (eventId, itemId, count) => {
    setItemCount(eventId, itemId, count);
};

const generateNameByItem = (item) => {
    return `${item.name} (${item.category}, ${item.group}) anzahl`;
}

const currentTab = ref(addItemsToEvent.value[0]);

const makeCurrent = (tab) => {
    currentTab.value = tab;
}

const nextEvent = () => {
    const currentIndex = addItemsToEvent.value.findIndex(event => event.id === currentTab.value.id);
    if (currentIndex + 1 < addItemsToEvent.value.length) {
        currentTab.value = addItemsToEvent.value[currentIndex + 1];
    }
}

const previousEvent = () => {
    const currentIndex = addItemsToEvent.value.findIndex(event => event.id === currentTab.value.id);
    if (currentIndex - 1 >= 0) {
        currentTab.value = addItemsToEvent.value[currentIndex - 1];
    }
}

const checkIfFirstEvent = () => {
    return addItemsToEvent.value.findIndex(event => event.id === currentTab.value.id) === 0;
}

const checkIfLastEvent = () => {
    return addItemsToEvent.value.findIndex(event => event.id === currentTab.value.id) === addItemsToEvent.value.length - 1;
}

const bookInventory = () => {
    // Hier kannst du die Anzahl der Items für jedes Event speichern
    router.post(route('inventory.multi.events.store'), {
        events: addItemsToEvent.value.map(event => ({
            id: event.id,
            // items without craft only id and count
            items: event.groupedItems.flatMap(item => item.items.map(
                // return id and count, but if count is empty return 0
                item => ({id: item.id, count: item.count === '' ? 0 : item.count})
            ))
        }))
    }, {
        preserveScroll: true,
        onSuccess: () => {
            emits('closed', true);
        }
    });
}
</script>

<style scoped>

</style>
