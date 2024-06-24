<template>
    <div class="w-full cursor-pointer">
        <div :class="[
                highlightMode && !isIdHighlighted(highlightedId, highlightedType) ?
                    'opacity-30' :
                    '',
                multiEditMode ?
                'text-[10px] my-1' :
                ''
             ]"
             class="flex items-center xsLight text-shiftText subpixel-antialiased"
             @dragover="onDragOver"
             @drop="onDrop"
        >
            <div v-if="multiEditMode && userForMultiEdit && checkIfUserIsInCraft && computedUsedWorkerCount !== computedMaxWorkerCount">
                <input v-model="shift.isCheckedForMultiEdit"
                       id="comments"
                       aria-describedby="comments-description"
                       name="comments"
                       type="checkbox"
                       class="h-5 w-5 border-gray-300 text-green-600 focus:ring-green-600 mr-1"
                />
            </div>
            <div class="flex items-center justify-between"
                 @click="this.showQualificationRowExpander = !this.showQualificationRowExpander">
                <div class="flex items-center">
                    <div>
                        {{ shift.craft.abbreviation }} {{ shift.start }} - {{ shift.end }}
                    </div>
                    <div v-if="!showRoom" class="ml-0.5 " :class="multiEditMode ? 'text-[10px]' : 'text-xs'">
                        ({{ this.computedUsedWorkerCount }}/{{ this.computedMaxWorkerCount}})
                    </div>
                    <div v-else-if="room" class="truncate">
                        , {{room?.name}}
                    </div>
                </div>
                <div v-if="computedUsedWorkerCount === computedMaxWorkerCount">
                    <IconCheck stroke-width="1.5" class="h-5 w-5 flex text-success" aria-hidden="true"/>
                </div>
            </div>
        </div>
        <div class="w-full" v-if="showQualificationRowExpander">
            <div class="w-full flex flex-row flex-wrap">
                <div v-for="(computedShiftsQualificationWithWorkerCount) in this.computedShiftsQualificationsWithWorkerCount"
                    class="flex xsLight items-center">
                    {{computedShiftsQualificationWithWorkerCount.workerCount}}/{{computedShiftsQualificationWithWorkerCount.maxWorkerCount}}
                    <ShiftQualificationIconCollection
                        class="text-black mx-1" :classes="['h-4', 'w-4', 'text-black', 'mx-0.5']"
                        :icon-name="this.getShiftQualificationById(computedShiftsQualificationWithWorkerCount.shift_qualification_id).icon"
                    />
                </div>
            </div>
        </div>
    </div>
    <ChooseUserSeriesShift
        v-if="this.showChooseUserSeriesShiftModal"
        @close-modal="this.showChooseUserSeriesShiftModal = false"
        @returnBuffer="this.setSeriesShiftData"
    />
    <MultipleShiftQualificationSlotsAvailable
        v-if="this.showMultipleShiftQualificationSlotsAvailableModal"
        :show="this.showMultipleShiftQualificationSlotsAvailableModal"
        :available-shift-qualification-slots="this.showMultipleShiftQualificationSlotsAvailableModalSlots"
        :dropped-user="this.showMultipleShiftQualificationSlotsAvailableModalDroppedUser"
        @close="this.closeMultipleShiftQualificationSlotsAvailableModal"
    />
</template>

<script>
import {defineComponent} from 'vue';
import {CheckIcon} from "@heroicons/vue/outline";
import VueMathjax from "vue-mathjax-next";
import ChooseUserSeriesShift from "@/Pages/Projects/Components/ChooseUserSeriesShift.vue";
import ShiftQualificationIconCollection from "@/Layouts/Components/ShiftQualificationIconCollection.vue";
import MultipleShiftQualificationSlotsAvailable from "@/Pages/Projects/Components/MultipleShiftQualificationSlotsAvailable.vue";
import {router} from "@inertiajs/vue3";
import IconLib from "@/Mixins/IconLib.vue";

