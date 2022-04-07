<template>
    <app-layout title="Profile">
        <div>
            <div class="max-w-7xl mx-auto py-4 sm:px-3 lg:px-5">
                <div v-if="$page.props.jetstream.canUpdateProfileInformation">
                    <form @submit.prevent="updateProfileInformation">
                        <div>
                            <div>
                                <h2 class="font-bold text-2xl my-2">Dein Konto</h2>
                                <div class="col-span-6 sm:col-span-4">
                                    <!-- Profile Photo File Input -->
                                    <input type="file" class="hidden"
                                           ref="photo"
                                           @change="updatePhotoPreview">

                                    <div class="mt-1 flex items-center">
                                        <!-- Current Profile Photo -->
                                        <div class="mt-2" v-show="! photoPreview">
                                            <img :src="user.profile_photo_url" :alt="user.first_name"
                                                 class="rounded-full h-20 w-20 object-cover">
                                        </div>

                                        <!-- New Profile Photo Preview -->
                                        <div class="mt-1 flex items-center">
                                            <div class="mt-2" v-show="photoPreview">
                            <span class="block rounded-full w-20 h-20 bg-cover bg-no-repeat bg-center"
                                  :style="'background-image: url(\'' + photoPreview + '\');'">
                            </span>
                                            </div>
                                        </div>
                                        <jet-secondary-button class="mt-2 mr-2 ml-3" type="button"
                                                              @click.prevent="selectNewPhoto">
                                            Profilbild ändern
                                        </jet-secondary-button>
                                        <jet-secondary-button type="button" class="mt-2" @click.prevent="deletePhoto"
                                                              v-if="user.profile_photo_path">
                                            Profilbild löschen
                                        </jet-secondary-button>

                                        <jet-input-error :message="userForm.errors.photo" class="mt-2"/>
                                    </div>
                                </div>
                                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                    <div class="sm:col-span-3">
                                        <div class="mt-1">
                                            <input type="text" v-model="userForm.first_name" placeholder="Vorname"
                                                   class="shadow-sm focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-300 "/>
                                        </div>
                                    </div>

                                    <div class="sm:col-span-3">
                                        <div class="mt-1">
                                            <input type="text" v-model="userForm.last_name" placeholder="Nachname"
                                                   class="shadow-sm focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-300 "/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                    <div class="sm:col-span-3">
                                        <div class="mt-1">
                                            <input type="text" v-model="userForm.business" placeholder="Unternehmen"
                                                   class="shadow-sm focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-300 "/>
                                        </div>
                                    </div>

                                    <div class="sm:col-span-3">
                                        <div class="mt-1">
                                            <input type="text" v-model="userForm.position" placeholder="Position"
                                                   class="shadow-sm focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-300 "/>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-3">
                                        <div class="mt-1">
                                            <input type="text" v-model="userForm.email" placeholder="E-Mail-Adresse"
                                                   class="shadow-sm focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-300 "/>
                                            <jet-input-error :message="userForm.errors.email" class="mt-2"/>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-3">
                                        <div class="mt-1">
                                            <input type="text" v-model="userForm.phone_number"
                                                   placeholder="Telefonnummer"
                                                   class="shadow-sm focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-300 "/>
                                        </div>
                                    </div>


                                    <div class="sm:col-span-6">
                                        <div class="mt-1">
                                            <textarea
                                                placeholder="Was sollten die anderen ArtWork.tool-User über dich wissen?"
                                                v-model="userForm.description" rows="3"
                                                class="shadow-sm focus:ring-black focus:border-black border-2 block w-full sm:text-sm border border-gray-300 "/>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-6">
                                        <div class="sm:col-span-6 ml-3 flex inline-flex">
                                            <span v-if="userForm.departments.length === 0"
                                                  class="text-secondary subpixel-antialiased my-auto">In keinem Team </span>
                                            <span v-else class="flex mt-3 -ml-3"
                                                  v-for="(team,index) in userForm.departments">
                                            <!--TODO: :src="team.logo_url" -->
                                            <img class="h-14 w-14 rounded-full flex justify-start"
                                                 src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                                                 alt=""/>
                                        </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-5">
                            <div class="flex justify-end">
                                <button type="submit"
                                        class=" inline-flex items-center px-8 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                                >Profil-Änderungen speichern
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div v-if="$page.props.jetstream.canUpdatePassword">
                    <h2 class="font-bold text-2xl my-6">Dein Passwort</h2>
                    <div class="col-span-6 sm:col-span-4 my-4">
                        <label for="current_password" class="font-medium"> Aktuelles Passwort</label>
                        <jet-input id="current_password" type="password" class="mt-1 block w-full"
                                   v-model="passwordForm.current_password" ref="current_password"
                                   autocomplete="current-password"/>
                        <jet-input-error :message="passwordForm.errors.current_password" class="mt-2"/>
                    </div>

                    <div class="col-span-6 sm:col-span-4 my-4">
                        <label for="password" class="font-medium"> Neues Passwort</label>
                        <jet-input id="password" type="password" class="mt-1 block w-full"
                                   v-model="passwordForm.password"
                                   ref="password" autocomplete="new-password"/>
                        <jet-input-error :message="passwordForm.errors.password" class="mt-2"/>
                        <p class="mt-2 text-sm text-gray-500">Das Passwort muss mind. 10 Zeichen lang sein und mind. 1
                            Ziffer und Groß- und Kleinbuchstaben beinhalten</p>

                        <span class="font-patrick text-help">Das Passwort muss mind. 8 Zeichen lang sein, mind. 1 Ziffer und Groß- und Kleinbuchstaben beinhalten</span>
                        <br>
                        <span class="font-nanum text-help">Das Passwort muss mind. 8 Zeichen lang sein, mind. 1 Ziffer und Groß- und Kleinbuchstaben beinhalten</span>

                    </div>

                    <div class="col-span-6 sm:col-span-4 my-4">
                        <label for="password_confirmation" class="font-medium"> Neues Passwort bestätigen</label>
                        <jet-input id="password_confirmation" type="password" class="mt-1 block w-full"
                                   v-model="passwordForm.password_confirmation" autocomplete="new-password"/>
                        <jet-input-error :message="passwordForm.errors.password_confirmation" class="mt-2"/>
                    </div>

                    <jet-action-message :on="passwordForm.recentlySuccessful" class="mr-3">
                        Saved.
                    </jet-action-message>

                    <button @click="updatePassword"
                            class=" inline-flex items-center px-8 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                    >Passwort ändern
                    </button>

                </div>
            </div>
        </div>
        <!-- Success Modal -->
        <jet-dialog-modal :show="showSuccessModal" @close="closeSuccessModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold text-primary text-2xl my-2">
                        Änderungen gespeichert
                    </div>
                    <XIcon @click="closeSuccessModal" class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer" aria-hidden="true" />
                    <div class="text-success" v-if="successType === 'profile'">
                        Deine Profildaten wurden erfolgreich geändert.
                    </div>
                    <div class="text-success" v-if="successType === 'password'">
                        Dein Passwort wurde erfolgreich geändert.
                    </div>
                    <div class="mt-6">
                        <button class="bg-success focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="closeSuccessModal">
                            <CheckIcon class="h-4 w-4 text-secondaryHover"/>
                        </button>
                    </div>
                </div>

            </template>

        </jet-dialog-modal>
    </app-layout>
