<template>
    <SelectUserForShiftMenu :can-edit-component="canEditComponent" :crafts-with-entities="sortedCraftsWithEntities" @create-on-drop-element-and-save="createOnDropElementAndSave">
        <div class="flex items-center p-1 hover:bg-gray-50/40 rounded cursor-pointer w-full h-full"  @dragover="onDragOver" @drop="onDrop">
            <div class="flex gap-1 items-center justify-between w-full h-full">
                <div class="h-full">
                    <div class="flex items-center gap-1" v-if="type === 0 || type === 1">
                        <UserPopoverTooltip :user="user" height="4" width="4" class="flex items-center" />
                        <span class="text-xs flex items-center gap-1">
                            {{ user.first_name }} {{ user.last_name }}
                        <span v-if="user.pivot.craft_abbreviation !== shift.craft.abbreviation && user.pivot.craft_abbreviation !== null" class="text-[8px]">[{{ user.pivot.craft_abbreviation}}]</span>
                        <span v-if="user.pivot.shift_count > 1" class="text-xs"> 1/{{ user.pivot.shift_count }}</span>
                    </span>
                    </div>
                    <div v-else class="flex items-center gap-1">
                        <img :src="user.profile_photo_url" class="h-4 w-4 rounded-full block bg-gray-500 object-cover" alt="profile-photo">
                        <span class="text-xs flex items-center gap-1">
                             <span class="w-24 truncate">{{ user.provider_name }}</span>
                        <span v-if="user.pivot.shift_count > 1" class="text-xs"> 1/{{ user.pivot.shift_count }}</span>
                        <span v-if="user.pivot.craft_abbreviation !== shift.craft.abbreviation && user.pivot.craft_abbreviation !== null" class="text-[8px]">[{{ user.pivot.craft_abbreviation}}]</span>
                    </span>
                    </div>
                </div>
                <PropertyIcon stroke-width="1.5"
                    class=" block group-hover:hidden size-4"
                    :name="getShiftQualificationById(user.pivot.shift_qualification_id).icon"/>
            </div>
        </div>
        <template #xButton>
            <div v-if="can('can plan shifts') || hasAdminRole()" class="hidden group-hover:block ml-1">
                <span class="flex items-center justify-center">
                    <span class="rounded-full bg-red-400 p-0.5 h-4 w-4 flex items-center justify-center border border-white shadow-[0px_0px_5px_0px_#fc8181]">
                        <IconX class="w-2 h-2 text-white cursor-pointer" @click="event.is_series ? openDeleteUserModal(user.pivot.id, type) : deleteUserFromShift(user.pivot.id, type)"/>
                    </span>
                </span>
            </div>
        </template>

    </SelectUserForShiftMenu>

    <div class="absolute w-full h-full bg-gray-300/10 top-0 left-0 rounded-lg z-50" v-show="isUpdateContainer">
        <div class="flex items-center justify-center h-full w-full text-xs">
            <div class="bg-black text-white px-4 py-1 rounded-lg">
                {{ $t('Updating...')}}
            </div>
        </div>
    </div>

    <ChooseDeleteUserShiftModal
        v-if="showDeleteUserModal"
        @close-modal="closeDeleteUserModal"
        @returnBuffer="deleteUserWithSeriesShiftData"
    />

    <ChooseUserSeriesShift
        v-if="showChooseUserSeriesShiftModal"
        @close-modal="showChooseUserSeriesShiftModal = false"
        @returnBuffer="setSeriesShiftData"
    />
    <MultipleShiftQualificationSlotsAvailable
        v-if="showMultipleShiftQualificationSlotsAvailableModal"
        :show="showMultipleShiftQualificationSlotsAvailableModal"
        :available-shift-qualification-slots="showMultipleShiftQualificationSlotsAvailableModalSlots"
        :dropped-user="showMultipleShiftQualificationSlotsAvailableModalDroppedUser"
        @close="closeMultipleShiftQualificationSlotsAvailableModal"
    />
