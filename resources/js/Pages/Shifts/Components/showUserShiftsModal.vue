<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-50" @close="closeModal">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="relative transform overflow-hidden bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">
                            <img src="/Svgs/Overlays/illu_appointment_edit.svg" class="-ml-6 -mt-8 mb-4"/>
                            <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500" @click="closeModal">
                                    <span class="sr-only">Close</span>
                                    <XIcon class="h-6 w-6" aria-hidden="true" />
                                </button>
                            </div>
                            <div class="relative z-40 mx-5">
                                <h1 class="headline1 mb-6">{{ day.day_string }} {{ day.full_day }}</h1>


                                <div class="flex items-center mb-5">
                                    <div>
                                        <img :src="user.element.profile_photo_url" class="object-cover h-10 w-10 rounded-full" alt="">
                                    </div>
                                    <div class="ml-3 text-sm font-bold">
                                        <span v-if="user.element.resource !== 'ServiceProviderShiftResource'">
                                            {{ user.element.first_name }} {{ user.element.last_name }}
                                            <span v-if="user.resource === 'FreelancerShiftResource'">
                                            (extern)
                                            </span>
                                            <span v-else>
                                                (intern)
                                            </span>
                                        </span>
                                        <span v-else>
                                            {{ user.element.provider_name }} (Dienstleister)
                                        </span>

                                    </div>
                                </div>
                                <div v-for="shift in user.element.shifts[day.full_day]">
                                    <div class="flex items-center justify-between group mb-2" :id="'shift-' + shift.id">
                                        <div class="flex text-sm">
                                            {{ shift.craft?.abbreviation }} {{ shift.start }} - {{ shift.end }} | {{ shift.event.room?.name }} | {{ shift.event.event_type?.abbreviation }}: {{ findProjectById(shift.event.project_id)?.name }}
                                        </div>
                                        <div class="hidden group-hover:block cursor-pointer">
                                            <button type="button" @click="removeUserFromShift(shift.id)">
                                                <SvgCollection svg-name="xMarkIcon" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex mt-10">
                                    <input v-model="checked"
                                           type="checkbox"
                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                    <p :class="[checked ? 'text-primary font-black' : 'text-secondary']"
                                       class="ml-4 my-auto text-sm">Verfügbar</p>
                                </div>
                            </div>
                            <div class="flex justify-center mt-5">
                                <AddButton mode="modal" @click="checkVacation"
                                           class="!border-2 !border-buttonBlue text-white bg-buttonHover !hover:border-transparent resize-none"
                                           text="Speichern"/>
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
import AddButton from "@/Layouts/Components/AddButton.vue";
import {Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot} from "@headlessui/vue";
import {XIcon} from "@heroicons/vue/solid";
import Button from "@/Jetstream/Button.vue";
import {Inertia} from "@inertiajs/inertia";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";

export default defineComponent({
    name: "showUserShiftsModal",
    components: {
        SvgCollection,
        Button,
        AddButton,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        XIcon, DialogPanel
    },
    data(){
        return {
            open: true,
            checked: !this.user.vacations?.includes(this.day.without_format)
        }
    },
    props: ['user', 'day', 'projects'],
    emits: ['closed', 'delete'],
    methods: {
        closeModal(bool) {
            this.$emit('closed', bool)
        },
        findProjectById(projectId) {
            return this.projects.find(project => project.id === projectId);
        },
        removeUserFromShift(shift){
            const shiftContainer = document.getElementById('shift-' + shift);
            if(this.user.element.resource === 'ServiceProviderShiftResource'){
                Inertia.delete(route('shifts.removeProvider', {shift: shift, serviceProvider: this.user.element.id}), {
                    onSuccess: () => {
                        shiftContainer.remove()
                    }
                })
            } else if(this.user.element.resource === 'FreelancerShiftResource'){
                Inertia.delete(route('shifts.removeFreelancer', {shift: shift, freelancer: this.user.element.id}), {
                    onSuccess: () => {
                        shiftContainer.remove()
                    }
                })
            } else {
                Inertia.delete(route('shifts.removeUser', {shift: shift, user: this.user.element.id}), {
                    onSuccess: () => {
                        shiftContainer.remove()
                    }
                })
            }
        },
        checkVacation(){
            Inertia.patch(route('user.check.vacation', {user: this.user.element.id}), {
                checked: this.checked,
                day: this.day.full_day
            }, {
                onSuccess: () => {
                    this.closeModal(true)
                }
            });

        }
    }

})
</script>


<style scoped>

</style>
