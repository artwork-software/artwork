<template>
    <ArtworkBaseModal
        v-if="show"
        title="Booking details"
        :description="currentSageAssignedData?.is_collective_booking ? 'Collective Booking' : ''"
        modal-size="sm:max-w-2xl"
        @close="close"
    >
        <div v-if="!cell.sage_assigned_data?.length" class="text-sm text-text-muted">
            {{ $t('No booking has been linked yet.') }}
        </div>
        <div v-else class="flex w-full flex-col">
            <!-- Blättern zwischen mehreren Buchungen der Zelle -->
            <nav v-if="maxIndex > 1" class="mb-5 flex items-center justify-between gap-x-2 border-b border-border-subtle pb-3">
                <BaseUIButton
                    label="Previous"
                    variant="ghost"
                    size="sm"
                    icon="IconChevronLeft"
                    :class="currentIndex > 0 ? '' : 'invisible'"
                    @click="currentIndex--"
                />
                <div v-if="maxIndex <= 9" class="flex items-center gap-x-1">
                    <button
                        v-for="index in maxIndex"
                        :key="index"
                        type="button"
                        class="inline-flex size-8 cursor-pointer items-center justify-center rounded-md text-sm tabular-nums transition-colors duration-150 motion-reduce:transition-none"
                        :class="currentIndex === (index - 1)
                            ? 'bg-surface-inverse font-semibold text-text-inverse'
                            : 'text-text-muted hover:bg-surface-hover hover:text-text'"
                        :aria-current="currentIndex === (index - 1) ? 'page' : undefined"
                        @click="currentIndex = (index - 1)"
                    >
                        {{ index }}
                    </button>
                </div>
                <select
                    v-else
                    v-model="currentIndex"
                    class="h-8 rounded-md border border-border bg-surface px-2 text-sm text-text tabular-nums focus:border-accent-600"
                >
                    <option v-for="index in maxIndex" :key="index" :value="(index - 1)">
                        {{ index }}
                    </option>
                </select>
                <BaseUIButton
                    label="Next"
                    variant="ghost"
                    size="sm"
                    icon="IconChevronRight"
                    :class="currentIndex < (maxIndex - 1) ? '' : 'invisible'"
                    @click="currentIndex++"
                />
            </nav>

            <div v-if="currentSageAssignedData">
                <BookingModalContents :booking="currentSageAssignedData"/>
                <div
                    v-if="currentSageAssignedData.find_children?.length"
                    class="mt-6 space-y-3"
                >
                    <div
                        v-for="(childBooking, index) in currentSageAssignedData.find_children"
                        :key="childBooking.id"
                        class="ml-6 border-l-2 border-border-subtle pl-4"
                    >
                        <button
                            type="button"
                            class="flex w-full cursor-pointer items-center justify-between rounded-md bg-surface-sunken px-3 py-2 text-left text-sm font-medium text-text transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover"
                            :aria-expanded="isOpen(childBooking.id)"
                            @click="toggleChild(childBooking.id)"
                        >
                            <span>{{ $t('single booking') }} {{ index + 1 }}</span>
                            <PropertyIcon
                                :name="isOpen(childBooking.id) ? 'IconChevronUp' : 'IconChevronDown'"
                                class="size-4 text-text-subtle"
                                stroke-width="1.5"
                                aria-hidden="true"
                            />
                        </button>
                        <transition name="fade">
                            <div v-show="isOpen(childBooking.id)" class="mt-2">
                                <BookingModalContents :booking="childBooking" :is-child="true"/>
                            </div>
                        </transition>
                    </div>
                </div>

                <!-- Nur lesend (z. B. nicht zugeordnete Buchungen in der Sage-Rechnungsübersicht):
                     keine Kommentare, kein Löschen -->
                <template v-if="!readOnly">
                    <div class="mt-6 border-t border-border-subtle pt-5">
                        <BaseTextarea
                            id="sageAssignedDataComment"
                            v-model="bookingDataCommentForm.comment"
                            label="Comment"
                            :placeholder="$t('Enter comment')"
                            :rows="4"
                        />
                        <div class="mt-3 flex justify-end">
                            <BaseUIButton
                                label="Save comment"
                                variant="primary"
                                size="sm"
                                icon="IconDeviceFloppy"
                                :processing="bookingDataCommentForm.processing"
                                :disabled="!bookingDataCommentForm.comment || bookingDataCommentForm.processing"
                                @click="saveComment(currentSageAssignedData)"
                            />
                        </div>
                    </div>

                    <ul v-if="currentSageAssignedData.comments?.length" class="mt-4 space-y-3">
                        <li
                            v-for="comment in currentSageAssignedData.comments"
                            :key="comment.id"
                            class="rounded-lg border border-border-subtle px-3 py-2"
                        >
                            <div class="flex items-center justify-between gap-x-2 text-xs text-text-subtle">
                                <div class="flex items-center gap-x-1.5">
                                    <UserPopoverTooltip
                                        :user="comment.user"
                                        :height="5"
                                        :width="5"
                                    />
                                    {{ formatDateTime(comment.created_at) }}
                                </div>
                                <button
                                    v-if="$page.props.auth.user.id === comment.user.id"
                                    type="button"
                                    class="inline-flex size-7 cursor-pointer items-center justify-center rounded-md text-text-subtle transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover hover:text-danger"
                                    :aria-label="$t('Delete comment')"
                                    v-tooltip.left="{ value: $t('Delete comment'), class: 'aw-tooltip' }"
                                    @click="removeComment(comment.id)"
                                >
                                    <PropertyIcon name="IconTrash" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                                </button>
                            </div>
                            <div class="mt-1 text-sm text-text">{{ comment.comment }}</div>
                        </li>
                    </ul>
                </template>
            </div>
        </div>
        <template #footer>
            <BaseUIButton
                label="Close"
                is-cancel-button
                hide-icon
                @click="close"
            />
            <BaseUIButton
                v-if="canDelete"
                label="Move to the trash"
                variant="danger"
                icon="IconTrash"
                @click="showDeleteConfirmation"
            />
        </template>
        <ConfirmationComponent
            v-if="showDeleteConfirmationModal"
            @closed="handleDeleteConfirmation"
            :description="$t('Do you really want to put the data set in the trash?', [currentSageAssignedData?.buchungstext])"
            :titel="$t('Move to the trash')"
            :z-index="200"
        />
    </ArtworkBaseModal>
