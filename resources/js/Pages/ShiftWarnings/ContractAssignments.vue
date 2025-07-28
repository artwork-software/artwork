<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-900">Regelzuweisungen zu Verträgen</h1>
                    </div>

                    <!-- Contracts Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Vertragsname
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Aktiv für
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Zugewiesene Regeln
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Aktionen
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="contract in contracts" :key="contract.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ contract.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex items-center space-x-2">
                                            <div 
                                                v-for="(user, index) in contract.user_contract_assigns.slice(0, 3)" 
                                                :key="user.user.id"
                                                class="flex items-center"
                                            >
                                                <img 
                                                    v-if="user.user.profile_photo_url"
                                                    :src="user.user.profile_photo_url" 
                                                    :alt="user.user.full_name"
                                                    class="w-8 h-8 rounded-full"
                                                />
                                                <div 
                                                    v-else
                                                    class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-xs"
                                                >
                                                    {{ getUserInitials(user.user) }}
                                                </div>
                                                <span class="ml-1 text-xs">{{ user.user.first_name }}</span>
                                            </div>
                                            <button 
                                                v-if="contract.user_contract_assigns.length > 3"
                                                @click="showAllUsers(contract)"
                                                class="text-blue-600 hover:text-blue-900 text-xs"
                                            >
                                                +{{ contract.user_contract_assigns.length - 3 }} weitere
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex flex-wrap gap-1">
                                            <span 
                                                v-for="rule in contract.workflow_rules" 
                                                :key="rule.id"
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                            >
                                                {{ rule.name }}
                                            </span>
                                            <span v-if="contract.workflow_rules.length === 0" class="text-gray-400">
                                                Keine Regeln zugewiesen
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button
                                            @click="editAssignments(contract)"
                                            class="text-indigo-600 hover:text-indigo-900"
                                            title="Regelzuweisung bearbeiten"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignment Modal -->
        <Modal :show="showAssignmentModal" @close="closeAssignmentModal" max-width="2xl">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Regelzuweisungen für "{{ selectedContract?.name }}"
                </h2>

                <div v-if="selectedContract" class="mb-4">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Vertrag aktiv für:</h3>
                    <div class="flex flex-wrap gap-2">
                        <div 
                            v-for="user in selectedContract.user_contract_assigns" 
                            :key="user.user.id"
                            class="flex items-center bg-gray-100 rounded-lg px-3 py-1"
                        >
                            <img 
                                v-if="user.user.profile_photo_url"
                                :src="user.user.profile_photo_url" 
                                :alt="user.user.full_name"
                                class="w-6 h-6 rounded-full mr-2"
                            />
                            <div 
                                v-else
                                class="w-6 h-6 rounded-full bg-gray-300 flex items-center justify-center text-xs mr-2"
                            >
                                {{ getUserInitials(user.user) }}
                            </div>
                            <span class="text-sm">{{ user.user.full_name }}</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-sm font-medium text-gray-700">Verfügbare Regeln:</h3>
                    <div class="grid grid-cols-1 gap-3 max-h-60 overflow-y-auto">
                        <label 
                            v-for="rule in rules" 
                            :key="rule.id"
                            class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer"
                            :class="{ 'border-blue-500 bg-blue-50': assignmentForm.rule_ids.includes(rule.id) }"
                        >
                            <input
                                type="checkbox"
                                v-model="assignmentForm.rule_ids"
                                :value="rule.id"
                                class="rounded border-gray-300 mr-3"
                            />
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium">{{ rule.name }}</span>
                                    <div 
                                        class="w-4 h-4 rounded-full border ml-2"
                                        :style="{ backgroundColor: rule.warning_color }"
                                    ></div>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ formatTriggerType(rule.trigger_type) }} - Wert: {{ rule.individual_number_value }}
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button
                        type="button"
                        @click="closeAssignmentModal"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded"
                    >
                        Abbrechen
                    </button>
                    <button
                        @click="saveAssignments"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    >
                        Speichern
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'
import { ref, reactive } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    contracts: Array,
    rules: Array
})

const showAssignmentModal = ref(false)
const selectedContract = ref(null)

const assignmentForm = reactive({
    rule_ids: []
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

function getUserInitials(user) {
    return (user.first_name?.charAt(0) || '') + (user.last_name?.charAt(0) || '')
}

function editAssignments(contract) {
    selectedContract.value = contract
    assignmentForm.rule_ids = contract.workflow_rules.map(r => r.id)
    showAssignmentModal.value = true
}

function closeAssignmentModal() {
    showAssignmentModal.value = false
    selectedContract.value = null
    assignmentForm.rule_ids = []
}

function saveAssignments() {
    useForm(assignmentForm).put(`/shift-warnings/contracts/${selectedContract.value.id}/assignments`, {
        onSuccess: () => {
            closeAssignmentModal()
        }
    })
}

function showAllUsers(contract) {
    // Implementierung für Modal mit allen Benutzern
    alert(`Alle ${contract.user_contract_assigns.length} Benutzer anzeigen`)
}
</script>