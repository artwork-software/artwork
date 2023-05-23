<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-30" @close="closeModal">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 z-30 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="relative transform overflow-hidden bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl sm:p-6">
                            <img src="/Svgs/Overlays/illu_appointment_edit.svg" class="-ml-6 -mt-8 mb-4"/>
                            <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500" @click="closeModal">
                                    <span class="sr-only">Close</span>
                                    <XIcon class="h-6 w-6" aria-hidden="true" />
                                </button>
                            </div>
                            <div class="relative z-40">
                                <div class="font-black font-lexend text-primary text-3xl my-2">
                                    Abwesenheit
                                </div>

                                <div class="flex pb-1 flex-col sm:flex-row align-baseline gap-1 mt-10">
                                    <div class="sm:w-1/2">
                                        <div class="w-full flex">
                                            <input v-model="vacation.start"
                                                   id="startDate"
                                                   type="date"
                                                   required
                                                   class="border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none flex-grow"/>
                                        </div>
                                    </div>
                                    <div class="sm:w-1/2">
                                        <div class="w-full flex">
                                            <input v-model="vacation.end"
                                                   id="endDate"
                                                   type="date"
                                                   required
                                                   class="border-gray-300 inputMain xsDark placeholder-secondary  disabled:border-none flex-grow"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-center mt-5">
                                <AddButton v-if="editVacation" mode="modal" class="!border-2 !border-buttonBlue text-white bg-buttonHover !hover:border-transparent resize-none" text="Speichern"/>
                                <AddButton v-else @click="saveOrUpdateVacation" mode="modal" class="!border-2 !border-buttonBlue text-white bg-buttonHover !hover:border-transparent resize-none" text="Speichern"/>
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
import {XIcon} from "@heroicons/vue/solid";
import AddButton from "@/Layouts/Components/AddButton.vue";
import {Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot} from "@headlessui/vue";
import {useForm} from "@inertiajs/inertia-vue3";

export default defineComponent({
    name: "AddEditVacationsModal",
    components: {
        AddButton, XIcon, Dialog, DialogTitle, DialogPanel, TransitionChild, TransitionRoot,
    },
    data(){
        return {
            open: true,
            vacation: useForm({
                start: this.editVacation ? this.editVacation.start : null,
                end: this.editVacation ? this.editVacation.start : null
            })
        }
    },
    emits: ['closed'],
    props: ['editVacation', 'user'],
    methods: {
        closeModal(bool){
            this.$emit('closed', bool)
        },
        saveOrUpdateVacation(){
            if(this.editVacation.id !== null){
                this.vacation.post(route('user.vacation.add', this.user.id), {
                    preserveScroll: true, preserveState: true
                })
            }
        }
    }
})
</script>
<style scoped>

</style>
