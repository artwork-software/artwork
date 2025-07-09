<template>
    <ArtworkBaseModal
        title="Request Work Time Change"
        description="Submit a request to change work time for a user"
        @close="$emit('close')"
    >
        <form @submit.prevent="submit" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                <div>
                    <label class="block font-medium text-gray-700 font-lexend">Datum</label>
                    <div class="mt-1 text-gray-900">{{ shift.start_of_shift }}</div>
                </div>

                <div class="flex gap-6">
                    <div class="flex-1">
                        <label class="block font-medium text-gray-700 font-lexend">Beginn</label>
                        <div class="mt-1 text-gray-900">{{ shift.start }}</div>
                    </div>
                    <div class="flex-1">
                        <label class="block font-medium text-gray-700 font-lexend">Ende</label>
                        <div class="mt-1 text-gray-900">{{ shift.end }}</div>
                    </div>
                </div>

                <div>
                    <label class="block font-medium text-gray-700 font-lexend">Raum</label>
                    <div class="mt-1 text-gray-900">{{ shift.roomName }}</div>
                </div>

                <div>
                    <label class="block font-medium text-gray-700 font-lexend">Firma</label>
                    <div class="mt-1 text-gray-900">{{ shift.craft.name }} ({{ shift.craft.abbreviation }})</div>
                </div>
            </div>

            <div>
                <label class="block font-medium text-gray-700 mb-1 font-lexend">Zuständige Personen</label>
                <ul class="space-y-2">
                    <li v-for="person in shift.craft.craft_shift_planer" :key="person.id" class="flex items-center space-x-3 bg-gray-50 p-3 rounded-lg shadow border border-gray-200">
                        <UserPopoverTooltip :user="person" width="10" height="10" />
                        <div>
                            <div class="font-semibold text-gray-800 font-lexend">{{ person.full_name }}</div>
                            <div class="text-xs text-gray-500 font-lexend">{{ person.position }} - {{ person.business }}</div>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <BaseInput id="shift_start" v-model="requestForm.request_start_time" type="time" label="Neuer Beginn" />
                </div>

                <div>
                    <BaseInput id="shift_end" v-model="requestForm.request_end_time" type="time" label="Neues Ende" />
                </div>

            </div>

            <div>
                <BaseTextarea
                    v-model="requestForm.request_comment"
                    rows="3"
                    label="Kommentar zur Anfrage"
                    id="description"
                />
            </div>

            <div class="mt-4 flex justify-between gap-4">
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
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {useForm, usePage} from "@inertiajs/vue3";

const props = defineProps({
    shift: {
        type: Object,
        required: true
    },
    user: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(["close"]);

const requestForm = useForm({
    request_start_time: props.shift.start,
    request_end_time:  props.shift.end,
    shift_id: props.shift.id,
    craft_id: props.shift.craft.id,
    request_comment: '',
    user_id: props.user.element.id,
    requested_by: usePage().props.auth.user.id
});

const  submit = () => {
    requestForm.post(route('shifts.requestWorkTimeChange'), {
        onSuccess: () => {
            emit('close');
        },
        onError: (errors) => {
            console.error(errors);
        }
    });
};
</script>

<style scoped>
textarea {
    resize: vertical;
}
</style>
