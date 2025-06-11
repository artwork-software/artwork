<template>
    <div class="grid grid-cols-1 md:grid-cols-2 w-full rounded-lg cursor-pointer select-none"
         :style="{ backgroundColor: `${shift.craft.color}50` }"
         @click.stop="toggleShiftDetails">
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
                            {{ qualification.value - getEmptyShiftQualification(qualification.shift_qualification_id)?.requiredDropElementsCount }}/{{ qualification.value }}
                        </div>
                        {{ findShiftQualification(qualification.shift_qualification_id)?.name || 'Unbekannte Qualifikation' }}
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end px-3">
                <component is="IconChevronDown"
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
                        <MenuButton class="flex items-center gap-x-2 font-lexend rounded-lg w-full" @click="checkShiftCollision(drop.shift_qualification_id)">
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
                            <MenuItems class="z-50 rounded-lg shadow-xl ring-1 ring-black ring-opacity-5 focus:outline-none bg-white">
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
                                                :tooltip-text="`${$t('Collision with shifts')}: ${user.collisionShifts.map(s => s.description).join(', ')}`"
                                                direction="top"
                                                classes="text-red-500 w-fit"
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
</template>

<script setup>
import {ref, computed, watch} from "vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {Float} from "@headlessui-float/vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import {router} from "@inertiajs/vue3";
import axios from "axios";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import GlassyIconButton from "@/Artwork/Buttons/GlassyIconButton.vue";
import SingleEntityInShift from "@/Pages/Shifts/DailyViewComponents/SingleEntityInShift.vue"; // Axios für API-Call

const props = defineProps({
    shift: Object,
    shiftQualifications: Array,
    crafts: Object
});

const showShiftDetails = ref(true);
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

const checkShiftCollision = async (shiftQualificationId) => {
    if (assignablePeopleCache.value[shiftQualificationId]?.length > 0) return;

    loadingStates.value[shiftQualificationId] = true;

    try {
        const people = getAssignablePeople(shiftQualificationId);
        const response = await axios.post(route('shift.check-collisions'), {
            people: people.map(p => ({
                id: p.id,
                type: p.type.replace('service_provider', 'service_provider') // Match backend naming
            })),
            start_date: props.shift.start_date,
            end_date: props.shift.end_date,
            start: props.shift.start,
            end: props.shift.end,
            shift_id: props.shift.id
        });

        assignablePeopleCache.value[shiftQualificationId] = people.map(person => {
            const collisionData = response.data.find(d =>
                d.id === person.id && d.type === person.type
            );

            return {
                ...person,
                hasCollision: collisionData?.hasCollision || false,
                collisionShifts: collisionData?.collisionShifts || []
            };
        });
    } catch (error) {
        console.error("Collision check failed:", error);
        assignablePeopleCache.value[shiftQualificationId] = getAssignablePeople(shiftQualificationId);
    } finally {
        loadingStates.value[shiftQualificationId] = false;
    }
};

const getAssignablePeople = (shiftQualificationId) => {
    const craftIds = [
        props.shift.craft.id,
        ...Object.values(props.crafts)
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

// Angepasste Methode für das Menü, die ggf. Kollisionsdaten lädt und auch bereits zugewiesene Personen anzeigt
const getAssignablePeopleWithCollision = (shiftQualificationId) => {
    // Hole die Benutzer aus dem Cache oder zeige alle an, wenn noch keine Kollisionsprüfung durchgeführt wurde
    if (!assignablePeopleCache.value[shiftQualificationId] || assignablePeopleCache.value[shiftQualificationId].length === 0) {
        checkShiftCollision(shiftQualificationId);
        // Während des Ladens, gib leere Liste zurück
        return [];
    }

    return assignablePeopleCache.value[shiftQualificationId].filter(person => {
        // Zeige alle Personen an, markiere aber bereits zugewiesene mit einem Flag
        return true; // Keine Filterung, wir wollen alle anzeigen, inkl. Personen mit Kollisionen
    });
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

watch(() => props.shift, () => {
    // Cache zurücksetzen wenn sich die Schichtdaten ändern
    assignablePeopleCache.value = {};
}, { deep: true });
</script>