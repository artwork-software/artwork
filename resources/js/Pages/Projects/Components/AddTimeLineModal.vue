<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-50" @close="closeModal">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100"
                             leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"/>
            </TransitionChild>
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300"
                                     enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                     enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200"
                                     leave-from="opacity-100 translate-y-0 sm:scale-100"
                                     leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel
                            class="relative transform overflow-hidden bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl sm:p-6">
                            <img src="/Svgs/Overlays/illu_appointment_edit.svg" class="-ml-6 -mt-8 mb-4" alt="appointment"/>
                            <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500"
                                        @click="closeModal">
                                    <span class="sr-only">Close</span>
                                    <XIcon class="h-6 w-6" aria-hidden="true"/>
                                </button>
                            </div>
                            <div class="relative z-40">
                                <div class="font-black font-lexend text-primary text-3xl my-2">
                                    {{ $t('Create timeline') }}
                                </div>
                                <p class="xsLight subpixel-antialiased">
                                    {{ $t('Define the shift-relevant times. You can create shifts along this timeline.') }}
                                </p>
                                <div class="mt-10">
                                    <div class="my-4" v-for="(time, index) in timeLine">
                                        <SingleTimeLine :time="time" :index="index" :key="index"/>
                                    </div>
                                    <div class="flex flex-row group">
                                        <div v-if="showAddTimeLineForm"
                                             class="timeline-container">
                                            <div class="timeline-dates-container">
                                                <input class="timeline-date-input"
                                                       type="date"
                                                       v-model="addTimeLineForm.start_date"
                                                       :placeholder="$t('Start*')"
                                                       @focusout="checkDates()"

                                                />
                                                <input class="timeline-date-input"
                                                       type="date"
                                                       v-model="addTimeLineForm.end_date"
                                                       :placeholder="$t('Ende*')"
                                                       @focusout="checkDates()"/>
                                            </div>
                                            <span class="timeline-error-text" v-if="showDatesNotGivenErrorText">
                                                {{ $t('Please fill in both fields.') }}
                                            </span>
                                            <span class="timeline-error-text" v-if="showDatesStartGreaterThanEndText">
                                                {{ $t('The start time must be before the end time.') }}
                                            </span>
                                            <div class="timeline-times-container">
                                                <input class="timeline-time-input"
                                                       type="time"
                                                       v-model="addTimeLineForm.start"
                                                       :placeholder="$t('Start*')"
                                                       @focusout="checkTime()"
                                                />
                                                <input class="timeline-time-input"
                                                       type="time"
                                                       :placeholder="$t('Ende*')"
                                                       v-model="addTimeLineForm.end"
                                                       @focusout="checkTime()"/>
                                            </div>
                                            <span class="timeline-error-text" v-if="showTimesNotGivenErrorText">
                                                {{ $t('Please fill in both fields.') }}
                                            </span>
                                            <span class="timeline-error-text" v-if="showTimesStartGreaterThanEndText">
                                                {{ $t('The start time must be before the end time.') }}
                                            </span>
                                            <textarea class="timeline-textarea"
                                                      v-model="addTimeLineForm.description"
                                                      rows="4"
                                                      :placeholder="$t('Comment')"
                                                      name="comment"
                                                      id="comment"
                                            />
                                        </div>
                                        <XCircleIcon class="group-hover:block ml-2 mt-2 delete-icon" @click="showAddTimeLineForm = false"/>
                                    </div>
                                    <div class="h-1">
                                        <div class="mt-5 w-full h-1 border-b-2 border-dashed !flex items-center justify-center relative cursor-pointer group-hover:block"
                                             @click="showAddTimeLineForm = true">
                                            <div class="absolute flex items-center justify-center w-full ">
                                                <PlusCircleIcon class="h-6 w-6"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-center mt-5">
                                <FormButton :text="$t('Save')" @click="saveTimeLines"/>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import {ref} from "vue";
import {
    XCircleIcon,
    XIcon
} from "@heroicons/vue/solid";
import {
    Dialog,
    DialogPanel,
    TransitionChild,
    TransitionRoot
} from "@headlessui/vue";
import Input from "@/Jetstream/Input.vue";
import {PlusCircleIcon} from "@heroicons/vue/outline";
import SingleTimeLine from "@/Pages/Projects/Components/SingleTimeLine.vue";
import {router, useForm} from "@inertiajs/vue3";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

const emits = defineEmits(['closed']),
    props = defineProps({
        event: Object,
        timeLine: Object
    }),
    open = ref(true),
    showAddTimeLineForm = ref((props.timeLine.length === 0)),
    addTimeLineForm = useForm({
        start_date: '',
        end_date: '',
        start: '',
        end: '',
        description: ''
    }),
    showDatesNotGivenErrorText = ref(false),
    showDatesStartGreaterThanEndText = ref(false),
    showTimesNotGivenErrorText = ref(false),
    showTimesStartGreaterThanEndText = ref(false),
    closeModal = (bool) => {
        emits.call(this, 'closed', bool);
    },
    saveTimeLines = () => {
        let hasInvalid = false;
        props.timeLine.forEach(function (timeline) {
            if (
                timeline.start_date.length === 0 ||
                timeline.end_date.length === 0 ||
                timeline.start.length === 0 ||
                timeline.end.length === 0 ||
                timeline.start_date > timeline.end_date ||
                timeline.start > timeline.end)
            {
                hasInvalid = true;
            }
        });
        if (!hasInvalid) {
            if (showAddTimeLineForm.value) {
                if (checkTime() && checkDates()) {
                    addTimeLineForm.post(
                        route('add.timeline.row', {event: props.event.id}),
                        {
                            preserveState: false,
                            preserveScroll: true,
                            onFinish: () => {
                                //handle existing timelines which may be updated
                                updateTimes();
                            }
                        }
                    );
                }
                return;
            }
            //handle existing timelines which may be updated
            updateTimes();
        }
    },
    updateTimes = () => {
        router.patch(
            route('update.timelines'),
            {
                timelines: props.timeLine
            }, {
                preserveState: false,
                preserveScroll: true,
                onFinish: () => {
                    closeModal(true);
                }
            }
        )
    },
    checkDates = () => {
        showDatesNotGivenErrorText.value =  addTimeLineForm.start_date.length === 0 || addTimeLineForm.end_date.length === 0;
        showDatesStartGreaterThanEndText.value = !showDatesNotGivenErrorText.value && addTimeLineForm.start_date > addTimeLineForm.end_date;

        return !showDatesNotGivenErrorText.value && !showDatesStartGreaterThanEndText.value;
    },
    checkTime = () => {
        showTimesNotGivenErrorText.value = addTimeLineForm.start.length === 0 || addTimeLineForm.end.length === 0;
        showTimesStartGreaterThanEndText.value = !showTimesNotGivenErrorText.value && addTimeLineForm.start > addTimeLineForm.end;

        return !showTimesNotGivenErrorText.value && !showTimesStartGreaterThanEndText.value;
    };
</script>
