<template>
    <AppLayout :title="$t('Shift warnings - rules')">
        <div class="artwork-container">
            <div class="">
                <h2 class="headline1">{{$t('Shift warnings - rules')}}</h2>
                <div class="xsLight mt-2">
                    {{$t('Shift warnings are used to monitor and enforce compliance with labor regulations and internal policies. You can create, edit, and delete shift warning rules here.')}}
                </div>
            </div>



            <div class="flex items-center justify-between mt-5">
                <TabComponent :tabs="tabs" use-translation/>
                <GlassyIconButton text="Create new rule" icon="IconPlus" @click="openCreateModal" />
            </div>


            <div class="card white p-5 mt-5">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Typ
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Wert
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Warnfarbe
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Verträge
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Aktionen
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="rule in rules" :key="rule.id">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ rule.name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatTriggerType(rule.trigger_type) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ rule.individual_number_value }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div
                                    class="w-6 h-6 rounded-full border"
                                    :style="{ backgroundColor: rule.warning_color }"
                                ></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span v-if="rule.contracts.length > 0">
                                            {{ rule.contracts.length }} Vertrag(e)
                                        </span>
                                <span v-else class="text-gray-400">Keine Zuweisungen</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button
                                    @click="editRule(rule)"
                                    class="text-indigo-600 hover:text-indigo-900 mr-4"
                                >
                                    Bearbeiten
                                </button>
                                <button
                                    @click="deleteRule(rule)"
                                    class="text-red-600 hover:text-red-900"
                                >
                                    Löschen
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>


            <ArtworkBaseModal v-if="showModal" @close="closeModal" :title="editingRule ? 'Edit Rule' : 'Create New Rule'" :description="editingRule ? 'Edit the selected rule.' : 'Create a new shift warning rule.'">
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

                            <div v-if="!editingRule" class="relative">
                                <Listbox as="div" class="flex relative" v-model="form.trigger_type" id="eventType">
                                    <ListboxButton v-if="form.trigger_type !== ''" class="menu-button">
                                        <div class="flex items-center justify-between w-full">
                                        <span class="truncate items-center flex">
                                            <span class="!text-gray-900">{{ formatTriggerType(form.trigger_type) }}</span>
                                        </span>
                                            <span class="pointer-events-none">
                                        <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                    </span>
                                        </div>
                                    </ListboxButton>
                                    <ListboxButton v-else class="menu-button">
                                        <div class="flex flex-grow xsLight text-left subpixel-antialiased">
                                            Regel-Typ
                                        </div>
                                        <span class="pointer-events-none">
                                             <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                        </span>
                                    </ListboxButton>
                                    <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                        <ListboxOptions class="absolute w-full z-10 mt-16 rounded-lg bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                            <ListboxOption as="template" class="max-h-8" v-for="type in availableRuleTypes" :key="type" :value="type" v-slot="{ active, selected }">
                                                <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                    <div class="flex">
                                                <span :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate']">
                                                    {{ formatTriggerType(type) }}
                                                </span>
                                                    </div>
                                                    <span :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 flex text-success" aria-hidden="true"/>
                                            </span>
                                                </li>
                                            </ListboxOption>
                                        </ListboxOptions>
                                    </transition>
                                </Listbox>
                            </div>

                            <div>
                                <BaseInput
                                    v-model="form.individual_number_value"
                                    label="Wert"
                                    required
                                    type="number"
                                    id="individual_number_value"
                                />
                            </div>

                            <div>
                                <div class="flex items-center mb-2">
                                    <label class="block text-sm font-medium text-gray-700 mr-2">Warnfarbe</label>
                                </div>
                                <ColorPickerComponent
                                    v-model="form.warning_color"
                                    label="Warnfarbe"
                                    class="!w-full"
                                    :color="form.warning_color"
                                />
                            </div>

                            <div>
                                <div class="flex gap-3">
                                    <div class="flex h-6 shrink-0 items-center">
                                        <div class="group grid size-4 grid-cols-1">
                                            <input v-model="form.notify_on_violation" id="notify_on_violation" aria-describedby="notify_on_violation-description" name="notify_on_violation" type="checkbox" checked="" class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto" />
                                            <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                                <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="text-sm/6">
                                        <label for="notify_on_violation" class="font-medium text-gray-900">{{ $t('Notification of rule violation') }}</label>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Verträge zuweisen</label>
                                <select
                                    v-model="form.contract_ids"
                                    multiple
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                    size="4"
                                >
                                    <option
                                        v-for="contract in contracts"
                                        :key="contract.id"
                                        :value="contract.id"
                                    >
                                        {{ contract.name }}
                                    </option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">
                                    Mehrere Verträge können mit Strg+Klick (oder Cmd+Klick) ausgewählt werden.
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-between">
                            <ArtworkBaseModalButton
                                type="button"
                                variant="danger"
                                @click="closeModal"
                            >
                                Abbrechen
                            </ArtworkBaseModalButton>
                            <ArtworkBaseModalButton
                                type="submit"
                                variant="primary"
                            >
                                {{ editingRule ? 'Aktualisieren' : 'Erstellen' }}
                            </ArtworkBaseModalButton>
                        </div>
                    </form>
                </div>
            </ArtworkBaseModal>
        </div>
    </AppLayout>
      <!--      <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-900">Schichtwarnungen - Regeln</h1>
                        <button
                            @click="openCreateModal"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                        >
                            Neue Regel erstellen
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Name
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Typ
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Wert
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Warnfarbe
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Verträge
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Aktionen
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="rule in rules" :key="rule.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ rule.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatTriggerType(rule.trigger_type) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ rule.individual_number_value }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div
                                            class="w-6 h-6 rounded-full border"
                                            :style="{ backgroundColor: rule.warning_color }"
                                        ></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span v-if="rule.contracts.length > 0">
                                            {{ rule.contracts.length }} Vertrag(e)
                                        </span>
                                        <span v-else class="text-gray-400">Keine Zuweisungen</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button
                                            @click="editRule(rule)"
                                            class="text-indigo-600 hover:text-indigo-900 mr-4"
                                        >
                                            Bearbeiten
                                        </button>
                                        <button
                                            @click="deleteRule(rule)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Löschen
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>


    </AppLayout>-->
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Jetstream/Modal.vue'
import { ref, reactive } from 'vue'
import { useForm } from '@inertiajs/vue3'
import GlassyIconButton from "@/Artwork/Buttons/GlassyIconButton.vue";
import TabComponent from "@/Components/Tabs/TabComponent.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import ColorPickerComponent from "@/Components/Globale/ColorPickerComponent.vue";
import {IconCheck, IconChevronDown} from "@tabler/icons-vue";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";

