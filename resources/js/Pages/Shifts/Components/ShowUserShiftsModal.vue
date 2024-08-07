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
                            class="relative transform overflow-hidden bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6 rounded-lg">
                            <img src="/Svgs/Overlays/illu_appointment_edit.svg" class="-ml-6 -mt-8 mb-4"/>
                            <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500"
                                        @click="closeModal">
                                    <span class="sr-only">Close</span>
                                    <XIcon class="h-6 w-6" aria-hidden="true"/>
                                </button>
                            </div>
                            <div class="relative z-40 mx-5">
                                <h1 class="headline1 mb-6">{{ day.day_string }} {{ day.full_day }}</h1>


                                <div class="flex items-center mb-5">
                                    <div>
                                        <img :src="user.element.profile_photo_url"
                                             class="object-cover h-10 w-10 rounded-full" alt="">
                                    </div>
                                    <div class="ml-3 text-sm font-bold">
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
                                <div v-for="shift in user.element.shifts">
                                    <div v-if="shift.days_of_shift?.includes(day.full_day)"
                                         class="flex items-center justify-between group mb-2" :id="'shift-' + shift.id">
                                        <div>
                                            <div class="flex text-sm">
                                                {{ shift.craftAbbreviation }} {{ shift.start }} - {{ shift.end }} |
                                                {{ shift.roomName }} | {{ shift.eventTypeAbbreviation }}:
                                                {{ shift.eventName }}
                                            </div>
                                            <p class="text-sm" v-if="shift.description">&bdquo;{{ shift.description }}&rdquo;</p>
                                        </div>
                                        <div class="hidden group-hover:block cursor-pointer">
                                            <button type="button" @click="removeUserFromShift(shift.id, shift.pivotId)">
                                                <SvgCollection svg-name="xMarkIcon"/>
                                            </button>
                                        </div>
                                    </div>
                                </div>


                                <div class="mt-5 text-sm" v-if="user.availabilities">
                                    <h3 class="font-bold mb-3">{{ $t('Registered availabilities') }}</h3>

                                    <div class="my-2" v-for="availability in user.availabilities[day.full_day]">
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

                                <div class="flex items-center mt-10" v-if="this.user.type === 0 || this.user.type === 1">
                                    <input v-model="checked"
                                           type="checkbox"
                                           class="input-checklist"/>
                                    <p :class="[checked ? 'text-primary font-black' : 'text-secondary']"
                                       class="ml-4 my-auto text-sm">{{ $t('Available') }}</p>
                                </div>
                            </div>
                            <div class="flex justify-center mt-5">
                                <FormButton
                                    :text="$t('Save')"
                                    @click="checkVacation"
                                />
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
<script>
import {defineComponent} from 'vue'
import {Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot} from "@headlessui/vue";
import {XIcon} from "@heroicons/vue/solid";
import Button from "@/Jetstream/Button.vue";
import axios from "axios";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

export default defineComponent({
    name: "showUserShiftsModal",
    components: {
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
            checked: !this.user.vacations?.includes(this.day.without_format)
        }
    },
    props: ['user', 'day'],
    emits: ['closed', 'delete', 'desiresReload'],
    methods: {
        closeModal(bool) {
            this.$emit('closed', bool)
        },
        removeUserFromShift(shiftId, usersPivotId) {
            let user = this.user;
            axios.delete(
                route(
                    'shift.removeUserByType',
                    {
                        usersPivotId: usersPivotId,
                        userType: this.user.type
                    }
                ),
                {
                    data: {
                        removeFromSingleShift: true
                    }
                }).then(() => {
                document.getElementById('shift-' + shiftId).remove();
                this.$emit('desiresReload', shiftId, user.element.id, user.type, this.day.full_day);
            });
        },
        checkVacation() {
            let callback = (afterRequest) => {
                if (afterRequest) {
                    this.$emit(
                        'desiresReload',
                        null,
                        this.user.element.id,
                        this.user.type,
                        this.day.full_day
                    );
                }
                this.closeModal(true);
            };

            if (this.user.type === 0) {
                axios.patch(route('user.check.vacation', {user: this.user.element.id}), {
                    checked: this.checked,
                    day: this.day.full_day
                }).then(() => {
                    callback(true);
                });
            } else if (this.user.type === 1) {
                axios.patch(route('freelancer.check.vacation', {freelancer: this.user.element.id}), {
                    checked: this.checked,
                    day: this.day.full_day
                }).then(() => {
                    callback(true);
                });
            } else {
                callback(false);
            }
        }
    }
})
</script>
