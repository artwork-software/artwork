<template>
    <div class=" flex items-stretch gap-x-3">
        <div
            class="p-1 rounded-lg"
            :style="{ backgroundColor: request.craft?.color }"
        />
        <div class="w-full space-y-5">
            <!-- Header mit Benutzerinfo -->
            <div class="">

                <div>
                    <div class="text-lg font-semibold text-text">
                        {{ request.user.first_name }} {{ request.user.last_name }}
                    </div>
                    <div class="text-sm text-text-subtle">
                        Eingereicht am {{ request.created_at }}
                    </div>
                </div>
            </div>

            <!-- Schichtinformationen -->
            <div class="grid md:grid-cols-2 gap-y-1 text-sm text-text-muted">
                <div>
                    <span class="font-medium">{{ $t('Date') }}:</span> {{ request.shift?.formatted_dates?.start }}
                </div>
                <div class="flex items-center justify-end gap-x-0.5">
                    <span class="font-medium">{{ $t('Time') }}:</span> {{ request.shift?.start }} - {{ request.shift?.end }}
                </div>
                <div>
                    <span class="font-medium">{{ $t('Craft') }}:</span> {{ request.craft?.name }}
                </div>
                <div class="flex items-center justify-end gap-x-0.5">
                    <span class="font-medium">Status: </span>
                    <span :class="{'text-warning': request.status === 'pending', 'text-success': request.status === 'approved', 'text-danger': request.status === 'rejected'}" class="font-semibold capitalize">
                        {{ $t(request.status) }}
                    </span>
                </div>
            </div>

            <!-- Kommentar -->
            <div v-if="request.request_comment || request.request_start_time" class="flex items-center justify-between text-sm">
                <div>
                    <div class="font-semibold text-accent-700">{{ $t('Comment') }}:</div>
                    <div v-if="request.request_comment" class="">{{ request.request_comment }}</div>
                </div>
                <div v-if="request.request_start_time && request.request_end_time">
                    <div class="font-semibold text-accent-700 mt-2">Angefragte Zeitänderung:</div>
                    <div class="">
                        {{ request.request_start_time }} – {{ request.request_end_time }}
                        <span v-if="request.request_end_date" class="text-xs text-text-subtle">(+{{ $t('1 day') }})</span>
                    </div>
                </div>
            </div>

            <!-- Zuständige Person(en) -->
            <div v-if="!needApproval">
                <div v-if="request.craft.craft_shift_planer?.length" class="text-sm text-text-muted">
                    <div>
                        <label class="block font-medium text-text-muted mb-1 font-lexend">Zuständige Personen</label>
                        <ul class="space-y-2">
                            <li v-for="person in request.craft.craft_shift_planer" :key="person.id" class="flex items-center space-x-3 bg-surface-sunken p-3 rounded-lg shadow border border-border-subtle">
                                <UserPopoverTooltip :user="person" width="10" height="10" />
                                <div>
                                    <div class="font-semibold text-text font-lexend">{{ person.full_name }}</div>
                                    <div class="text-xs text-text-subtle font-lexend">{{ person.position }} - {{ person.business }}</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div v-else>
                <div class="flex justify-end space-x-4">
                    <BaseUIButton v-if="request.status === 'pending'" variant="danger" @click="showDeclineWorkTimeRequestModal = true" icon="IconX" :label="$t('Decline')" is-delete-button />
                    <BaseUIButton icon="IconCheck" is-add-button :label="$t('Approve')" v-if="request.status === 'pending'" @click="approveRequest" />

                </div>
            </div>
        </div>
    </div>

    <DeclineWorkTimeRequest
        v-if="showDeclineWorkTimeRequestModal"
        @close="showDeclineWorkTimeRequestModal = false"
        :request-id="request.id"
    />
</template>

<script setup>

import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import {router} from "@inertiajs/vue3";
import DeclineWorkTimeRequest from "@/Pages/WorkTime/Components/DeclineWorkTimeRequest.vue";
import {ref} from "vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const props = defineProps({
    request: {
        type: Object,
        required: true
    },
    needApproval: {
        type: Boolean,
        default: false
    }
})

const showDeclineWorkTimeRequestModal = ref(false);

const approveRequest = () => {
    router.post(route('worktime.change-request.approve', {workTimeChangeRequest: props.request.id}), {
        preserveScroll: true,
        onSuccess: () => {
        },
        onError: (error) => {
            console.error('Error approving request:', error);
        }
    });
}



</script>

<style scoped>

</style>
