<template>
    <BaseModal @closed="close" v-if="show" modal-image="/Svgs/Overlays/illu_project_edit.svg">
            <div class="mx-4">
                <div class="mt-8 headline1">
                    {{ $t('Booking details') }}
                </div>
                <div class="flex mt-4">
                    <div v-if="this.cell.sage_assigned_data === null">
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
                        <div v-if="this.currentSageAssignedData">
                            <div class="grid grid-cols-2">
                                <span class="xsLight">{{ $t('Creditor') }}</span>
                                <span class="xsLight text-black">{{ this.currentSageAssignedData.kreditor }}</span>
                                <span class="xsLight">{{ $t('Amount') }}</span>
                                <span class="xsLight text-black">{{
                                        Number(
                                            String(this.currentSageAssignedData.buchungsbetrag).replace(',', '.')
                                        ).toLocaleString(
                                            'de-DE',
                                            {
                                                minimumFractionDigits: 2
                                            }
                                        )
                                    }} EUR</span>
                                <span class="xsLight">{{ $t('Booking text') }}</span>
                                <span class="xsLight text-black">{{ this.currentSageAssignedData.buchungstext }}</span>

                                <span class="xsLight mt-4">{{ $t('Document number') }}</span>
                                <span class="xsLight text-black mt-4">{{ this.currentSageAssignedData.belegnummer }}</span>
                                <span class="xsLight">{{ $t('Document date') }}</span>
                                <span class="xsLight text-black">
                                    {{ this.formatBookingDataDate(this.currentSageAssignedData.belegdatum) }}
                                </span>

                                <span class="xsLight mt-4">{{ $t('General ledger account') }}</span>
                                <span class="xsLight text-black mt-4">{{ this.currentSageAssignedData.sa_kto }}</span>
                                <span class="xsLight">{{ $t('Cost bearer') }}</span>
                                <span class="xsLight text-black">{{ this.currentSageAssignedData.kst_traeger }}</span>
                                <span class="xsLight">{{ $t('Cost center') }}</span>
                                <span class="xsLight text-black">{{ this.currentSageAssignedData.kst_stelle }}</span>

                                <span class="xsLight mt-4">{{ $t('Booking date') }}</span>
                                <span class="xsLight text-black mt-4">
                                    {{ this.formatBookingDataDate(this.currentSageAssignedData.buchungsdatum) }}
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
                                                @click="this.saveComment(this.currentSageAssignedData)"
                                                :text="$t('Save')"
                                    />
                                </div>
                            </div>
                            <div class="flex flex-col mt-6">
                                <div v-for="comment in this.currentSageAssignedData.comments" class="my-2">
                                    <div class="flex flex-row items-center mb-2 text-xs text-gray-500 justify-between">
                                        <div class="flex flex-row items-center">
                                            <UserPopoverTooltip class="mr-1"
                                                                :user="comment.user"
                                                                :height="5"
                                                                :width="5"
                                            />
                                            {{ comment.created_at }}
                                        </div>
                                        <IconTrash v-if="this.$page.props.user.id === comment.user.id"
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
    </BaseModal>
</template>

<script>
import {defineComponent} from 'vue';
import {XIcon} from "@heroicons/vue/outline";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {useForm} from "@inertiajs/vue3";
import {TrashIcon} from "@heroicons/vue/solid";
import Permissions from "@/Mixins/Permissions.vue";
import {router} from "@inertiajs/vue3";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default defineComponent({
    components: {
        BaseModal,
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
    computed: {
        currentSageAssignedData() {
            return this.cell.sage_assigned_data[this.currentIndex];
        }
    },
    methods: {
        formatBookingDataDate(dateString) {
            let parts = dateString.split('T');
            parts = parts[0].split('-');

            return parts[2] + '.' + parts[1] + '.' + parts[0];
        },
        saveComment(sageAssignedData) {
            this.bookingDataCommentForm.sageAssignedDataId = sageAssignedData.id;
            this.bookingDataCommentForm.post(
                route('sageAssignedDataComments.store'),
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        //add created comment at first position in comments
                        sageAssignedData.comments.unshift(
                            this.$page.props.recentlyCreatedSageAssignedDataComment
                        );
                        this.bookingDataCommentForm.reset();
                    }
                }
            )
        },
        removeComment(id) {
            router.delete(
                route('sageAssignedDataComments.destroy', {sageAssignedDataComment: id}),
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        let indexOfRemovedElement = this.currentSageAssignedData.comments.findIndex((comment) => {
                            return comment.id === id;
                        });

                        this.currentSageAssignedData.comments.splice(indexOfRemovedElement, 1);
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
