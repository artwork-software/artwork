<template>
    <div class="w-full group/shift bg-background-gray hover:bg-gray-50 duration-300 ease-in-out cursor-pointer">
        <div
            :class="[!highlightMode || !isIdHighlighted(highlightedId, highlightedType) ? 'opacity-30 px-1' : 'bg-pink-500 !text-white px-1', multiEditMode ?'text-[10px]' : '']"
            class="flex items-center xsLight text-shiftText subpixel-antialiased"
            @dragover="onDragOver"
            @drop="onDrop"
            @click="handleClickEvent"
        >
            <div v-if="multiEditMode && userForMultiEdit && checkIfUserIsInCraft">
                <input :checked="userForMultiEdit.shift_ids.includes(shift.id)"
                       @change="(e) => handleShiftAndEventForMultiEdit(e.target.checked, shift, event)"
                       type="checkbox"
                       :value="shift.id"
                       class="input-checklist mr-1"/>
            </div>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-x-1.5">

                    <!--@click="this.showQualificationRowExpander = !this.showQualificationRowExpander"-->
                    <div>
                        <span>{{ shift.craft.abbreviation }} {{ shift.start }} - {{ shift.end }}</span>

                    </div>
                    <div v-if="!showRoom" class="ml-0.5 " :class="multiEditMode ? 'text-[10px]' : 'text-xs'">
                        ({{ this.computedUsedWorkerCount }}/{{ this.computedMaxWorkerCount }})
                        <span class="inline-block w-2.5 h-2.5 rounded-full ml-1"
                              :class="{'bg-red-500': computedUsedWorkerCount === 0 && computedMaxWorkerCount !== 0,
                              'bg-yellow-500': computedUsedWorkerCount !== 0 && computedUsedWorkerCount < computedMaxWorkerCount,
                              'bg-green-500': computedUsedWorkerCount === computedMaxWorkerCount}">
                        </span>
                    </div>
                    <div v-else-if="room" class="truncate">
                        , {{ room?.name }}
                    </div>
                    <component :is="IconLock" class="text-right h-3 w-3" v-if="shift.isCommitted" />
                </div>
            </div>
        </div>
        <div class="w-full px-1" v-if="usePage().props.auth.user.calendar_settings.show_qualifications">
            <div class="w-full flex flex-row flex-wrap">
                <div
                    v-for="(computedShiftsQualificationWithWorkerCount) in this.computedShiftsQualificationsWithWorkerCount"
                    class="flex xsLight items-center">
                    {{ computedShiftsQualificationWithWorkerCount.workerCount }}/{{ computedShiftsQualificationWithWorkerCount.maxWorkerCount }}
                    <component stroke-width="1"
                        class="text-black size-3.5 mx-1"
                        :is="this.getShiftQualificationById(computedShiftsQualificationWithWorkerCount.shift_qualification_id).icon"
                    />
                </div>
            </div>
        </div>
        <div v-if="usePage().props.auth.user.calendar_settings.shift_notes" class="px-1 xsLight">
            {{ shift.description }}
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
import MultipleShiftQualificationSlotsAvailable
    from "@/Pages/Projects/Components/MultipleShiftQualificationSlotsAvailable.vue";
import IconLib from "@/Mixins/IconLib.vue";
import axios from "axios";
import Permissions from "@/Mixins/Permissions.vue";
import {usePage} from "@inertiajs/vue3";
import {IconLock} from "@tabler/icons-vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";


export default defineComponent({
    components: {
        BaseMenuItem,
        BaseMenu,
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
    emits: ['dropFeedback', 'desiresReload', 'handleShiftAndEventForMultiEdit', 'clickOnEdit'],
    mixins: [IconLib, Permissions],
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

            this.shift?.shifts_qualifications?.forEach(
                (shiftsQualification) => maxWorkerCount += shiftsQualification.value
            );

            return maxWorkerCount;
        },
        computedUsedWorkerCount() {
            return this.shift.users.length + this.shift.freelancer?.length + this.shift.serviceProviders?.length;
        },
        computedShiftsQualificationsWithWorkerCount() {
            let shiftsQualificationsWithWorkerCount = [];

            this.shift?.shifts_qualifications?.forEach((shiftsQualification) => {
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

                this.shift.serviceProviders.forEach((serviceProvider) => {
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

            this.shift.serviceProviders.forEach((provider) => {
                ids.providerIds.push(provider.id)
            })

            return ids;
        },
        checkIfUserIsInCraft() {
            return this.userForMultiEdit.assigned_craft_ids.includes(this.shift.craft.id) || this.userForMultiEdit.craft_are_universally_applicable;
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
        IconLock,
        handleClickEvent() {
            if (this.multiEditMode) {
                return;
            }

            if (this.$can('can plan shifts') || this.hasAdminRole()) {
                this.$emit('clickOnEdit', this.shift)
            }
        },
        usePage,
        onDragOver(event) {
            event.preventDefault();
        },
        onDrop(event) {
            event.preventDefault();

            this.droppedUser = JSON.parse(event.dataTransfer.getData('application/json'));

            if (this.event?.is_series) {
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
            const typeMap = {
                0: 'userIds',
                1: 'freelancerIds',
                2: 'providerIds'
            };

            return highlightedId ? this.shiftUserIds[typeMap[highlightedType]].includes(highlightedId) : false;
        },
        saveUser() {
            if (!this.droppedUser.craft_universally_applicable) {
                if (this.droppedUserCannotBeAssignedToCraft(this.droppedUser)) {
                    this.dropFeedbackUserCannotBeAssignedToCraft(this.droppedUser.type);
                    return;
                }
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
                this.assignUser(this.droppedUser, this.droppedUser.shift_qualifications[0].id);
            } else {
                let availableShiftQualificationSlots = [];

                this.droppedUser.shift_qualifications.forEach((userShiftQualification) => {
                    this.computedShiftsQualificationsWithWorkerCount.forEach((shiftsQualification) => {
                        if (userShiftQualification.id === shiftsQualification.shift_qualification_id) {
                            availableShiftQualificationSlots.push(userShiftQualification);
                        }
                    })
                });

                if (availableShiftQualificationSlots.length === 0) {
                    this.openMultipleShiftQualificationSlotsAvailableModal(
                        this.droppedUser,
                        this.droppedUser.shift_qualifications
                    );
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
            axios.post(
                route('shift.assignUserByType', {shift: this.shift.id}),
                {
                    userId: droppedUser.id,
                    userType: droppedUser.type,
                    shiftQualificationId: shiftQualificationId,
                    seriesShiftData: this.seriesShiftData,
                    craft_abbreviation: droppedUser.craft_abbreviation
                }
            ).then(() => {
                this.$emit(
                    'desiresReload',
                    droppedUser.id,
                    droppedUser.type,
                    this.seriesShiftData
                );
            });
        },
        handleShiftAndEventForMultiEdit(checked, shift, event) {
            this.$emit(
                'handleShiftAndEventForMultiEdit',
                checked,
                shift,
                event
            );
        }
    }
})
</script>
