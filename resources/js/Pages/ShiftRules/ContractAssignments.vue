<template>
    <AppLayout>
        <div class="max-w-screen-2xl mx-auto px-4 py-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-text">Regelzuweisungen an Verträge</h1>
                <p class="text-text-muted">Weisen Sie Schicht-Regeln zu bestehenden Arbeitsverträgen zu</p>
            </div>

            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-border-subtle">
                    <h2 class="text-lg font-medium text-text">Aktive Verträge</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border-subtle">
                        <thead class="bg-surface-sunken">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                    Name
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                    Aktiv für
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                    Zugewiesene Regeln
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-text-subtle uppercase tracking-wider">
                                    Aktionen
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-border-subtle">
                            <tr v-for="contract in contracts" :key="contract.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-text">{{ contract.name }}</div>
                                    <div v-if="contract.description" class="text-sm text-text-subtle">{{ contract.description }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <div v-if="contract.user" class="flex items-center">
                                            <img 
                                                v-if="contract.user.profile_photo_url" 
                                                :src="contract.user.profile_photo_url" 
                                                :alt="contract.user.first_name + ' ' + contract.user.last_name"
                                                class="h-8 w-8 rounded-full"
                                            >
                                            <div v-else class="h-8 w-8 rounded-full bg-border flex items-center justify-center">
                                                <span class="text-xs font-medium text-text-muted">
                                                    {{ contract.user.first_name[0] }}{{ contract.user.last_name[0] }}
                                                </span>
                                            </div>
                                            <span class="ml-2 text-sm text-text">
                                                {{ contract.user.first_name }} {{ contract.user.last_name }}
                                            </span>
                                        </div>
                                        <span v-else class="text-sm text-text-subtle">Nicht zugewiesen</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span 
                                            v-for="rule in contract.shift_rules" 
                                            :key="rule.id"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-accent-100 text-accent-700"
                                        >
                                            {{ rule.name }}
                                        </span>
                                        <span v-if="contract.shift_rules.length === 0" class="text-sm text-text-subtle">
                                            Keine Regeln zugewiesen
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button 
                                        @click="editAssignments(contract)"
                                        class="text-accent-600 hover:text-accent-700"
                                        title="Regelzuweisung bearbeiten"
                                    >
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

        <!-- Edit Assignments Modal -->
        <div v-if="showEditModal" class="fixed inset-0 bg-text-subtle bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-text mb-4">
                        Regelzuweisungen für "{{ editingContract?.name }}"
                    </h3>
                    
                    <div v-if="editingContract?.user" class="mb-6 p-4 bg-surface-sunken rounded-md">
                        <div class="flex items-center">
                            <img 
                                v-if="editingContract.user.profile_photo_url" 
                                :src="editingContract.user.profile_photo_url" 
                                :alt="editingContract.user.first_name + ' ' + editingContract.user.last_name"
                                class="h-10 w-10 rounded-full"
                            >
                            <div v-else class="h-10 w-10 rounded-full bg-border flex items-center justify-center">
                                <span class="text-sm font-medium text-text-muted">
                                    {{ editingContract.user.first_name[0] }}{{ editingContract.user.last_name[0] }}
                                </span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-text">
                                    {{ editingContract.user.first_name }} {{ editingContract.user.last_name }}
                                </p>
                                <p class="text-sm text-text-subtle">Vertrag: {{ editingContract.name }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h4 class="text-md font-medium text-text mb-3">Zugewiesene Regeln</h4>
                        <div class="space-y-2">
                            <div 
                                v-for="rule in selectedRules" 
                                :key="rule.id"
                                class="flex items-center justify-between p-3 bg-surface-sunken rounded-md"
                            >
                                <div>
                                    <span class="font-medium text-text">{{ rule.name }}</span>
                                    <p class="text-sm text-text-subtle">{{ rule.description }}</p>
                                </div>
                                <button 
                                    @click="removeRule(rule.id)"
                                    class="text-danger hover:text-danger"
                                    title="Regel entfernen"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <div v-if="selectedRules.length === 0" class="text-sm text-text-subtle italic">
                                Keine Regeln zugewiesen
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h4 class="text-md font-medium text-text mb-3">Verfügbare Regeln hinzufügen</h4>
                        <div class="space-y-2">
                            <div 
                                v-for="rule in availableRules" 
                                :key="rule.id"
                                class="flex items-center justify-between p-3 bg-white border border-border-subtle rounded-md"
                            >
                                <div>
                                    <span class="font-medium text-text">{{ rule.name }}</span>
                                    <p class="text-sm text-text-subtle">{{ rule.description }}</p>
                                </div>
                                <button 
                                    @click="addRule(rule)"
                                    class="text-accent-600 hover:text-accent-700"
                                    title="Regel hinzufügen"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </button>
                            </div>
                            <div v-if="availableRules.length === 0" class="text-sm text-text-subtle italic">
                                Alle verfügbaren Regeln sind bereits zugewiesen
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button 
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 text-sm font-medium text-text-muted bg-surface-sunken rounded-md hover:bg-border-subtle"
                        >
                            Abbrechen
                        </button>
                        <button 
                            @click="saveAssignments"
                            class="px-4 py-2 text-sm font-medium text-white bg-accent-600 rounded-md hover:bg-accent-700"
                        >
                            Speichern
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    contracts: Array,
    shiftRules: Array
})

const showEditModal = ref(false)
const editingContract = ref(null)
const selectedRules = ref([])

const availableRules = computed(() => {
    const selectedRuleIds = selectedRules.value.map(rule => rule.id)
    return props.shiftRules.filter(rule => !selectedRuleIds.includes(rule.id) && rule.is_active)
})

const editAssignments = (contract) => {
    editingContract.value = contract
    selectedRules.value = [...(contract.shift_rules || [])]
    showEditModal.value = true
}

const closeModal = () => {
    showEditModal.value = false
    editingContract.value = null
    selectedRules.value = []
}

const addRule = (rule) => {
    selectedRules.value.push(rule)
}

const removeRule = (ruleId) => {
    selectedRules.value = selectedRules.value.filter(rule => rule.id !== ruleId)
}

const saveAssignments = () => {
    const contractIds = selectedRules.value.map(rule => rule.id)
    
    router.post(`/shift-rules/contracts/${editingContract.value.id}/assign`, {
        contract_ids: contractIds
    }, {
        onSuccess: () => {
            closeModal()
        }
    })
}
</script>