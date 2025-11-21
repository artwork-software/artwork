<template>
    <Popover v-slot="{ open, close }" as="div" class="relative text-left artwork" v-if="isCurrentUserPlannerOfShiftCraft && !shift.is_committed || hasAdminRole()">
        <Float auto-placement portal :offset="{ mainAxis: 5, crossAxis: 25}">
            <PopoverButton class="gap-x-2 font-lexend rounded-lg">
                <div
                    class="py-1.5 px-1 pr-2 cursor-pointer rounded-l-lg"
                    :style="{ backgroundColor: `${returnCraftColor}` }"
                    v-tooltip.bottom="{ value: 'Arbeitszeitänderung vornehmen', appendTo: 'body', class: 'aw-tooltip', position: 'bottom', useTranslation: false }"
                >
                    <p class="text-xs text-left font-lexend whitespace-nowrap">{{ person.pivot?.start_time ?? shift.start }} - {{ person.pivot?.end_time ?? shift.end }}</p>
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
                            <BaseUIButton label="Save" use-translation :icon="IconDeviceFloppy" icon-size="size-4" @click.stop="saveIndividualShiftTime(close)"/>
                        </div>
                    </div>
                </PopoverPanel>
            </transition>
        </Float>
    </Popover>

    <div v-else class="gap-x-1 font-lexend rounded-lg" @click="showRequestWorkTimeChangeModal = true">
        <div
            class="py-1.5 px-1 rounded-l-lg"
            :style="{ backgroundColor: `${returnCraftColor}` }"
            v-tooltip.bottom="{ value: 'Arbeitszeitänderung anfragen', appendTo: 'body', class: 'aw-tooltip', position: 'bottom', useTranslation: false }"
        >
            <p class="text-xs text-left font-lexend whitespace-nowrap">{{ person.pivot?.start_time ?? shift.start }} - {{ person.pivot?.end_time ?? shift.end }}</p>
        </div>
    </div>
    <div class="flex w-full gap-x-2 group">
        <div class="text-xs truncate col-span-1 flex items-center gap-x-3">
            <span v-if="person.pivot?.craft_abbreviation !== shift.craft?.abbreviation">
                [{{ person.pivot?.craft_abbreviation }}]
            </span>
            {{ person.name || person.full_name }}

        </div>

        <div class="flex items-center">
            <ToolTipComponent
                :icon="findShiftQualification(person.pivot?.shift_qualification_id)?.icon"
                :tooltip-text="findShiftQualification(person.pivot?.shift_qualification_id)?.name || ''"
                icon-size="size-5"
                :stroke="1.75"
                black-icon
                classes-button=""
            />
            <!-- Globale Qualifikationen der Person (nur wenn in dieser Schicht gefordert > 0) -->
            <div class="flex items-center gap-x-1 ml-1">
                <ToolTipComponent
                    v-for="gq in personGlobalQualificationsInDemand"
                    :key="'person-gq-' + gq.id"
                    :icon="gq.icon"
                    :tooltip-text="gq.name || ''"
                    icon-size="size-5"
                    :stroke="1.75"
                    black-icon
                    classes-button=""
                />
            </div>
        </div>
        <div class=" items-center flex col-span-2">
            <Popover as="div" v-slot="{ open, close }" class="relative text-left ring-0">
                <Float auto-placement portal :offset="{ mainAxis: 5, crossAxis: 25}">
                    <PopoverButton class="font-lexend rounded-lg flex items-center gap-x-1 truncate w-full !ring-0 border-none">
                        <component
                            :is="IconNote"
                            class="size-4 min-h-4 min-w-4 transition-all duration-150 ease-in-out cursor-pointer"
                            :class="hasCollision ? person.pivot?.short_description?.length > 0 ? 'text-black border-1 border-gray-100 w-5 h-5' : 'text-gray-500 hover:text-gray-700' : 'text-gray-500 hover:text-gray-700'"
                            v-tooltip.bottom="descriptionTooltip"
                        />
                        <span
                            v-if="!hasCollision"
                            class="truncate max-w-72 xsDark"
                            v-tooltip.bottom="descriptionTooltip"
                        >
                            {{ person.pivot?.short_description}}
                        </span>
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
                                    <BaseUIButton label="Save" use-translation :icon="IconDeviceFloppy" icon-size="size-4" @click.stop="saveShortDescription(close)"/>
                                </div>
                            </div>
                        </PopoverPanel>
                    </transition>
                </Float>
            </Popover>

        </div>
        <div v-if="can('can plan shifts') || is('artwork admin')" class="hidden items-center pt-1 group-hover:block ml-1">
                <span class="flex items-center justify-center">
                    <span class="rounded-full bg-red-400 p-0.5 h-4 w-4 flex items-center justify-center border border-white shadow-[0px_0px_5px_0px_#fc8181]">
                        <IconX class="w-2 h-2 text-white cursor-pointer" @click="deleteUserFromShift(person)"/>
                    </span>
                </span>
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
import {IconDeviceFloppy, IconNote, IconX} from "@tabler/icons-vue";
import {can, is} from "laravel-permission-to-vuejs";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";

