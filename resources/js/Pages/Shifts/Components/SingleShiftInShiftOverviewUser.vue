<template>
    <div>
        <div class="grid grid-cols-1 md:grid-cols-12 gap-x-4">
            <div class="col-span-2 flex items-center gap-x-2">
                <component is="IconLock" v-if="shift.isCommitted" class="w-4 h-4" />
                <div class="px-2 py-0.5 border rounded-lg text-xs w-fit" :style="{ backgroundColor: shift.craft.color + '22', borderColor: blackColorIfColorIsWhite(shift.craft.color) + '55', color: blackColorIfColorIsWhite(shift.craft.color) }">
                    {{ shift.craftAbbreviation }}
                    <span v-if="shift.craftAbbreviation !== shift.craftAbbreviationUser" class="mx-1">
                        [{{ shift.craftAbbreviationUser }}]
                    </span>
                </div>
            </div>
            <div class="col-span-3 flex items-center">
                <Popover v-slot="{ open, close }" as="div" class="relative text-left artwork" v-if="isCurrentUserPlannerOfShiftCraft && !shift.is_committed">
                    <Float auto-placement portal :offset="{ mainAxis: 5, crossAxis: 25}">
                        <PopoverButton class="font-lexend rounded-lg ring-0 focus:ring-0 focus:outline-none">
                            <p class="text-xs text-left font-lexend">{{ shift.startPivot }} - {{ shift.endPivot }}</p>
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
                                            Schichtzeiten für
                                            {{ user.element.provider_name || user.element.first_name }}
                                            <span v-if="user.element.last_name"> {{ user.element.last_name }}</span>
                                            anpassen
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-x-2">
                                        <BaseInput
                                            id="start" type="time" class="max-w-28 text-xs"
                                            v-model="shift.startPivot"
                                        />

                                        <BaseInput
                                            id="start" type="time" class="max-w-28 text-xs"
                                            v-model="shift.endPivot"
                                        />
                                        <GlassyIconButton text="Save" icon="IconDeviceFloppy" icon-size="size-4" @click.stop="saveIndividualShiftTime(close)"/>
                                    </div>
                                </div>
                            </PopoverPanel>
                        </transition>
                    </Float>
                </Popover>
                <div v-else class="font-lexend rounded-lg" @click="showRequestWorkTimeChangeModal = true">
                    <div class="rounded-l-lg">
                        <p class="text-xs text-left font-lexend">{{ shift.startPivot }} - {{ shift.endPivot  }}</p>
                    </div>
                </div>
            </div>
            <div class="col-span-3 text-xs">
                <div>
                    {{ $t('Room') }}:
                </div>
                {{ shift.roomName }}
            </div>
            <div class="col-span-3 text-xs flex items-center">
                {{ $t('Qualification') }}: {{ shift.qualificationName }}
            </div>
            <div class="col-span-2 text-xs flex items-center" v-if="shift.eventTypeAbbreviation">
                {{ shift.eventTypeAbbreviation }}:
                {{ shift.eventName }}
            </div>
        </div>
    </div>
    <div class="invisible group-hover:visible cursor-pointer flex items-center gap-x-2">
        <button type="button" @click="showRequestWorkTimeChangeModal = true" v-if="user.element.id === usePage().props.auth.user.id && user.type === 0">
            <Component is="IconClockEdit" class="h-5 w-5 hover:text-blue-500 transition-colors duration-300 ease-in-out cursor-pointer" stroke-width="1.5"/>
        </button>
        <button type="button" @click="showConfirmDeleteModal = true">
            <Component is="IconSquareRoundedXFilled" class="h-5 w-5 hover:text-red-500 transition-colors duration-300 ease-in-out cursor-pointer" stroke-width="1.5"/>
        </button>
    </div>

    <ConfirmDeleteModal
        :title="$t('Delete user from shift')"
        :description="$t('Are you sure you want to delete the user from this shift?')"
        :loading="isDeletingUser"
        @closed="closeConfirmDeleteModal"
        @delete="submitDeleteUserFromShift(shift.id, shift.pivotId)"
        v-if="showConfirmDeleteModal"
    />

    <RequestWorkTimeChangeModal
        :user="user.element"
        :shift="shift"
        v-if="showRequestWorkTimeChangeModal"
        @close="showRequestWorkTimeChangeModal = false"
    />
</template>

<script setup>

import {router, usePage} from "@inertiajs/vue3";
import {computed, defineAsyncComponent, ref} from "vue";
import {Popover, PopoverButton, PopoverPanel} from "@headlessui/vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {Float} from "@headlessui-float/vue";
import GlassyIconButton from "@/Artwork/Buttons/GlassyIconButton.vue";

const props = defineProps({
    user: {
        type: Object,
        required: true
    },
    shift: {
        type: Object,
        required: true
    },
})

const showConfirmDeleteModal = ref(false);
const showRequestWorkTimeChangeModal = ref(false);
const isDeletingUser = ref(false);

const closeConfirmDeleteModal = () => {
    showConfirmDeleteModal.value = false;
}

const ConfirmDeleteModal = defineAsyncComponent({
    loader: () => import('@/Layouts/Components/ConfirmDeleteModal.vue'),
    delay: 200,
    timeout: 5000,
});

const RequestWorkTimeChangeModal = defineAsyncComponent({
    loader: () => import('@/Pages/Shifts/Components/RequestWorkTimeChangeModal.vue'),
    delay: 200,
    timeout: 5000,
});

const submitDeleteUserFromShift = (shiftId, pivotId) => {
    isDeletingUser.value = true;
    router.delete(route('shift.removeUserByType', {usersPivotId: pivotId, userType: props.user.type}), {
        data: {
            removeFromSingleShift: true
        },
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            closeConfirmDeleteModal()
        },
        onFinish: () => {
            isDeletingUser.value = false;
            document.getElementById('shift-' + shiftId)?.remove();
        }
    });
}

const blackColorIfColorIsWhite = (color) => {
    return color === '#ffffff' ? '#000000' : color;
}

const isCurrentUserPlannerOfShiftCraft = computed(() => {
    return props.shift.craft.craft_shift_planer.some(planner => planner.id === usePage().props.auth.user.id);
});

const typMapping = {
    0: 'user',
    1: 'freelancer',
    2: 'service_provider'
};


const userToSend = ref({
    id: props.user.id,
    type: typMapping[props.user.type],
});


const saveIndividualShiftTime = (closePopover) => {
    // Logic to save the individual shift time for the person, freelancer, or service provider
    // This could involve making an API call to update the shift time in the database
    router.post(route('shifts.updateIndividualShiftTime', {
        entity: userToSend.value,
        shiftPivotId: props.shift.pivotId
    }), {
        start_time: props.shift.startPivot,
        end_time: props.shift.endPivot
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

</script>

<style scoped>

</style>
