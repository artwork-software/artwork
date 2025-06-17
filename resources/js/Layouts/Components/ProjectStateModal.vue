<template>
    <BaseModal @closed="closeModal">
        <div class="space-y-6">
            <div class="font-black font-lexend text-primary text-2xl mb-4">
                {{ title }}
            </div>

            <div class="flex">
                <!-- Left side: Color picker -->
                <div class="mr-4">
                    <div class="xsLight mb-1">{{ $t('Color') }}</div>
                    <ColorPickerComponent @updateColor="addColor" :color="form.color" />
                </div>

                <!-- Right side: Name and checkbox -->
                <div class="flex-1">
                    <!-- Name input -->
                    <BaseInput
                        id="name"
                        v-model="form.name"
                        label="Name"
                    />

                    <!-- Checkbox -->
                    <div class="relative flex items-start mt-4">
                        <div class="flex h-6 items-center">
                            <input id="is_planning" v-model="form.is_planning" type="checkbox" class="input-checklist" />
                        </div>
                        <div class="ml-3 text-sm leading-6 flex items-center">
                            <label for="is_planning" class="font-medium text-gray-900">
                                {{ $t('Project counts as "in planning"') }}
                            </label>
                            <div class="relative ml-2 group">
                                <InformationCircleIcon class="h-5 w-5 text-gray-400" />
                                <div class="absolute left-0 bottom-full mb-2 hidden group-hover:block bg-gray-800 text-white text-xs rounded p-2 w-64">
                                    {{ $t('When a project counts as "in planning", all appointments assigned to this project during creation are initially planned appointments instead of firmly scheduled ones.') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" @click="closeModal" class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm">
                    {{ $t('Cancel') }}
                </button>
                <button type="button" @click="submit" :disabled="form.name === ''" class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-artwork-buttons-create text-base font-medium text-white hover:bg-artwork-buttons-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    {{ $t('Save') }}
                </button>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>
import { ref, watch, onMounted } from "vue";
import { InformationCircleIcon } from "@heroicons/vue/solid";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import ColorPickerComponent from "@/Components/Globale/ColorPickerComponent.vue";

const props = defineProps({
    title: {
        type: String,
        default: ''
    },
    state: {
        type: Object,
        default: () => ({
            name: '',
            color: '#FCFCFB',
            is_planning: false
        })
    }
});

const emit = defineEmits(['close', 'submit']);

// Form data
const form = ref({
    id: null,
    name: '',
    color: '#FCFCFB', // Default color (light gray)
    is_planning: false
});

// Initialize form data from state prop
const initializeForm = () => {
    console.log("ProjectStateModal initializeForm:", props.state);
    if (props.state) {
        form.value.id = props.state.id || null;
        form.value.name = props.state.name || '';
        // Convert color from class name to hex if needed
        form.value.color = props.state.color;
        form.value.is_planning = props.state.is_planning === 1;
    }
};

// Initialize form on component creation
onMounted(() => {
    initializeForm();
});

// Watch for changes to the state prop
watch(() => props.state, (newValue) => {
    if (newValue) {
        form.value.id = newValue.id || null;
        form.value.name = newValue.name || '';
        // Convert color from class name to hex if needed
        form.value.color = newValue.color;
        form.value.is_planning = newValue.is_planning || false;
    }
}, { deep: true, immediate: true });

// Close modal and reset form
const closeModal = () => {
    emit('close');
    // Reset form values to default after closing
    form.value = {
        id: null,
        name: '',
        color: '#FCFCFB', // Default color (light gray)
        is_planning: false
    };
};

// Submit form data
const submit = () => {
    // Create a copy of the form data before resetting it
    const formData = { ...form.value };
    console.log("ProjectStateModal submit:", formData);
    emit('submit', formData);
    closeModal();
};

// Add color to form
const addColor = (color) => {
    form.value.color = color;
};
</script>

<style scoped>
</style>
