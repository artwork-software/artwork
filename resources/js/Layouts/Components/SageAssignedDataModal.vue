<template>
    <jet-dialog-modal :show="this.show" @close="this.close">
        <template #content>
            <img src="/Svgs/Overlays/illu_project_edit.svg" class="-ml-6 -mt-8 mb-4" alt="artwork"/>
            <div class="mx-4">
                <XIcon @click="this.close"
                       class="h-5 w-5 flex text-secondary cursor-pointer absolute right-0 mr-10"
                       aria-hidden="true"/>
                <div class="mt-8 headline1">
                    {{ $t('Booking details') }}
                </div>
                <div class="flex mt-4">
                    <div v-if="this.cell.sage_assigned_data === null">
                        {{ $t('No booking has been linked yet.') }}
                    </div>
                    <div v-else class="flex flex-col w-full">

                        <!-- index change -->
                        <nav class="flex items-center justify-between border-t border-gray-200 px-4 sm:px-0 mb-5">
                            <div class="-mt-px flex w-0 flex-1">
                                <div v-if="currentIndex > 0" @click="currentIndex--" class="inline-flex items-center border-t-2 border-transparent pr-1 pt-4 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 cursor-pointer">
                                    <IconChevronLeft class="mr-3 h-5 w-5 text-gray-400" aria-hidden="true" />
                                    {{ $t('Previous') }}
                                </div>
                            </div>
                            <div class="hidden md:-mt-px md:flex">
                                <span v-for="(index, position) in maxIndex" @click="currentIndex = position" class="cursor-pointer inline-flex items-center border-t-2 border-transparent px-4 pt-4 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700" :class="currentIndex === position ? 'text-artwork-buttons-create font-extrabold' : ''">
                                    {{ index }}
                                </span>
                            </div>
                            <div class="-mt-px flex w-0 flex-1 justify-end">
                                <div v-if="currentIndex < maxIndex -1" @click="currentIndex++" class="inline-flex items-center border-t-2 border-transparent pl-1 pt-4 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 cursor-pointer">
                                    {{ $t('Next')}}
                                    <IconChevronRight class="ml-3 h-5 w-5 text-gray-400" aria-hidden="true"  />
                                </div>
                            </div>
                        </nav>

                        <div v-for="(item, position) in cell.sage_assigned_data">
                            <div v-show="currentIndex === position">
                                <div class="grid grid-cols-2">
                                    <span class="xsLight">{{ $t('Creditor') }}</span>
                                    <span class="xsLight text-black">{{ item.kreditor }}</span>
                                    <span class="xsLight">{{ $t('Amount') }}</span>
                                    <span class="xsLight text-black">{{ item.buchungsbetrag }} EUR</span>
                                    <span class="xsLight">{{ $t('Booking text') }}</span>
                                    <span class="xsLight text-black">{{ item.buchungstext }}</span>

                                    <span class="xsLight mt-4">{{ $t('Document number') }}</span>
                                    <span class="xsLight text-black mt-4">{{ item.belegnummer }}</span>
                                    <span class="xsLight">{{ $t('Document date') }}</span>
                                    <span class="xsLight text-black">
                                {{ this.formatBookingDataDate(item.belegdatum) }}
                            </span>

                                    <span class="xsLight mt-4">{{ $t('General ledger account') }}</span>
                                    <span class="xsLight text-black mt-4">{{ item.sa_kto }}</span>
                                    <span class="xsLight">{{ $t('Cost bearer') }}</span>
                                    <span class="xsLight text-black">{{ item.kst_traeger }}</span>
                                    <span class="xsLight">{{ $t('Cost center') }}</span>
                                    <span class="xsLight text-black">{{ item.kst_stelle }}</span>

                                    <span class="xsLight mt-4">{{ $t('Booking date') }}</span>
                                    <span class="xsLight text-black mt-4">
                                {{ this.formatBookingDataDate(item.buchungsdatum) }}
                            </span>
                                </div>
                                <hr class="mt-6 mb-6"/>
                                <div class="flex flex-col">
                            <textarea v-model="this.bookingDataCommentForm.comment"
                                      rows="5"
                                      :placeholder="$t('Enter comment')"
                                      class="resize-none border-2 border-gray-300 text-md p-4"
                            />
                                    <div class="flex justify-center mt-6">
                                        <FormButton :disabled="
                                                this.bookingDataCommentForm.comment === null ||
                                                this.bookingDataCommentForm.comment === ''
                                           "
                                                    @click="this.saveComment(item)"
                                                    :text="$t('Save')"
                                        />
                                    </div>
                                </div>
                                <div class="flex flex-col mt-6">
                                    <div v-for="comment in item.comments" class="my-2">
                                        <div class="flex flex-row items-center mb-2 text-xs text-gray-500 justify-between">
                                            <div class="flex flex-row items-center">
                                                <UserPopoverTooltip class="mr-1"
                                                                    :user="comment.user"
                                                                    :height="5"
                                                                    :width="5"
                                                />
                                                {{ comment.created_at }}
                                            </div>
                                            <TrashIcon v-if="this.$page.props.user.id === comment.user.id"
                                                       class="w-6 h-6 hover:text-red-600 cursor-pointer"
                                                       @click="this.removeComment(comment.id)"
                                            />
                                        </div>
                                        <div class="text-sm">
                                            {{ comment.comment }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
</template>

<script>
import {defineComponent} from 'vue';
import {XIcon} from "@heroicons/vue/outline";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {useForm} from "@inertiajs/inertia-vue3";
import {TrashIcon} from "@heroicons/vue/solid";
import Permissions from "@/mixins/Permissions.vue";
import {Inertia} from "@inertiajs/inertia";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/mixins/IconLib.vue";

export default defineComponent({
    components: {
        FormButton,
        UserPopoverTooltip,
        JetDialogModal,
        XIcon,
        TrashIcon
    },
    mixins: [
        Permissions, IconLib
    ],
    props: [
        'show',
        'cell',
    ],
    emits: [
        'close'
    ],
    data() {
        return {
            bookingDataCommentForm: useForm({
                userId: this.$page.props.user.id,
                sageAssignedDataId: null,
                comment: null
            }),
            currentIndex: 0,
            maxIndex: this.cell.sage_assigned_data.length
        }
    },
    methods: {
        formatBookingDataDate(dateString) {
            let parts = dateString.split('T');
            parts = parts[0].split('-');

            return parts[2] + '.' + parts[1] + '.' + parts[0];
        },
        saveComment(SageData) {
            this.bookingDataCommentForm.sageAssignedDataId = SageData.id;
            this.bookingDataCommentForm.post(
                route('sageAssignedDataComments.store'),
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        //add created comment at first position in comments
                        SageData.comments.unshift(
                            this.$page.props.recentlyCreatedSageAssignedDataComment
                        );
                        this.bookingDataCommentForm.reset();
                    }
                }
            )
        },
        removeComment(id) {
            Inertia.delete(
                route('sageAssignedDataComments.destroy', {sageAssignedDataComment: id}),
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        let indexOfRemovedElement = this.cell.sage_assigned_data.comments.findIndex((comment) => {
                            return comment.id === id;
                        });

                        this.cell.sage_assigned_data.comments.splice(indexOfRemovedElement, 1);
                    }
                }
            );
        },
        close() {
            this.$emit('close');
        }
    },
});
</script>
