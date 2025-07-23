<template>
    <div class="grid grid-cols-1 md:grid-cols-2 w-full rounded-lg select-none"
         :style="{ backgroundColor: `${shift.craft.color}50` }">
        <div class="flex items-center gap-x-2">
            <div class="bg-gray-500 py-1.5 px-2 rounded-l-lg" :style="{ backgroundColor: `${shift.craft.color}90` }">
                {{ shift.start }} - {{ shift.end }}
            </div>
            <div class="text-gray-700 font-semibold">
                [{{ shift.craft.abbreviation }}] {{ shift.craft.name }}
            </div>
        </div>
        <div class="flex justify-between items-center w-full px-3">
            <div class="flex items-center gap-x-4">
                <div v-for="qualification in shift.shifts_qualifications" :key="qualification.shift_qualification_id">
                    <div class="text-gray-500 text-[10px] flex items-center gap-x-1 ">
                        <component :is="findShiftQualification(qualification.shift_qualification_id)?.icon" class="size-3" />
                        <div>
                            {{
                                qualification.value -
                                (getEmptyShiftQualification(qualification.shift_qualification_id)?.requiredDropElementsCount ?? 0)
                            }}/{{ qualification.value }}
                        </div>
                        {{ findShiftQualification(qualification.shift_qualification_id)?.name || 'Unbekannte Qualifikation' }}
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end px-3 gap-x-4">
                <component is="IconEdit"
                           class="size-5 text-gray-500 hover:text-gray-700 cursor-pointer transition-all duration-150 ease-in-out"
                           @click.stop="showAddShiftModal = true" />
                <component is="IconChevronDown"
                           @click.stop="toggleShiftDetails"
                           class="size-5 text-gray-500 hover:text-gray-700 cursor-pointer transition-all duration-150 ease-in-out"
                           :class="{ 'rotate-180': showShiftDetails }"/>
            </div>
        </div>
    </div>

        <div v-if="showShiftDetails" class="mt-1 ml-4 space-y-1">
            <template v-for="group in shiftGroups" :key="group.label">
                <div v-for="person in group.items" :key="person.id" class="flex items-center gap-x-2 font-lexend rounded-lg" :style="{ backgroundColor: `${shift.craft.color}20` }">
                    <SingleEntityInShift :person="person" :shift="shift" :shift-qualifications="shiftQualifications" />
                </div>
            </template>

            <div v-for="drop in computedShiftQualificationDropElements" :key="drop.shift_qualification_id" class="flex items-center w-full gap-x-2 font-lexend rounded-lg bg-red-100">
                <Menu as="div" class="relative w-full">
                    <Float auto-placement portal :offset="{ mainAxis: 5, crossAxis: 25}">
                        <MenuButton class="flex cursor-pointer items-center gap-x-2 font-lexend rounded-lg w-full" @click="checkShiftCollision(drop.shift_qualification_id, true)">
                            <div class="py-1.5 px-3 min-w-28 w-28 rounded-l-lg bg-red-200">
                                <div class="text-xs text-left flex items-center gap-x-1">
                                    <component is="IconInfoTriangle" class="size-4 text-red-600" />
                                    {{ $t('Unoccupied') }}
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 w-full gap-x-2">
                                <p class="text-xs text-left">{{ drop.requiredDropElementsCount }} {{ findShiftQualification(drop.shift_qualification_id)?.name || 'Unbekannte Qualifikation' }} {{ $t('Unoccupied') }}</p>
                                <div class="flex items-center gap-x-1">
                                    <component :is="findShiftQualification(drop.shift_qualification_id)?.icon" class="size-3" />
                                    {{ findShiftQualification(drop.shift_qualification_id)?.name || 'Unbekannte Qualifikation' }}
                                </div>
                            </div>
                        </MenuButton>
                        <transition enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95">
                            <MenuItems class="z-50 rounded-lg shadow-xl ring-1 ring-gray-200 ring-opacity-5 focus:outline-none bg-white">
                                <MenuItem as="div" v-slot="{ active }" v-for="user in getAssignablePeopleWithCollision(drop.shift_qualification_id)" :key="user.id" class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer">
                                    <div class="flex justify-between items-center gap-x-2 w-48" @click="createOnDropElementAndSave(user, user.originCraft, drop.shift_qualification_id) ">
                                        <span class="text-xs truncate w-36">{{ user.name || user.full_name }}</span>
                                        <div class="text-xs text-gray-500 flex items-center gap-x-1">
                                            <ToolTipComponent
                                                icon="IconId"
                                                icon-size="w-4 h-4"
                                                tooltip-text="Freelancer"
                                                direction="top"
                                                classes="text-gray-800 w-fit"
                                                v-if="user.type === 'freelancer'"
                                                use-translation
                                            />
                                            <ToolTipComponent
                                                icon="IconBuildingCommunity"
                                                icon-size="w-4 h-4"
                                                tooltip-text="ServiceProvider"
                                                direction="top"
                                                classes="text-gray-800 w-fit"
                                                v-if="user.type === 'service_provider'"
                                                use-translation
                                            />
                                            <ToolTipComponent
                                                icon="IconAlertTriangle"
                                                icon-size="w-4 h-4"
                                                :tooltip-text="$t('User already assigned as {0}', [user.qualification])"
                                                direction="top"
                                                classes="text-red-500 w-fit"
                                                v-if="user.alreadyAssigned"
                                            />
                                            <ToolTipComponent
                                                v-if="user.hasCollision"
                                                icon="IconClock"
                                                icon-size="w-4 h-4"
                                                :tooltip-text="getCollisionTooltip(user)"
                                                direction="top"
                                                classes="text-red-500 w-fit"
                                                tooltip-css-class="w-72"
                                            />
                                            <span v-if="user.pivot?.craft_id" class="font-semibold">{{ user.originCraft?.abbreviation || 'N/A' }}</span>
                                        </div>
                                    </div>
                                </MenuItem>
                            </MenuItems>
                        </transition>
                    </Float>
                </Menu>

            </div>
        </div>

    <AddShiftModal
        v-if="showAddShiftModal"
        :crafts="crafts"
        :event="null"
        :shift="shift"
        :currentUserCrafts="usePage().props.currentUserCrafts"
        :buffer="null"
        :shift-qualifications="usePage().props.shiftQualifications"
        @closed="showAddShiftModal = false"
        :shift-time-presets="usePage().props.shiftTimePresets"
        :shift-plan-modal="true"
        :edit="shift !== null"
    />
