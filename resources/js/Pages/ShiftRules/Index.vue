<template>
    <AppLayout>
        <div class="max-w-screen-2xl mx-auto px-4 py-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Schicht-Regeln</h1>
                <p class="text-gray-600">Verwalten Sie die Regeln für Schichtplanung und Arbeitszeiten</p>
            </div>

            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-medium text-gray-900">Regeln</h2>
                        <button 
                            @click="showCreateModal = true"
                            class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700"
                        >
                            Neue Regel erstellen
                        </button>
                    </div>
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
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Verträge
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aktionen
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="rule in shiftRules" :key="rule.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ rule.name }}</div>
                                    <div class="text-sm text-gray-500">{{ rule.description }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900">{{ triggerTypes[rule.trigger_type] }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900">{{ rule.individual_number_value }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span 
                                        :class="rule.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                        class="px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{ rule.is_active ? 'Aktiv' : 'Inaktiv' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900">{{ rule.contracts.length }} Verträge</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
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

        <!-- Create/Edit Modal -->
        <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        {{ showCreateModal ? 'Neue Regel erstellen' : 'Regel bearbeiten' }}
                    </h3>
                    
                    <form @submit.prevent="saveRule">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                            <input 
                                v-model="form.name"
                                type="text" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            >
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Beschreibung</label>
                            <textarea 
                                v-model="form.description"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            ></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Regel-Typ</label>
                            <select 
                                v-model="form.trigger_type"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option value="">Bitte wählen...</option>
                                <option v-for="(label, value) in triggerTypes" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Wert</label>
                            <input 
                                v-model="form.individual_number_value"
                                type="number" 
                                step="0.01"
                                min="0"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            >
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Warnfarbe</label>
                            <input 
                                v-model="form.warning_color"
                                type="color" 
                                required
                                class="w-full h-10 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            >
                        </div>

                        <div class="mb-6">
                            <label class="flex items-center">
                                <input 
                                    v-model="form.is_active"
                                    type="checkbox" 
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                >
                                <span class="ml-2 text-sm text-gray-700">Regel ist aktiv</span>
                            </label>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button 
                                type="button"
                                @click="closeModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                            >
                                Abbrechen
                            </button>
                            <button 
                                type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700"
                            >
                                {{ showCreateModal ? 'Erstellen' : 'Speichern' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    shiftRules: Array,
    triggerTypes: Object
})

const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingRule = ref(null)

const form = reactive({
    name: '',
    description: '',
    trigger_type: '',
    individual_number_value: 0,
    warning_color: '#ff0000',
    is_active: true
})

const resetForm = () => {
    form.name = ''
    form.description = ''
    form.trigger_type = ''
    form.individual_number_value = 0
    form.warning_color = '#ff0000'
    form.is_active = true
}

const closeModal = () => {
    showCreateModal.value = false
    showEditModal.value = false
    editingRule.value = null
    resetForm()
}

const editRule = (rule) => {
    editingRule.value = rule
    form.name = rule.name
    form.description = rule.description || ''
    form.trigger_type = rule.trigger_type
    form.individual_number_value = rule.individual_number_value
    form.warning_color = rule.warning_color
    form.is_active = rule.is_active
    showEditModal.value = true
}

const saveRule = () => {
    if (showCreateModal.value) {
        router.post('/shift-rules', form, {
            onSuccess: () => {
                closeModal()
            }
        })
    } else if (showEditModal.value) {
        router.put(`/shift-rules/${editingRule.value.id}`, form, {
            onSuccess: () => {
                closeModal()
            }
        })
    }
}

const deleteRule = (rule) => {
    if (confirm('Sind Sie sicher, dass Sie diese Regel löschen möchten?')) {
        router.delete(`/shift-rules/${rule.id}`)
    }
}
</script>