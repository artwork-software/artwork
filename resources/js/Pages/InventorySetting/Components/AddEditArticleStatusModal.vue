<template>
    <ArtworkBaseModal @close="$emit('close')"
        title="Edit Article Status"
        description="Edit the details of the article status."
    >

        <form @submit.prevent="submit">
            <!-- headline with status name -->
            <h2 class="text-lg font-semibold mb-4">{{ props.status.name }}</h2>

            <div class="mb-4">
                <label for="status-name" class="block text-sm font-medium text-gray-700 mb-2">Status Farbe</label>
                <ColorPickerComponent
                    :color="statusForm.color"
                    @update-color="changeColor"
                />
            </div>

            <div class="flex items-center justify-center mt-4   ">
                <ArtworkBaseModalButton variant="primary" type="submit">
                    {{ $t('Save') }}
                </ArtworkBaseModalButton>
            </div>
        </form>



    </ArtworkBaseModal>
</template>

<script setup>


import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ColorPickerComponent from "@/Components/Globale/ColorPickerComponent.vue";
import {useForm} from "@inertiajs/vue3";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";

const props = defineProps({
    status: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['close']);


const statusForm = useForm({
    name: props.status.name,
    color: props.status.color
})

const changeColor = (color) => {
    statusForm.color = color;
}


const submit = () => {
    statusForm.put(route('inventory.article-status.update', props.status.id), {
        onSuccess: () => {
            emit('close');
        },
        preserveScroll: false,
        preserveState: true
    });
}
</script>

<style scoped>

</style>
