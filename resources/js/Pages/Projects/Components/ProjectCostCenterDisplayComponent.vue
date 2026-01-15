<template>
    <div>
        <div class="flex items-center gap-x-3">
            <div
                class="font-lexend font-black tracking-wide"
                :class="inSidebar ? 'text-white text-md' : 'text-primaryText text-md'"
            >
                {{ $t('Name of the cost unit') }}
            </div>

            <PropertyIcon
                v-if="canEditCostCenter"
                name="IconEdit"
                class="w-5 h-5 rounded-full cursor-pointer"
                :class="inSidebar ? 'text-white' : 'text-artwork-buttons-context'"
                @click="openEditModal"
            />
        </div>

        <div class="mt-4">
            <div
                :class="inSidebar ? 'text-white text-sm' : 'text-primaryText text-sm'"
            >
                {{ project?.cost_center?.name || '-' }}
            </div>
        </div>

        <BaseModal
            v-if="showEditModal"
            @closed="closeEditModal"
            modal-size="sm:max-w-lg"
        >
            <div class="space-y-4">
                <div class="text-xl font-bold text-primaryText">
                    {{ $t('Edit cost unit') }}
                </div>

                <div>
                    <BaseInput
                        id="cost_center_input"
                        v-model="costCenterName"
                        type="text"
                        :label="$t('Name of the cost unit')"
                        :placeholder="$t('Name of the cost unit')"
                        without-translation
                    />
                </div>

                <div class="flex justify-end gap-x-2">
                    <button
                        type="button"
                        @click="closeEditModal"
                        class="px-4 py-2 text-sm font-medium text-secondary hover:text-primaryText transition-colors"
                    >
                        {{ $t('Cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="saveCostCenter"
                        class="px-4 py-2 text-sm font-medium text-white bg-artwork-buttons-create hover:bg-artwork-buttons-hover rounded-md transition-colors"
                    >
                        {{ $t('Save') }}
                    </button>
                </div>
            </div>
        </BaseModal>
    </div>
</template>

<script>
import {defineComponent} from 'vue';
import {can, is} from 'laravel-permission-to-vuejs';
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue';
import BaseModal from '@/Components/Modals/BaseModal.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import {router} from '@inertiajs/vue3';

export default defineComponent({
    name: 'ProjectCostCenterDisplayComponent',
    components: {BaseModal, PropertyIcon, BaseInput},
    props: {
        project: {
            type: Object,
            required: true
        },
        headerObject: {
            type: Object,
            required: true
        },
        inSidebar: {
            type: Boolean,
            required: false,
            default: false
        },
    },
    data() {
        return {
            showEditModal: false,
            costCenterName: '',
        };
    },
    computed: {
        canEditCostCenter() {
            const userId = this.$page?.props?.auth?.user?.id;

            return (
                can('write projects') ||
                is('artwork admin') ||
                this.headerObject.projectManagerIds?.includes(userId) ||
                this.headerObject.projectWriteIds?.includes(userId)
            );
        },
    },
    methods: {
        openEditModal() {
            this.costCenterName = this.project?.cost_center?.name || '';
            this.showEditModal = true;
        },
        closeEditModal() {
            this.showEditModal = false;
            this.costCenterName = '';
        },
        saveCostCenter() {
            router.post(
                route('projects.update.cost-center', {project: this.project.id}),
                {
                    cost_center_name: this.costCenterName
                },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.closeEditModal();
                    }
                }
            );
        }
    }
});
</script>
