<template>
    <ArtworkBaseModal
        @close="closeModal"
        :title="dayServiceToEdit ? $t('Day Service edit') : $t('Day Service create')"
        :description="dayServiceToEdit ? $t('Here you can edit the day service {0}.', [dayServiceToEdit?.name]) : $t('You can create a new day service here.')"
    >
        <div>
            <div class="flex items-center gap-x-3">
                <IconSelector
                    @update:modelValue="addIconToForm"
                    :current-icon="dayServiceForm ? dayServiceForm.icon : null"
                />

                <div class="w-full">
                    <BaseInput
                        id="name"
                        no-margin-top
                        v-model="dayServiceForm.name"
                        :label="$t('Name of the day service')"
                    />
                </div>

                <div>
                    <ColorPickerComponent
                        @updateColor="addColor"
                        :color="dayServiceForm.hex_color"
                    />
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <BaseUIButton
                    @click="createOrUpdate"
                    :label="dayServiceToEdit ? $t('Save') : $t('Create')"
                    is-add-button
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup lang="ts">
import { defineProps, defineEmits } from 'vue'
import { useForm } from '@inertiajs/vue3'

import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import IconSelector from '@/Components/Icon/IconSelector.vue'
import ColorPickerComponent from '@/Components/Globale/ColorPickerComponent.vue'

defineOptions({ name: 'AddEditDayServiceModal' })

const props = defineProps<{
    dayServiceToEdit?: {
        id?: number|string|null
        name?: string
        icon?: string
        hex_color?: string
    } | null
}>()

const emit = defineEmits<{
    (e: 'closed', val: boolean): void
}>()

// Inertia-Form (Initialwerte aus dayServiceToEdit oder Defaults)
const dayServiceForm = useForm({
    id: props.dayServiceToEdit?.id ?? null,
    name: props.dayServiceToEdit?.name ?? '',
    icon: props.dayServiceToEdit?.icon ?? '',
    hex_color: props.dayServiceToEdit?.hex_color ?? '#0d6be3'
})

// Events/Methoden
function addIconToForm(icon: string) {
    dayServiceForm.icon = icon
}

function addColor(color: string) {
    dayServiceForm.hex_color = color
}

function closeModal(val: boolean) {
    emit('closed', val)
}

function createOrUpdate() {
    if (props.dayServiceToEdit?.id) {
        dayServiceForm.patch(
            route('day-service.update', { dayService: props.dayServiceToEdit.id }),
            {
                preserveScroll: true,
                onSuccess: () => closeModal(false)
            }
        )
    } else {
        dayServiceForm.post(route('day-service.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(false)
        })
    }
}
</script>