</template>

<script setup>
import {ref, computed, watch, defineAsyncComponent, onMounted} from "vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {Float} from "@headlessui-float/vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import {router, usePage} from "@inertiajs/vue3";
import axios from "axios";
import SingleEntityInShift from "@/Pages/Shifts/DailyViewComponents/SingleEntityInShift.vue";
import {can, is} from "laravel-permission-to-vuejs";
import {useI18n} from "vue-i18n";

const props = defineProps({
    shift: Object,
    shiftQualifications: Array,
    crafts: Object
});

// Initialize i18n
const { t } = useI18n();

const showShiftDetails = ref(true);
const showAddShiftModal = ref(false);
const droppedUser = ref({});
const seriesShiftData = ref(null);
// Initialisiere Cache mit leeren Arrays pro Qualifikation
const assignablePeopleCache = ref(
    props.shift.shifts_qualifications.reduce((acc, sq) => {
        acc[sq.shift_qualification_id] = [];
        return acc;
    }, {})
);

const loadingStates = ref(
    props.shift.shifts_qualifications.reduce((acc, sq) => {
        acc[sq.shift_qualification_id] = false;
        return acc;
    }, {})
);

// Track when the last request was made for each qualification ID
const lastRequestTime = ref(
    props.shift.shifts_qualifications.reduce((acc, sq) => {
        acc[sq.shift_qualification_id] = 0;
        return acc;
    }, {})
);

const toggleShiftDetails = () => showShiftDetails.value = !showShiftDetails.value;

const findShiftQualification = (id) =>
    props.shiftQualifications.find(q => q.id === id);

const computedShiftQualificationDropElements = computed(() => {
    return props.shift.shifts_qualifications.map(sq => {
        const totalAssigned = ['users', 'freelancer', 'serviceProviders'].reduce((acc, group) => {
            return acc + props.shift[group].filter(item => item.pivot.shift_qualification_id === sq.shift_qualification_id).length;
        }, 0);
        const remaining = sq.value - totalAssigned;
        return remaining > 0 ? { shift_qualification_id: sq.shift_qualification_id, requiredDropElementsCount: remaining } : null;
    }).filter(Boolean);
});

const getEmptyShiftQualification = (id) =>
    computedShiftQualificationDropElements.value.find(drop => drop.shift_qualification_id === id);

const shiftGroups = computed(() => [
    { label: 'users', items: props.shift.users },
    { label: 'freelancers', items: props.shift.freelancer },
    { label: 'serviceProviders', items: props.shift.serviceProviders }
]);

// Debounce time in milliseconds - prevent requests more frequently than this
const DEBOUNCE_TIME = 2000; // 2 seconds

