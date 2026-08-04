<template>
    <ArtworkBaseModal
        :title="modalTitle"
        :description="modalDescription"
        @close="$emit('closeModal', true)"
    >
        <!-- Step 1: Type Selection -->
        <div v-if="step === 1">
            <fieldset class="mt-4">
                <div class="space-y-4 px-6">
                    <div v-for="method in addMethods" :key="method.id" class="flex items-center">
                        <input
                            v-model="selectedType"
                            :value="method.id"
                            :id="method.id"
                            name="user-type"
                            type="radio"
                            class="h-4 w-4 border-border text-accent-600 focus:ring-accent-600"
                        />
                        <label :for="method.id" class="ml-3 block text-sm font-medium leading-6 text-text">
                            {{ method.title }}
                        </label>
                    </div>
                </div>
            </fieldset>
            <div class="flex justify-center mt-6">
                <BaseUIButton
                    is-add-button
                    :label="$t('Continue')"
                    @click="step = 2"
                />
            </div>
        </div>

        <!-- Step 2: Freelancer Form -->
        <div v-else-if="step === 2 && selectedType === 'extern'">
            <div class="space-y-4 mt-4">
                <BaseInput
                    id="freelancer_first_name"
                    v-model="freelancerForm.first_name"
                    :label="$t('First name')"
                    :required="true"
                    :error="freelancerForm.errors.first_name"
                />
                <BaseInput
                    id="freelancer_last_name"
                    v-model="freelancerForm.last_name"
                    :label="$t('Last name')"
                    :required="true"
                    :error="freelancerForm.errors.last_name"
                />
            </div>
            <div class="flex justify-between mt-6">
                <BaseUIButton
                    is-cancel-button
                    :label="$t('Back')"
                    @click="step = 1"
                />
                <BaseUIButton
                    is-add-button
                    :label="$t('Create freelancer')"
                    :processing="freelancerForm.processing"
                    :disabled="freelancerForm.processing"
                    @click="submitFreelancer"
                />
            </div>
        </div>

        <!-- Step 2: Service Provider Form -->
        <div v-else-if="step === 2 && selectedType === 'service_provider'">
            <div class="space-y-4 mt-4">
                <BaseInput
                    id="provider_name"
                    v-model="serviceProviderForm.provider_name"
                    :label="$t('Company name')"
                    :required="true"
                    :error="serviceProviderForm.errors.provider_name"
                />
            </div>
            <div class="flex justify-between mt-6">
                <BaseUIButton
                    is-cancel-button
                    :label="$t('Back')"
                    @click="step = 1"
                />
                <BaseUIButton
                    is-add-button
                    :label="$t('Create service provider')"
                    :processing="serviceProviderForm.processing"
                    :disabled="serviceProviderForm.processing"
                    @click="submitServiceProvider"
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineEmits(['closeModal'])

const step = ref(1)
const selectedType = ref('extern')

const addMethods = [
    { id: 'extern', title: t('External employee') },
    { id: 'service_provider', title: t('Service provider (company)') },
]

const freelancerForm = useForm({
    first_name: '',
    last_name: '',
})

const serviceProviderForm = useForm({
    provider_name: '',
})

const modalTitle = computed(() => {
    if (step.value === 1) {
        return t('New user')
    }
    if (selectedType.value === 'extern') {
        return t('Create freelancer')
    }
    return t('Create service provider')
})

const modalDescription = computed(() => {
    if (step.value === 1) {
        return t('Who would you like to add to artwork? You can add external employees, as well as service providers (companies) without artwork access.')
    }
    if (selectedType.value === 'extern') {
        return t('Enter the name of the new freelancer. You can add more details later.')
    }
    return t('Enter the name of the new service provider. You can add more details later.')
})

function submitFreelancer() {
    freelancerForm.post(route('freelancer.add'))
}

function submitServiceProvider() {
    serviceProviderForm.post(route('service_provider.add'))
}
</script>
