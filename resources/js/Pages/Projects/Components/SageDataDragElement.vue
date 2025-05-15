<template>
    <div>
        <!-- Draggable Element -->
        <div class="flex items-center my-1.5 cursor-grab" draggable="true" @dragstart="onDragStart">
            <div class="w-28">
                {{ sageData.sa_kto !== '' ? sageData.sa_kto : sageData.kto_soll }}
            </div>
            <div class="w-28">
                {{ sageData.kst_stelle }}
            </div>
            <div class="w-64 truncate">
                {{ sageData.buchungstext }}
            </div>
            <div class="w-52 flex justify-end items-center">
                {{ toCurrencyString(sageData.buchungsbetrag) }}
                <IconAbacus
                    v-if="sageData.is_collective_booking"
                    class="w-5 h-5 cursor-pointer ml-2"
                    @click.stop="showChildren(sageData)"
                />
            </div>
            <div class="w-40 text-right">
                {{ formatBookingDataDate(sageData.buchungsdatum) }}
            </div>
            <div v-if="type === 'global'" class="w-28 text-right">
                {{ sageData.kst_traeger }}
            </div>
            <div class="ml-2 flex">
                <IconTrash
                    class="w-5 h-5 cursor-pointer hover:text-red-600"
                    @click="$emit('removeSageNotAssignedData', sageData)"
                />
            </div>
            <div class="w-5 ml-2">
                <IconDragDrop class="w-5 h-5 cursor-grab ml-2" />
            </div>
        </div>

        <!-- Modal for Child Bookings -->
        <SageChildrenModal
            v-if="showModal"
            :children="modalChildren"
            @close="closeModal"
        />
    </div>
</template>

<script>
import { TrashIcon, XIcon } from "@heroicons/vue/solid";
import IconLib from "@/Mixins/IconLib.vue";
import CurrencyFloatToStringFormatter from "@/Mixins/CurrencyFloatToStringFormatter.vue";
import SageChildrenModal from "@/Pages/Projects/Components/CollectiveBooking/SageChildrenModal.vue";

export default {
    name: "SageDataDragElement",
    components: {
        SageChildrenModal,
        TrashIcon,
        XIcon
    },
    mixins: [IconLib, CurrencyFloatToStringFormatter],
    props: {
        sageData: {
            type: Object,
            required: true
        },
        type: {
            type: String,
            default: ''
        }
    },
    emits: ['removeSageNotAssignedData'],
    data() {
        return {
            showModal: false,
            modalChildren: []
        };
    },
    methods: {
        showChildren(sageData) {
            this.modalChildren = sageData.find_children || [];
            this.showModal = true;
        },
        closeModal() {
            this.showModal = false;
        },
        onDragStart(event) {
            this.sageData.type = 'globaleMove';
            event.dataTransfer.setData('text/plain', JSON.stringify(this.sageData));
        },
        formatBookingDataDate(dateString) {
            let parts = dateString.split('T');
            parts = parts[0].split('-');
            return parts[2] + '.' + parts[1] + '.' + parts[0];
        }
    }
};
</script>


