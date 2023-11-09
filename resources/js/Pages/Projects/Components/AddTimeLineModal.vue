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
                            <img src="/Svgs/Overlays/illu_appointment_edit.svg" class="-ml-6 -mt-8 mb-4"/>
                            <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500"
                                        @click="closeModal">
                                    <span class="sr-only">Close</span>
                                    <XIcon class="h-6 w-6" aria-hidden="true"/>
                                </button>
                            </div>
                            <div class="relative z-40">
                                <div class="font-black font-lexend text-primary text-3xl my-2">
                                    Timeline erstellen
                                </div>
                                <p class="xsLight subpixel-antialiased">
                                    Lege die Schichtrelevanten Zeiten fest. Du kannst Schichten jeweils entlang dieser
                                    Timeline erstellen.
                                </p>
                                <div class="mt-10">
                                    <div class="my-4" v-for="(time, index) in timeLine">
                                        <SingleTimeLine :time="time" :index="index" :key="index"/>
                                    </div>
                                    <div class="w-full flex group">
                                        <div
                                            v-if="showAddTimeLineForm"
                                            class="grid grid-cols-1 sm:grid-cols-2 w-full gap-2"
                                        >
                                            <div>
                                                <input type="text"
                                                       onfocus="(this.type='time')"
                                                       placeholder="Start*"
                                                       v-model="addTimeLineForm.start"
                                                       class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
                                                       required
                                                       @focusout="checkTime()"
                                                />
                                            </div>
                                            <div>
                                                <input type="text"
                                                       onfocus="(this.type='time')"
                                                       placeholder="Ende*"
                                                       v-model="addTimeLineForm.end"
                                                       maxlength="3"
                                                       required
                                                       class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
                                                       @focusout="checkTime()"
                                                />
                                            </div>
                                            <span class="mt-2 text-red-500 text-xs" v-show="helpText.length > 0">{{
                                                    helpText
                                                }}</span>
                                            <div class="mt-2 col-span-2">
                                                <textarea
                                                    v-model="addTimeLineForm.description"
                                                    rows="4"
                                                    name="comment"
                                                    id="comment"
                                                    class="block w-full inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
                                                />
                                            </div>
                                        </div>
                                        <div class="hidden group-hover:block ml-3">
                                            <XCircleIcon @click="showAddTimeLineForm = false" class="mt-2 h-5 w-5 text-buttonBlue hover:text-error cursor-pointer"/>
                                        </div>
                                    </div>
                                    <div class="h-1">
                                        <div
                                            class="mt-5 w-full h-1 border-b-2 border-dashed !flex items-center justify-center relative cursor-pointer hidden group-hover:block"
                                            @click="showAddTimeLineForm = true">
                                            <div class="absolute flex items-center justify-center w-full ">
                                                <PlusCircleIcon class="h-6 w-6"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-center mt-5">
                                <AddButton mode="modal" text="Speichern" @click="saveTimeLines"/>
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
import {XCircleIcon, XIcon} from "@heroicons/vue/solid";
import AddButton from "@/Layouts/Components/AddButton.vue";
import Permissions from "@/mixins/Permissions.vue";
import {Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot} from "@headlessui/vue";
import Input from "@/Jetstream/Input.vue";
import {PlusCircleIcon} from "@heroicons/vue/outline";
import SingleTimeLine from "@/Pages/Projects/Components/SingleTimeLine.vue";
import {useForm} from "@inertiajs/inertia-vue3";

export default defineComponent({
    name: "AddTimeLineModal",
    mixins: [Permissions],
    components: {
        XCircleIcon,
        SingleTimeLine,
        Input,
        AddButton,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        XIcon, DialogPanel, PlusCircleIcon
    },
    props: ['event', 'timeLine'],
    data() {
        return {
            open: true,
            helpText: '',
            showAddTimeLineForm: this.timeLine.length === 0,
            addTimeLineForm: useForm({
                start: null,
                end: null,
                description: null
            }),
        }
    },
    emits: ['closed'],
    methods: {
        closeModal(bool) {
            this.$emit('closed', bool);
        },
        saveTimeLines() {
            let hasInvalid = false;
            this.timeLine.forEach(function (timeline) {
                if (timeline.start >= timeline.end) {
                    hasInvalid = true;
                }
            });
            if (!hasInvalid) {
                if (this.showAddTimeLineForm) {
                    if (this.checkTime()) {
                        this.addTimeLineForm.post(
                            route('add.timeline.row', {event: this.event.id}),
                            {
                                preserveState: true,
                                preserveScroll: true,
                                onFinish: () => {
                                    //handle existing timelines which may be updated
                                    this.updateTimes();
                                }
                            }
                        );
                    }
                    return;
                }
                //handle existing timelines which may be updated
                this.updateTimes();
            }
        },
        updateTimes() {
            this.$inertia.patch(route('update.timelines'), {
                timelines: this.timeLine
            }, {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => {
                    this.closeModal(true);
                }
            })
        },
        checkTime() {
            if (this.addTimeLineForm.start === null || this.addTimeLineForm.end === null) {
                this.helpText = 'Bitte fülle beide Felder aus.';
                return false;
            } else if (this.addTimeLineForm.start >= this.addTimeLineForm.end) {
                this.helpText = 'Die Startzeit muss vor der Endzeit liegen.';
                return false;
            } else {
                this.helpText = '';
                return true;
            }
        }
    }
})
</script>
<style scoped>

</style>
