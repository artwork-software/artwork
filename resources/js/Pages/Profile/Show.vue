<template>
    <app-layout title="Profile">
        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-3 lg:px-5">
                <div v-if="$page.props.jetstream.canUpdateProfileInformation">
                    <form @submit.prevent="updateProfileInformation">
                        <div class="space-y-8 divide-y divide-gray-200">
                            <div>
                                <h2 class="font-bold text-2xl my-2" >Dein Konto</h2>
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
                                    <label class="block text-medium font-medium text-gray-700">
                                        Vorname </label>
                                    <div class="mt-1">
                                        <input type="text" v-model="userForm.first_name"
                                               class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"/>
                                    </div>
                                </div>

                                <div class="sm:col-span-3">
                                    <label class="block text-medium font-medium text-gray-700">
                                        Nachname </label>
                                    <div class="mt-1">
                                        <input type="text" v-model="userForm.last_name"
                                               class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"/>
                                    </div>
                                </div>
                            </div>
                            </div>
                            <div class="pt-8">
                                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                    <div class="sm:col-span-3">
                                        <label class="block text-medium font-medium text-gray-700">
                                            Unternehmen </label>
                                        <div class="mt-1">
                                            <input type="text" v-model="userForm.business"
                                                   class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"/>
                                        </div>
                                    </div>

                                    <div class="sm:col-span-3">
                                        <label class="block text-medium font-medium text-gray-700">
                                            Position </label>
                                        <div class="mt-1">
                                            <input type="text" v-model="userForm.position"
                                                   class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"/>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label class="block text-medium font-medium text-gray-700">
                                            Telefonnummer </label>
                                        <div class="mt-1">
                                            <input type="text" v-model="userForm.phone_number"
                                                   class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"/>
                                        </div>
                                    </div>

                                    <div class="sm:col-span-3">
                                        <label class="block text-medium font-medium text-gray-700">
                                            E-Mail-Adresse </label>
                                        <div class="mt-1">
                                            <input type="text" v-model="userForm.email"
                                                   class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"/>
                                            <jet-input-error :message="userForm.errors.email" class="mt-2" />
                                        </div>
                                    </div>
                                    <div class="sm:col-span-6">
                                        <label class="block text-medium font-medium text-gray-700">
                                            Beschreibung </label>
                                        <div class="mt-1">
                                            <textarea placeholder="Was sollten die anderen ArtWork.tool-User über dich wissen?" v-model="userForm.description" rows="3"
                                                      class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border border-gray-300 rounded-md"/>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-6">
                                        <label for="department" class="block text-medium font-medium text-gray-700">
                                            Abteilung </label>
                                        <div class="mt-1">
                                            <select id="department" v-model="userForm.department"
                                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                                <option>A</option>
                                                <option>B</option>
                                                <option>C</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-5">
                            <div class="flex justify-end">
                                <button type="button"
                                        class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-bold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Abbrechen
                                </button>
                                <button :class="{ 'opacity-25': userForm.processing }" :disabled="userForm.processing" type="submit"
                                        class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-bold rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Speichern
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div v-if="$page.props.jetstream.canUpdatePassword">
                    <h2 class="font-bold text-2xl my-6">Passwort ändern</h2>
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
                        <p class="mt-2 text-sm text-gray-500">Das Passwort muss mind. 8 Zeichen lang sein und mind. 1
                            Ziffer und Groß- und Kleinbuchstaben beinhalten</p>
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

                    <button
                        class="ml-3 mb-6 py-2 px-4 float-right border border-transparent shadow-sm text-sm font-bold rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        :class="{ 'opacity-25': passwordForm.processing }"
                        :disabled="passwordForm.processing">
                        Passwort ändern
                    </button>

                </div>
            </div>
        </div>
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
    },
    props: ['user'],
    data() {
        return {
            userForm: this.$inertia.form({
                _method: 'PUT',
                business: this.user.business,
                position: this.user.position,
                first_name: this.user.first_name,
                last_name: this.user.last_name,
                department: this.user.department,
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
                onSuccess: () => (this.clearPhotoFileInput()),
            });
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
        updatePassword() {
            this.passwordForm.put(route('user-password.update'), {
                errorBag: 'updatePassword',
                preserveScroll: true,
                onSuccess: () => this.passwordForm.reset(),
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
