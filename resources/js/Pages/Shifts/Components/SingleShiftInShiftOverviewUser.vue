<template>
    <div>
        <div class="grid grid-cols-1 md:grid-cols-12 gap-x-4">
            <div class="col-span-2 flex items-center gap-x-2">
                <PropertyIcon name="IconLock" v-if="shift.isCommitted" class="w-4 h-4"
                              v-tooltip.bottom="{ value: $t('Committed'), class: 'aw-tooltip' }" />
                <PropertyIcon name="IconGitPullRequest" v-else-if="shift.inWorkflow" class="w-4 h-4"
                              v-tooltip.bottom="{ value: $t('Requested'), class: 'aw-tooltip' }" />
                <div class="px-2 py-0.5 border rounded-lg text-xs w-fit" :style="{ backgroundColor: (craft.color ?? '#ccc') + '22', borderColor: blackColorIfColorIsWhite(craft.color ?? '#ccc') + '55', color: blackColorIfColorIsWhite(craft.color ?? '#ccc') }">
                    {{ shift.craftAbbreviation }}
                    <span v-if="shift.craftAbbreviation !== shift.craftAbbreviationUser" class="mx-1">
                        [{{ shift.craftAbbreviationUser }}]
                    </span>
                </div>
            </div>
            <div class="col-span-3 flex items-center">
                <Popover v-slot="{ open, close }" as="div" class="relative text-left artwork" v-if="isCurrentUserPlannerOfShiftCraft && !(shift.isCommitted ?? shift.is_committed)">
                    <Float auto-placement portal :offset="{ mainAxis: 5, crossAxis: 25}">
                        <PopoverButton class="font-lexend rounded-lg ring-0 focus:ring-0 focus:outline-none">
                            <p class="text-xs text-left font-lexend">{{ shift.startPivot }} - {{ shift.endPivot }}</p>
                        </PopoverButton>
                        <transition enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95">
                            <PopoverPanel static class="z-50 w-96 focus:outline-none  rounded-lg bg-surface border border-border-subtle shadow-raised">
                                <div class="px-4 py-2">
                                    <div>
                                        <p class="text-xs text-text-muted mb-2 font-lexend font-bold">
                                            Schichtzeiten für
                                            {{ user.element.provider_name || user.element.first_name }}
                                            <span v-if="user.element.last_name"> {{ user.element.last_name }}</span>
                                            anpassen
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-x-2">
                                        <BaseInput
                                            id="start" type="time" class="max-w-28 text-xs"
                                            v-model="shift.startPivot"
                                        />

                                        <BaseInput
                                            id="start" type="time" class="max-w-28 text-xs"
                                            v-model="shift.endPivot"
                                        />
                                        <BaseUIButton label="Save" use-translation icon="IconDeviceFloppy" icon-size="size-4" @click.stop="saveIndividualShiftTime(close)"/>
                                    </div>
                                </div>
                            </PopoverPanel>
                        </transition>
                    </Float>
                </Popover>
                <div v-else class="font-lexend rounded-lg" @click="showRequestWorkTimeChangeModal = true">
                    <div class="rounded-l-lg">
                        <p class="text-xs text-left font-lexend">{{ shift.startPivot }} - {{ shift.endPivot  }}</p>
                    </div>
                </div>
            </div>
            <div class="col-span-3 text-xs">
                <div>
                    {{ $t('Room') }}:
                </div>
                {{ shift.roomName }}
            </div>
            <div class="col-span-3 text-xs flex items-center">
                {{ $t('Craft function') }}: {{ shift.qualificationName }}
            </div>
            <div class="col-span-2 text-xs flex items-center" v-if="shift.eventTypeAbbreviation">
                {{ shift.eventTypeAbbreviation }}:
                {{ shift.eventName }}
            </div>
        </div>

        <!-- Zu-/Absage der Zuweisung: Status-Pille wie in der Zelle; Antwort in der eigenen
             Zelle oder stellvertretend (Planer:in/Admin) — nur für Personen mit dem Recht -->
        <div v-if="confirmationInfo" class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1">
            <span
                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold text-white"
                :class="confirmationInfo.requested ? 'bg-accent-600' : (confirmationInfo.accepted ? 'bg-success' : 'bg-danger')"
                :title="getConfirmationTooltip(confirmationWorker, $t)"
            >
                <PropertyIcon
                    :name="confirmationInfo.requested ? 'IconClockQuestion' : (confirmationInfo.accepted ? 'IconCheck' : 'IconX')"
                    class="h-3 w-3"
                    stroke-width="2.5"
                />
                {{ confirmationInfo.requested
                    ? $t('Reply requested')
                    : (confirmationInfo.accepted
                        ? $t('Accepted on {date}', { date: confirmationInfo.date ?? '–' })
                        : $t('Declined on {date}', { date: confirmationInfo.date ?? '–' })) }}
            </span>
            <span v-if="confirmationInfo.comment" class="text-[11px] text-text-subtle">
                „{{ confirmationInfo.comment }}“
            </span>
            <div v-if="canRespondToConfirmation" class="flex items-center gap-1.5">
                <button
                    v-if="!confirmationInfo.accepted"
                    type="button"
                    class="inline-flex items-center justify-center h-6 w-6 rounded-lg border border-success-border bg-success-surface text-success hover:opacity-80 transition"
                    :aria-label="$t(isOwnCell ? 'Accept shift' : 'Record acceptance')"
                    v-tooltip.bottom="{ value: $t(isOwnCell ? 'Accept shift' : 'Record acceptance'), class: 'aw-tooltip' }"
                    @click="responseModalMode = 'accept'"
                >
                    <PropertyIcon name="IconCheck" class="h-3.5 w-3.5" stroke-width="2.5" />
                </button>
                <button
                    v-if="!confirmationInfo.declined"
                    type="button"
                    class="inline-flex items-center justify-center h-6 w-6 rounded-lg border border-danger-border bg-danger-surface text-danger hover:opacity-80 transition"
                    :aria-label="$t(isOwnCell ? 'Decline shift' : 'Record declination')"
                    v-tooltip.bottom="{ value: $t(isOwnCell ? 'Decline shift' : 'Record declination'), class: 'aw-tooltip' }"
                    @click="responseModalMode = 'decline'"
                >
                    <PropertyIcon name="IconX" class="h-3.5 w-3.5" stroke-width="2.5" />
                </button>
            </div>
        </div>
    </div>
    <!-- Aktionen immer sichtbar, aber dezent — unsichtbare Hover-Buttons sind nicht entdeckbar -->
    <div class="opacity-60 hover:opacity-100 focus-within:opacity-100 transition-opacity cursor-pointer flex items-center gap-x-2">
        <button
            type="button"
            @click="showRequestWorkTimeChangeModal = true"
            v-if="user.element.id === usePage().props.auth.user.id && user.type === 0"
            :aria-label="$t('Request work time change')"
            v-tooltip.bottom="{ value: $t('Request work time change'), class: 'aw-tooltip' }"
        >
            <PropertyIcon name="IconClockEdit" class="h-5 w-5 hover:text-accent-600 transition-colors duration-300 ease-in-out cursor-pointer" stroke-width="1.5"/>
        </button>
        <button
            type="button"
            @click="showConfirmDeleteModal = true"
            :aria-label="$t('Delete user from shift')"
            v-tooltip.bottom="{ value: $t('Delete user from shift'), class: 'aw-tooltip' }"
        >
            <PropertyIcon name="IconTrash" class="h-5 w-5 hover:text-danger transition-colors duration-300 ease-in-out cursor-pointer" stroke-width="1.5"/>
        </button>
    </div>

    <ConfirmDeleteModal
        :title="$t('Delete user from shift')"
        :description="$t('Are you sure you want to delete the user from this shift?')"
        :loading="isDeletingUser"
        @closed="closeConfirmDeleteModal"
        @delete="submitDeleteUserFromShift(shift.id, shift.pivotId)"
        v-if="showConfirmDeleteModal"
    />

    <RequestWorkTimeChangeModal
        :user="user.element"
        :shift="shift"
        v-if="showRequestWorkTimeChangeModal"
        @close="showRequestWorkTimeChangeModal = false"
    />

    <!-- Zu-/Absage mit optionalem Kommentar (stellvertretend mit Namen der Person) -->
    <ShiftConfirmationResponseModal
        v-if="responseModalMode"
        :mode="responseModalMode"
        :worker-name="proxyWorkerName"
        @close="responseModalMode = null"
        @submit="submitConfirmationResponse"
    />
