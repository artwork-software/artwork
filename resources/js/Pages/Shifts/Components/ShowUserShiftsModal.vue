<template>
    <ArtworkBaseModal :title="day.dayString + ' ' + day.fullDay" description="" @close="closeModal">
        <div class="font-lexend">
            <div class="flex items-center mb-5">
                <div>
                    <img :src="user.element.profile_photo_url"
                         class="object-cover h-10 w-10 rounded-full" alt="">
                </div>
                <div class="ml-3 text-sm font-lexend font-bold">
                <span v-if="user.element.type === 'service_provider'">
                    {{ user.element.provider_name }} ({{ $t('Service provider') }})
                </span>
                    <span v-else>
                    {{ user.element.first_name }} {{ user.element.last_name }}
                    <span v-if="user.element.type === 'freelancer'">
                        ({{ $t('external') }})
                    </span>
                    <span v-else>
                        ({{ $t('internal') }})
                    </span>
                </span>
                </div>
            </div>

            <div class="space-y-2">
                <div v-for="shift in user.element.shifts" class="pb-1">
                    <div v-show="shift.days_of_shift?.includes(day.fullDay)" class="flex items-center justify-between group border-b border-dashed border-gray-300 py-2" :id="'shift-' + shift.id">
                        <SingleShiftInShiftOverviewUser :user="user" :shift="shift" @shiftDeleted="handleShiftDeleted" />
                        <!--<SingleEntityInShift :shift="shift" :person="user.element" :shift-qualifications="shiftQualifications" />-->
                        <!--
                        <div>
                            <div class="flex items-center text-sm gap-x-1">
                                <div class="w-14">
                                    <div class="px-2 py-0.5 border rounded-lg text-xs w-fit" :style="{ backgroundColor: shift.craft.color + '22', borderColor: blackColorIfColorIsWhite(shift.craft.color) + '55', color: blackColorIfColorIsWhite(shift.craft.color) }">
                                        {{ shift.craftAbbreviation }}
                                        <span v-if="shift.craftAbbreviation !== shift.craftAbbreviationUser" class="mx-1">
                                        [{{ shift.craftAbbreviationUser }}]
                                    </span>
                                    </div>
                                </div>
                                <div class="pr-1">
                                    {{ shift.startPivot }} - {{ shift.endPivot }}
                                </div>
                                <div class="pr-1">
                                    {{ shift.roomName }}
                                </div>
                                <div class="pl-1" v-if="shift.eventTypeAbbreviation">
                                    {{ shift.eventTypeAbbreviation }}:
                                    {{ shift.eventName }}
                                </div>
                            </div>

                            <p class="text-sm" v-if="shift.description">&bdquo;{{ shift.description }}&rdquo;</p>
                        </div>
                        <div class="invisible group-hover:visible cursor-pointer flex items-center gap-x-2">
                            <button type="button" @click="openRequestWorkTimeChangeModal(shift)" v-if="user.element.id === usePage().props.auth.user.id && user.type === 0">
                                <Component is="IconClockEdit" class="h-5 w-5 hover:text-blue-500 transition-colors duration-300 ease-in-out cursor-pointer" stroke-width="1.5"/>
                            </button>
                            <button type="button" @click="openConfirmDeleteModal(shift.id, shift.pivotId)">
                                <Component is="IconSquareRoundedXFilled" class="h-5 w-5 hover:text-red-500 transition-colors duration-300 ease-in-out cursor-pointer" stroke-width="1.5"/>
                            </button>
                        </div>-->


                    </div>
                </div>
            </div>


            <div class="flex items-center my-5" v-if="this.user.type === 0 || this.user.type === 1">
                <Listbox as="div" v-model="checked" class="w-full relative mt-2">
                    <ListboxButton class="menu-button">
                        <div>
                            <div>
                                {{ checked.name }}
                            </div>
                        </div>
                        <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                    </ListboxButton>
                    <ListboxOptions class="absolute w-full z-10 bg-artwork-navigation-background shadow-lg rounded-md max-h-40 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                        <ListboxOption v-for="type in vacationTypes"
                                       class="text-secondary cursor-pointer p-2 flex justify-between "
                                       :key="type.type"
                                       :value="type"
                                       v-slot="{ active, selected }">
                            <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']">
                                {{ type.name }}
                            </div>
                            <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                        </ListboxOption>
                    </ListboxOptions>
                </Listbox>
            </div>

            <div>
                <div>
                    <h4 class="font-semibold">Individuelle Zeit</h4>
                </div>
                <div v-if="getIndividualTimesByDate.length > 0">
                    <div class="text-sm mt-3 xsLight mb-3">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                            <div>
                                {{ $t('Title') }}
                            </div>
                            <div class="col-span-2">
                                {{ $t('Period') }}
                            </div>
                        </div>
                    </div>
                    <div v-for="(individual_time, index) in getIndividualTimesByDate" class="mb-2">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-3 group" v-if="individual_time?.days_of_individual_time?.includes(day.withoutFormat)">
                            <BaseInput id="title" v-model="individual_time.title" label="Title" :show-label="false" no-margin-top />
                            <div class="flex items-center justify-center col-span-2">
                                <BaseInput type="time" id="start_time" classes="rounded-r-none" v-model="individual_time.start_time" label="Startzeit" :show-label="false" no-margin-top />
                                <BaseInput type="time" id="end_time" v-model="individual_time.end_time" classes="border-l-0 rounded-l-none" label="Endzeit" :show-label="false" no-margin-top />
                            </div>
                            <div class="invisible group-hover:visible flex items-center justify-center" v-if="individual_time.id">
                                <component :is="IconTrash" class="h-6 w-6 hover:text-red-500 transition-colors duration-300 ease-in-out cursor-pointer" stroke-width="1.5" @click="deleteIndividualTimeById(individual_time)" />
                            </div>
                        </div>
                        <div v-if="individual_time.error" class="text-xs text-red-500 -mt-2">
                            {{ individual_time.error }}
                        </div>
                    </div>
                    <div class="mt-5">
                        <component
                            :is="IconCirclePlus"
                            class="h-6 w-6 xsLight cursor-pointer hover:text-artwork-buttons-hover transition-all duration-300 ease-in-out"
                            stroke-width="2"
                            @click="addIndividualTime"
                        />
                    </div>
                </div>
                <div v-else class="cursor-pointer" @click="addIndividualTime">
                    <div class="w-full px-3 py-4 bg-blue-400/30 rounded-lg mt-3">
                        <AlertComponent text="Es wurden noch keine Zeiten festgelegt. Klicke hier um Zeiten zu erstellen" show-icon icon-size="h-4 w-4" />
                    </div>
                </div>
            </div>

            <div class="my-2">
                <div class="mb-2">
                    <h4 class="font-semibold">{{ $t('Comment')}}</h4>
                </div>
                <div>
                    <BaseTextarea id="shift_comment" v-model="shiftPlanComment.comment" label="Comment" :show-label="false" no-margin-top />
                </div>
            </div>

            <div class="mt-5 text-sm" v-if="user.availabilities">
                <h3 class="font-bold mb-3">{{ $t('Registered availabilities') }}</h3>

                <div class="my-2" v-for="availability in user.availabilities[day.fullDay]">
                    <div>
                        <div class="flex items-center">
                            <div>
                                {{ availability.date_casted }}
                            </div>
                            <div v-if="!availability.full_day">
                                , {{ availability.start_time }} - {{ availability.end_time }}
                            </div>
                        </div>
                        <p v-if="availability.comment">&bdquo;{{ availability.comment }}&rdquo;</p>
                    </div>
                </div>
            </div>
            <div class="flex justify-center mt-5">
                <BaseUIButton
                    :label="$t('Save')"
                    is-add-button
                    @click="checkVacation"
                />
            </div>
        </div>

        <ConfirmDeleteModal :title="$t('Delete user from shift')" :description="$t('Are you sure you want to delete the user from this shift?')" @closed="closeConfirmDeleteModal" @delete="submitDeleteUserFromShift" v-if="showConfirmDeleteModal" />

        <RequestWorkTimeChangeModal
            :user="user.element"
            :shift="selectedShift"
            v-if="showRequestWorkTimeChangeModal"
            @close="showRequestWorkTimeChangeModal = false"
        />
    </ArtworkBaseModal>