const checkShiftCollision = async (shiftQualificationId, forceRefresh = false) => {
    // Skip if already loading (prevents duplicate requests)
    if (loadingStates.value[shiftQualificationId]) {
        return;
    }

    // Check if we've made a request recently (debounce)
    const now = Date.now();
    const timeSinceLastRequest = now - lastRequestTime.value[shiftQualificationId];

    // Skip if we've made a request recently and not forcing refresh
    if (!forceRefresh && timeSinceLastRequest < DEBOUNCE_TIME && assignablePeopleCache.value[shiftQualificationId]?.length > 0) {
        return;
    }

    // Update last request time
    lastRequestTime.value[shiftQualificationId] = now;

    // Set loading state
    loadingStates.value[shiftQualificationId] = true;

    try {
        // Check if we have the required time parameters
        if (!props.shift.start || !props.shift.end) {
            // Set empty result and return early
            assignablePeopleCache.value[shiftQualificationId] = getAssignablePeople(shiftQualificationId);
            return;
        }

        // Use current date as fallback if date parameters are missing
        const today = new Date().toISOString().split('T')[0]; // Format: YYYY-MM-DD

        const people = getAssignablePeople(shiftQualificationId);
        // Don't make the request if there are no people to check
        if (people.length === 0) {
            assignablePeopleCache.value[shiftQualificationId] = [];
            return;
        }

        // Create request parameters with the correct parameter names and fallback values
        // The backend expects start_date and end_date
        const requestParams = {
            people: people.map(p => ({
                id: p.id,
                type: p.type // Backend expects 'user', 'freelancer', or 'service_provider'
            })),
            // Use the correct property names for dates - could be either startDate/endDate or start_date/end_date
            start_date: props.shift.startDate || props.shift.start_date || today,
            end_date: props.shift.endDate || props.shift.end_date || today,
            start: props.shift.start,
            end: props.shift.end,
            shift_id: props.shift.id
        };

        const response = await axios.post(route('shift.check-collisions'), requestParams);

        assignablePeopleCache.value[shiftQualificationId] = people.map(person => {
            const collisionData = response.data.find(d =>
                d.id === person.id && d.type === person.type
            );

            // Ensure hasCollision is set correctly based on collisionShifts
            const hasCollision = collisionData?.hasCollision || false;
            const collisionShifts = collisionData?.collisionShifts || [];

            return {
                ...person,
                // Set hasCollision to true if either the backend says it's true OR there are collision shifts
                hasCollision: hasCollision || collisionShifts.length > 0,
                collisionShifts: collisionShifts
            };
        });
    } catch (error) {
        assignablePeopleCache.value[shiftQualificationId] = getAssignablePeople(shiftQualificationId);
    } finally {
        loadingStates.value[shiftQualificationId] = false;
    }
};

const getAssignablePeople = (shiftQualificationId) => {
    // Validate that the shift has the required properties
    if (!props.shift || !props.shift.craft || !props.shift.craft.id) {
        return [];
    }

    const craftIds = [
        props.shift.craft.id,
        ...Object.values(props.crafts || {})
            .filter(c => c.universally_applicable)
            .map(c => c.id)
    ];

    // IDs aller bereits zugewiesenen Personen pro Typ sammeln
    const assigned = {
        user: (props.shift.users || []).map(u => u.id),
        freelancer: (props.shift.freelancer || []).map(f => f.id),
        service_provider: (props.shift.serviceProviders || []).map(s => s.id),
    };

    const peopleWithCraft = [];

    Object.values(props.crafts).forEach(craft => {
        const personTypes = [
            { type: 'user', list: craft.users || [] },
            { type: 'freelancer', list: craft.freelancers || [] },
            { type: 'service_provider', list: craft.service_providers || [] }
        ];

        personTypes.forEach(({ type, list }) => {
            list.forEach(person => {
                const key = `${type}-${person.id}`;
                const alreadyAdded = peopleWithCraft.some(p => p.key === key);
                const hasCraft = person.pivot && craftIds.includes(person.pivot.craft_id);
                const hasQualification = person.shift_qualifications?.some(q => q.id === shiftQualificationId);

                // Prüfen, ob Person bereits in der Schicht ist
                const alreadyAssigned = assigned[type]?.includes(person.id);

                // Zeige alle Personen mit der Qualifikation an, unabhängig davon, ob sie bereits in der Schicht sind
                // Dies ist wichtig, um Kollisionen auch bei bereits zugewiesenen Personen zu erkennen
                if (!alreadyAdded && hasCraft && hasQualification) {
                    peopleWithCraft.push({
                        ...person,
                        type,
                        key,
                        alreadyAssigned, // Flag, ob die Person bereits in dieser Schicht ist
                        qualification: person.shift_qualifications.find(q => q.id === shiftQualificationId)?.name || 'Unbekannt',
                        originCraft: {
                            id: craft.id,
                            name: craft.name,
                            abbreviation: craft.abbreviation,
                            color: craft.color
                        }
                    });
                }
            });
        });
    });

    return peopleWithCraft;
};

