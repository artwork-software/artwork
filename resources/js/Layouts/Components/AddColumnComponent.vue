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
                            class="h-5 w-5 border-gray-300 text-artwork-buttons-create focus:ring-artwork-buttons-create pointer-events-none"
                            tabindex="-1"
                            readonly
                        />
                        <label
                            :for="columnType.type"
                            :class="[checked ? 'xsDark' : 'xsLight']"
                            class="ml-3 block cursor-pointer pointer-events-none"
                        >
                            {{ columnType.title }}
                        </label>
                    </RadioGroupOption>
                </div>
            </RadioGroup>

            <div v-if="selectedType !== 'empty'" class="mt-6 rounded-lg bg-gray-50 border border-gray-200 p-4">
                <h2 class="xsLight mb-4">
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
                    <div class="xsDark shrink-0">{{ selectedType === 'sum' ? '+' : '-' }}</div>
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
                <ArtworkBaseModalButton
                    variant="primary"
                    :disabled="isSubmitDisabled"
                    @click="addColumn"
                >
                    {{ $t('Create column') }}
                </ArtworkBaseModalButton>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script>
import {RadioGroup, RadioGroupOption} from "@headlessui/vue";
import Permissions from "@/Mixins/Permissions.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";

export default {
    name: 'AddColumnComponent',
    mixins: [Permissions],
    components: {
        ArtworkBaseModal,
        ArtworkBaseListbox,
        ArtworkBaseModalButton,
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
