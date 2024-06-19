<script>
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import {Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot} from "@headlessui/vue";
import {XIcon} from "@heroicons/vue/solid";
import IconLib from "@/Mixins/IconLib.vue";
import {router} from "@inertiajs/vue3";
import CurrencyFloatToStringFormatter from "@/Mixins/CurrencyFloatToStringFormatter.vue";

export default {
    name: "SageDropMultipleDataSelectModal",
    mixins: [IconLib, CurrencyFloatToStringFormatter],
    components: {
        FormButton,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        XIcon, DialogPanel
    },
    data(){
        return {
            open: true,
        }
    },
    props: ['cellData', 'cell', 'type'],
    emits: ['close'],
    computed: {
        checkIfAllSelected(){
            return this.cellData.sage_assigned_data.every(data => data.checked);
        },
        checkIfOneSelected(){
            return this.cellData.sage_assigned_data.some(data => data.checked);
        }
    },
    unmounted() {
        this.cellData.sage_assigned_data.forEach(data => data.checked = false)
    },
    mounted() {
        this.cellData.sage_assigned_data.forEach(data => data.checked = false)
    },
    methods: {
        closeModal(bool){
            this.$emit('close', bool)
        },
        moveRow(){
            // get all checked data form cellData.sage_assigned_data only the id
            let checkedData = this.cellData.sage_assigned_data.filter(data => data.checked).map(data => data.id);
            if(this.type === 'dropOnValue') {
                router.post(route('project.budget.move.sage.row', {
                    columnCell: this.cell.id,
                    movedColumn: this.cellData.id
                }), {
                    multiple: true,
                    selectedData: checkedData
                }, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        this.closeModal(false);
                    },
                });
            }

            if(this.type === 'dropOnRow') {
                router.post(this.route('project.budget.move.sage.to.row', {
                    table_id: this.cellData.table_id,
                    sub_position_id: this.cellData.sub_position_id,
                    positionBefore: this.cellData.positionBefore,
                    columnCell: this.cellData.id
                }), {
                    multiple: true,
                    selectedData: checkedData
                }, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        this.closeModal(false);
                    },
                });
            }
        },
        formatBookingDataDate(dateString) {
            let parts = dateString.split('T');
            parts = parts[0].split('-');

            return parts[2] + '.' + parts[1] + '.' + parts[0];
        },
        SelectAllSageAssignedData(){
            this.cellData.sage_assigned_data.forEach(data => data.checked = true)
        },
        DeselectAllSageAssignedData(){
            this.cellData.sage_assigned_data.forEach(data => data.checked = false)
        }
    }
}
</script>

<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-50" @close="closeModal">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="relative transform overflow-hidden bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:max-w-4xl sm:p-6">
                            <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4"/>
                            <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500" @click="closeModal">
                                    <span class="sr-only">Close</span>
                                    <IconX stroke-width="1.5" class="h-6 w-6" aria-hidden="true" />
                                </button>
                            </div>
                            <div class="relative z-40 pl-4">
                                <div class="font-black font-lexend text-primary text-3xl my-2 mb-6">
                                    Sage Daten auswählen
                                </div>
                                <p class="subpixel-antialiased">
                                    Welche Daten sollen verschoben werden?
                                </p>
                                <div>
                                    <div class="flex flex-row font-bold my-3">
                                        <div class="relative flex items-start w-4 mr-3">
                                        </div>
                                        <div class="w-28">
                                            {{ $t('KTO') }}
                                        </div>
                                        <div class="w-28">
                                            {{ $t('KST') }}
                                        </div>
                                        <div class="w-64 truncate">
                                            {{ $t('Booking text') }}
                                        </div>
                                        <div class="w-52 text-right">
                                            {{ $t('Booking amount') }}
                                        </div>
                                    </div>
                                    <div class="flex items-center my-2" v-for="data in cellData.sage_assigned_data">
                                        <div class="relative flex items-start mr-3">
                                            <div class="flex h-6 items-center">
                                                <input id="candidates" v-model="data.checked" aria-describedby="candidates-description" name="candidates" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-artwork-buttons-hover focus:ring-0" />
                                            </div>
                                        </div>
                                        <div class="w-28">
                                            {{ data.sa_kto }}
                                        </div>
                                        <div class="w-28">
                                            {{ data.kst_stelle }}
                                        </div>
                                        <div class="w-64 truncate">
                                            {{ data.buchungstext }}
                                        </div>
                                        <div class="w-52 text-right">
                                            {{ this.toCurrencyString(data.buchungsbetrag) }}
                                        </div>
                                    </div>
                                    <div class="flex justify-between my-3">
                                        <div>
                                            <p class="underline text-artwork-buttons-create cursor-pointer text-xs" @click="DeselectAllSageAssignedData" v-if="checkIfOneSelected">Alle Datensätze abwählen</p>
                                        </div>
                                        <div>
                                            <p class="underline text-artwork-buttons-create cursor-pointer text-xs" @click="SelectAllSageAssignedData" v-if="!checkIfAllSelected">Alle Datensätze auswählen</p>
                                            <p class="underline text-artwork-buttons-create cursor-pointer text-xs" @click="DeselectAllSageAssignedData" v-else>Alle Datensätze abwählen</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between mt-5 items-center pr-4">
                                <FormButton
                                    @click="moveRow(true)"
                                    text="Verschieben" />
                                <p class="cursor-pointer text-sm mt-3 text-secondary" @click="closeModal">
                                    {{ $t('No, not really') }}
                                </p>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<style scoped>

</style>
