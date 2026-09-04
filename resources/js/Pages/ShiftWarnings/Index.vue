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
                'Rule violations are marked in the shift plan and collected in the \'Open violations\' tab, where you can process, ignore or compensate them with substitute days off.'
            ]"
            footnote="A rule only applies to people whose contract is assigned to it."
        />

        <RuleViewSwitch current="rules" class="mb-4" />

        <div class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5">
            <!-- Leerzustand -->
            <div v-if="!rules || rules.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
                <IconShieldCheck class="h-10 w-10 text-text-subtle mb-3" stroke-width="1.5" />
                <p class="text-sm font-medium text-text">{{ $t('No rules yet') }}</p>
                <p class="mt-1 text-xs text-text-subtle max-w-md">
                    {{ $t('Create a rule to have the shift plan checked automatically — e.g. daily maximum hours, rest times or work on Sundays and special days.') }}
                </p>
                <BaseUIButton class="mt-4" @click="openCreateModal" label="Create rule" use-translation is-add-button />
            </div>

            <div v-else class="overflow-x-auto">
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
                    <tbody class="bg-surface divide-y divide-border-subtle">
                        <tr v-for="rule in rules" :key="rule.id">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-text">
                                {{ rule.name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-subtle">
                                {{ formatTriggerType(rule.trigger_type) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-subtle">
                                {{ formatRuleValue(rule, $t) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-subtle">
                                <div
                                    class="w-6 h-6 rounded-full border border-border"
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
                            <Listbox as="div" class="flex relative" v-model="form.trigger_type" id="ruleType">
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
                                    <ListboxOptions class="absolute w-full z-10 mt-16 rounded-lg bg-surface-inverse shadow-lg max-h-40 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" class="max-h-8" v-for="type in selectableRuleTypes" :key="type" :value="type" v-slot="{ active, selected }">
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

                        <!-- Wert je Regeltyp: Stunden / Tage / Uhrzeit / kein Wert -->
                        <div v-if="valueKind === 'hours' || valueKind === 'days' || valueKind === 'count'">
                            <div class="flex items-end gap-2">
                                <div class="grow">
                                    <BaseInput
                                        v-model="form.individual_number_value"
                                        :label="valueLabel"
                                        :required="!valueOptional"
                                        type="number"
                                        :min="valueOptional ? 0 : (valueKind === 'hours' ? 0.5 : 1)"
                                        :step="valueKind === 'hours' ? 0.5 : 1"
                                        :placeholder="valuePlaceholder"
                                        id="individual_number_value"
                                    />
                                </div>
                                <span class="mb-2 text-sm text-text-subtle whitespace-nowrap">
                                    {{ valueUnit }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-text-subtle">{{ valueHelpText }}</p>
                            <p v-if="form.errors.individual_number_value" class="mt-1 text-xs text-danger">
                                {{ form.errors.individual_number_value }}
                            </p>
                        </div>

                        <div v-else-if="valueKind === 'time'">
                            <BaseInput
                                v-model="timeValue"
                                :label="$t('Time of day')"
                                required
                                type="time"
                                id="individual_number_time"
                                :placeholder="valuePlaceholder"
                            />
                            <p class="mt-1 text-xs text-text-subtle">{{ valueHelpText }}</p>
                            <p v-if="form.errors.individual_number_value" class="mt-1 text-xs text-danger">
                                {{ form.errors.individual_number_value }}
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
                            <div class="relative">
                                <Listbox as="div" class="flex relative" v-model="form.contract_ids" id="contractIds" multiple>
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

                        <!-- Benachrichtigen: Personen, die bei neuen Verstößen informiert werden -->
                        <div v-if="users && users.length">
                            <div class="relative">
                                <Listbox as="div" class="flex relative" v-model="form.user_ids" id="notifyUserIds" multiple>
                                    <ListboxButton class="menu-button">
                                        <div class="flex flex-grow text-sm/5 font-bold text-text-subtle text-left subpixel-antialiased">
                                            {{ $t('Notify on rule violation') }}
                                        </div>
                                        <span class="pointer-events-none">
                                            <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-text" aria-hidden="true"/>
                                        </span>
                                    </ListboxButton>
                                    <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                        <ListboxOptions class="absolute w-full z-10 mt-16 rounded-lg bg-surface-inverse shadow-lg max-h-40 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                            <ListboxOption
                                                as="template"
                                                class="max-h-8"
                                                v-for="user in users"
                                                :key="user.id"
                                                :value="user.id"
                                                v-slot="{ active, selected }"
                                            >
                                                <li :class="[active ? ' text-white' : 'text-text-subtle', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                    <div class="flex">
                                                        <span :class="[selected ? 'text-sm/5 font-bold text-white' : 'font-normal', 'ml-4 block truncate']">
                                                            {{ user.first_name }} {{ user.last_name }}
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
                            <div class="mt-2 flex flex-wrap gap-1">
                                <span
                                    v-for="user in selectedNotifyUsers"
                                    :key="user.id"
                                    class="inline-flex items-center gap-1 rounded-full bg-surface-sunken px-2 py-0.5 text-xs text-text"
                                >
                                    {{ user.first_name }} {{ user.last_name }}
                                    <button type="button" class="text-text-subtle hover:text-danger" @click="removeNotifyUser(user.id)">
                                        <IconX class="h-3 w-3" stroke-width="2" />
                                    </button>
                                </span>
                                <span v-if="!selectedNotifyUsers.length" class="text-sm text-text-subtle">{{ $t('Nobody is notified') }}</span>
                            </div>
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
                            :disabled="form.processing"
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
import {IconCheck, IconChevronDown, IconShieldCheck, IconX} from "@tabler/icons-vue";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";
import ArtworkBaseDeleteModal from "@/Artwork/Modals/ArtworkBaseDeleteModal.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import RuleViewSwitch from "@/Pages/ShiftWarnings/Components/RuleViewSwitch.vue";
import {
    RULE_TYPES,
    SELECTABLE_RULE_TYPES,
    decimalHourToTime,
    formatRuleValue,
    ruleTypeLabelKey,
    ruleTypeValueKind,
    ruleTypeValueOptional,
    timeToDecimalHour,
} from "@/Pages/ShiftWarnings/ruleTypes.js";

const props = defineProps({
    rules: Array,
    availableRuleTypes: Array,
    contracts: Array,
    users: { type: Array, default: () => [] },
})

const { t: $t } = useI18n()

const showModal = ref(false)
const editingRule = ref(null)
const ruleToDelete = ref(null)

const form = useForm({
    name: '',
    description: '',
    trigger_type: '',
    individual_number_value: null,
    warning_color: '#ff6b6b',
    default_compensation_days: null,
    default_compensation_deadline_days: null,
    contract_ids: [],
    user_ids: []
})

// Nur Typen anbieten, die das Backend kennt (availableRuleTypes) — in der Reihenfolge aus ruleTypes.js
const selectableRuleTypes = computed(() => {
    const backend = props.availableRuleTypes ?? []
    return SELECTABLE_RULE_TYPES.filter((type) => backend.includes(type))
})

function formatTriggerType(type) {
    const key = ruleTypeLabelKey(type)
    return key ? $t(key) : type
}

const triggerTypeHint = computed(() => {
    const hint = RULE_TYPES[form.trigger_type]?.hint
    return hint ? $t(hint) : null
})

const valueKind = computed(() => (form.trigger_type ? ruleTypeValueKind(form.trigger_type) : 'none'))
const valueOptional = computed(() => ruleTypeValueOptional(form.trigger_type))
const valuePlaceholder = computed(() => RULE_TYPES[form.trigger_type]?.placeholder ?? '')

const valueLabel = computed(() => {
    switch (valueKind.value) {
        case 'hours':
            return $t('Value (hours)')
        case 'count':
            return $t('Value (count)')
        default:
            return $t('Value (days)')
    }
})

const valueUnit = computed(() => {
    switch (valueKind.value) {
        case 'hours':
            return 'h'
        case 'count':
            return $t('Sundays')
        default:
            return $t('Days')
    }
})

const valueHelpText = computed(() => {
    switch (valueKind.value) {
        case 'hours':
            return $t('Hours, half hours allowed (e.g. 8 or 10.5).')
        case 'days':
            return $t('Whole days.')
        case 'count':
            return $t('Whole number. Leave empty or 0 to use the target from the contract (free Sundays with Saturday/Monday per season half).')
        case 'time':
            return $t('Time of day as HH:MM — a morning off requires the shift to start at or after this time, an afternoon off requires it to end at or before.')
        default:
            return ''
    }
})

// Uhrzeit-Picker (HH:MM) <-> Dezimalstunde im Formular (14:30 <-> 14.5)
const timeValue = computed({
    get: () => decimalHourToTime(form.individual_number_value),
    set: (value) => {
        form.individual_number_value = timeToDecimalHour(value)
    },
})

const selectedNotifyUsers = computed(() =>
    (props.users ?? []).filter((user) => form.user_ids.includes(user.id))
)

function removeNotifyUser(userId) {
    form.user_ids = form.user_ids.filter((id) => id !== userId)
}

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
    form.individual_number_value = ruleTypeValueKind(rule.trigger_type) === 'none' ? null : rule.individual_number_value
    form.warning_color = rule.warning_color
    form.default_compensation_days = rule.default_compensation_days ?? null
    form.default_compensation_deadline_days = rule.default_compensation_deadline_days ?? null
    form.contract_ids = rule.contracts ? rule.contracts.map(c => c.id) : []
    form.user_ids = rule.users_to_notify ? rule.users_to_notify.map(u => u.id) : []
    form.clearErrors()
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
    form.individual_number_value = null
    form.warning_color = '#ff6b6b'
    form.default_compensation_days = null
    form.default_compensation_deadline_days = null
    form.contract_ids = []
    form.user_ids = []
    form.clearErrors()
}

function saveRule() {
    const url = editingRule.value
        ? route('shift-rules.update', editingRule.value.id)
        : route('shift-rules.store')

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            closeModal()
        }
    }

    // Typen ohne Wert senden null (Backend ignoriert den Wert und speichert 0)
    form.transform((data) => ({
        ...data,
        individual_number_value: valueKind.value === 'none' ? null : data.individual_number_value,
    }))

    if (editingRule.value) {
        form.put(url, options)
    } else {
        form.post(url, options)
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