</template>

<script>
import {defineComponent} from 'vue'
import JetActionMessage from '@/Jetstream/ActionMessage.vue'
import JetButton from '@/Jetstream/Button.vue'
import JetFormSection from '@/Jetstream/FormSection.vue'
import JetInput from '@/Jetstream/Input.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import JetLabel from '@/Jetstream/Label.vue'
import AppLayout from "@/Layouts/AppLayout";
import DeleteUserForm from "@/Pages/Profile/Partials/DeleteUserForm";
import JetSectionBorder from "@/Jetstream/SectionBorder";
import LogoutOtherBrowserSessionsForm from "@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm";
import TwoFactorAuthenticationForm from "@/Pages/Profile/Partials/TwoFactorAuthenticationForm";
import UpdatePasswordForm from "@/Pages/Profile/Partials/UpdatePasswordForm";
import UpdateProfileInformationForm from "@/Pages/Profile/Partials/UpdateProfileInformationForm";
import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
import {CheckIcon} from "@heroicons/vue/solid";
import JetDialogModal from '@/Jetstream/DialogModal.vue'

export default defineComponent({
    components: {
        JetActionMessage,
        JetButton,
        JetFormSection,
        JetInput,
        JetInputError,
        JetLabel,
        AppLayout,
        DeleteUserForm,
        JetSectionBorder,
        LogoutOtherBrowserSessionsForm,
        TwoFactorAuthenticationForm,
        UpdatePasswordForm,
        UpdateProfileInformationForm,
        JetSecondaryButton,
        CheckIcon,
        JetDialogModal
    },
    props: ['user', 'departments'],
    data() {
        return {
            userForm: this.$inertia.form({
                _method: 'PUT',
                business: this.user.business,
                position: this.user.position,
                first_name: this.user.first_name,
                last_name: this.user.last_name,
                departments: this.departments,
                phone_number: this.user.phone_number,
                email: this.user.email,
                description: this.user.description,
                photo: this.user.profile_photo_path,
            }),
            passwordForm: this.$inertia.form({
                current_password: '',
                password: '',
                password_confirmation: '',
            }),
            photoPreview: null,
            showSuccessModal: false,
            successType: ''
        }
    },

    methods: {
        updateProfileInformation() {
            if (this.$refs.photo) {
                this.userForm.photo = this.$refs.photo.files[0]
            }

            this.userForm.post(route('user-profile-information.update'), {
                errorBag: 'updateProfileInformation',
                preserveScroll: true,
                onSuccess: () => (this.clearPhotoFileInput(),this.openSuccessModal("profile")),
            });
        },
        openSuccessModal(successType){
          this.showSuccessModal = true;
          // 'profile' or 'password'
          this.successType = successType;
        },
        closeSuccessModal(){
          this.showSuccessModal = false;
          this.successType = '';
        },
        selectNewPhoto() {
            this.$refs.photo.click();
        },

        updatePhotoPreview() {
            const photo = this.$refs.photo.files[0];

            if (!photo) return;

            const reader = new FileReader();

            reader.onload = (e) => {
                this.photoPreview = e.target.result;
            };

            reader.readAsDataURL(photo);
        },

        deletePhoto() {
            this.$inertia.delete(route('current-user-photo.destroy'), {
                preserveScroll: true,
                onSuccess: () => {
                    this.photoPreview = null;
                    this.clearPhotoFileInput();
                },
            });
        },

        clearPhotoFileInput() {
            if (this.$refs.photo?.value) {
                this.$refs.photo.value = null;
            }
        },
        deleteTeamFromDepartmentsArray(index) {
            this.userForm.departments.splice(index, 1);
        },
        updatePassword() {
            this.passwordForm.put(route('user-password.update'), {
                errorBag: 'updatePassword',
                preserveScroll: true,
                onSuccess: () => (this.passwordForm.reset(),this.openSuccessModal('password')),
                onError: () => {
                    if (this.passwordForm.errors.password) {
                        this.passwordForm.reset('password', 'password_confirmation')
                        this.$refs.password.focus()
                    }

                    if (this.passwordForm.errors.current_password) {
                        this.passwordForm.reset('current_password')
                        this.$refs.current_password.focus()
                    }
                }
            })
        },
    },
})
</script>