</template>

<script setup>

import {IconX} from "@tabler/icons-vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import ShiftQualificationIconCollection from "@/Layouts/Components/ShiftQualificationIconCollection.vue";
import {usePermission} from "@/Composeables/Permission.js";
import {router, usePage} from "@inertiajs/vue3";
import ChooseDeleteUserShiftModal from "@/Pages/Projects/Components/ChooseDeleteUserShiftModal.vue";
import {computed, nextTick, ref} from "vue";
import ChooseUserSeriesShift from "@/Pages/Projects/Components/ChooseUserSeriesShift.vue";
import MultipleShiftQualificationSlotsAvailable
    from "@/Pages/Projects/Components/MultipleShiftQualificationSlotsAvailable.vue";
import SelectUserForShiftMenu from "@/Pages/Projects/Components/SelectUserForShiftMenu.vue";
const { can, canAny, hasAdminRole } = usePermission(usePage().props)

import {useTranslation} from "@/Composeables/Translation.js";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
const $t = useTranslation()

const props = defineProps({
    user: {
        type: Object,
        required: true
    },
    type: {
        type: Number,
        required: true
    },
    event: {
        type: Object,
        required: true
    },
    shift: {
        type: Object,
        required: true
    },
    shiftQualifications: {
        type: Object,
        required: true
    },
    craftWithEntities: {
        type: Object,
        required: true
    },
    craftId: {
        type: Number,
        required: true
    },
    shiftId: {
        type: Number,
        required: true
    },
    shiftUserIds: {
        type: Object,
        required: true
    },
    allShiftQualificationDropElements: {
        type: Object,
        required: true
    },
    eventIsSeries: {
        type: Boolean,
        required: true
    },
    crafts: {
        type: Object,
        required: true
    },
    canEditComponent: {
        type: Boolean,
        default: false
    },
})


const showDeleteUserModal = ref(false);
const usersPivotIdToDelete = ref(null);
const userTypeToDelete = ref(null);
const droppedUser = ref({});
const isUpdateContainer = ref(false);

const showChooseUserSeriesShiftModal = ref(false);
const seriesShiftData = ref(null);
const showMultipleShiftQualificationSlotsAvailableModal = ref(false);
const showMultipleShiftQualificationSlotsAvailableModalSlots = ref(null);
const showMultipleShiftQualificationSlotsAvailableModalDroppedUser = ref(null);

const emits = defineEmits(['dropFeedback']);
const assigningInProgress = ref(false);

const sortedCraftsWithEntities = computed(() => {
    return props.craftWithEntities.map((craft) => {
        let users = craft.users.map((user) => {
            return {
                ...user,
                type: 0
            }
        });

        let freelancers = craft.freelancers.map((freelancer) => {
            return {
                ...freelancer,
                type: 1
            }
        });

        let serviceProviders = craft.service_providers.map((serviceProvider) => {
            return {
                ...serviceProvider,
                type: 2
            }
        });

        return {
            ...craft,
            users: users.concat(freelancers).concat(serviceProviders)
        }
    });
})

const onDragOver = (event) => {
    event.preventDefault();
}

const onDrop = (event) =>  {
    event.preventDefault();

    droppedUser.value = JSON.parse(event.dataTransfer.getData('application/json'));

    saveUser();
}

const getShiftQualificationById = (id) => {
    return props.shiftQualifications.find((shiftQualification) => shiftQualification.id === id);
}

const closeDeleteUserModal = () => {
    showDeleteUserModal.value = false;
}

const deleteUserWithSeriesShiftData = (removeFromSingleShift) => {
   closeDeleteUserModal();
    deleteUserFromShift(usersPivotIdToDelete.value, userTypeToDelete.value, removeFromSingleShift);

    usersPivotIdToDelete.value = null;
    userTypeToDelete.value = null;
}

const openDeleteUserModal = (usersPivotId, userType) => {
    showDeleteUserModal.value = true;
    usersPivotIdToDelete.value = usersPivotId;
    userTypeToDelete.value = userType;
}

