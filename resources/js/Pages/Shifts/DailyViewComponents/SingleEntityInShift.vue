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
    <div ref="rowRef" class="flex w-full min-w-0 items-center gap-x-2 flex-nowrap">
        <!-- LINKS: Name (darf schrumpfen) -->
        <div class="flex min-w-0 flex-1 items-center gap-x-2">
            <span v-if="person.pivot?.craft_abbreviation !== shift.craft?.abbreviation" class="shrink-0 text-[10px] text-gray-500">
                [{{ person.pivot?.craft_abbreviation }}]
            </span>

            <span
                ref="personNameSpan"
                class="min-w-0 truncate text-xs"
                @mouseenter="showPersonNameTooltipHandler"
                @mouseleave="hidePersonNameTooltip"
            >
                {{ person.name || person.full_name }}
            </span>
            <Teleport to="body">
                <div
                    v-if="isPersonNameTruncated && showPersonNameTooltipFlag"
                    class="fixed z-[9999] pointer-events-none"
                    :style="{ top: personNameTooltipPosition.top + 'px', left: personNameTooltipPosition.left + 'px' }"
                >
                    <div class="rounded-lg bg-artwork-navigation-background px-4 py-0.5 text-[14px] text-white whitespace-nowrap">
                        {{ person.name || person.full_name }}
                    </div>
                </div>
            </Teleport>
        </div>

        <!-- RECHTS: Icons (dürfen NICHT rausgedrückt werden) -->
        <div class="flex shrink-0 items-center gap-x-2">
            <ToolTipComponent
                :icon="findShiftQualification(person.pivot?.shift_qualification_id)?.icon"
                :tooltip-text="findShiftQualification(person.pivot?.shift_qualification_id)?.name || ''"
                icon-size="size-5"
                :stroke="1.75"
                black-icon
                classes-button=""
            />

            <!-- GQ-Icons -->
            <div class="flex shrink-0 items-center gap-x-1">
                <template v-if="!collapseGQIcons">
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
                </template>

                <template v-else>
                    <div class="relative" @mouseenter="showGQTooltip = true" @mouseleave="showGQTooltip = false">
                        <component :is="IconChevronDown" class="size-4 text-gray-600 hover:text-gray-800" />
                        <div
                            v-show="showGQTooltip"
                            class="gq-tooltip absolute z-50 top-full mt-1 right-0 bg-white border border-gray-200 rounded-md shadow-lg p-2"
                        >
                            <div class="flex items-center gap-1">
                                <ToolTipComponent
                                    v-for="gq in personGlobalQualificationsInDemand"
                                    :key="'person-gq-tooltip-' + gq.id"
                                    :icon="gq.icon"
                                    :tooltip-text="gq.name || ''"
                                    icon-size="size-5"
                                    :stroke="1.75"
                                    black-icon
                                    classes-button=""
                                />
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Notes: begrenzen, damit sie nie den Rest killen -->
            <div v-if="$page.props.auth.user.calendar_settings.shift_notes" class="flex min-w-0 items-center max-w-56">
                <Popover as="div" v-slot="{ open, close }" class="relative text-left ring-0">
                    <Float auto-placement portal :offset="{ mainAxis: 5, crossAxis: 25}">
                        <PopoverButton class="flex items-center gap-x-1 min-w-0 w-full !ring-0 border-none">
                            <component
                                :is="IconNote"
                                class="size-4 min-h-4 min-w-4 shrink-0 transition-all duration-150 ease-in-out cursor-pointer"
                                :class="person.pivot?.short_description?.length > 0 ? 'text-black border-1 border-gray-100 w-5 h-5' : 'text-gray-500 hover:text-gray-700'"
                                v-tooltip.bottom="descriptionTooltip"
                            />
                            <span v-if="!hasCollision" class="truncate min-w-0 hidden xl:block xsDark" v-tooltip.bottom="descriptionTooltip">
                                {{ person.pivot?.short_description }}
                            </span>
                        </PopoverButton>

                        <transition enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95">
                            <PopoverPanel class="z-50 w-96 focus:outline-none card glassy">
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

            <!-- Menü: NICHT absolut, sondern im Flow => immer rechts am Rand -->
            <div v-if="can('can plan shifts') || is('artwork admin')" class="shrink-0">
                <BaseMenu has-no-offset white-menu-background dots-size="size-4"
                          :dots-color="$page.props.auth.user.calendar_settings.high_contrast ? 'text-white' : ''"
                          menu-width="w-fit">
                    <BaseMenuItem
                        white-menu-background
                        :icon="IconTrash"
                        title="User von Schicht entfernen"
                        @click="deleteUserFromShift(person)"
                    />
                </BaseMenu>
            </div>
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
import {computed, ref, onMounted, onBeforeUnmount, watch, nextTick} from "vue";
import {IconDeviceFloppy, IconNote, IconChevronDown, IconTrash} from "@tabler/icons-vue";
import {can, is} from "laravel-permission-to-vuejs";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";

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
    },
    craftColor: {
        type: String,
        required: false,
        default: null
    },
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

// Person name tooltip (like project name in FullEventInCalendar)
const personNameSpan = ref(null);
const isPersonNameTruncated = ref(false);
const showPersonNameTooltipFlag = ref(false);
const personNameTooltipPosition = ref({ top: 0, left: 0 });

const checkPersonNameTruncation = () => {
    const el = personNameSpan.value;
    if (!el) { isPersonNameTruncated.value = false; return; }
    isPersonNameTruncated.value = el.scrollWidth > el.clientWidth;
};

const showPersonNameTooltipHandler = (e) => {
    checkPersonNameTruncation();
    if (!isPersonNameTruncated.value) return;
    const rect = e.target.getBoundingClientRect();
    personNameTooltipPosition.value = { top: rect.bottom + 4, left: rect.left };
    showPersonNameTooltipFlag.value = true;
};

const hidePersonNameTooltip = () => {
    showPersonNameTooltipFlag.value = false;
};

// UI: Platzmangel-Erkennung für GQ-Icons → auf Chevron zusammenfalten
const rowRef = ref(null)
const collapseGQIcons = ref(false)
const showGQTooltip = ref(false)
let resizeObserver = null

const checkOverflow = () => {
    const el = rowRef.value
    if (!el) return
    // Nur umschalten, wenn überhaupt GQ-Icons existieren
    const hasGQ = personGlobalQualificationsInDemand.value?.length > 0
    if (!hasGQ) {
        collapseGQIcons.value = false
        return
    }
    // Wenn der Container horizontal überläuft → Icons einklappen
    collapseGQIcons.value = el.scrollWidth > el.clientWidth
}

onMounted(() => {
    checkOverflow()
    if ('ResizeObserver' in window) {
        // @ts-ignore
        resizeObserver = new ResizeObserver(() => checkOverflow())
        if (rowRef.value) resizeObserver.observe(rowRef.value)
    } else {
        window.addEventListener('resize', checkOverflow)
    }
})

onBeforeUnmount(() => {
    if (resizeObserver && rowRef.value) {
        resizeObserver.unobserve(rowRef.value)
    }
    if (resizeObserver) resizeObserver.disconnect?.()
    window.removeEventListener('resize', checkOverflow)
})

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
    const color = props.craftColor?.toLowerCase();
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
