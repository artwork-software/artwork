<template>
    <ArtworkBaseModal
        title="Course of funding sources"
        description="Here you can see what was changed by whom and when."
        modal-size="sm:max-w-2xl"
        @close="closeModal(false)"
    >
        <EmptyState
            v-if="historyItems.length === 0"
            icon="IconHistory"
            :title="$t('No entries found')"
        />
        <ol
            v-else
            class="max-h-96 divide-y divide-border-subtle overflow-y-auto rounded-lg border border-border-subtle"
        >
            <li
                v-for="(historyItem, index) in historyItems"
                :key="historyItem.id ?? index"
                class="flex items-start gap-x-3 px-3 py-2"
            >
                <UserPopoverTooltip
                    :user="historyItem.change_by ?? historyItem.changes[0].changed_by"
                    :id="`money-source-history-${index}`"
                    height="7"
                    width="7"
                />
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-text">
                        {{ $t(historyItem.changes[0].translationKey, historyItem.changes[0].translationKeyPlaceholderValues) }}
                    </p>
                    <p class="mt-0.5 text-xs text-text-muted tabular-nums">
                        {{ historyItem.created_at }}<template v-if="!(historyItem.change_by ?? historyItem.changes[0].changed_by)"> · {{ $t('deleted User') }}</template>
                    </p>
                </div>
            </li>
        </ol>
    </ArtworkBaseModal>
</template>

<script>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import EmptyState from "@/Artwork/Feedback/EmptyState.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";

export default {
    name: 'MoneySourceHistoryComponent',
    components: {
        ArtworkBaseModal,
        EmptyState,
        UserPopoverTooltip,
    },
    props: ['history'],
    emits: ['closed'],
    computed: {
        /** Einträge ohne Änderungsdetails ließen das Template an changes[0] scheitern */
        historyItems() {
            return (this.history ?? []).filter((historyItem) => historyItem?.changes?.[0])
        },
    },
    methods: {
        closeModal(bool) {
            this.$emit('closed', bool);
        },
    },
}
</script>
