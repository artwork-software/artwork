<template>
    <AppLayout :title="$t('Inventory')">
        <div class="ml-14 mt-5">
            <div class="container max-w-3xl">
                <div class="mb-10">
                    <h2 class="headline1">{{$t('Inventory')}}</h2>
                    <div class="xsLight mt-2">
                        {{$t('Define global settings for inventory planning.')}}
                    </div>
                </div>
                <TinyPageHeadline
                    :title="$t('Inventory-relevant Event Types')"
                    :description="$t('Specify which appointment types are displayed as relevant to stock by default. Stock can only be posted to these appointment types.')"
                />
                <div class="mt-3">
                    <Listbox as="div">
                        <div class="relative mt-2 w-1/2">
                            <ListboxButton class="menu-button">
                                <span class="block truncate text-left pl-3">{{$t('Select Event Types')}}</span>
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                    <IconChevronDown  stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </span>
                            </ListboxButton>

                            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions class="absolute z-50 mt-1 max-h-28 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                    <ListboxOption as="template" v-for="type in notRelevantEventTypes" :key="type.id" :value="type" v-slot="{ active, selected }">
                                        <li @click="addRelevantEventType(type)" :class="[active ? 'bg-artwork-buttons-create text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                            <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ type.name }}</span>
                                            <span v-if="selected" :class="[active ? 'text-white' : 'text-artwork-buttons-create', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                <IconCheck stroke-width="1.5" class="h-5 w-5" aria-hidden="true" />
                                            </span>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </transition>
                        </div>
                    </Listbox>
                </div>
                <div class="mt-3 flex flex-wrap">
                    <TagComponent v-for="type in relevantEventTypes" :method="removeRelevantEventType" :displayed-text="type.name" :property="type" />
                </div>

                <div class="mt-10">
                   <div class="mb-4">
                       <TinyPageHeadline
                           :title="$t('Column Order')"
                           :description="$t('Define the order of the columns in the inventory overview.')"
                       />
                   </div>

                    <draggable ghost-class="opacity-50" key="draggableKey" item-key="id" :list="columns" @start="dragging=true" @end="dragging=false" @change="updateColumnOrder(columns)">
                        <template #item="{element}" :key="element.id">
                            <div v-show="!element.temporary" class="flex group" :key="element.id">
                                <div class="flex bg-artwork-project-background py-5 px-4 my-1 rounded-lg flex-wrap w-full" :key="element.id" :class="dragging? 'cursor-grabbing' : 'cursor-grab'">
                                    <div class="flex w-full">
                                        <div class="flex">
                                            <IconDragDrop class="my-auto xsDark h-5 w-5 hidden group-hover:block"/>
                                            <Link :href="route('rooms.show',{room: element.id})" class="ml-4 my-auto xsDark">
                                                {{ element.name }}
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </draggable>
                </div>


            </div>
        </div>
    </AppLayout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import {IconCheck, IconChevronDown, IconDragDrop} from "@tabler/icons-vue";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";
import {computed, ref} from "vue";
import {Link, router} from "@inertiajs/vue3";
import draggable from "vuedraggable";

const props = defineProps({
    eventTypes: {
        type: Object,
        required: true
    },
    columns: {
        type: Object,
        required: true
    }
})

const dragging = ref(false);

const relevantEventTypes = computed(() => {
    const types = [];
    props.eventTypes.forEach((type) => {
        if(type.relevant_for_inventory){
            types.push(type)
        }
    })
    return types;
})
const notRelevantEventTypes = computed(() => {
    const types = [];
    props.eventTypes.forEach((type) => {
        if(!type.relevant_for_inventory && type.id !== 1){
            types.push(type)
        }
    })
    return types;
})

const removeRelevantEventType = (type) => {
    router.patch(route('event-type.update.inventory.relevant', {
        eventType: type.id
    }), {
        relevant_for_inventory: false
    })
}

const addRelevantEventType = (type) => {
    router.patch(route('event-type.update.inventory.relevant', {
        eventType: type.id
    }), {
        relevant_for_inventory: true
    })
}

const updateColumnOrder = (columns) => {
    columns.map((column, index) => {
        column.order = index + 1
    })

    router.post(route('inventory-management.settings.columns.reorder'), {
        columns: columns
    });
}

</script>

<style scoped>

</style>