export default defineComponent({
    components: {
        MultipleShiftQualificationSlotsAvailable,
        ShiftQualificationIconCollection,
        ChooseUserSeriesShift,
        CheckIcon,
        VueMathjax
    },
    props: [
        'shift',
        'showRoom',
        'craftId',
        'event',
        'room',
        'maxCount',
        'currentCount',
        'freeEmployeeCount',
        'freeMasterCount',
        'highlightMode',
        'highlightedId',
        'highlightedType',
        'multiEditMode',
        'userForMultiEdit',
        'shiftQualifications'
    ],
    emits: ['dropFeedback'],
    mixins: [IconLib],
    data() {
        return {
            showChooseUserSeriesShiftModal: false,
            buffer: {
                onlyThisDay: false,
                start: null,
                end: null,
                dayOfWeek: null
            },
            selectedUser: null,
            dropFeedback: null,
            showQualificationRowExpander: false,
            showMultipleShiftQualificationSlotsAvailableModal: false,
            showMultipleShiftQualificationSlotsAvailableModalSlots: null,
            showMultipleShiftQualificationSlotsAvailableModalDroppedUser: null
        }
    },
    computed: {
        computedMaxWorkerCount() {
            let maxWorkerCount = 0;

            this.shift.shifts_qualifications.forEach(
                (shiftsQualification) => maxWorkerCount += shiftsQualification.value
            );

            return maxWorkerCount;
        },
        computedUsedWorkerCount() {
            return this.shift.users.length + this.shift.freelancer.length + this.shift.service_provider.length;
        },
        computedShiftsQualificationsWithWorkerCount() {
            let shiftsQualificationsWithWorkerCount = [];

            this.shift.shifts_qualifications.forEach((shiftsQualification) => {
                if (shiftsQualification.value === null || shiftsQualification.value === 0) {
                    return;
                }

                let assignedUserCount = 0;

                this.shift.users.forEach((user) => {
                    if (user.pivot.shift_qualification_id === shiftsQualification.shift_qualification_id) {
                        assignedUserCount++;
                    }
                });

                this.shift.freelancer.forEach((freelancer) => {
                    if (freelancer.pivot.shift_qualification_id === shiftsQualification.shift_qualification_id) {
                        assignedUserCount++;
                    }
                });

                this.shift.service_provider.forEach((serviceProvider) => {
                    if (serviceProvider.pivot.shift_qualification_id === shiftsQualification.shift_qualification_id) {
                        assignedUserCount++;
                    }
                });

                shiftsQualificationsWithWorkerCount.push({
                    shift_qualification_id: shiftsQualification.shift_qualification_id,
                    maxWorkerCount: shiftsQualification.value,
                    workerCount: assignedUserCount
                });
            });

            return shiftsQualificationsWithWorkerCount;
        },
        shiftUserIds() {
            const ids = {
                userIds: [],
                freelancerIds: [],
                providerIds: []
            }
            this.shift.users.forEach(user => {
                ids.userIds.push(user.id)
            })

            this.shift.freelancer.forEach((freelancer) => {
                ids.freelancerIds.push(freelancer.id)
            })

            this.shift.service_provider.forEach((provider) => {
                ids.providerIds.push(provider.id)
            })

            return ids;
        },
        checkIfUserIsInCraft() {
            return this.userForMultiEdit.assigned_craft_ids.includes(this.shift.craft.id);
        },
    },
    watch: {
        multiEditMode: {
            handler() {
                if (!this.multiEditMode) {
                    this.shift.isCheckedForMultiEdit = false;
                }
            },
            deep: true
        },
        userForMultiEdit: {
            handler() {
                this.shift.isCheckedForMultiEdit = this.userForMultiEdit ?
                    this.userForMultiEdit.shift_ids.includes(this.shift.id) :
                    false;
            },
            deep: true
        },
    },
    methods: {
        onDragOver(event) {
            event.preventDefault();
        },
        onDrop(event) {
            event.preventDefault();

            this.droppedUser = JSON.parse(event.dataTransfer.getData('application/json'));

            if (this.event.is_series) {
                this.showChooseUserSeriesShiftModal = true;
                return;
            }

            this.saveUser();
        },
        getShiftQualificationById(id) {
            return this.shiftQualifications.find((shiftQualification) => shiftQualification.id === id);
        },
        setSeriesShiftData(seriesShiftData) {
            this.showChooseUserSeriesShiftModal = false;
            this.seriesShiftData = seriesShiftData;
            this.saveUser();
        },
        isIdHighlighted(highlightedId, highlightedType) {
            // Map the highlightedType to the correct property in shiftUserIds
            const typeMap = {
                0: 'userIds',
                1: 'freelancerIds',
                2: 'providerIds'
            };

            if (highlightedId) {
                // Get the correct array from shiftUserIds based on the highlightedType
                const arrayToCheck = this.shiftUserIds[typeMap[highlightedType]];

                // Check if the array contains the highlightedId
                return arrayToCheck.includes(highlightedId);
            } else {
                return false;
            }
        },
        saveUser() {
            if (this.droppedUserCannotBeAssignedToCraft(this.droppedUser)) {
                this.dropFeedbackUserCannotBeAssignedToCraft(this.droppedUser.type);
                return;
            }

            if (this.droppedUserAlreadyWorksOnShift(this.droppedUser)) {
                this.dropFeedbackUserAlreadyWorksOnShift(this.droppedUser.type);
                return;
            }

            if (this.droppedUserHasNoQualifications(this.droppedUser)) {
                this.dropFeedbackUserHasNoQualifications(this.droppedUser.type);
                return;
            }

            if (this.droppedUser.shift_qualifications.length === 1) {
                let availableSlot = this.computedShiftsQualificationsWithWorkerCount.find(
                    (shiftsQualification) =>
                        shiftsQualification.shift_qualification_id === this.droppedUser.shift_qualifications[0].id &&
                        shiftsQualification.workerCount < shiftsQualification.maxWorkerCount
                );

                if (
                    typeof availableSlot === 'undefined' ||
                    availableSlot.workerCount === availableSlot.maxWorkerCount
                ) {
                    this.dropFeedbackNoSlotsForQualification(this.droppedUser.type);
                    return;
                }

                this.assignUser(this.droppedUser, availableSlot.shift_qualification_id);
            } else {
                let availableShiftQualificationSlots = [];

                this.droppedUser.shift_qualifications.forEach((userShiftQualification) => {
                    this.computedShiftsQualificationsWithWorkerCount.forEach((shiftsQualification) => {
                        if (
                            userShiftQualification.id === shiftsQualification.shift_qualification_id &&
                            shiftsQualification.workerCount < shiftsQualification.maxWorkerCount
                        ) {
                            availableShiftQualificationSlots.push(userShiftQualification);
                        }
                    })
                });

                if (availableShiftQualificationSlots.length === 0) {
                    this.dropFeedbackNoSlotsForQualification(this.droppedUser.type);
                    return;
                }

                if (availableShiftQualificationSlots.length === 1) {
                    this.assignUser(
                        this.droppedUser,
                        availableShiftQualificationSlots[0].id
                    );
                    return;
                }

                //show select modal by availableSlots
                this.openMultipleShiftQualificationSlotsAvailableModal(
                    this.droppedUser,
                    availableShiftQualificationSlots
                );
            }
        },
        droppedUserAlreadyWorksOnShift(droppedUser) {
            if (droppedUser.type === 0) {
                if (this.shiftUserIds.userIds.includes(droppedUser.id)) {
                    return true;
                }
            }

            if (droppedUser.type === 1) {
                if (this.shiftUserIds.freelancerIds.includes(droppedUser.id)) {
                    return true;
                }
            }

            if (droppedUser.type === 2) {
                if (this.shiftUserIds.providerIds.includes(droppedUser.id)) {
                    return true;
                }
            }

            return false;
        },
        dropFeedbackUserAlreadyWorksOnShift(userType) {
            let userDescription = '';

            switch (userType) {
                case 0:
                    userDescription = this.$t('Employee');
                    break;
                case 1:
                    userDescription = this.$t('Freelancer');
                    break;
                case 2:
                    userDescription = this.$t('ServiceProvider');
                    break;
            }

            this.$emit(
                'dropFeedback', this.$t('{0} already assigned to a shift.', userDescription)
            );
        },
        droppedUserCannotBeAssignedToCraft(droppedUser) {
            return droppedUser.craft_ids && !droppedUser.craft_ids.includes(this.craftId);
        },
        dropFeedbackUserCannotBeAssignedToCraft(userType) {
            let userDescription = '';

            switch (userType) {
                case 0:
                    userDescription = this.$t('Employee');
                    break;
                case 1:
                    userDescription = this.$t('Freelancer');
                    break;
                case 2:
                    userDescription = this.$t('ServiceProvider');
                    break;
            }

            this.$emit(
                'dropFeedback',
                this.$t('{0} cannot be assigned to shifts of this craft.', userDescription)
            );
        },
        droppedUserHasNoQualifications(droppedUser) {
            return droppedUser.shift_qualifications.length === 0;
        },
        dropFeedbackUserHasNoQualifications(userType) {
            let userDescription = '';

            switch (userType) {
                case 0:
                    userDescription = this.$t('Employee');
                    break;
                case 1:
                    userDescription = this.$t('Freelancer');
                    break;
                case 2:
                    userDescription = this.$t('ServiceProvider');
                    break;
            }

            this.$emit(
                'dropFeedback',
                this.$t('{0} has no qualifications and therefore cannot be assigned.', userDescription)
            );
        },
        dropFeedbackNoSlotsForQualification(userType) {
            let userDescription = '';

            switch (userType) {
                case 0:
                    userDescription = this.$t('Employee');
                    break;
                case 1:
                    userDescription = this.$t('Freelancer');
                    break;
                case 2:
                    userDescription = this.$t('ServiceProvider');
                    break;
            }

            this.$emit(
                'dropFeedback',
                this.$t('There is no position that can be filled by {0} with the available qualifications.', userDescription)
            );
        },
        openMultipleShiftQualificationSlotsAvailableModal(droppedUser, availableShiftQualificationSlots) {
            this.showMultipleShiftQualificationSlotsAvailableModalDroppedUser = droppedUser;
            this.showMultipleShiftQualificationSlotsAvailableModalSlots = availableShiftQualificationSlots;
            this.showMultipleShiftQualificationSlotsAvailableModal = true;
        },
        closeMultipleShiftQualificationSlotsAvailableModal(droppedUser, selectedShiftQualificationId) {
            this.showMultipleShiftQualificationSlotsAvailableModal = false;
            this.showMultipleShiftQualificationSlotsAvailableModalSlots = null;
            this.showMultipleShiftQualificationSlotsAvailableModalDroppedUser = null;

            if (droppedUser && selectedShiftQualificationId) {
                this.assignUser(droppedUser, selectedShiftQualificationId);
            }
        },
        assignUser(droppedUser, shiftQualificationId) {
            router.post(
                route('shift.assignUserByType', {shift: this.shift.id}),
                {
                    userId: droppedUser.id,
                    userType: droppedUser.type,
                    shiftQualificationId: shiftQualificationId,
                    seriesShiftData: this.seriesShiftData
                },
                {
                    preserveScroll: true
                }
            )
        }
    }
})
</script>
