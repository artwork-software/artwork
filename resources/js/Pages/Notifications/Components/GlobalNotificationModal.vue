<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-50" @close="closeModal">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="relative transform overflow-hidden bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">
                            <img src="/Svgs/Overlays/illu_appointment_edit.svg" class="-ml-6 -mt-8 mb-4"/>
                            <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500" @click="closeModal">
                                    <span class="sr-only">Close</span>
                                    <XIcon class="h-6 w-6" aria-hidden="true" />
                                </button>
                            </div>
                            <div class="relative z-40">
                                <div v-if="hasAdminRole() || $canAny(['change system notification'])">

                                    <div class="headline2 mt-12 mb-6">
                                        {{ $t('Notification to all')}}
                                    </div>
                                    <div class="xsLight">
                                        {{ $t('Share something important with all users - e.g. changes or new functions in the artwork or important messages that affect the whole house. All users can view the message in the notifications (including external users!).')}}
                                    </div>
                                    <div>
                                        <label class="block mt-12 mb-2 xsLight">
                                            {{$t('Image')}} </label>
                                        <div class="items-center">
                                            <div
                                                class="flex w-full justify-center border-2 bg-stone-50 w-5/12 border-gray-300 cursor-pointer border-dashed rounded-md p-2"
                                                @click="selectNewNotificationImage"
                                                @dragover.prevent
                                                @drop.stop.prevent="uploadDraggedImage($event)">
                                                <div v-show="!notificationImagePreview" class="space-y-1 text-center">
                                                    <div class="xsLight flex my-auto h-40 items-center"
                                                         v-if="this.globalNotificationForm.notificationImage === null && notificationImagePreview === null">
                                                        {{ $t('Drag your image here for the notification')}}
                                                        <input id="notificationImage-upload" ref="notificationImage"
                                                               @change="updateNotificationImagePreview()"
                                                               name="file-upload" type="file" class="sr-only"/>
                                                    </div>
                                                    <div class="cursor-pointer" v-else>
                                                        <img :src="this.globalNotificationForm.notificationImage" :alt="$t('Current picture')"
                                                             class="rounded-md h-40 w-40">
                                                    </div>
                                                </div>
                                                <div class="cursor-pointer">
                                                    <img v-show="notificationImagePreview" :src="notificationImagePreview"
                                                         :alt="$t('Current banner')"
                                                         class="rounded-md h-40 w-40">
                                                    <input type="file" class="hidden"
                                                           ref="notificationImage"
                                                           @change="updateNotificationImagePreview">
                                                </div>
                                            </div>
                                            <p v-if="uploadDocumentFeedback.length > 0" class="text-xs text-red-500 mt-2">
                                                {{ uploadDocumentFeedback }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4 mt-4">
                                        <div class="">
                                            <label for="deadlineDate" class="flex items-center xsLight">
                                                {{$t('Title')}}:
                                            </label>
                                            <input type="text"
                                                   v-model="this.globalNotificationForm.notificationName"
                                                   id="eventTitle"
                                                   :placeholder="$t('Title')+ '*'"
                                                   class="sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                                        </div>
                                        <div>
                                            <label for="deadlineDate" class="flex items-center xsLight">
                                                {{ $t('Expiration date')}}:
                                            </label>
                                            <div class="flex items-center w-full">
                                                <input v-model="this.globalNotificationForm.notificationDeadlineDate"
                                                       id="deadlineDate"
                                                       type="date"
                                                       required
                                                       class="border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none flex-grow"/>
                                                <input v-model="this.globalNotificationForm.notificationDeadlineTime"
                                                       id="deadlineTime"
                                                       type="time"
                                                       required
                                                       class="border-gray-300 inputMain xsDark placeholder-secondary  disabled:border-none"/>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="py-2">
                                        <textarea :placeholder="$t('Notification') + '*'"
                                                  id="description"
                                                  v-model="this.globalNotificationForm.notificationDescription"
                                                  rows="4"
                                                  class="inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300"/>
                                    </div>

                                    <div class="w-full flex justify-center">
                                        <FormButton
                                            @click="createGlobalNotification()"
                                            v-if="!globalNotification.title"
                                            class="flex"
                                            :text="$t('Share notification')"
                                        />
                                        <FormButton
                                            @click="deleteGlobalNotification(globalNotification.id)"
                                            v-else
                                            class="flex"
                                            :text="$t('Delete notification')"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between mt-5">

                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script>
import {Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot} from '@headlessui/vue'
import {XIcon} from "@heroicons/vue/solid";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

export default {
    name: "GlobalNotificationModal",
    mixins: [Permissions],
    components: {
        FormButton,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        XIcon, DialogPanel
    },
    props: ['globalNotification'],
    data(){
        return {
            open: true,
            notificationImagePreview: null,
            buttonText: this.button ? this.button : this.$t('Delete'),
            globalNotificationForm: this.$inertia.form({
                notificationImage: this.globalNotification?.image_url,
                notificationName: this.globalNotification?.title,
                notificationDeadlineDate: this.globalNotification.expiration_date ? this.getDateOfDate(this.globalNotification.expiration_date) : null,
                notificationDeadlineTime: this.globalNotification.expiration_date ? this.getTimeOfDate(this.globalNotification.expiration_date) : null,
                notificationDescription: this.globalNotification?.description,
            }),
            uploadDocumentFeedback: "",
        }
    },
    emits: ['closed'],
    methods: {
        closeModal(bool){
            this.$emit('closed', bool)
        },
        validateTypeAndUpload(file, type) {
            this.uploadDocumentFeedback = "";

            const allowedTypes = [
                "image/jpeg",
                "image/svg+xml",
                "image/png",
                "image/gif"
            ]

            if (allowedTypes.includes(file?.type)) {

                const reader = new FileReader();

                reader.onload = (e) => {

                    if (type === 'bigLogo') {
                        this.bigLogoPreview = e.target.result;
                        this.form.bigLogo = file
                    }

                    if (type === 'smallLogo') {
                        this.smallLogoPreview = e.target.result;
                        this.form.smallLogo = file
                    }

                    if (type === 'banner') {
                        this.bannerPreview = e.target.result;
                        this.form.banner = file
                    }
                    if (type === 'notificationImage') {
                        this.notificationImagePreview = e.target.result;
                        this.globalNotificationForm.notificationImage = file
                    }

                }

                reader.readAsDataURL(file);

            } else {
                this.uploadDocumentFeedback = this.$t('Only logos and illustrations of the type .jpeg, .svg, .png and .gif are accepted.')
            }
        },
        getDateOfDate(isoDate) {
            if(isoDate.split(' ')[0]){
                return isoDate.split(' ')[0];
            }

        },
        getTimeOfDate(isoDate) {
            if(isoDate.split(' ')[1]){
                return isoDate.split(' ')[1].substring(0, 5);
            }

        },
        uploadDraggedImage(event) {
            this.validateTypeAndUpload(event.dataTransfer.files[0], 'notificationImage')
        },
        selectNewNotificationImage() {
            this.$refs.notificationImage.click();
        },
        updateNotificationImagePreview(){
            this.validateTypeAndUpload(this.$refs.notificationImage.files[0], 'notificationImage')
        },
        createGlobalNotification(){
            this.globalNotificationForm.post(route('global_notification.store'), {
                preserveScroll: true,
                onSuccess: () => {
                    this.closeModal(true)
                },
            });
        },
        deleteGlobalNotification(globalNotificationId){
            this.$inertia.delete(route('global_notification.destroy',globalNotificationId));
            this.globalNotificationForm.notificationImage = null;
            this.globalNotificationForm.notificationName = null;
            this.globalNotificationForm.notificationDeadlineDate = null;
            this.globalNotificationForm.notificationDeadlineTime = null;
            this.globalNotificationForm.notificationDescription = null;
            this.notificationImagePreview = null;
            this.closeModal(true)
        }
    }
}
</script>

<style scoped>

</style>
