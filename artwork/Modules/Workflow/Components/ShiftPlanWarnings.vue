<template>
    <div class="shift-plan-warnings">
        <!-- Button zum Triggern der Regel-Validierung -->
        <div class="mb-4">
            <button
                @click="validateShiftRules"
                :disabled="isValidating"
                class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded flex items-center"
            >
                <svg v-if="isValidating" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ isValidating ? 'Validiere...' : 'Schicht-Regelverstöße prüfen' }}
            </button>
        </div>

        <!-- Warnungen in Tageszellen -->
        <div 
            v-for="violation in visibleViolations" 
            :key="violation.id"
            class="rule-violation-warning absolute bottom-0 right-0 z-10"
            :style="{ backgroundColor: violation.warning_color + '20', borderColor: violation.warning_color }"
        >
            <div 
                class="warning-icon cursor-pointer p-1 rounded-full"
                :style="{ backgroundColor: violation.warning_color }"
                @click="showViolationDetails(violation)"
                :title="violation.message"
            >
                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
            </div>
        </div>

        <!-- Violation Details Modal -->
        <Modal :show="showViolationModal" @close="closeViolationModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Regelverstoß Details
                </h2>

                <div v-if="selectedViolation" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Regel</label>
                        <p class="mt-1 text-sm text-gray-900">{{ selectedViolation.rule_name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Datum</label>
                        <p class="mt-1 text-sm text-gray-900">{{ formatDate(selectedViolation.violation_date) }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Beschreibung</label>
                        <p class="mt-1 text-sm text-gray-900">{{ selectedViolation.message }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Schweregrad</label>
                        <span 
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                            :class="{
                                'bg-red-100 text-red-800': selectedViolation.severity === 'high',
                                'bg-yellow-100 text-yellow-800': selectedViolation.severity === 'medium',
                                'bg-green-100 text-green-800': selectedViolation.severity === 'low'
                            }"
                        >
                            {{ getSeverityLabel(selectedViolation.severity) }}
                        </span>
                    </div>

                    <div v-if="selectedViolation.violation_data">
                        <label class="block text-sm font-medium text-gray-700">Details</label>
                        <div class="mt-1 bg-gray-50 rounded p-3">
                            <pre class="text-xs text-gray-600">{{ JSON.stringify(selectedViolation.violation_data, null, 2) }}</pre>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        @click="closeViolationModal"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded"
                    >
                        Schließen
                    </button>
                </div>
            </div>
        </Modal>
    </div>
</template>

<script setup>
import Modal from '@/Components/Modal.vue'
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    violations: {
        type: Array,
        default: () => []
    },
    dateRange: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['violations-updated'])

const isValidating = ref(false)
const showViolationModal = ref(false)
const selectedViolation = ref(null)

const visibleViolations = computed(() => {
    return props.violations.filter(violation => {
        const violationDate = new Date(violation.violation_date)
        const startDate = new Date(props.dateRange.start)
        const endDate = new Date(props.dateRange.end)
        
        return violationDate >= startDate && violationDate <= endDate
    })
})

async function validateShiftRules() {
    isValidating.value = true
    
    try {
        const response = await fetch('/api/shift-rules/validate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                start_date: props.dateRange.start,
                end_date: props.dateRange.end
            })
        })
        
        const data = await response.json()
        
        if (response.ok) {
            emit('violations-updated', data.violations)
        } else {
            console.error('Fehler beim Validieren der Regeln:', data.message)
        }
    } catch (error) {
        console.error('Fehler beim Validieren der Regeln:', error)
    } finally {
        isValidating.value = false
    }
}

function showViolationDetails(violation) {
    selectedViolation.value = violation
    showViolationModal.value = true
}

function closeViolationModal() {
    showViolationModal.value = false
    selectedViolation.value = null
}

function formatDate(dateString) {
    const date = new Date(dateString)
    return date.toLocaleDateString('de-DE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    })
}

function getSeverityLabel(severity) {
    const labels = {
        'high': 'Hoch',
        'medium': 'Mittel',
        'low': 'Niedrig'
    }
    return labels[severity] || severity
}
</script>

<style scoped>
.rule-violation-warning {
    border: 2px solid;
    border-radius: 4px;
    margin: 2px;
}

.warning-icon:hover {
    transform: scale(1.1);
    transition: transform 0.2s ease-in-out;
}
</style>