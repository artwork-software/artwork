<template>
    <jet-dialog-modal :show="this.show" @close="this.close">
        <template #content>
            <img src="/Svgs/Overlays/illu_project_edit.svg" class="-ml-6 -mt-8 mb-4" alt="artwork"/>
            <div class="mx-4">
                <XIcon @click="this.close"
                       class="h-5 w-5 flex text-secondary cursor-pointer absolute right-0 mr-10"
                       aria-hidden="true"/>
                <div class="mt-8 headline1">
                    Buchungsdetails
                </div>
                <div class="flex mt-4">
                    <div v-if="this.cell.sage_assigned_data === null">
                        Bisher wurde noch keine Buchung verknüpft.
                    </div>
                    <div v-else class="flex flex-col w-full">
                        <div class="grid grid-cols-2">
                            <span class="xsLight">Kreditor</span>
                            <span class="xsLight text-black">{{ this.cell.sage_assigned_data.kreditor }}</span>
                            <span class="xsLight">Betrag</span>
                            <span class="xsLight text-black">{{ this.cell.sage_assigned_data.buchungsbetrag }} EUR</span>
                            <span class="xsLight">Buchungstext</span>
                            <span class="xsLight text-black">{{ this.cell.sage_assigned_data.buchungstext }}</span>

                            <span class="xsLight mt-4">Belegnummer</span>
                            <span class="xsLight text-black mt-4">{{ this.cell.sage_assigned_data.belegnummer }}</span>
                            <span class="xsLight">Belegdatum</span>
                            <span class="xsLight text-black">
                                {{ this.formatBookingDataDate(this.cell.sage_assigned_data.belegdatum) }}
                            </span>

                            <span class="xsLight mt-4">Sachkonto</span>
                            <span class="xsLight text-black mt-4">{{ this.cell.sage_assigned_data.sa_kto }}</span>
                            <span class="xsLight">Kostenträger</span>
                            <span class="xsLight text-black">{{ this.cell.sage_assigned_data.kst_traeger }}</span>
                            <span class="xsLight">Kostenstelle</span>
                            <span class="xsLight text-black">{{ this.cell.sage_assigned_data.kst_stelle }}</span>

                            <span class="xsLight mt-4">Buchungsdatum</span>
                            <span class="xsLight text-black mt-4">
                                {{ this.formatBookingDataDate(this.cell.sage_assigned_data.buchungsdatum) }}
                            </span>
                        </div>
                        <hr class="mt-6 mb-6"/>
                        <div class="flex flex-col">
                            <textarea v-model="this.bookingDataCommentForm.comment"
                                      rows="5"
                                      placeholder="Kommentar eingeben"
                                      class="resize-none border-2 border-gray-300 text-md p-4"
                            />
                            <div class="flex justify-center mt-6">
                                <AddButton :disabled="
                                                this.bookingDataCommentForm.comment === null ||
                                                this.bookingDataCommentForm.comment === ''
                                           "
                                           @click="this.saveComment()"
                                           text="Speichern"
                                           mode="modal"
                                />
                            </div>
                        </div>
                        <div class="flex flex-col mt-6">
                            <div v-for="comment in this.cell.sage_assigned_data.comments" class="my-2">
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
        </template>
    </jet-dialog-modal>
</template>

<script>
import {defineComponent} from 'vue';
import {XIcon} from "@heroicons/vue/outline";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {useForm} from "@inertiajs/inertia-vue3";
import AddButton from "@/Layouts/Components/AddButton.vue";
import {TrashIcon} from "@heroicons/vue/solid";
import Permissions from "@/mixins/Permissions.vue";
import {Inertia} from "@inertiajs/inertia";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";

export default defineComponent({
    components: {
        UserPopoverTooltip,
        AddButton,
        JetDialogModal,
        XIcon,
        TrashIcon
    },
    mixins: [
        Permissions
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
                sageAssignedDataId: this.cell.sage_assigned_data ? this.cell.sage_assigned_data.id : null,
                comment: null
            })
        }
    },
    methods: {
        formatBookingDataDate(dateString) {
            let parts = dateString.split('T');
            parts = parts[0].split('-');

            return parts[2] + '.' + parts[1] + '.' + parts[0];
        },
        saveComment() {
            this.bookingDataCommentForm.post(
                route('sageAssignedDataComments.store'),
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        //add created comment at first position in comments
                        this.cell.sage_assigned_data.comments.unshift(
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
