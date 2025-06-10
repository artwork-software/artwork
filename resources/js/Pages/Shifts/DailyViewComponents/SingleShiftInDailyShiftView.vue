<template>
    <div class="grid grid-cols-1 md:grid-cols-2 w-full rounded-lg cursor-pointer select-none"
         :style="{ backgroundColor: `${shift.craft.color}50` }"
         @click.stop="toggleShiftDetails">
        <div class="flex items-center gap-x-2">
            <div class="bg-gray-500 py-1.5 px-2 rounded-l-lg" :style="{ backgroundColor: `${shift.craft.color}90` }">
                {{ shift.start }} - {{ shift.end }}
            </div>
            <div class="text-gray-700 font-semibold">
                {{ shift.craft.abbreviation }}: {{ shift.craft.name }}
            </div>
        </div>
        <div class="flex justify-between items-center w-full px-3">
            <div class="flex items-center gap-x-4">
                <div v-for="qualification in shift.shifts_qualifications" :key="qualification.shift_qualification_id">
                    <div class="text-gray-500 text-[10px] flex items-center gap-x-1 group hover:bg-gray-50 ring-inset hover:ring-1 ring-artwork-buttons-create p-1 rounded-lg transition-all duration-150 ease-in-out cursor-pointer hover:text-artwork-buttons-create">
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
            <div v-for="person in group.items" :key="person.id" class="flex items-center gap-x-2 font-lexend rounded-lg"
                 :style="{ backgroundColor: `${shift.craft.color}20` }">
                <div class="py-1.5 px-3 min-w-28 rounded-l-lg" :style="{ backgroundColor: `${shift.craft.color}50` }">
                    <p class="text-xs">{{ shift.start }} - {{ shift.end }}</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 w-full gap-x-2">
                    <p class="text-xs">{{ person.name || person.full_name }}</p>
                    <div class="flex items-center gap-x-1">
                        <component :is="findShiftQualification(person.pivot.shift_qualification_id)?.icon" class="size-3" />
                        {{ findShiftQualification(person.pivot.shift_qualification_id)?.name }}
                    </div>
                    <div>
                        <component is="IconNote"
                                   class="size-4 text-gray-500 hover:text-gray-700 transition-all duration-150 ease-in-out cursor-pointer"
                                   @click.stop="toggleShiftDetails"/>
                    </div>
                </div>
            </div>
        </template>

        <div v-for="drop in computedShiftQualificationDropElements" :key="drop.shift_qualification_id" class="flex items-center w-full gap-x-2 font-lexend rounded-lg " :style="{ backgroundColor: `${shift.craft.color}20` }">
            <Menu as="div" class="relative w-full">
                <Float auto-placement portal :offset="{ mainAxis: 5, crossAxis: 25}">
                    <MenuButton class="flex items-center gap-x-2 font-lexend rounded-lg w-full">
                        <div class="py-1.5 px-3 min-w-28 w-28 rounded-l-lg" :style="{ backgroundColor: `${shift.craft.color}50` }">
                            <p class="text-xs text-left">{{ shift.start }} - {{ shift.end }}</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 w-full gap-x-2">
                            <p class="text-xs text-left">{{ drop.requiredDropElementsCount }} freie Plätze</p>
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
                            <MenuItem as="div" v-slot="{ active }" v-for="user in getAssignablePeople(drop.shift_qualification_id)" :key="user.id" class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer">
                                <div>
                                    <div class="flex items-center justify-between gap-x-2">
                                        <span class="text-xs">{{ user.name || user.full_name }}</span>
                                        <ToolTipComponent
                                            icon="IconId"
                                            icon-size="w-4 h-4"
                                            tooltip-text="Freelancer*in"
                                            direction="top"
                                            classes="text-gray-800"
                                            v-if="user.type === 'freelancer'"
                                        />
                                    </div>
                                    <pre class="max-w-44 max-h-44 overflow-auto">
                                        {{ user }}
                                    </pre>
                                </div>
                            </MenuItem>
                        </MenuItems>
                    </transition>
                </Float>
            </Menu>

        </div>
    </div>

    <pre>
        {{ shift }}
    </pre>
</template>

<script setup>
import { ref, computed } from "vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {Float} from "@headlessui-float/vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";

const props = defineProps({
    shift: Object,
    shiftQualifications: Array,
    crafts: Object
});

const showShiftDetails = ref(true);
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

const getAssignablePeople = (shiftQualificationId) => {
    const craftIds = [
        props.shift.craft.id,
        ...Object.values(props.crafts)
            .filter(c => c.universally_applicable)
            .map(c => c.id)
    ];

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

                if (!alreadyAdded && hasCraft && hasQualification) {
                    peopleWithCraft.push({
                        ...person,
                        type,
                        key,
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
</script>
