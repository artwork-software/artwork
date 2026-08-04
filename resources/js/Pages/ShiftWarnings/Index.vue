<template>
    <ShiftSettingsHeader
        :title="$t('Shift warnings - rules')"
        :description="$t('Shift warnings are used to monitor and enforce compliance with labor regulations and internal policies.')"
    >
        <template #actions>
            <BaseUIButton @click="openCreateModal" label="Create new rule" use-translation is-add-button class="whitespace-nowrap shrink-0" />
        </template>

        <SettingsGuideBanner
            storage-key="settings-guide.shift.rules"
            title="How shift warning rules work"
            class="mb-6"
            :paragraphs="[
                'Rules automatically check the shift plan for violations of working time requirements — for example maximum hours, rest times or free days.',
                'Violations are highlighted in colour in the shift plan and collected in the \'Open violations\' tab, where you can resolve, ignore or compensate them.'
            ]"
            footnote="A rule only applies to people whose contract is assigned to it."
        />

        <div class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border-subtle">
                    <thead class="bg-surface-sunken">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Name') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Type') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Value') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Warning color') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Contracts') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-border-subtle">
                        <tr v-for="rule in rules" :key="rule.id">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-text">
                                {{ rule.name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-subtle">
                                {{ formatTriggerType(rule.trigger_type) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-subtle">
                                {{ rule.individual_number_value }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-subtle">
                                <div
                                    class="w-6 h-6 rounded-full border"
                                    :style="{ backgroundColor: rule.warning_color }"
                                ></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-subtle">
                                <span v-if="rule.contracts.length > 0">
                                    {{ rule.contracts.length }} {{ $t('Contract(s)')}}
                                </span>
                                <span v-else class="text-text-subtle">{{ $t('No assignments') }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium flex justify-end items-center">
                                <BaseMenu white-menu-background has-no-offset >
                                    <BaseMenuItem white-menu-background title="Edit" @click="editRule(rule)" />
                                    <BaseMenuItem white-menu-background title="Delete" icon="IconTrash" @click="deleteRule(rule)" />
                                </BaseMenu>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <ArtworkBaseModal v-if="showModal" @close="closeModal" :title="editingRule ? 'Edit Rule' : 'Create new rule'" :description="editingRule ? 'Edit the selected rule.' : 'Create a new shift warning rule.'">
            <div class="p-5">
                <form @submit.prevent="saveRule">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <BaseInput
                                v-model="form.name"
                                label="Name"
                                required
                                id="name"
                            />
                        </div>

                        <div>
                            <BaseTextarea
                                v-model="form.description"
                                label="Description"
                                required
                                id="description"
                            />
                        </div>

                        <div v-if="editingRule" class="mb-1">
                            <label class="block text-sm font-medium text-text-muted mb-1">{{ $t('Rule type') }}</label>
                            <p class="text-sm text-text">{{ formatTriggerType(form.trigger_type) }}</p>
                        </div>

                        <div v-if="!editingRule" class="relative">
                            <Listbox as="div" class="flex relative" v-model="form.trigger_type" id="eventType">
                                <ListboxButton v-if="form.trigger_type !== ''" class="menu-button">
                                    <div class="flex items-center justify-between w-full">
                                    <span class="truncate items-center flex">
                                        <span class="!text-text">{{ formatTriggerType(form.trigger_type) }}</span>
                                    </span>
                                        <span class="pointer-events-none">
                                    <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-text" aria-hidden="true"/>
                                </span>
                                    </div>
                                </ListboxButton>
                                <ListboxButton v-else class="menu-button">
                                    <div class="flex flex-grow text-sm/5 font-bold text-text-subtle text-left subpixel-antialiased">
                                        {{ $t('Rule type') }}
                                    </div>
                                    <span class="pointer-events-none">
                                         <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-text" aria-hidden="true"/>
                                    </span>
                                </ListboxButton>
                                <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions class="absolute w-full z-10 mt-16 rounded-lg bg-surface-inverse shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" class="max-h-8" v-for="type in availableRuleTypes" :key="type" :value="type" v-slot="{ active, selected }">
                                            <li :class="[active ? ' text-white' : 'text-text-subtle', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                <div class="flex">
                                            <span :class="[selected ? 'text-sm/5 font-bold text-white' : 'font-normal', 'ml-4 block truncate']">
                                                {{ formatTriggerType(type) }}
                                            </span>
                                                </div>
                                                <span :class="[active ? ' text-white' : 'text-text-subtle', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                            <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 flex text-success" aria-hidden="true"/>
                                        </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </Listbox>
                        </div>

                        <SettingsGuideBanner
                            v-if="triggerTypeHint"
                            variant="static"
                            title="What does this rule check?"
                            :paragraphs="[triggerTypeHint]"
                        />

                        <div>
                            <BaseInput
                                v-model="form.individual_number_value"
                                label="Value"
                                required
                                type="number"
                                id="individual_number_value"
                            />
                            <p v-if="form.trigger_type === 'halfDayOffConflict'" class="mt-1 text-xs text-text-subtle">
                                {{ $t('Time as a decimal hour (14 = 14:00, 14.5 = 14:30).') }}
                            </p>
                        </div>

                        <div>
                            <BaseInput
                                v-model.number="form.default_compensation_days"
                                :label="$t('Default substitute days off')"
                                type="number"
                                id="default_compensation_days"
                                :min="0.5"
                                :step="0.5"
                            />
                            <p class="mt-1 text-xs text-text-subtle">{{ $t('Default number of substitute days off when a violation occurs') }}</p>
                        </div>

                        <div>
                            <BaseInput
                                v-model.number="form.default_compensation_deadline_days"
                                :label="$t('Default compensation deadline (days)')"
                                type="number"
                                id="default_compensation_deadline_days"
                                :min="1"
                                :step="1"
                            />
                            <p class="mt-1 text-xs text-text-subtle">{{ $t('Number of days after violation date by which the compensation day must be granted') }}</p>
                        </div>

                        <div>
                            <div class="flex items-center mb-2">
                                <label class="block text-sm font-medium text-text-muted mr-2">{{ $t('Warning color')}}</label>
                            </div>
                            <ColorPickerComponent
                                v-model="form.warning_color"
                                label="Warnfarbe"
                                class="!w-full"
                                :color="form.warning_color"
                                @updateColor="addColor"
                            />
                        </div>

                        <div>
                            <div class="flex gap-3">
                                <div class="flex h-6 shrink-0 items-center">
                                    <div class="group grid size-4 grid-cols-1">
                                        <input v-model="form.notify_on_violation" id="notify_on_violation" aria-describedby="notify_on_violation-description" name="notify_on_violation" type="checkbox" checked="" class="col-start-1 row-start-1 appearance-none rounded-sm border border-border bg-white checked:border-accent-600 checked:bg-accent-600 indeterminate:border-accent-600 indeterminate:bg-accent-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600 disabled:border-border disabled:bg-surface-sunken disabled:checked:bg-surface-sunken forced-colors:appearance-auto" />
                                        <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-text-subtle" viewBox="0 0 14 14" fill="none">
                                            <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="text-sm/6">
                                    <label for="notify_on_violation" class="font-medium text-text">{{ $t('Notification of rule violation') }}</label>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="relative">
                                <Listbox as="div" class="flex relative" v-model="form.contract_ids" id="eventType" multiple>
                                    <ListboxButton class="menu-button">
                                        <div class="flex flex-grow text-sm/5 font-bold text-text-subtle text-left subpixel-antialiased">
                                            {{ $t('Assign contracts')}}
                                        </div>
                                        <span class="pointer-events-none">
                                         <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-text" aria-hidden="true"/>
                                    </span>
                                    </ListboxButton>
                                    <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                        <ListboxOptions class="absolute w-full z-10 mt-16 rounded-lg bg-surface-inverse shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                            <ListboxOption
                                                as="template"
                                                class="max-h-8"
                                                v-for="contract in contracts"
                                                :key="contract.id"
                                                :value="contract.id"
                                                v-slot="{ active, selected }"
                                            >
                                                <li :class="[active ? ' text-white' : 'text-text-subtle', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                    <div class="flex">
                                                        <span :class="[selected ? 'text-sm/5 font-bold text-white' : 'font-normal', 'ml-4 block truncate']">
                                                            {{ contract.name }}
                                                        </span>
                                                    </div>
                                                    <span :class="[active ? ' text-white' : 'text-text-subtle', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                        <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 flex text-success" aria-hidden="true"/>
                                                    </span>
                                                </li>
                                            </ListboxOption>
                                        </ListboxOptions>
                                    </transition>
                                </Listbox>
                            </div>

                            <!-- Display selected contracts -->
                            <div class="mt-2">
                                <span v-if="form.contract_ids.length > 0" class="text-sm text-text-muted">
                                    {{ form.contract_ids.length }} {{ $t('Contract(s) selected')}}
                                </span>
                                <span v-else class="text-sm text-text-subtle">{{ $t('No contracts selected')}}</span>
                            </div>

                            <SettingsGuideBanner
                                variant="static"
                                title="Who is covered by this rule?"
                                class="mt-3"
                                :paragraphs="[
                                    'Without assigned contracts this rule applies to nobody: only people who have one of the assigned contracts in their user profile are checked.'
                                ]"
                            />
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-between">
                        <BaseUIButton
                            type="button"
                            variant="danger"
                            hide-icon
                            @click="closeModal"
                        >
                            {{ $t('Cancel') }}
                        </BaseUIButton>
                        <BaseUIButton
                            type="submit"
                            variant="primary"
                            hide-icon
                        >
                            {{ editingRule ? $t('Update') : $t('Create') }}
                        </BaseUIButton>
                    </div>
                </form>
            </div>
        </ArtworkBaseModal>

        <ArtworkBaseDeleteModal
            v-if="ruleToDelete"
            :title="$t('Delete rule')"
            :description="$t('Do you really want to delete this rule? This action cannot be undone.')"
            @close="closeDeleteRuleModal"
            @delete="confirmDeleteRule"
        />
    </ShiftSettingsHeader>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import ShiftSettingsHeader from "@/Pages/Settings/Components/ShiftSettingsHeader.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import ColorPickerComponent from "@/Components/Globale/ColorPickerComponent.vue";
import {IconCheck, IconChevronDown} from "@tabler/icons-vue";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";
import ArtworkBaseDeleteModal from "@/Artwork/Modals/ArtworkBaseDeleteModal.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";

const props = defineProps({
    rules: Array,
    availableRuleTypes: Array,
    contracts: Array
})


const { t: $t } = useI18n()

const showModal = ref(false)
const editingRule = ref(null)
const ruleToDelete = ref(null)

const form = useForm({
    name: '',
    description: '',
    trigger_type: '',
    individual_number_value: 0,
    warning_color: '#ff6b6b',
    default_compensation_days: null,
    default_compensation_deadline_days: null,
    notify_on_violation: false,
    contract_ids: [],
    user_ids: []
})

const triggerTypeLabels = {
    'maxWorkingHoursOnDay': 'Daily maximum of hours',
    'maxConsecWorkingDays': 'Maximum consecutive working days',
    'weeklyMaxHours': 'Weekly maximum of hours',
    'maxWorkingHoursOnWeek': 'Weekly maximum of hours',
    'restTimeBeforeWorkday': 'Rest time before a working day',
    'restTimeBeforeHoliday': 'Rest time before a Sunday or special day',
    'restTimeBetweenShiftGroups': 'Rest time between shift groups',
    'halfDayOffConflict': 'Conflict: half day off / shift',
    'halfDayOffOnSpecialDay': 'No half day off on special days',
    'minDaysBeforeCommit': 'Minimum days before binding commitment'
}

function formatTriggerType(type) {
    return triggerTypeLabels[type] ? $t(triggerTypeLabels[type]) : type
}

// Context-sensitive explanation per rule type: what is checked and in which unit the "Value" field is interpreted.
const triggerTypeHints = {
    'maxWorkingHoursOnDay': 'Checks the total planned working hours of a person per day. Value = maximum number of hours per day.',
    'maxConsecWorkingDays': 'Checks how many days in a row a person is scheduled without a day off. Value = maximum number of consecutive working days.',
    'weeklyMaxHours': 'Checks the total planned working hours of a person per week. Value = maximum number of hours per week.',
    'maxWorkingHoursOnWeek': 'Checks the total planned working hours of a person per week. Value = maximum number of hours per week.',
    'restTimeBeforeWorkday': 'Checks the rest time between the end of a shift and the start of the next shift before a regular working day. Value = minimum rest time in hours.',
    'restTimeBeforeHoliday': 'Checks the rest time before a Sunday or special day. Value = minimum rest time in hours.',
    'restTimeBetweenShiftGroups': 'Checks the rest time between shifts of different shift groups. Value = minimum rest time in hours. Requires maintained shift groups (tab \'shift groups\').',
    'halfDayOffConflict': 'Checks whether a shift conflicts with a half day off. Value = time of day as a decimal hour (14 = 14:00, 14.5 = 14:30).',
    'halfDayOffOnSpecialDay': 'Checks that no half day off is planned on a special day. Requires the special-day rule to be active in the assigned contract.',
    'minDaysBeforeCommit': 'Checks whether there is enough lead time between committing and the start of a shift. Value = minimum number of days before shifts become binding.'
}

const triggerTypeHint = computed(() => triggerTypeHints[form.trigger_type] ?? null)

function openCreateModal() {
    editingRule.value = null
    resetForm()
    showModal.value = true
}

const addColor = (color) => {
    form.warning_color = color
}

function editRule(rule) {
    editingRule.value = rule
    form.name = rule.name
    form.description = rule.description || ''
    form.trigger_type = rule.trigger_type
    form.individual_number_value = rule.individual_number_value
    form.warning_color = rule.warning_color
    form.default_compensation_days = rule.default_compensation_days ?? null
    form.default_compensation_deadline_days = rule.default_compensation_deadline_days ?? null
    form.notify_on_violation = rule.notify_on_violation || false
    form.contract_ids = rule.contracts ? rule.contracts.map(c => c.id) : []
    form.user_ids = rule.users_to_notify ? rule.users_to_notify.map(u => u.id) : []
    showModal.value = true
}

function closeModal() {
    showModal.value = false
    editingRule.value = null
    resetForm()
}

function resetForm() {
    form.name = ''
    form.description = ''
    form.trigger_type = ''
    form.individual_number_value = 0
    form.warning_color = '#ff6b6b'
    form.default_compensation_days = null
    form.default_compensation_deadline_days = null
    form.notify_on_violation = false
    form.contract_ids = []
    form.user_ids = []
}

function saveRule() {
    const url = editingRule.value
        ? route('shift-rules.update', editingRule.value.id)
        : route('shift-rules.store')


    if(editingRule.value){
        form.put(url, {
            preserveScroll: true,
            onSuccess: () => {
                closeModal()
            }
        })
    } else {
        form.post(url, {
            preserveScroll: true,
            onSuccess: () => {
                closeModal()
            }
        })
    }
}

function deleteRule(rule) {
    ruleToDelete.value = rule
}

function closeDeleteRuleModal() {
    ruleToDelete.value = null
}

function confirmDeleteRule() {
    if (!ruleToDelete.value) return
    useForm({}).delete(route('shift-rules.destroy', ruleToDelete.value.id), {
        preserveScroll: true,
        onFinish: () => closeDeleteRuleModal()
    })
}
</script>