const props = defineProps({
    rules: Array,
    availableRuleTypes: Array,
    contracts: Array
})

const tabs = ref([
    {
        name: 'Shift Settings',
        href: route('shift.settings'),
        current: route().current('shift.settings'),
        show: true,
        icon: 'IconCalendarUser'
    },
    {
        name: 'Day Services',
        href: route('day-service.index'),
        current: route().current('day-service.index'),
        show: true,
        icon: 'IconHours24'
    },
    {
        name: 'Work Time Pattern',
        href: route('shift.work-time-pattern'),
        current: route().current('shift.work-time-pattern'),
        show: true,
        icon: 'IconClockCog'
    },
    {
        name: 'User Contracts',
        href: route('user-contract-settings.index'),
        current: route().current('user-contract-settings.index'),
        show: true,
        icon: 'IconContract'
    },
    {
        name: 'Workflows',
        href: route('shift-warnings.rules.index'),
        current: route().current('shift-warnings.rules.index'),
        show: true,
        icon: 'IconJumpRope'
    }
])

const showModal = ref(false)
const editingRule = ref(null)

const form = reactive({
    name: '',
    description: '',
    trigger_type: '',
    individual_number_value: 0,
    warning_color: '#ff6b6b',
    notify_on_violation: false,
    contract_ids: [],
    user_ids: []
})

const triggerTypeLabels = {
    'max_working_hours_on_day': 'Tagesmaximum an Stunden',
    'max_consecutive_working_days': 'Maximale Tage in Folge arbeiten',
    'weekly_max_hours': 'Wochenmaximum an Stunden',
    'rest_time_before_workday': 'Ruhezeit vor Werktag',
    'rest_time_before_holiday': 'Ruhezeit vor Sonder-/Sonntag',
    'min_days_before_commit': 'Mindesttage bis zur Verbindlich-Schaltung'
}

function formatTriggerType(type) {
    return triggerTypeLabels[type] || type
}

function openCreateModal() {
    editingRule.value = null
    resetForm()
    showModal.value = true
}

function editRule(rule) {
    editingRule.value = rule
    form.name = rule.name
    form.description = rule.configuration?.description || ''
    form.trigger_type = rule.trigger_type
    form.individual_number_value = rule.individual_number_value
    form.warning_color = rule.warning_color
    form.notify_on_violation = rule.notify_on_violation
    form.contract_ids = rule.contracts.map(c => c.id)
    form.user_ids = rule.users_to_notify.map(u => u.id)
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
    form.notify_on_violation = false
    form.contract_ids = []
    form.user_ids = []
}

function saveRule() {
    const url = editingRule.value
        ? `/shift-warnings/rules/${editingRule.value.id}`
        : '/shift-warnings/rules'

    const method = editingRule.value ? 'put' : 'post'

    useForm(form)[method](url, {
        onSuccess: () => {
            closeModal()
        }
    })
}

function deleteRule(rule) {
    if (confirm('Möchten Sie diese Regel wirklich löschen?')) {
        useForm({}).delete(`/shift-warnings/rules/${rule.id}`)
    }
}
</script>