</template>

<script setup>

import {router, usePage} from "@inertiajs/vue3";
import {computed, defineAsyncComponent, ref} from "vue";
import {Popover, PopoverButton, PopoverPanel} from "@headlessui/vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {Float} from "@headlessui-float/vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import {useShiftPlanLookups} from "@/Composeables/useShiftPlanLookups.js";
import {usePermission} from "@/Composeables/Permission.js";
import {useShiftWorkerConfirmation} from "@/Composeables/useShiftWorkerConfirmation.js";

const { resolveCraft } = useShiftPlanLookups();

const props = defineProps({
    user: {
        type: Object,
        required: true
    },
    shift: {
        type: Object,
        required: true
    },
})

const emit = defineEmits(['shiftDeleted', 'confirmationChanged'])
const page = usePage()
const { can, hasAdminRole } = usePermission(page.props)
const { getConfirmationInfo, getConfirmationTooltip, respond: respondToShift } = useShiftWorkerConfirmation()

const isOwnCell = computed(() => props.user.type === 0 && props.user.element.id === page.props.auth.user.id)

// Der Zellen-Payload (WorkerShiftPlanResource) trägt die Pivot-Felder camelCase am Shift und das
// Recht-Flag an der Person — für das gemeinsame Composable in die Worker/Pivot-Form bringen
const confirmationWorker = computed(() => ({
    id: props.user.element.id,
    type: 'user',
    first_name: props.user.element.first_name,
    last_name: props.user.element.last_name,
    confirmation_eligible: !!props.user.element.confirmation_eligible,
    pivot: {
        id: props.shift.pivotId,
        confirmation_status: props.shift.confirmationStatus ?? null,
        confirmation_at: props.shift.confirmationAt ?? null,
        confirmation_comment: props.shift.confirmationComment ?? null,
        confirmation_by_user_id: props.shift.confirmationByUserId ?? null,
    },
}))

