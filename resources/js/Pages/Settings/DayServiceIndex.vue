<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import TabComponent from "@/Components/Tabs/TabComponent.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import AddEditDayServiceModal from "@/Pages/Settings/Components/AddEditDayServiceModal.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import IconLib from "@/Mixins/IconLib.vue";
import GlassyIconButton from "@/Artwork/Buttons/GlassyIconButton.vue";
import {IconPlus} from "@tabler/icons-vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import ShiftSettingTabs from "@/Pages/Settings/Components/ShiftSettingTabs.vue";

export default {
    name: "DayServiceIndex",
    mixins: [IconLib],
    components: {
        ShiftSettingTabs,
        PropertyIcon, GlassyIconButton, AddEditDayServiceModal, ConfirmDeleteModal, AddButtonSmall, TabComponent, AppLayout},
    props: [
        'dayServices'
    ],
    data() {
        return {
            tabs: [
                {
                    name: this.$t('Shift Settings'),
                    href: route('shift.settings'),
                    current: route().current('shift.settings'),
                    show: true,
                    icon: 'IconCalendarUser'
                },
                {
                    name: this.$t('Day Services'),
                    href: route('day-service.index'),
                    current: route().current('day-service.index'),
                    show: true,
                    icon: 'IconHours24'
                },
                {
                    name: this.$t('Work Time Pattern'),
                    href: route('shift.work-time-pattern'),
                    current: route().current('shift.work-time-pattern'),
                    show: true,
                    icon: 'IconClockCog'
                },
                {
                    name: this.$t('User Contracts'),
                    href: route('user-contract-settings.index'),
                    current: route().current('user-contract-settings.index'),
                    show: true,
                    icon: 'IconContract'
                }
            ],
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
            dayServiceToEdit: null,
            openConfirmDeleteModal: false,
            dayServiceToDelete: null,
            confirmDeleteTitle: '',
            confirmDeleteDescription: ''
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
        },
        openDeleteDayServiceModal(dayService){
            this.dayServiceToDelete = dayService;
            this.confirmDeleteTitle = this.$t('Delete Day Service');
            this.confirmDeleteDescription = this.$t('Are you sure you want to delete the selected day service? All assignments to this day service will be removed.');
            this.openConfirmDeleteModal = true;
        },
        closedDeleteDayServiceModal(){
            this.openConfirmDeleteModal = false;
            this.dayServiceToDelete = null;
        },
        submitDelete(){
            this.$inertia.delete(route('day-service.destroy', this.dayServiceToDelete.id), {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => {
                    this.closedDeleteDayServiceModal();
                }
            });
        }
    }
}
</script>

<template>
    <AppLayout :title="$t('Day Services')">
        <div class="artwork-container">
            <div class="">
                <h2 class="headline1">{{$t('Day Services')}}</h2>
                <div class="xsLight mt-2">
                    {{$t('Define global settings for shift scheduling.')}}
                </div>
            </div>

            <ShiftSettingTabs />

           <div class="flex items-center justify-between">

               <GlassyIconButton text="New Day Service" :icon="IconPlus"@click="showAddEditDayServiceModal = true" />
           </div>

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
                            <div class="flex items-center gap-x-2">
                                <IconEdit class="h-6 w-6 cursor-pointer flex items-center" @click="editDayService(dayService)" />
                                <IconTrash class="h-6 w-6 cursor-pointer flex items-center text-red-500" @click="openDeleteDayServiceModal(dayService)" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <AddEditDayServiceModal :icon-list="iconList" v-if="showAddEditDayServiceModal" :day-service-to-edit="dayServiceToEdit" @closed="closeModal" />
        <ConfirmDeleteModal :title="confirmDeleteTitle" :description="confirmDeleteDescription" @closed="closedDeleteDayServiceModal" @delete="submitDelete" v-if="openConfirmDeleteModal" />
    </AppLayout>
</template>

<style scoped>

</style>
