<template>
    <span class="rounded-full items-center font-medium border px-3 mt-2 text-sm mr-1 mb-1 h-8 inline-flex" :style="{backgroundColor: backgroundColorWithOpacity(item.color), color: TextColorWithDarken(item.color), borderColor: TextColorWithDarken(item.color)}">
        <span class="cursor-pointer flex items-center" @click="showEditModal = true">
            {{ item.name }}
            <component is="IconCalendarCog" v-if="item.is_planning" class="ml-1 h-4 w-4" />
        </span>

        <button type="button" @click="showConfirmation = true">
            <XIcon class="ml-1 h-4 w-4 hover:text-error" />
        </button>
    </span>

    <ProjectStateModal
        v-if="showEditModal"
        :title="$t('Edit Status')"
        :state="item"
        @close="showEditModal = false"
        @submit="updateState"
    />

    <ConfirmationComponent
        v-if="showConfirmation"
        :titel="$t('Delete Status')"
        :description="$t('Are you sure you want to delete the status {status} from the system?', { status: item.name })"
        @closed="handleConfirmation"
    />
</template>

<script>
import { XIcon } from "@heroicons/vue/outline";
import ColorHelper from "@/Mixins/ColorHelper.vue";
import ProjectStateModal from "@/Layouts/Components/ProjectStateModal.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";

export default {
    name: "ProjectStateTagComponent",
    components: {
        ProjectStateModal,
        ConfirmationComponent,
        XIcon,
    },
    mixins: [ColorHelper],
    emits: ['delete', 'update'],
    props: {
        item: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            showEditModal: false,
            showConfirmation: false
        };
    },
    methods: {
        updateState(updatedState) {
            this.$emit('update', updatedState);
            this.showEditModal = false;
        },
        handleConfirmation(confirmed) {
            if (confirmed) {
                this.$emit('delete', this.item);
            }
            this.showConfirmation = false;
        }
    }
};
</script>
