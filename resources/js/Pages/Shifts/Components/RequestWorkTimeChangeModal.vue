<template>
    <ArtworkBaseModal
        title="Request Work Time Change"
        description="Submit a request to change work time for a user"
        @close="$emit('close')"
    >
        <form @submit.prevent="submit" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                <div>
                    <label class="block font-medium text-text-muted font-lexend">Datum</label>
                    <div class="mt-1 text-text">{{ shiftDate }}</div>
                </div>

                <div class="flex gap-6">
                    <div class="flex-1">
                        <label class="block font-medium text-text-muted font-lexend">Beginn</label>
                        <div class="mt-1 text-text">{{ normalizeTime(shift.start) }}</div>
                    </div>
                    <div class="flex-1">
                        <label class="block font-medium text-text-muted font-lexend">Ende</label>
                        <div class="mt-1 text-text">{{ normalizeTime(shift.end) }}</div>
                    </div>
                </div>

                <div>
                    <label class="block font-medium text-text-muted font-lexend">Raum</label>
                    <div class="mt-1 text-text">{{ shift.roomName ?? shift?.room?.name ?? '-' }}</div>
                </div>

                <div>
                    <label class="block font-medium text-text-muted font-lexend">Firma</label>
                    <div class="mt-1 text-text" v-if="craft?.id">{{ craft.name }} [{{ craft.abbreviation }}]</div>
                    <div class="mt-1 text-text" v-else>-</div>
                </div>
            </div>

            <div v-if="craft?.id">
                <label class="block font-medium text-text-muted mb-1 font-lexend">Zuständige Personen</label>
                <ul class="space-y-2">
                    <li v-for="person in craft.craft_shift_planer" :key="person.id" class="flex items-center space-x-3 bg-surface-sunken p-3 rounded-lg shadow border border-border-subtle">
                        <UserPopoverTooltip :user="person" width="10" height="10" />
                        <div>
                            <div class="font-semibold text-text font-lexend">{{ person.full_name }}</div>
                            <div class="text-xs text-text-subtle font-lexend">{{ person.position }} - {{ person.business }}</div>
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
                    required
                />
            </div>

            <div class="mt-4 flex justify-between gap-4">
                <BaseUIButton type="button" @click="$emit('close')" :label="$t('Cancel')" is-cancel-button />
                <BaseUIButton type="submit" is-add-button :label="$t('Submit Request')" />
            </div>
        </form>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed } from "vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import {useShiftPlanLookups} from "@/Composeables/useShiftPlanLookups.js";
import dayjs from "dayjs";

const { resolveCraft } = useShiftPlanLookups();

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

function normalizeTime(val) {
    if (!val || typeof val !== 'string') return val
    if (/^\d{2}:\d{2}$/.test(val)) return val
    const m = val.match(/T(\d{2}:\d{2})/)
    if (m) return m[1]
    const sp = val.match(/(\d{2}:\d{2})(:\d{2})?$/)
    if (sp) return sp[1]
    return val
}

const craft = computed(() => props.shift.craft ?? resolveCraft(props.shift.craftId) ?? {});
const shiftDate = computed(() => props.shift.start_of_shift ?? props.shift.formatted_dates?.start ?? (props.shift.startDate ? dayjs(props.shift.startDate).format('DD.MM.YYYY') : '-'));

const requestForm = useForm({
    request_start_time: normalizeTime(props.shift.start || props.shift.start_time) || '',
    request_end_time:  normalizeTime(props.shift.end || props.shift.end_time) || '',
    shift_id: props.shift.id,
    craft_id: props.shift.craft?.id ?? props.shift.craftId,
    request_comment: '',
    user_id: props.user?.id,
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
