<script>
import ShiftSettingsHeader from "@/Pages/Settings/Components/ShiftSettingsHeader.vue";
import TabComponent from "@/Components/Tabs/TabComponent.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import AddEditDayServiceModal from "@/Pages/Settings/Components/AddEditDayServiceModal.vue";
import IconLib from "@/Mixins/IconLib.vue";
import GlassyIconButton from "@/Artwork/Buttons/GlassyIconButton.vue";
import {IconPlus} from "@tabler/icons-vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

export default {
    name: "DayServiceIndex",
    mixins: [IconLib],
    components: {
        ShiftSettingsHeader,
        PropertyIcon, GlassyIconButton, AddEditDayServiceModal, AddButtonSmall, TabComponent},
    props: [
        'dayServices'
    ],
    data() {
        return {
            iconList: [
                {iconName: 'IconAbacus'},
                {iconName: 'IconKey'},
                {iconName: 'IconSpeakerphone'},
                {iconName: 'IconTrolley'},
                {iconName: 'IconCamera'},
                {iconName: 'IconBuildingWarehouse'},
                {iconName: 'IconForklift'},
                {iconName: 'IconTruckDelivery'},
                {iconName: 'IconHotelService'},
                {iconName: 'IconServer'},
                {iconName: 'IconDevices2'},
                {iconName: 'IconDeviceSpeaker'},
                {iconName: 'IconDeviceAudioTape'},
                {iconName: 'IconLiveView'},
                {iconName: 'IconMicroscope'},
                {iconName: 'IconGavel'},
                {iconName: 'IconHelp'},
                {iconName: 'IconInfoCircle'},
            ],
            showAddEditDayServiceModal: false,
            dayServiceToEdit: null
        }
    },
    methods: {
        IconPlus,
        closeModal(){
            this.showAddEditDayServiceModal = false;
            this.dayServiceToEdit = null;
        },
        editDayService(dayService){
            this.dayServiceToEdit = dayService;
            this.showAddEditDayServiceModal = true;
        }
    }
}
</script>

<template>
    <ShiftSettingsHeader :title="$t('Day Services')">
        <template #actions>
            <button class="ui-button-add" @click="showAddEditDayServiceModal = true">
                <component :is="IconPlus" stroke-width="1" class="size-5" />
                {{ $t('New Day Service') }}
            </button>
        </template>

            <div class="my-5 card white p-5" >
                <div v-for="dayService in dayServices">
                    <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-8 mb-3">
                        <div class="col-span-full md:col-span-2 xl:col-span-7">
                            <div class="flex items-center gap-x-1.5">
                                <PropertyIcon :name="dayService.icon" stroke-width="1.5" :style="{color: dayService.hex_color}" class="h-8 w-8 cursor-pointer flex items-center text-black" />
                                <div class="">{{ dayService.name }}</div>
                            </div>
                        </div>
                        <div class="col-span-full md:col-span-1 xl:col-span-1">
                            <IconEdit class="h-6 w-6 cursor-pointer flex items-center" @click="editDayService(dayService)" />
                        </div>
                    </div>
                </div>
            </div>

        <AddEditDayServiceModal :icon-list="iconList" v-if="showAddEditDayServiceModal" :day-service-to-edit="dayServiceToEdit" @closed="closeModal" />
    </ShiftSettingsHeader>
</template>

<style scoped>

</style>