</template>


<script>
import {defineComponent, nextTick} from 'vue'
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    Listbox,
    ListboxButton, ListboxOption, ListboxOptions,
    TransitionChild,
    TransitionRoot
} from "@headlessui/vue";
import {CheckIcon, XIcon} from "@heroicons/vue/solid";
import Button from "@/Jetstream/Button.vue";
import axios from "axios";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {ChevronDownIcon} from "@heroicons/vue/outline";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import DateInputComponent from "@/Components/Inputs/DateInputComponent.vue";
import TimeInputComponent from "@/Components/Inputs/TimeInputComponent.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import {router, usePage} from "@inertiajs/vue3";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import RequestWorkTimeChangeModal from "@/Pages/Shifts/Components/RequestWorkTimeChangeModal.vue";
import SingleEntityInShift from "@/Pages/Shifts/DailyViewComponents/SingleEntityInShift.vue";
import SingleShiftInShiftOverviewUser from "@/Pages/Shifts/Components/SingleShiftInShiftOverviewUser.vue";
import {IconCirclePlus, IconTrash} from "@tabler/icons-vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

export default defineComponent({
    name: "showUserShiftsModal",
    components: {
        BaseUIButton,
        SingleShiftInShiftOverviewUser,
        SingleEntityInShift,
        RequestWorkTimeChangeModal,
        ArtworkBaseModal,
        BaseTextarea,
        BaseInput,
        AlertComponent,
        TimeInputComponent,
        DateInputComponent,
        TextInputComponent,
        BaseModal,
        ListboxOption, ListboxOptions, ListboxButton, CheckIcon, ChevronDownIcon, Listbox,
        ConfirmDeleteModal,
        FormButton,
        SvgCollection,
        Button,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        XIcon,
        DialogPanel
    },
    data() {
        return {
            open: true,
            checked: null,
            wantedShiftId: null,
            wantedUserId: null,
            showConfirmDeleteModal: false,
            vacationTypes: [
                { name: 'Verfügbar', type: 'AVAILABLE'},
                { name: 'Arbeitsfreier Tag', type: 'OFF_WORK'},
                { name: 'Nicht Verfügbar', type: 'NOT_AVAILABLE'},
            ],
            vacationTypeBeforeUpdate: null,
            copyOfUserIndividualTimes: this.user.individual_times ? this.user.individual_times : [],
            showRequestWorkTimeChangeModal: false,
            selectedShift: null,
            shiftPlanComment: this.user.shift_comments[this.day.withoutFormat] ? this.user.shift_comments[this.day.withoutFormat][0] : {comment: '', date: this.day.withoutFormat},
        }
    },
    props: ['user', 'day', 'shiftQualifications'],
    emits: ['closed', 'delete', 'desiresReload'],
    mounted() {
        const vacation = this.user.vacations?.find(v => v.date === this.day.withoutFormat);
        if (vacation) {
            const vacationType = this.vacationTypes.find(type => type.type === vacation.type);
            this.checked =  vacationType ? vacationType : this.vacationTypes[0];
            this.vacationTypeBeforeUpdate =  vacationType ? vacationType : this.vacationTypes[0];
        } else {
            this.checked = this.vacationTypes[0];
            this.vacationTypeBeforeUpdate = this.vacationTypes[0];
        }
    },
    computed: {
        getIndividualTimesByDate(){
            return this.copyOfUserIndividualTimes.filter(individual_time => individual_time.days_of_individual_time.includes(this.day.withoutFormat));
        },

    },
    methods: {
        IconCirclePlus,
        IconTrash,
        usePage,
        blackColorIfColorIsWhite(color) {
            return color === '#ffffff' ? '#000000' : color;
        },
        deleteIndividualTimeById(individualTime){
            if (individualTime.id) {
                router.delete(route('delete.individualTimes', {individualTime: individualTime}), {
                    preserveScroll: true,
                    preserveState: false,
                });
            }
        },
        addIndividualTime() {
            this.user.individual_times.push({
                id: null,
                title: '',
                start_time: '',
                end_time: '',
                start_date: this.day.withoutFormat,
                days_of_individual_time: [this.day.withoutFormat]
            })
        },
        closeModal(bool) {
            this.user.individual_times = [...this.copyOfUserIndividualTimes];
            this.$emit('closed', bool)
        },
        removeUserFromShift(shiftId, usersPivotId) {
            router.delete(route('shift.removeUserByType', {usersPivotId: usersPivotId, userType: this.user.type}), {
                data: {
                    removeFromSingleShift: true
                },
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    document.getElementById('shift-' + shiftId)?.remove();
                },
                onFinish: () => {
                    document.getElementById('shift-' + shiftId)?.remove();
                }
            });
        },
        submitDeleteUserFromShift() {
            this.removeUserFromShift(this.wantedShiftId, this.wantedUserId);
            this.closeConfirmDeleteModal();
        },
        openConfirmDeleteModal(shiftId, usersPivotId) {
            this.wantedShiftId = shiftId;
            this.wantedUserId = usersPivotId;
            this.showConfirmDeleteModal = true;
        },
        closeConfirmDeleteModal() {
            this.showConfirmDeleteModal = false;
        },
        handleShiftDeleted(deletedShiftId) {
            // Remove the deleted shift from the user.element.shifts array
            // This prevents stale pivot IDs from causing issues on subsequent deletions
            this.user.element.shifts = this.user.element.shifts.filter(shift => shift.id !== deletedShiftId);
        },
        sendIndividualTimes() {
            axios.post(route('add.update.individualTimesAndShiftPlanComment'), {
                modelId: this.user.element.id,
                modelType: this.user.type,
                individualTimes: this.user.individual_times,
                shift_comment: this.shiftPlanComment,
            }).then(response => {
                // Relevante Daten ersetzen
                if (response.data.individual_times) {
                    this.copyOfUserIndividualTimes = response.data.individual_times;
                }

                if (response.data.shift_comment) {
                    this.shiftPlanComment = response.data.shift_comment;
                }

                this.sendCheckVacation(); // wichtig für Freigabe etc.
            }).catch(() => {
                // Handle error case - could add error handling here if needed
            });
            /*router.post(route('add.update.individualTimesAndShiftPlanComment'), {
                modelId: this.user.element.id,
                modelType: this.user.type,
                individualTimes: this.user.individual_times,
                shift_comment: this.shiftPlanComment,
            }, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    router.reload({
                        only: ['user'],
                        preserveScroll: true,
                        preserveState: true,
                        replace: true,
                        onSuccess: () => {
                            this.sendCheckVacation(); // nachgeladen, jetzt sicher aktuell
                        }
                    });
                },
                onError: () => {
                    return false;
                }
            });
            return false;*/
        },
        sendCheckVacation() {
            if (this.user.type === 0) {
                router.patch(route('user.check.vacation', {user: this.user.element.id}), {
                    checked: this.checked,
                    day: this.day.fullDay,
                    vacationTypeBeforeUpdate: this.vacationTypeBeforeUpdate,
                }, {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        this.closeModal(true);
                    }
                })
            } else if (this.user.type === 1) {
                router.patch(route('service_provider.check.vacation', {service_provider: this.user.element.id}), {
                    checked: this.checked,
                    day: this.day.fullDay,
                    vacationTypeBeforeUpdate: this.vacationTypeBeforeUpdate,
                }, {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        this.closeModal(true);
                    }
                })
            } else {
                this.closeModal(false);
            }
        },
        checkVacation() {
            // Clear previous validation errors
            for (let individualTime of this.user.individual_times) {
                delete individualTime.error;
            }

            // Validate individual times
            for (let individualTime of this.user.individual_times) {
                if (individualTime.start_time && !individualTime.end_time) {
                    individualTime.error = $t('Please also enter an end time here.');
                    return;
                }
                if (!individualTime.start_time && individualTime.end_time) {
                    individualTime.error = $t('Please also enter a start time here.');
                    return;
                }
            }

            this.sendIndividualTimes();
        },
        openRequestWorkTimeChangeModal(shift) {
            this.selectedShift = shift;
            this.showRequestWorkTimeChangeModal = true;
        }
    }
})
</script>
