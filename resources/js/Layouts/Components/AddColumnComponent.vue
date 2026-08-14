<template>
    <ArtworkBaseModal
        @close="closeModal(false)"
        :title="$t('New column')"
        :description="$t('Create a new, empty column. Alternatively, you can also create a function column (sum/difference).')"
    >
        <div class="mx-4">
            <RadioGroup v-model="selectedType" class="mt-4">
                <legend class="sr-only">{{ $t('Column type') }}</legend>
                <div class="space-y-3">
                    <RadioGroupOption
                        :value="columnType.type"
                        v-for="columnType in columnTypes"
                        :key="columnType.type"
                        class="flex items-center cursor-pointer"
                        v-slot="{ checked }"
                    >
                        <input
                            :id="columnType.type"
                            name="column-type"
                            type="radio"
                            :checked="checked"
                            class="h-5 w-5 border-border text-accent-600 focus:ring-accent-600 pointer-events-none"
                            tabindex="-1"
                            readonly
                        />
                        <label
                            :for="columnType.type"
                            :class="[checked ? 'text-sm/5 font-semibold text-text' : 'text-sm/5 font-bold text-text-subtle']"
                            class="ml-3 block cursor-pointer pointer-events-none"
                        >
                            {{ columnType.title }}
                        </label>
                    </RadioGroupOption>
                </div>
            </RadioGroup>

            <div v-if="selectedType === 'empty'" class="mt-6 rounded-lg bg-surface-sunken border border-border-subtle p-4 text-xs text-text-subtle">
                {{ $t('The new empty column is automatically marked as relevant for budget. You can move this flag to another value column at any time via the column menu.') }}
            </div>

            <div v-if="selectedType !== 'empty'" class="mt-6 rounded-lg bg-surface-sunken border border-border-subtle p-4">
                <h2 class="text-sm/5 font-bold text-text-subtle mb-4">
                    {{ selectedType === 'sum' ? $t('What amount would you like to receive?') : $t('What difference do you want to get?') }}
                </h2>
                <div class="flex items-center gap-x-3">
                    <ArtworkBaseListbox
                        v-model="selectedFirstColumn"
                        :items="selectableColumns"
                        :placeholder="$t('Select column')"
                        class="w-1/2"
                        is-small
                    />
                    <div class="text-sm/5 font-semibold text-text shrink-0">{{ selectedType === 'sum' ? '+' : '-' }}</div>
                    <ArtworkBaseListbox
                        v-model="selectedSecondColumn"
                        :items="selectableColumns"
                        :placeholder="$t('Select column')"
                        class="w-1/2"
                        is-small
                    />
                </div>
            </div>

            <div class="flex justify-center mt-8">
                <BaseUIButton
                    variant="primary"
                    hide-icon
                    :disabled="isSubmitDisabled"
                    @click="addColumn"
                >
                    {{ $t('Create column') }}
                </BaseUIButton>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script>
import {RadioGroup, RadioGroupOption} from "@headlessui/vue";
import Permissions from "@/Mixins/Permissions.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

export default {
    name: 'AddColumnComponent',
    mixins: [Permissions],
    components: {
        ArtworkBaseModal,
        ArtworkBaseListbox,
        BaseUIButton,
        RadioGroupOption,
        RadioGroup,
    },
    data() {
        return {
            columnTypes: [
                {type: 'empty', title: this.$t('Empty column')},
                {type: 'sum', title: this.$t('Sum column')},
                {type: 'difference', title: this.$t('Difference column')},
            ],
            selectedType: 'empty',
            selectedFirstColumn: null,
            selectedSecondColumn: null,
            isSubmitting: false,
        }
    },
    props: [
        'project',
        'table'
    ],
    emits: ['closed'],
    computed: {
        selectableColumns() {
            return (this.table?.columns ?? []).slice(3);
        },
        isSubmitDisabled() {
            if (this.isSubmitting) {
                return true;
            }
            if (this.selectedType === 'empty') {
                return false;
            }
            return this.selectedFirstColumn === null
                || this.selectedSecondColumn === null
                || this.selectedFirstColumn === this.selectedSecondColumn;
        },
    },
    methods: {
        closeModal(bool) {
            this.$emit('closed', bool);
        },
        addColumn() {
            const payload = this.selectedType === 'empty'
                ? {
                    column_type: this.selectedType,
                    table_id: this.table.id
                }
                : {
                    first_column_id: this.selectedFirstColumn.id,
                    second_column_id: this.selectedSecondColumn.id,
                    column_type: this.selectedType,
                    table_id: this.table.id
                };

            this.isSubmitting = true;
            this.$inertia.post(route('project.budget.column.add'), payload, {
                preserveScroll: true,
                onSuccess: () => {
                    this.closeModal(true);
                },
                onFinish: () => {
                    this.isSubmitting = false;
                }
            });
        }
    },
}
</script>
