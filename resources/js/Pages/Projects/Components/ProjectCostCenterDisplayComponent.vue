<template>
    <div>
        <div class="flex items-center gap-x-3">
            <span class="componentLabel" :class="{'!text-white': inSidebar}">
                {{ $t('cost unit') }}
            </span>
        </div>

        <div class="mt-4">
            <BaseInput
                v-if="canEditCostCenter"
                id="cost_center_input"
                v-model="costCenterName"
                type="text"
                :label="$t('Name of the cost unit')"
                :placeholder="$t('Name of the cost unit')"
                without-translation
                is-small
                @focusout="saveCostCenter"
                @keydown.enter="blurInput"
            />
            <div
                v-else
                :class="inSidebar ? 'text-white text-sm' : 'text-primaryText text-sm'"
            >
                {{ project?.cost_center?.name || '-' }}
            </div>
        </div>
    </div>
</template>

<script>
import {defineComponent} from 'vue';
import {can, is} from 'laravel-permission-to-vuejs';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import {router} from '@inertiajs/vue3';

export default defineComponent({
    name: 'ProjectCostCenterDisplayComponent',
    components: {BaseInput},
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
            costCenterName: this.project?.cost_center?.name || '',
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
        currentCostCenterName() {
            return this.project?.cost_center?.name || '';
        },
    },
    watch: {
        currentCostCenterName(newValue) {
            this.costCenterName = newValue;
        },
    },
    methods: {
        blurInput(event) {
            event?.target?.blur?.();
        },
        saveCostCenter() {
            if (this.costCenterName.trim() === this.currentCostCenterName) {
                return;
            }

            router.post(
                route('projects.update.cost-center', {project: this.project.id}),
                {
                    cost_center_name: this.costCenterName.trim()
                },
                {
                    preserveScroll: true
                }
            );
        }
    }
});
</script>
