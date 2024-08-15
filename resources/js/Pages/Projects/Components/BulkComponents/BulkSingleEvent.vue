<template>
   <div>
       <div class="grid gird-cols-1 md:grid-cols-8 gap-4">
           <div class="">
               <Listbox as="div" class="" v-model="event.type" id="eventType">
                   <ListboxButton class="menu-button">
                       <div class="flex w-full justify-between">
                           <div class="flex items-center gap-x-2">
                               <div>
                                   <div class="block w-5 h-5 rounded-full"
                                        :style="{'backgroundColor' : event.type?.hex_code }"/>
                               </div>
                               <div>
                                   {{ event.type?.name }}
                               </div>
                           </div>
                           <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                       </div>
                   </ListboxButton>

                   <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100"
                               leave-to-class="opacity-0">
                       <ListboxOptions
                           class="absolute w-72 z-10 bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                           <ListboxOption as="template" class="max-h-8"
                                          v-for="eventType in event_types"
                                          :key="eventType.name"
                                          :value="eventType"
                                          v-slot="{ active, selected }">
                               <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                   <div class="flex">
                                       <div>
                                           <div class="block w-3 h-3 rounded-full"
                                                :style="{'backgroundColor' : eventType?.hex_code }"/>
                                       </div>
                                       <span
                                           :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate']">
                                                        {{ eventType.name }}
                                                    </span>
                                   </div>
                                   <span
                                       :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                      <IconCheck stroke-width="1.5" v-if="selected"
                                                                 class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                               </li>
                           </ListboxOption>
                       </ListboxOptions>
                   </transition>
               </Listbox>
           </div>
           <div>
               <input
                   type="text"
                   :id="'name-' + index"
                   v-model="event.name"
                   class="input h-12"
                   :class="event.type?.individual_name && !event.name ? 'border-red-500' : ''"
                   placeholder="Name"
               />
           </div>
           <div>
               <Listbox as="div" class="relative" v-model="event.room" id="room">
                   <ListboxButton class="menu-button">
                       <div class="flex-grow flex text-left xsDark">
                           {{ event.room?.name }}
                       </div>
                       <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                   </ListboxButton>
                   <ListboxOptions class="w-full rounded-lg bg-primary max-h-32 overflow-y-auto text-sm absolute z-30">
                       <ListboxOption v-for="room in rooms"
                                      class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between"
                                      :key="room.name"
                                      :value="room"
                                      v-slot="{ active, selected }">
                           <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']">
                               {{ room.name }}
                           </div>
                           <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 text-success"
                                      aria-hidden="true"/>
                       </ListboxOption>
                   </ListboxOptions>
               </Listbox>
           </div>
           <div>
               <input
                   type="date"
                   :id="'day-' + index"
                   v-model="event.day"
                   placeholder="Tag"
                   class="input h-12"
               />
           </div>
           <div class="col-span-2">
               <div class="flex items-center" v-if="timeArray">
                   <input
                       type="time"
                       :id="'start-time-' + index"
                       v-model="event.start_time"
                       placeholder="Tag"
                       class="input h-12 !rounded-r-none"
                   />
                   <input
                       type="time"
                       :id="'end_time-' + index"
                       v-model="event.end_time"
                       placeholder="Tag"
                       class="input h-12 !rounded-l-none border-l-0"
                   />
               </div>
           </div>
           <div class="flex items-center">
               <div class="flex items-center gap-x-2">
                   <IconCopy @click="event.copy = true" v-if="!event.copy"
                             class="w-6 h-6 text-artwork-buttons-context cursor-pointer hover:text-artwork-buttons-hover transition-all duration-150 ease-in-out"
                             stroke-width="2"/>
                   <IconTrash v-if="index > 0 && !event.copy" @click="deleteCurrentEvent(event)"
                              class="w-6 h-6 text-artwork-buttons-context cursor-pointer hover:text-artwork-messages-error transition-all duration-150 ease-in-out"
                              stroke-width="2"/>
                   <div v-if="event.copy" class="flex items-center gap-x-2">
                       <IconPlus class="w-6 h-6 text-artwork-buttons-context" stroke-width="2"/>
                       <input
                           type="number"
                           class="input h-12 w-14"
                           placeholder="Anzahl"
                           v-model="event.copyCount"
                           min="1"
                           minlength="1"
                           max="1000"
                       />
                       <Listbox as="div" class="relative" v-model="event.copyType" id="room">
                           <ListboxButton class="menu-button">
                               <div class="flex-grow flex text-left xsDark !w-12 truncate">
                                   {{ event.copyType?.name }}
                               </div>
                               <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                           </ListboxButton>
                           <ListboxOptions
                               class="w-full rounded-lg bg-primary max-h-32 overflow-y-auto text-sm absolute z-30">
                               <ListboxOption v-for="copyType in copyTypes"
                                              class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between"
                                              :key="copyType.name"
                                              :value="copyType"
                                              v-slot="{ active, selected }">
                                   <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']">
                                       {{ copyType.name }}
                                   </div>
                                   <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 text-success"
                                              aria-hidden="true"/>
                               </ListboxOption>
                           </ListboxOptions>
                       </Listbox>
                       <IconCircleCheckFilled @click="createCopyByEventWithData(event)"
                                              class="w-8 h-8 text-artwork-buttons-create cursor-pointer hover:text-artwork-buttons-hover transition-all duration-150 ease-in-out"
                                              stroke-width="2"/>
                       <IconX @click="event.copy = false"
                              class="w-6 h-6 text-artwork-buttons-context cursor-pointer hover:text-artwork-buttons-hover transition-all duration-150 ease-in-out"
                              stroke-width="2"/>
                   </div>
               </div>
           </div>
       </div>
       <div v-if="event.nameError && !event.name" class="text-xs mt-1 text-artwork-messages-error">
           {{ $t('Event name is mandatory') }}
       </div>
   </div>
</template>

<script setup>

import {
    IconCheck,
    IconChevronDown,
    IconCircleCheckFilled,
    IconCopy,
    IconPlus,
    IconTrash,
    IconX
} from "@tabler/icons-vue";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";
import Input from "@/Layouts/Components/InputComponent.vue";

const props = defineProps({
    event: {
        type: Object,
        required: true
    },
    timeArray: {
        type: Boolean,
        required: true
    },
    event_types: {
        type: Object,
        required: true
    },
    rooms: {
        type: Object,
        required: true
    },
    copyTypes: {
        type: Array,
        required: true
    },
    index: {
        type: Number,
        required: true
    }
})

const emit = defineEmits(['deleteCurrentEvent', 'createCopyByEventWithData']);

const createCopyByEventWithData = (event) => {
    emit('createCopyByEventWithData', event);
}

const deleteCurrentEvent = (event) => {
    emit('deleteCurrentEvent', event);
}

</script>

<style scoped>

</style>