// null = Feature aus, Person ohne Recht oder Externe (nie im Flow) → kein Block
const confirmationInfo = computed(() =>
    props.user.type === 0 && props.shift.pivotId
        ? getConfirmationInfo(confirmationWorker.value, props.shift)
        : null
)

// Eigene Zelle oder stellvertretend (Backend: Proxy nur mit „Schichten planen", Admins via Gate::before)
const canRespondToConfirmation = computed(() =>
    !!confirmationInfo.value && (isOwnCell.value || can('can plan shifts') || hasAdminRole())
)

const responseModalMode = ref(null)
const proxyWorkerName = computed(() => isOwnCell.value
    ? null
    : [props.user.element.first_name, props.user.element.last_name].filter(Boolean).join(' ') || null
)

const submitConfirmationResponse = (comment) => {
    const status = responseModalMode.value === 'accept' ? 'accepted' : 'declined'
    responseModalMode.value = null
    // preserveState: der Dienstplan darf nicht remounten (Modal bleibt offen);
    // die Personenzeile zieht der Parent über desiresReload nach
    respondToShift(props.shift.pivotId, status, comment, {
        preserveState: true,
        onSuccess: () => emit('confirmationChanged'),
    })
}

// Lookup-Craft bevorzugen: shift.craft aus WorkerShiftPlanResource ist schlank (ohne craft_shift_planer),
// nur der craftsById-Lookup enthält die Planer für isCurrentUserPlannerOfShiftCraft
const craft = computed(() => {
    const own = props.shift.craft;
    const resolved = resolveCraft(props.shift.craftId ?? own?.id);
    if (resolved && own) return { ...own, ...resolved };
    return resolved ?? own ?? {};
});
const showConfirmDeleteModal = ref(false);
const showRequestWorkTimeChangeModal = ref(false);
const isDeletingUser = ref(false);

