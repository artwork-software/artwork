<template>
    <Popover v-slot="{ open, close }" as="div" class="relative text-left artwork" v-if="isCurrentUserPlannerOfShiftCraft && !shift.is_committed">
        <Float auto-placement portal :offset="{ mainAxis: 5, crossAxis: 25}">
            <PopoverButton class="gap-x-2 font-lexend rounded-lg">
                <div class="py-1.5 px-3 min-w-28 rounded-l-lg" :style="{ backgroundColor: `${shift.craft.color}60` }">
                    <p class="text-xs text-left font-lexend">{{ person.pivot.start_time ?? shift.start }} - {{ person.pivot.end_time ?? shift.end }}</p>
                </div>
            </PopoverButton>
            <transition enter-active-class="transition ease-out duration-100"
                        enter-from-class="transform opacity-0 scale-95"
                        enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95">
                <PopoverPanel static class="z-50 w-96 focus:outline-none  card glassy">
                    <div class="px-4 py-2">
                        <div>
                            <p class="text-xs text-gray-700 mb-2 font-lexend font-bold">
                                Schichtzeiten für {{ person.name || person.full_name }} anpassen
                            </p>
                        </div>
                        <div class="flex items-center gap-x-2">
                            <BaseInput
                                id="start" type="time" class="max-w-28 text-xs"
                                v-model="person.pivot.start_time"
                            />

                            <BaseInput
                                id="start" type="time" class="max-w-28 text-xs"
                                v-model="person.pivot.end_time"
                            />
                            <GlassyIconButton text="Save" icon="IconDeviceFloppy" icon-size="size-4" @click.stop="saveIndividualShiftTime(close)"/>
                        </div>
                    </div>
                </PopoverPanel>
            </transition>
        </Float>
    </Popover>
    <div v-else class="gap-x-2 font-lexend rounded-lg" @click="showRequestWorkTimeChangeModal = true">
        <div class="py-1.5 px-3 min-w-28 rounded-l-lg" :style="{ backgroundColor: `${shift.craft.color}60` }">
            <p class="text-xs text-left font-lexend">{{ person.pivot.start_time ?? shift.start }} - {{ person.pivot.end_time ?? shift.end }}</p>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-4 w-full gap-x-2">
        <p class="text-xs truncate col-span-1">
            <span v-if="person.pivot.craft_abbreviation !== shift.craft.abbreviation">
                [{{ person.pivot.craft_abbreviation }}]
            </span>
            {{ person.name || person.full_name }}</p>

        <div class="flex items-center gap-x-1 col-span-1">
            <component :is="findShiftQualification(person.pivot.shift_qualification_id)?.icon" class="size-3" />
            {{ findShiftQualification(person.pivot.shift_qualification_id)?.name }}
        </div>
        <div class=" col-span-2">
            <Popover as="div" v-slot="{ open, close }" class="relative text-left ring-0">
                <Float auto-placement portal :offset="{ mainAxis: 5, crossAxis: 25}">
                    <PopoverButton class="font-lexend rounded-lg flex items-center gap-x-1 truncate w-full text-gray-500 !ring-0 border-none">
                        <component is="IconNote"
                                   class="size-4 min-h-4 min-w-4 text-gray-500 hover:text-gray-700 transition-all duration-150 ease-in-out cursor-pointer"
                        />
                        <span class="truncate">{{ person.pivot.short_description || 'Keine Beschreibung' }}</span>
                    </PopoverButton>

                    <transition enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95">
                        <PopoverPanel class="z-50 w-96 focus:outline-none  card glassy">
                            <div class="px-4 py-2">
                                <div>
                                    <p class="text-xs text-gray-700 mb-2 font-lexend font-bold">
                                        Schichtbeschreibung für {{ person.name || person.full_name }} anpassen
                                    </p>
                                </div>
                                <div class="flex items-center gap-x-2">
                                    <BaseInput
                                        id="start" label="Short Description" type="text" class="max-w-56 text-xs"
                                        v-model="person.pivot.short_description"
                                    />
                                    <GlassyIconButton text="Save" icon="IconDeviceFloppy" icon-size="size-4" @click.stop="saveShortDescription(close)"/>
                                </div>
                            </div>
                        </PopoverPanel>
                    </transition>
                </Float>
            </Popover>

        </div>
    </div>

    <RequestWorkTimeChangeModal :user="person" :shift="shift" v-if="showRequestWorkTimeChangeModal" @close="showRequestWorkTimeChangeModal = false" />
</template>

<script setup>

import GlassyIconButton from "@/Artwork/Buttons/GlassyIconButton.vue";
import {
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    Popover,
    PopoverButton,
    PopoverOverlay,
    PopoverPanel
} from "@headlessui/vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {Float} from "@headlessui-float/vue";
import {router, usePage} from "@inertiajs/vue3";
import RequestWorkTimeChangeModal from "@/Pages/Shifts/Components/RequestWorkTimeChangeModal.vue";
import {computed, ref} from "vue";

const props = defineProps({
    person: {
        type: Object,
        required: true
    },
    shift: {
        type: Object,
        required: true
    },
    shiftQualifications: {
        type: Object,
        required: true
    }
})


const findShiftQualification = (id) =>
    props.shiftQualifications.find(q => q.id === id);

const showRequestWorkTimeChangeModal = ref(false);


const saveIndividualShiftTime = (closePopover) => {
    // Logic to save the individual shift time for the person, freelancer, or service provider
    // This could involve making an API call to update the shift time in the database
    router.post(route('shifts.updateIndividualShiftTime', {
        entity: props.person,
        shiftPivotId: props.person.pivot.id
    }), {
        start_time: props.person.pivot.start_time,
        end_time: props.person.pivot.end_time
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Optionally, you can show a success message or perform any other action after saving
            console.log('Shift time saved successfully');
            if (typeof closePopover === 'function') closePopover();
        },
        onError: (error) => {
            // Handle error if needed
            console.error('Error saving shift time:', error);
        }
    });
}

const saveShortDescription = (closePopover) => {
    // Logic to save the short description for the person, freelancer, or service provider
    // This could involve making an API call to update the short description in the database
    router.post(route('shifts.updateShortDescription', {
        entity: props.person,
        shiftPivotId: props.person.pivot.id
    }), {
        short_description: props.person.pivot.short_description
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Optionally, you can show a success message or perform any other action after saving
            console.log('Short description saved successfully');
            // Close the popover after saving
            if (typeof closePopover === 'function') closePopover();
        },
        onError: (error) => {
            // Handle error if needed
            console.error('Error saving short description:', error);
        }
    });
}

const isCurrentUserPlannerOfShiftCraft = computed(() => {
    return props.shift.craft.craft_shift_planer.some(planner => planner.id === usePage().props.auth.user.id);
});
</script>

<style scoped>

</style>