</template>

<script>
import {defineComponent} from 'vue';
import {formatBookingDataDate, formatDateTime} from '@/Layouts/Components/Budget/bookingDate.js';
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue';
import {router, useForm} from '@inertiajs/vue3';
import Permissions from '@/Mixins/Permissions.vue';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue';
import BookingModalContents from '@/Layouts/Components/Budget/BookingModalContents.vue';
import UserPopoverTooltip from '@/Layouts/Components/UserPopoverTooltip.vue';
import CurrencyFloatToStringFormatter from '@/Mixins/CurrencyFloatToStringFormatter.vue';
import ConfirmationComponent from '@/Layouts/Components/ConfirmationComponent.vue';

export default defineComponent({
    components: {
        PropertyIcon,
        ArtworkBaseModal,
        BaseUIButton,
        BaseTextarea,
        BookingModalContents,
        UserPopoverTooltip,
        ConfirmationComponent
    },
    mixins: [Permissions, CurrencyFloatToStringFormatter],
    props: {
        show: {type: Boolean, default: false},
        cell: {type: Object, required: true},
        // Startindex, wenn das Modal aus einer Liste heraus für eine bestimmte Buchung geöffnet wird
        // (Sage-Rechnungsübersicht); die Budgettabelle startet weiterhin bei 0.
        initialIndex: {type: Number, default: 0},
        // Nur anzeigen: verbirgt Löschen und Kommentare (Buchungen ohne Zuordnung haben beides nicht)
        readOnly: {type: Boolean, default: false}
    },
    emits: ['close', 'budget-updated'],
    data() {
        const length = this.cell.sage_assigned_data?.length ?? 0;

        return {
            bookingDataCommentForm: useForm({
                userId: this.$page.props.auth.user.id,
                sageAssignedDataId: null,
                comment: null
            }),
            currentIndex: Math.min(Math.max(0, this.initialIndex), Math.max(0, length - 1)),
            maxIndex: length,
            openChildren: {},
            showDeleteConfirmationModal: false
        };
    },
    computed: {
        currentSageAssignedData() {
            return this.cell.sage_assigned_data?.[this.currentIndex];
        },
        canDelete() {
            return !this.readOnly
                && !!this.currentSageAssignedData
                && this.$canAny(['can view project sage data', 'can view global sage data']);
        }
    },
    watch: {
        // Liste kann von außen ersetzt werden (Neuladen nach Löschen/Kommentar) → Pager nachziehen
        'cell.sage_assigned_data.length'(length) {
            this.maxIndex = length ?? 0;
            if (this.currentIndex > this.maxIndex - 1) {
                this.currentIndex = Math.max(0, this.maxIndex - 1);
            }
        }
    },
    methods: {
        toggleChild(id) {
            this.openChildren[id] = !this.openChildren[id];
        },
        isOpen(id) {
            return Boolean(this.openChildren[id]);
        },
        formatBookingDataDate,
        formatDateTime,
        formattedAmount(value) {
            return this.toCurrencyString(value);
        },
        saveComment(sageAssignedData) {
            this.bookingDataCommentForm.sageAssignedDataId = sageAssignedData.id;
            this.bookingDataCommentForm.post(route('sageAssignedDataComments.store'), {
                preserveScroll: true,
                onSuccess: () => {
                    // Nur die Budgettabelle liefert den frischen Kommentar über die Seiten-Props;
                    // andere Aufrufer (Sage-Rechnungsübersicht) laden nach 'budget-updated' selbst neu.
                    const createdComment = this.$page.props.loadedProjectInformation?.BudgetTab
                        ?.recentlyCreatedSageAssignedDataComment;
                    if (createdComment) {
                        sageAssignedData.comments.unshift(createdComment);
                    }
                    this.bookingDataCommentForm.reset();
                    this.$emit('budget-updated');
                }
            });
        },
        removeComment(id) {
            router.delete(route('sageAssignedDataComments.destroy', {sageAssignedDataComment: id}), {
                preserveScroll: true,
                onSuccess: () => {
                    const idx = this.currentSageAssignedData.comments.findIndex(c => c.id === id);
                    if (idx > -1) this.currentSageAssignedData.comments.splice(idx, 1);
                    this.$emit('budget-updated');
                }
            });
        },
        showDeleteConfirmation() {
            this.showDeleteConfirmationModal = true;
        },
        handleDeleteConfirmation(confirmed) {
            if (confirmed) {
                router.delete(
                    route('sageAssignedData.destroy', {
                        sageAssignedData: this.currentSageAssignedData.id
                    }),
                    {
                        preserveState: true,
                        preserveScroll: true,
                        onSuccess: () => {
                            // Remove the deleted item from the array
                            const idx = this.cell.sage_assigned_data.findIndex(
                                item => item.id === this.currentSageAssignedData.id
                            );
                            if (idx > -1) {
                                this.cell.sage_assigned_data.splice(idx, 1);
                                // Adjust currentIndex if needed
                                if (this.currentIndex >= this.cell.sage_assigned_data.length) {
                                    this.currentIndex = Math.max(0, this.cell.sage_assigned_data.length - 1);
                                }
                                this.maxIndex = this.cell.sage_assigned_data.length;
                                // If no more items, close the modal
                                if (this.cell.sage_assigned_data.length === 0) {
                                    this.close();
                                    // Emit event to reload budget after closing
                                    this.$emit('budget-updated');
                                } else {
                                    // Emit event to reload budget even if modal stays open
                                    this.$emit('budget-updated');
                                }
                            }
                        }
                    }
                );
            }
            this.showDeleteConfirmationModal = false;
        },
        close() {
            this.$emit('close');
        }
    }
});
</script>

<style>
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.2s;
}

.fade-enter-from, .fade-leave-to {
    opacity: 0;
}
</style>