const deleteUserFromShift = (usersPivotId, userType, removeFromSingleShift = true, preserveState = false) => {
    router.delete(
        route(
            'shift.removeUserByType',
            {
                usersPivotId: usersPivotId,
                userType: userType,
                isShiftTab: true
            }
        ),
        {
            data: {
                removeFromSingleShift: removeFromSingleShift
            },
            preserveScroll: true,
            preserveState: preserveState,
            onSuccess: () => {
                isUpdateContainer.value = false;
            }
        }
    );
}


const setSeriesShiftData = (seriesShiftData) => {
    showChooseUserSeriesShiftModal.value = false;
    seriesShiftData.value = seriesShiftData;
    saveUser();
}
const saveUser = () => {

    if(!droppedUser.value.craft_universally_applicable) {
        if (droppedUserCannotBeAssignedToCraft(droppedUser.value)) {
            dropFeedbackUserCannotBeAssignedToCraft(droppedUser.value.type);
            isUpdateContainer.value = false;
            assigningInProgress.value = false;
            return;
        }
    }

    if (droppedUserAlreadyWorksOnShift(droppedUser.value)) {
        dropFeedbackUserAlreadyWorksOnShift(droppedUser.value.type);
        isUpdateContainer.value = false;
        assigningInProgress.value = false;
        return;
    }

    if (droppedUserHasNoQualifications(droppedUser.value)) {
        dropFeedbackUserHasNoQualifications(droppedUser.value.type);
        isUpdateContainer.value = false;
        assigningInProgress.value = false;
        return;
    }

    if (droppedUser.value.shift_qualifications.length === 1) {
        let availableSlot = props.allShiftQualificationDropElements.find(
            (dropElement) => dropElement.shift_qualification_id === droppedUser.value.shift_qualifications[0].id
        );

        if (typeof availableSlot === 'undefined') {
            dropFeedbackNoSlotsForQualification(droppedUser.value.type);
            isUpdateContainer.value = false;
            assigningInProgress.value = false;
            return;
        }

        assignUser(droppedUser.value, availableSlot.shift_qualification_id);
    } else {
        let availableShiftQualificationSlots = [];

        droppedUser.value.shift_qualifications.forEach((userShiftQualification) => {
            props.allShiftQualificationDropElements.forEach((shiftQualificationDropElement) => {
                if (userShiftQualification.id === shiftQualificationDropElement.shift_qualification_id) {
                    availableShiftQualificationSlots.push(userShiftQualification);
                }
            })
        });

        if (availableShiftQualificationSlots.length === 0) {
            dropFeedbackNoSlotsForQualification(droppedUser.value.type);
            isUpdateContainer.value = false;
            assigningInProgress.value = false;
            return;
        }

        if (availableShiftQualificationSlots.length === 1) {
            assignUser(
                droppedUser.value,
                availableShiftQualificationSlots[0].id
            );
            return;
        }

        //show select modal by availableSlots
        openMultipleShiftQualificationSlotsAvailableModal(
            droppedUser.value,
            availableShiftQualificationSlots
        );
    }
}
const droppedUserAlreadyWorksOnShift = (droppedUser) => {
    if (droppedUser.type === 0) {
        if (props.shiftUserIds.userIds.includes(droppedUser.id)) {
            return true;
        }
    }

    if (droppedUser.type === 1) {
        if (props.shiftUserIds.freelancerIds.includes(droppedUser.id)) {
            return true;
        }
    }

    if (droppedUser.type === 2) {
        if (props.shiftUserIds.providerIds.includes(droppedUser.id)) {
            return true;
        }
    }

    return false;
}
const dropFeedbackUserAlreadyWorksOnShift = (userType) => {
    let userDescription = '';

    switch (userType) {
        case 0:
            userDescription = $t('Employee');
            break;
        case 1:
            userDescription = $t('Freelancer');
            break;
        case 2:
            userDescription = $t('ServiceProvider');
            break;
    }

    emits(
        'dropFeedback',
        $t('{0} already assigned to a shift.', [userDescription])
    );
}
const droppedUserCannotBeAssignedToCraft = (droppedUser) => {
    return droppedUser.craft_ids && !droppedUser.craft_ids.includes(props.craftId);
}
const dropFeedbackUserCannotBeAssignedToCraft = (userType) => {
    let userDescription = '';

    switch (userType) {
        case 0:
            userDescription = $t('Employee');
            break;
        case 1:
            userDescription = $t('Freelancer');
            break;
        case 2:
            userDescription = $t('ServiceProvider');
            break;
    }

    emits(
        'dropFeedback',
        $t('{0} cannot be assigned to shifts of this craft.', [userDescription])
    );
}
const droppedUserHasNoQualifications = (droppedUser) => {
    return droppedUser.shift_qualifications.length === 0;
}
const dropFeedbackUserHasNoQualifications = (userType) => {
    let userDescription = '';

    switch (userType) {
        case 0:
            userDescription = $t('Employee');
            break;
        case 1:
            userDescription = $t('Freelancer');
            break;
        case 2:
            userDescription = $t('ServiceProvider');
            break;
    }

    emits(
        'dropFeedback',
        $t('{0} has no qualifications and therefore cannot be assigned.', [userDescription])
    );
}
const dropFeedbackNoSlotsForQualification = (userType) => {
    let userDescription = '';

    switch (userType) {
        case 0:
            userDescription = $t('this employee');
            break;
        case 1:
            userDescription = $t('this freelancer');
            break;
        case 2:
            userDescription = $t('this service provider');
            break;
    }

    emits(
        'dropFeedback',
        $t(
            'There is no position that can be filled by {0} with the available qualifications.',
            [userDescription]
        )
    );
}
const openMultipleShiftQualificationSlotsAvailableModal = (droppedUser, availableShiftQualificationSlots) => {
    showMultipleShiftQualificationSlotsAvailableModalDroppedUser.value = droppedUser;
    showMultipleShiftQualificationSlotsAvailableModalSlots.value = availableShiftQualificationSlots;
    showMultipleShiftQualificationSlotsAvailableModal.value = true;
}
const closeMultipleShiftQualificationSlotsAvailableModal = (droppedUser, selectedShiftQualificationId, closeOnButton) => {
    showMultipleShiftQualificationSlotsAvailableModal.value = false;
    showMultipleShiftQualificationSlotsAvailableModalSlots.value = null;
    showMultipleShiftQualificationSlotsAvailableModalDroppedUser.value = null;

    if (droppedUser && selectedShiftQualificationId) {
        assignUser(droppedUser, selectedShiftQualificationId);
    }

    if(closeOnButton) {
        isUpdateContainer.value = false;
        assigningInProgress.value = false;
    }
}
const assignUser = (droppedUser, shiftQualificationId) => {
    router.post(
        route('shift.assignUserByType', {shift: props.shiftId}),
        {
            userId: droppedUser.id,
            userType: droppedUser.type,
            shiftQualificationId: shiftQualificationId,
            seriesShiftData: seriesShiftData.value,
            isShiftTab: true,
            craft_abbreviation: droppedUser.craft_abbreviation
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                assigningInProgress.value = false;
                deleteUserFromShift(props.user.pivot.id, props.type, true, false);
            }
        },
    )
}
const createOnDropElementAndSave = (user, craft) => {

    if (assigningInProgress.value) {
        return;
    }

    assigningInProgress.value = true;

    isUpdateContainer.value = true;
    droppedUser.value = {
        id: user.id,
        type: user.type,
        craft_ids: user.assigned_craft_ids,
        shift_qualifications: user.shift_qualifications ?? [],
        craft_universally_applicable: craft?.universally_applicable ?? false,
        craft_abbreviation: craft.abbreviation ?? '',
    };

    saveUser();
}

</script>

<style scoped>

</style>