const props = defineProps({
    person: {
        type: Object,
        required: true
    },
    shift: {
        type: Object,
        required: true
    },
    // Kann als Array oder Objekt kommen – wir normalisieren unten zu Array
    shiftQualifications: {
        type: [Array, Object],
        required: true
    },
    // Wenn mind. 2 Termine nebeneinander (Kollision), Beschreibung ausblenden und Icon schwarz
    hasCollision: {
        type: Boolean,
        default: false
    }
})


// Normalisierte Liste der Qualifikationen (Array)
const shiftQualificationsArray = computed(() =>
    Array.isArray(props.shiftQualifications)
        ? props.shiftQualifications
        : Object.values(props.shiftQualifications || {})
);

const findShiftQualification = (id) =>
    shiftQualificationsArray.value.find(q => q.id === id);

const showRequestWorkTimeChangeModal = ref(false);

// Tooltip-Binding für die (ggf. gekürzte) Kurzbeschreibung
const descriptionTooltip = computed(() => ({
    value: props.person?.pivot?.short_description || '',
    disabled: !props.person?.pivot?.short_description,
    appendTo: 'body',
    class: 'aw-tooltip',
    position: 'bottom',
    useTranslation: false,
}));

// ----- Globale Qualifikationen: Ermittlung der in der Schicht geforderten und der von der Person besessenen -----
const globalQualificationsMeta = computed(() => {
    const list = usePage()?.props?.globalQualifications ?? [];
    return Array.isArray(list) ? list : Object.values(list || {});
});

const demandedGlobalQualificationIds = computed(() => {
    const arr = Array.isArray(props.shift?.globalQualifications)
        ? props.shift.globalQualifications
        : Object.values(props.shift?.globalQualifications || {});
    return arr
        .filter(gq => (gq?.pivot?.quantity ?? gq?.quantity ?? 0) > 0)
        .map(gq => gq.id);
});

// IDs der globalen Qualifikationen der Person ermitteln – verschiedene Datenformen unterstützen
const personGlobalQualificationIds = computed(() => {
    // Unterstütze mehrere mögliche Property-Namen (camelCase, snake_case, bereits-normalisierte ID-Listen)
    const raw =
        props.person?.globalQualifications ??
        props.person?.global_qualifications ??
        props.person?.globalQualificationIds ??
        props.person?.global_qualification_ids ??
        [];

    const arr = Array.isArray(raw) ? raw : Object.values(raw || {});
    return arr
        .map((x) => {
            if (typeof x === 'number') return x;
            // Häufige Shapes abdecken
            return x?.id ?? x?.global_qualification_id ?? null;
        })
        .filter((v) => typeof v === 'number' && Number.isFinite(v));
});

const personGlobalQualificationsInDemand = computed(() => {
    const demanded = new Set(demandedGlobalQualificationIds.value);
    const personIds = personGlobalQualificationIds.value;
    // Schnittmenge bilden und auf Meta (Name/Icon) mappen
    return personIds
        .filter(id => demanded.has(id))
        .map(id => globalQualificationsMeta.value.find(m => m.id === id))
        .filter(Boolean);
});


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

const returnCraftColor = computed(() => {
    const color = props.shift?.craft?.color?.toLowerCase();
    if (!color || color === '#ffffff') {
        return '#9e9e9e60'; // Standardgrau mit Transparenz
    }
    return `${color}60`; // Farbe mit Transparenz
});

const deleteUserFromShift = (user, removeFromSingleShift = true, preserveState = true) => {

    const userType = user.type === 'user' ? 0 : user.type === 'freelancer' ? 1 : 2;
    const usersPivotId = user.pivot.id;
    router.delete(
        route(
            'shift.removeUserByType',
            {
                usersPivotId: usersPivotId,
                userType: userType,
                isShiftTab: true
            }
        ),
        {
            data: {
                removeFromSingleShift: removeFromSingleShift
            },
            preserveScroll: true,
            // Verhindere kompletten Page-Reload – WebSockets übernehmen das UI-Update
            preserveState: true,
        }
    );
}

const hasAdminRole = () => props.isAdmin || usePage().props.auth.user?.roles?.some?.(r => r.name?.toLowerCase?.().includes('admin'))
</script>

<style scoped>

</style>
