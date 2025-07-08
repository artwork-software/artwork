<template>
    <ArtworkBaseModal title="Request Work Time Change" description="Submit a request to change work time for a user" @close="$emit('close')">
        <form @submit.prevent="submit">
            <div class="space-y-4">


                <pre>
                    {{ shift }}
                </pre>
            </div>

            <div class="mt-6 flex justify-between">
                <ArtworkBaseModalButton type="button" @click="$emit('close')" variant="danger">
                    {{ $t('Cancel') }}
                </ArtworkBaseModalButton>
                <ArtworkBaseModalButton type="submit">
                    {{ $t('Submit Request') }}
                </ArtworkBaseModalButton>
            </div>
        </form>

    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";

const props = defineProps({
    shift: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['close']);


</script>

<style scoped>

</style>