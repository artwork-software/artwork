<script setup>
import {useForm} from "@inertiajs/vue3";
import {ref, computed} from "vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

const props = defineProps({
    tabToEdit: {
        type: Object,
        default: null
    },
    tab: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['close', 'saved']);

const tabForm = useForm({
    name: props.tabToEdit ? props.tabToEdit.name : '',
});

const isEditing = computed(() => !!props.tabToEdit);

const closeModal = () => {
    emit('close');
};

const saveTab = () => {
    if (!tabForm.name || tabForm.name.trim() === '') {
        tabForm.setError('name', 'Der Name darf nicht leer sein');
        return;
    }

    if (isEditing.value) {
        tabForm.patch(
            route('tab.sidebar.update', {projectTabSidebarTab: props.tabToEdit.id}),
            {
                preserveScroll: true,
                onSuccess: () => {
                    emit('saved');
                    closeModal();
                },
                onError: (errors) => {
                    console.error('Fehler beim Update:', errors);
                }
            }
        );
    } else {
        tabForm.post(
            route('tab.sidebar.store', {projectTab: props.tab.id}),
            {
                preserveScroll: true,
                onSuccess: () => {
                    emit('saved');
                    closeModal();
                },
                onError: (errors) => {
                    console.error('Fehler beim Erstellen:', errors);
                }
            }
        );
    }
};
</script>

<template>

    <ArtworkBaseModal :title="tabToEdit ? $t('Rename sidebar tab') : $t('Create sidebar tab')" description="" @close="closeModal">
        <div>
            <BaseInput v-model="tabForm.name" name="name" id="sidebar-tab-name" label="Name" />
            <div v-if="tabForm.errors.name" class="mt-1 text-sm text-red-600">
                {{ tabForm.errors.name }}
            </div>
        </div>
        <div class="flex justify-between mt-5 items-center">
            <BaseUIButton
                @click="saveTab"
                is-add-button
                :disabled="tabForm.processing"
                :label="tabToEdit ? $t('Edit') : $t('Create')" />

            <BaseUIButton
                @click="closeModal(false)"
                is-cancel-button
                :disabled="tabForm.processing"
                :label="$t('No, not really')" />
        </div>
    </ArtworkBaseModal>
</template>

<style scoped>

</style>
