<template>
    <BaseModal @closed="close" v-if="show" modal-image="/Svgs/Overlays/illu_project_edit.svg">
        <div class="mx-4">
            <div class="mt-8 headline1 flex items-center justify-between">
                <div>
                    {{ $t('Booking details') }}
                    <span v-if="currentSageAssignedData.is_collective_booking">
          - {{ $t('Collective Booking') }}
        </span>
                </div>
                <TrashIcon
                    v-if="currentSageAssignedData && this.$canAny(['can view project sage data', 'can view global sage data', 'can view and delete sage100-api-data'])"
                    class="w-6 h-6 hover:text-red-600 cursor-pointer"
                    @click="showDeleteConfirmation"
                />
            </div>
            <div class="flex mt-4">
                <div v-if="!cell.sage_assigned_data">
                    {{ $t('No booking has been linked yet.') }}
                </div>
                <div v-else class="flex flex-col w-full">
                    <!-- index change -->
                    <nav class="w-full h-10 flex items-center border-t border-gray-200 mb-5">
                        <div class="w-1/6 h-10 flex">
                            <div v-show="currentIndex > 0"
                                 @click="currentIndex--"
                                 class="justify-around w-full inline-flex items-center border-t-2 border-transparent text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 cursor-pointer">
                                <IconChevronLeft class="h-4 w-4 text-gray-400" aria-hidden="true" />
                                <span>{{ $t('Previous') }}</span>
                            </div>
                        </div>
                        <div v-if="this.cell.sage_assigned_data.length <= 9" class="w-4/6 h-10 flex justify-center">
                                <span v-for="(index) in maxIndex"
                                      @click="currentIndex = (index - 1)"
                                      class="cursor-pointer inline-flex items-center justify-center border-t-2 px-4 text-sm  text-gray-500 hover:border-gray-300 hover:text-gray-700"
                                      :class="currentIndex === (index - 1) ? 'border-gray-300 text-gray-700 font-bold' : 'border-transparent text-gray-500 font-medium'">
                                    {{ index }}
                                </span>
                        </div>
                        <div v-else class="w-4/6 h-10 flex justify-center">
                            <select v-model="currentIndex" class="h-10">
                                <option v-for="(index) in maxIndex" :key="index" :value="(index - 1)">
                                    {{ index }}
                                </option>
                            </select>
                        </div>
                        <div class="w-1/6 h-10 flex">
                            <div v-show="currentIndex < (maxIndex - 1)"
                                 @click="currentIndex++"
                                 class="justify-around w-full inline-flex items-center border-t-2 border-transparent text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 cursor-pointer">
                                <span>{{ $t('Next')}}</span>
                                <IconChevronRight class="h-4 w-4 text-gray-400" aria-hidden="true"/>
                            </div>
                        </div>
                    </nav>
                    <div v-if="currentSageAssignedData">
                        <BookingModalContents :booking="currentSageAssignedData"/>
                        <div
                            v-if="currentSageAssignedData.find_children.length"
                            class="mt-6 space-y-4"
                        >
                            <div
                                v-for="(childBooking, index) in currentSageAssignedData.find_children"
                                :key="childBooking.id"
                                class="ml-6 border-l-2 border-gray-200 pl-4 rounded"
                            >
                                <div
                                    class="flex items-center justify-between cursor-pointer p-2 bg-gray-50 hover:bg-gray-100 rounded"
                                    @click="toggleChild(childBooking.id)"
                                >
                                  <span class="font-medium">
                                    {{ $t('single booking') }} {{ index + 1 }}
                                  </span>
                                    <template>
                                        <ChevronUpIcon
                                            v-if="isOpen(childBooking.id)"
                                            class="w-5 h-5 text-gray-500"
                                        />
                                        <ChevronDownIcon
                                            v-else
                                            class="w-5 h-5 text-gray-500"
                                        />
                                    </template>
                                </div>
                                <transition name="fade">
                                    <div v-show="isOpen(childBooking.id)" class="mt-2">
                                        <BookingModalContents :booking="childBooking" :is-child="true"/>
                                    </div>
                                </transition>
                            </div>
                        </div>

                        <hr class="mt-6 mb-6"/>

                        <div class="flex flex-col">
              <textarea
                  v-model="bookingDataCommentForm.comment"
                  rows="5"
                  :placeholder="$t('Enter comment')"
                  class="resize-none border-2 border-gray-300 text-md p-4"
              />
                            <div class="flex justify-center mt-6">
                                <FormButton
                                    :disabled="!bookingDataCommentForm.comment"
                                    @click="saveComment(currentSageAssignedData)"
                                    :text="$t('Save')"
                                />
                            </div>
                        </div>

                        <!-- Existing comments unchanged -->
                        <div class="flex flex-col mt-6">
                            <div
                                v-for="comment in currentSageAssignedData.comments"
                                :key="comment.id"
                                class="my-2"
                            >
                                <div class="flex items-center mb-2 text-xs text-gray-500 justify-between">
                                    <div class="flex items-center">
                                        <UserPopoverTooltip
                                            class="mr-1"
                                            :user="comment.user"
                                            :height="5"
                                            :width="5"
                                        />
                                        {{ comment.created_at }}
                                    </div>
                                    <TrashIcon
                                        v-if="$page.props.auth.user.id === comment.user.id"
                                        class="w-6 h-6 hover:text-red-600 cursor-pointer"
                                        @click="removeComment(comment.id)"
                                    />
                                </div>
                                <div class="text-sm">{{ comment.comment }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <ConfirmationComponent
            v-if="showDeleteConfirmationModal"
            @closed="handleDeleteConfirmation"
            :description="$t('Do you really want to put the data set in the trash?', [currentSageAssignedData?.buchungstext])"
            :titel="$t('Move to the trash')"
            :z-index="200"
        />
    </BaseModal>
</template>

<script>
import {defineComponent} from 'vue';
import {XIcon, ChevronDownIcon, ChevronUpIcon} from '@heroicons/vue/outline';
import {TrashIcon} from '@heroicons/vue/solid';
import {router, useForm} from '@inertiajs/vue3';
import Permissions from '@/Mixins/Permissions.vue';
import IconLib from '@/Mixins/IconLib.vue';
import BaseModal from '@/Components/Modals/BaseModal.vue';
import BookingModalContents from '@/Layouts/Components/Budget/BookingModalContents.vue';
import UserPopoverTooltip from '@/Layouts/Components/UserPopoverTooltip.vue';
import FormButton from '@/Layouts/Components/General/Buttons/FormButton.vue';
import CurrencyFloatToStringFormatter from '@/Mixins/CurrencyFloatToStringFormatter.vue';
import ConfirmationComponent from '@/Layouts/Components/ConfirmationComponent.vue';

export default defineComponent({
    components: {
        XIcon,
        ChevronDownIcon,
        ChevronUpIcon,
        TrashIcon,
        BaseModal,
        BookingModalContents,
        UserPopoverTooltip,
        FormButton,
        ConfirmationComponent
    },
    mixins: [Permissions, IconLib, CurrencyFloatToStringFormatter],
    props: ['show', 'cell'],
    emits: ['close', 'budget-updated'],
    data() {
        return {
            bookingDataCommentForm: useForm({
                userId: this.$page.props.auth.user.id,
                sageAssignedDataId: null,
                comment: null
            }),
            currentIndex: 0,
            maxIndex: this.cell.sage_assigned_data.length,
            openChildren: {},
            showDeleteConfirmationModal: false
        };
    },
    computed: {
        currentSageAssignedData() {
            return this.cell.sage_assigned_data[this.currentIndex];
        }
    },
    methods: {
        toggleChild(id) {
            this.openChildren[id] = !this.openChildren[id];
        },
        isOpen(id) {
            return Boolean(this.openChildren[id]);
        },
        formatBookingDataDate(dateString) {
            const [date] = dateString.split('T');
            const [year, month, day] = date.split('-');
            return `${day}.${month}.${year}`;
        },
        formattedAmount(value) {
            return Number(String(value).replace(',', '.')).toLocaleString('de-DE', {
                minimumFractionDigits: 2
            });
        },
        saveComment(sageAssignedData) {
            this.bookingDataCommentForm.sageAssignedDataId = sageAssignedData.id;
            this.bookingDataCommentForm.post(route('sageAssignedDataComments.store'), {
                preserveScroll: true,
                onSuccess: () => {
                    sageAssignedData.comments.unshift(
                        this.$page.props.loadedProjectInformation.BudgetTab.recentlyCreatedSageAssignedDataComment
                    );
                    this.bookingDataCommentForm.reset();
                }
            });
        },
        removeComment(id) {
            router.delete(route('sageAssignedDataComments.destroy', {sageAssignedDataComment: id}), {
                preserveScroll: true,
                onSuccess: () => {
                    const idx = this.currentSageAssignedData.comments.findIndex(c => c.id === id);
                    if (idx > -1) this.currentSageAssignedData.comments.splice(idx, 1);
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
