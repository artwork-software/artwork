<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
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

                    <!-- Rules Table -->
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

        <!-- Create/Edit Modal -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{ editingRule ? 'Regel bearbeiten' : 'Neue Regel erstellen' }}
                </h2>

                <form @submit.prevent="saveRule">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Beschreibung</label>
                            <textarea
                                v-model="form.description"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                rows="3"
                            ></textarea>
                        </div>

                        <div v-if="!editingRule">
                            <label class="block text-sm font-medium text-gray-700">Regel-Typ</label>
                            <select
                                v-model="form.trigger_type"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                required
                            >
                                <option value="">Bitte wählen...</option>
                                <option
                                    v-for="type in availableRuleTypes"
                                    :key="type"
                                    :value="type"
                                >
                                    {{ formatTriggerType(type) }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Wert</label>
                            <input
                                v-model.number="form.individual_number_value"
                                type="number"
                                min="0"
                                step="0.1"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Warnfarbe</label>
                            <input
                                v-model="form.warning_color"
                                type="color"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm h-10"
                            />
                        </div>

                        <div>
                            <label class="flex items-center">
                                <input
                                    v-model="form.notify_on_violation"
                                    type="checkbox"
                                    class="rounded border-gray-300"
                                />
                                <span class="ml-2 text-sm text-gray-700">
                                    Benachrichtigung bei Regelverstoß
                                </span>
                            </label>
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

                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="closeModal"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded"
                        >
                            Abbrechen
                        </button>
                        <button
                            type="submit"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                        >
                            {{ editingRule ? 'Aktualisieren' : 'Erstellen' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Jetstream/Modal.vue'
import { ref, reactive } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    rules: Array,
    availableRuleTypes: Array,
    contracts: Array
})

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
