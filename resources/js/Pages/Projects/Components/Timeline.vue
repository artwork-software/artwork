<template>
    <div class="w-60">
        <div class="h-9 bg-gray-500 px-4 rounded-lg mb-2 flex items-center justify-between shadow-md">
            <div class="uppercase text-white text-xs">
                {{ $t('Timeline') }}
            </div>
            <div class="flex items-center gap-x-2">
                <ToolTipComponent
                    icon="IconClipboard"
                    icon-size="w-5 h-5"
                    white-icon
                    :tooltip-text="$t('Copy timeline to clipboard')"
                    @click="copyTimelineToClipboard"
                    direction="bottom"
                    v-if="canEditComponent"

                />
                <ToolTipComponent
                    icon="IconWand"
                    icon-size="w-5 h-5"
                    white-icon
                    :tooltip-text="$t('Create new timeline')"
                    @click="openTimelineModal(false)"
                    direction="bottom"
                    v-if="canEditComponent"
                />
                <BaseMenu white-menu-background has-no-offset white-icon v-if="canEditComponent">
                    <BaseMenuItem white-menu-background title="Read from template" icon="IconFileImport" @click="showSearchTimelinePresetModal = true" />
                    <BaseMenuItem white-menu-background title="Save as template" icon="IconFileExport" @click="showCreateTimelinePresetModal = true" />
                    <BaseMenuItem white-menu-background title="Edit" @click="openTimelineModal(true)" />
                </BaseMenu>
            </div>
        </div>

        <div>
            <template v-for="(time) in timeLine">
                <NewSingleTimeline :canEditComponent="canEditComponent" :time="time" :event="event" @wantsFreshPlacements="this.$emit('wantsFreshPlacements')"/>
            </template>

            <div v-if="canEditComponent">
                <div class="flex items-center justify-center mt-1 py-5 rounded-lg cursor-pointer border border-dashed border-gray-300 group btn-border-hover" @click="addEmptyTimeline">
                    <component is="IconCircleDashedPlus" class="h-6 w-6 text-artwork-buttons-context/30 btn-group-hover" stroke-width="1.5" />
                </div>
            </div>
        </div>
    </div>

    <transition name="fade" appear>
        <div class="pointer-events-none fixed z-50 inset-x-0 top-5 sm:flex sm:justify-center sm:px-6 sm:pb-5 lg:px-8" v-show="successCopied">
            <div class="pointer-events-auto flex items-center justify-between gap-x-6 bg-gray-900 px-6 py-2.5 sm:rounded-xl sm:py-3 sm:pl-4 sm:pr-3.5">
                <p class="text-sm/6 text-white">
                    {{ $t('Timeline copied to clipboard') }}
                </p>
                <button type="button" class="-m-1.5 flex-none p-1.5">
                    <span class="sr-only">Dismiss</span>
                    <component is="IconX" class="size-5 text-white" aria-hidden="true" @click="successCopied = false" />
                </button>
            </div>
        </div>
    </transition>

    <AddEditTimelineModal
        v-if="showAddTimeLineModal"
        :timeline-to-edit="addTimelineToEdit ? timeLine : null"
        :event="event"
        @close="closeModal()"/>

    <SearchTimelinePresetModal
        v-if="showSearchTimelinePresetModal"
        :event="event"
        @close="closeSearchTimelinePresetModal"
    />

    <CreateTimelinePresetFormEvent
        :event="event"
        v-if="showCreateTimelinePresetModal"
        @close="closeModalShowCreateTimelinePresetModal()"
    />

    <!--<AddTimeLineModal v-if="showAddTimeLineModal"
                      :event="event"
                      :timeLine="timeLine"
                      @closed="this.closeModal()"/>-->
</template>
<script>
import {defineComponent} from 'vue'
import AddTimeLineModal from "@/Pages/Projects/Components/AddTimeLineModal.vue";
import dayjs from "dayjs";
import Permissions from "@/Mixins/Permissions.vue";
import IconLib from "@/Mixins/IconLib.vue";
import {router} from "@inertiajs/vue3";
import NewSingleTimeline from "@/Pages/Projects/Components/TimelineComponents/NewSingleTimeline.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import AddEditTimelineModal from "@/Pages/Projects/Components/TimelineComponents/AddEditTimelineModal.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import SearchTimelinePresetModal from "@/Pages/Projects/Components/TimelineComponents/SearchTimelinePresetModal.vue";
import CreateTimelinePresetFormEvent
    from "@/Pages/Projects/Components/TimelineComponents/CreateTimelinePresetFormEvent.vue";

export default defineComponent({
    name: "Timeline",
    computed: {
        dayjs() {
            return dayjs;
        }
    },
    components: {
        CreateTimelinePresetFormEvent,
        SearchTimelinePresetModal,
        ToolTipComponent,
        AddEditTimelineModal,
        BaseMenuItem,
        BaseMenu,
        NewSingleTimeline,
        AddTimeLineModal
    },
    props: {
        timeLine: Array,
        event: Object,
        canEditComponent: {
            type: Boolean,
            default: false
        }
    },
    emits: [
        'wantsFreshPlacements'
    ],
    mixins: [
        Permissions,
        IconLib
    ],
    data(){
        return {
            showAddTimeLineModal: false,
            addTimelineToEdit: false,
            successCopied: false,
            showSearchTimelinePresetModal: false,
            showCreateTimelinePresetModal: false
        }
    },
    methods: {
        closeModal() {
            this.$emit('wantsFreshPlacements');
            this.showAddTimeLineModal = false;
        },
        openTimelineModal(boolean) {
            if(this.$can('can plan shifts') || this.hasAdminRole()) {
                this.showAddTimeLineModal = true;
                this.addTimelineToEdit = boolean;
            }
        },
        addEmptyTimeline(){
            router.post(route('add.timeline.row', {event: this.event.id}), {
            }, {
                preserveScroll: true,
                preserveState: false,
                onSuccess: () => {
                    this.$emit('wantsFreshPlacements');
                }
            })
        },
        closeSearchTimelinePresetModal() {
            this.$emit('wantsFreshPlacements');
            this.showSearchTimelinePresetModal = false;
        },
        closeModalShowCreateTimelinePresetModal() {
            this.$emit('wantsFreshPlacements');
            this.showCreateTimelinePresetModal = false;
        },
        copyTimelineToClipboard(){
            let text = '';
            this.timeLine.forEach((time) => {
                text += `${time.start} - ${time.end} ${time.description}\n`
            })
            navigator.clipboard.writeText(text);

            this.successCopied = true;
            setTimeout(() => {
                this.successCopied = false;
            }, 1000)
        }
    }
});
</script>


<style>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