// Angepasste Methode für das Menü, die bereits zugewiesene Personen anzeigt
// Diese Funktion sollte KEINE Requests auslösen, da sie bei jedem Rendering aufgerufen wird
const getAssignablePeopleWithCollision = (shiftQualificationId) => {
    // Nur die gecachten Daten zurückgeben, ohne einen neuen Request auszulösen
    // Der Request wird stattdessen beim Öffnen des Dropdowns über den @click Handler ausgelöst
    if (assignablePeopleCache.value[shiftQualificationId]?.length > 0) {
        return assignablePeopleCache.value[shiftQualificationId];
    }

    // Wenn keine Daten im Cache sind, leeres Array zurückgeben
    return [];
};

// Function to generate a more informative tooltip for collision warnings
const getCollisionTooltip = (user) => {
    // Basic collision message
    let tooltip = t('Collisions with shifts:');

    // Add details for each collision shift
    const collisionDetails = user.collisionShifts.map(shift => {
        let detail = '';

        // Add craft abbreviation if available
        if (shift.craftAbbreviation) {
            detail += ` ${shift.craftAbbreviation}`;
        }
        // Add time information
        let shiftStartDate = new Date(shift.start);
        let shiftEndDate = new Date(shift.end);
        let shiftStart = shiftStartDate.getHours().toString().padStart(2, '0') + ':' + shiftStartDate.getMinutes().toString().padStart(2, '0');
        let shiftEnd = shiftEndDate.getHours().toString().padStart(2, '0') + ':' + shiftEndDate.getMinutes().toString().padStart(2, '0');

        //check Same day
        if (shiftStartDate.toDateString() !== shiftEndDate.toDateString()) {
            detail += ` ${shiftStartDate.toLocaleDateString()} ${shiftStart} - ${shiftEndDate.toLocaleDateString()} ${shiftEnd}`;
        } else {
            detail += ` ${shiftStartDate.toLocaleDateString()} ${shiftStart} - ${shiftEnd}`;
        }

        return detail;
    });

    return tooltip + '<br>' + collisionDetails.join('<br>');
};

const createOnDropElementAndSave = (user, craft, shiftQualificationId) => {

    let userType = 0;
    if (user.type === 'freelancer') {
        userType = 1;
    } else if (user.type === 'service_provider') {
        userType = 2;
    } else {
        userType =0;
    }


    droppedUser.value = {
        id: user.id,
        type: userType,
        craft_ids: user.assigned_craft_ids,
        shift_qualifications: user.shift_qualifications ?? [],
        craft_universally_applicable: craft?.universally_applicable ?? false,
        craft_abbreviation: craft.abbreviation ?? '',
    };
    assignUser(droppedUser, shiftQualificationId);
}

const assignUser = (droppedUser, shiftQualificationId) => {

    router.post(
        route('shift.assignUserByType', {shift: props.shift.id}),
        {
            userId: droppedUser.value.id,
            userType: droppedUser.value.type,
            shiftQualificationId: shiftQualificationId,
            seriesShiftData: seriesShiftData.value,
            isShiftTab: true,
            craft_abbreviation: droppedUser.value.craft_abbreviation
        },
        {
            preserveScroll: true,
            onSuccess: () => {

            }
        },
    )
}

const AddShiftModal = defineAsyncComponent({
    loader: () => import('@/Pages/Projects/Components/AddShiftModal.vue'),
    delay: 200,
})

// Funktion, um Kollisionen für alle Qualifikationen mit leeren Slots zu prüfen
const checkAllShiftCollisions = (forceRefresh = false) => {
    // Validate that the shift has the minimum required properties before checking for collisions
    if (!props.shift || !props.shift.shifts_qualifications || !props.shift.start || !props.shift.end) {
        return;
    }

    // Check if there are any empty slots to check
    if (computedShiftQualificationDropElements.value.length === 0) {
        return;
    }

    // Für jede Qualifikation mit leeren Slots eine Kollisionsprüfung durchführen
    computedShiftQualificationDropElements.value.forEach(drop => {
        checkShiftCollision(drop.shift_qualification_id, forceRefresh);
    });
};

// Bei Komponenten-Initialisierung Kollisionen prüfen
onMounted(() => {
    // Force refresh on initial load to ensure we have the latest data
    checkAllShiftCollisions(true);
});

// Wenn sich die Schichtdaten ändern, Cache zurücksetzen und Kollisionen neu prüfen
watch(() => props.shift, () => {
    // Cache zurücksetzen wenn sich die Schichtdaten ändern
    assignablePeopleCache.value = {};
    // Kollisionen neu prüfen mit Force Refresh, da sich die Schichtdaten geändert haben
    checkAllShiftCollisions(true);
}, { deep: true });
</script>
