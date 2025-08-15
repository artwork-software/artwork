<template>
    <div v-if="violations.length > 0" class="absolute bottom-1 right-1 flex items-center space-x-1">
        <div 
            v-for="violation in violations" 
            :key="violation.id"
            class="h-3 w-3 rounded-full cursor-pointer flex items-center justify-center"
            :style="{ backgroundColor: violation.shift_rule.warning_color }"
            :title="getViolationTooltip(violation)"
            @click="showViolationDetails(violation)"
        >
            <svg class="h-2 w-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
        </div>
    </div>

    <!-- Violation Details Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Regelverletzung Details</h3>
                
                <div v-if="selectedViolation" class="space-y-4">
                    <div class="bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex items-start">
                            <div 
                                class="h-4 w-4 rounded-full mt-0.5 mr-3"
                                :style="{ backgroundColor: selectedViolation.shift_rule.warning_color }"
                            ></div>
                            <div class="flex-1">
                                <h4 class="font-medium text-red-800">{{ selectedViolation.shift_rule.name }}</h4>
                                <p class="text-sm text-red-700 mt-1">{{ selectedViolation.shift_rule.description }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Datum</label>
                            <p class="text-sm text-gray-900">{{ formatDate(selectedViolation.violation_date) }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Schweregrad</label>
                            <span 
                                :class="selectedViolation.severity === 'error' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'"
                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            >
                                {{ selectedViolation.severity === 'error' ? 'Fehler' : 'Warnung' }}
                            </span>
                        </div>
                    </div>

                    <div v-if="selectedViolation.violation_data">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Details</label>
                        <div class="bg-gray-50 rounded-md p-3">
                            <div v-for="(value, key) in selectedViolation.violation_data" :key="key" class="text-sm">
                                <span class="font-medium">{{ formatDataKey(key) }}:</span> {{ value }}
                            </div>
                        </div>
                    </div>

                    <div v-if="selectedViolation.status === 'active'" class="flex space-x-2">
                        <button 
                            @click="resolveViolation"
                            class="flex-1 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700"
                        >
                            Als gelöst markieren
                        </button>
                        <button 
                            @click="ignoreViolation"
                            class="flex-1 bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700"
                        >
                            Ignorieren
                        </button>
                    </div>
                    <div v-else class="text-sm text-gray-500">
                        Status: {{ getStatusText(selectedViolation.status) }}
                        <span v-if="selectedViolation.resolved_at">
                            am {{ formatDate(selectedViolation.resolved_at) }}
                        </span>
                    </div>
                </div>

                <div class="flex justify-end mt-6">
                    <button 
                        @click="closeModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                    >
                        Schließen
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    violations: {
        type: Array,
        default: () => []
    }
})

const showModal = ref(false)
const selectedViolation = ref(null)

const showViolationDetails = (violation) => {
    selectedViolation.value = violation
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    selectedViolation.value = null
}

const getViolationTooltip = (violation) => {
    return `${violation.shift_rule.name}: ${violation.shift_rule.description}`
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('de-DE')
}

const formatDataKey = (key) => {
    const keyMap = {
        'planned_hours': 'Geplante Stunden',
        'max_allowed': 'Maximum erlaubt',
        'consecutive_days': 'Aufeinanderfolgende Tage',
        'weekly_hours': 'Wochenstunden',
        'rest_hours': 'Ruhezeit (Stunden)',
        'min_required': 'Minimum erforderlich',
        'days_until_shift': 'Tage bis Schicht'
    }
    return keyMap[key] || key
}

const getStatusText = (status) => {
    const statusMap = {
        'active': 'Aktiv',
        'resolved': 'Gelöst',
        'ignored': 'Ignoriert'
    }
    return statusMap[status] || status
}

const resolveViolation = () => {
    router.post(`/shift-rule-violations/${selectedViolation.value.id}/resolve`, {}, {
        onSuccess: () => {
            closeModal()
            // Refresh the page or update the violation status
        }
    })
}

const ignoreViolation = () => {
    router.post(`/shift-rule-violations/${selectedViolation.value.id}/ignore`, {}, {
        onSuccess: () => {
            closeModal()
            // Refresh the page or update the violation status
        }
    })
}
</script>