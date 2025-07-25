<template>
    <div class="flex items-center pl-1 py-1 hover:bg-gray-50/40 rounded cursor-pointer w-full h-6" @dragover="onDragOver" @drop="onDrop">
        <SelectUserForShiftMenu :can-edit-component="canEditComponent" :crafts-with-entities="craftsWithAllEntities" @create-on-drop-element-and-save="createOnDropElementAndSave">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <span class="h-4 w-4 rounded-full block bg-gray-500"></span>
                    <span class="text-xs">{{ $t('Unoccupied') }}</span>
                </div>
                <component stroke-width="1.5" class="size-4" :is="this.shiftQualification.icon"/>
            </div>
        </SelectUserForShiftMenu>
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
import {defineComponent, nextTick} from 'vue';
import ShiftQualificationIconCollection from "@/Layouts/Components/ShiftQualificationIconCollection.vue";
import ChooseUserSeriesShift from "@/Pages/Projects/Components/ChooseUserSeriesShift.vue";
import MultipleShiftQualificationSlotsAvailable
    from "@/Pages/Projects/Components/MultipleShiftQualificationSlotsAvailable.vue";
import {router} from "@inertiajs/vue3";
import SelectUserForShiftMenu from "@/Pages/Projects/Components/SelectUserForShiftMenu.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import {IconChevronDown, IconChevronUp, IconDotsVertical, IconFilter, IconSearch, IconX} from "@tabler/icons-vue";
import {Float} from "@headlessui-float/vue";
import {Menu, MenuButton, MenuItems} from "@headlessui/vue";
import DragElement from "@/Pages/Projects/Components/DragElement.vue";
import ColorHelper from "@/Mixins/ColorHelper.vue";
import CraftFilter from "@/Components/Filter/CraftFilter.vue";
import Input from "@/Jetstream/Input.vue";

export default defineComponent({
    name: 'ShiftsQualificationsDropElement',
    mixins: [ColorHelper],
    components: {
        IconFilter, Input, IconSearch, IconChevronUp, CraftFilter, IconX,
        DragElement, IconChevronDown,
        Float,
        IconDotsVertical, ToolTipComponent,
        SelectUserForShiftMenu,
        MultipleShiftQualificationSlotsAvailable,
        ShiftQualificationIconCollection,
        ChooseUserSeriesShift,
        MenuButton, MenuItems, Menu
    },
    props: {
        craftId: [String, Number],
        shiftId: [String, Number],
        shiftUserIds: Object,
        eventIsSeries: Boolean,
        shiftQualification: Object,
        allShiftQualificationDropElements: Array,
        shiftCraftsWithUsers: Array,
        crafts: Array,
        canEditComponent: {
            type: Boolean,
            default: false
        }
    },
    emits: ['dropFeedback'],
    data(){
        return {
            showChooseUserSeriesShiftModal: false,
            seriesShiftData: null,
            droppedUser: null,
            showMultipleShiftQualificationSlotsAvailableModal: false,
            showMultipleShiftQualificationSlotsAvailableModalSlots: null,
            showMultipleShiftQualificationSlotsAvailableModalDroppedUser: null,
            showSelectUserMenu: false,
            openFilter: false,
            showIntern: false,
            showExtern: false,
            showProvider: false,
            userSearch: '',
            isMenuOpen: false
        }
    },
    computed: {
        craftsWithAllEntities() {
            // return craft with all entities also users, freelancers, service providers in one array named entities
            return this.shiftCraftsWithUsers.map((craft) => {
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
        },
    },
    methods: {
        onDragOver(event) {
            event.preventDefault();
        },
        onDrop(event) {
            event.preventDefault();

            this.droppedUser = JSON.parse(event.dataTransfer.getData('application/json'));

            if (this.eventIsSeries) {
                this.showChooseUserSeriesShiftModal = true;
                return;
            }
            this.saveUser();
        },
        setSeriesShiftData(seriesShiftData) {
            this.showChooseUserSeriesShiftModal = false;
            this.seriesShiftData = seriesShiftData;
            this.saveUser();
        },
        saveUser() {
            if(!this.droppedUser.craft_universally_applicable) {
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
                let availableSlot = this.allShiftQualificationDropElements.find(
                    (dropElement) => dropElement.shift_qualification_id === this.droppedUser.shift_qualifications[0].id
                );

                if (typeof availableSlot === 'undefined') {
                    this.dropFeedbackNoSlotsForQualification(this.droppedUser.type);
                    return;
                }

                this.assignUser(this.droppedUser, availableSlot.shift_qualification_id);
            } else {
                let availableShiftQualificationSlots = [];

                this.droppedUser.shift_qualifications.forEach((userShiftQualification) => {
                    this.allShiftQualificationDropElements.forEach((shiftQualificationDropElement) => {
                        if (userShiftQualification.id === shiftQualificationDropElement.shift_qualification_id) {
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
                'dropFeedback',
                this.$t('{0} already assigned to a shift.', [userDescription])
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
                this.$t('{0} cannot be assigned to shifts of this craft.', [userDescription])
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
                this.$t('{0} has no qualifications and therefore cannot be assigned.', [userDescription])
            );
        },
        dropFeedbackNoSlotsForQualification(userType) {
            let userDescription = '';

            switch (userType) {
                case 0:
                    userDescription = this.$t('this employee');
                    break;
                case 1:
                    userDescription = this.$t('this freelancer');
                    break;
                case 2:
                    userDescription = this.$t('this service provider');
                    break;
            }

            this.$emit(
                'dropFeedback',
                this.$t(
                    'There is no position that can be filled by {0} with the available qualifications.',
                    [userDescription]
                )
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
                route('shift.assignUserByType', {shift: this.shiftId}),
                {
                    userId: droppedUser.id,
                    userType: droppedUser.type,
                    shiftQualificationId: shiftQualificationId,
                    seriesShiftData: this.seriesShiftData,
                    isShiftTab: true,
                    craft_abbreviation: droppedUser.craft_abbreviation
                },
                {
                    preserveScroll: true
                }
            )
        },
        createOnDropElementAndSave(user, craft) {

            this.droppedUser = {
                id: user.id,
                type: user.type,
                craft_ids: user.assigned_craft_ids,
                shift_qualifications: user.shift_qualifications ?? [],
                craft_universally_applicable: craft?.universally_applicable ?? false,
                craft_abbreviation: craft.abbreviation ?? '',
            };
            this.isMenuOpen = false;

            // click on page to close menu
            nextTick(() => {
                document.addEventListener('click', this.closeMenu);
            });

            this.saveUser();
        }
   }
});
</script>
