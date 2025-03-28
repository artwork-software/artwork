<template>
    <InventorySettingsHeader>
        <div>
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
        </div>
    </InventorySettingsHeader>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import {IconCheck, IconChevronDown} from "@tabler/icons-vue";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";
import {computed, ref} from "vue";
import {router} from "@inertiajs/vue3";
import InventorySettingsHeader from "@/Pages/InventorySetting/Components/InventorySettingsHeader.vue";

const props = defineProps({
        eventTypes: {
            type: Object,
            required: true
        },
        columns: {
            type: Object,
            required: true
        }
    }),
    dragging = ref(false),
    relevantEventTypes = computed(() => {
        const types = [];

        props.eventTypes.forEach((type) => {
            if(type.relevant_for_inventory){
                types.push(type)
            }
        });

        return types;
    }),
    notRelevantEventTypes = computed(() => {
        const types = [];

        props.eventTypes.forEach((type) => {
            if(!type.relevant_for_inventory && type.id !== 1){
                types.push(type)
            }
        });

        return types;
    }),
    removeRelevantEventType = (type) => {
        router.patch(route('event-type.update.inventory.relevant', {
            eventType: type.id
        }), {
            relevant_for_inventory: false
        });
    },
    addRelevantEventType = (type) => {
        router.patch(route('event-type.update.inventory.relevant', {
            eventType: type.id
        }), {
            relevant_for_inventory: true
        });
    };
</script>