const closeConfirmDeleteModal = () => {
    showConfirmDeleteModal.value = false;
}

const ConfirmDeleteModal = defineAsyncComponent({
    loader: () => import('@/Layouts/Components/ConfirmDeleteModal.vue'),
    delay: 200,
    timeout: 5000,
});

const RequestWorkTimeChangeModal = defineAsyncComponent({
    loader: () => import('@/Pages/Shifts/Components/RequestWorkTimeChangeModal.vue'),
    delay: 200,
    timeout: 5000,
});

const ShiftConfirmationResponseModal = defineAsyncComponent({
    loader: () => import('@/Layouts/Components/ShiftPlanComponents/ShiftConfirmationResponseModal.vue'),
    delay: 200,
    timeout: 5000,
});

const submitDeleteUserFromShift = (shiftId, pivotId) => {
    isDeletingUser.value = true;
    router.delete(route('shift.removeUserByType', {usersPivotId: pivotId, userType: props.user.type}), {
        data: {
            removeFromSingleShift: true
        },
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            closeConfirmDeleteModal();
            // Emit event to parent to remove shift from user.element.shifts array
            emit('shiftDeleted', shiftId);
        },
        onFinish: () => {
            isDeletingUser.value = false;
            document.getElementById('shift-' + shiftId)?.remove();
        }
    });
}

const blackColorIfColorIsWhite = (color) => {
    return color === '#ffffff' ? '#000000' : color;
}

const isCurrentUserPlannerOfShiftCraft = computed(() => {
    const currentUserId = page.props.auth?.user?.id
    if (!currentUserId) return false

    const planners = craft.value?.craft_shift_planer

    if (!Array.isArray(planners)) return false

    return planners.some((planner) => planner?.id === currentUserId)
})

const typMapping = {
    0: 'user',
    1: 'freelancer',
    2: 'service_provider'
};


const userToSend = ref({
    id: props.user.id,
    type: typMapping[props.user.type],
});


const saveIndividualShiftTime = (closePopover) => {
    // Logic to save the individual shift time for the person, freelancer, or service provider
    // This could involve making an API call to update the shift time in the database
    router.post(route('shifts.updateIndividualShiftTime', {
        entity: userToSend.value,
        shiftPivotId: props.shift.pivotId
    }), {
        start_time: props.shift.startPivot,
        end_time: props.shift.endPivot
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Optionally, you can show a success message or perform any other action after saving
            console.log('Shift time saved successfully');
            if (typeof closePopover === 'function') closePopover();
        },
        onError: (error) => {
            // Handle error if needed
            console.error('Error saving shift time:', error);
        }
    });
}

const saveShortDescription = (closePopover) => {
    // Logic to save the short description for the person, freelancer, or service provider
    // This could involve making an API call to update the short description in the database
    router.post(route('shifts.updateShortDescription', {
        entity: props.person,
        shiftPivotId: props.person.pivot.id
    }), {
        short_description: props.person.pivot.short_description
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Optionally, you can show a success message or perform any other action after saving
            console.log('Short description saved successfully');
            // Close the popover after saving
            if (typeof closePopover === 'function') closePopover();
        },
        onError: (error) => {
            // Handle error if needed
            console.error('Error saving short description:', error);
        }
    });
}

</script>

<style scoped>

</style>
