<template>
    <Head title="Register"/>

    <jet-authentication-card>
        <template #AuthHeading>
            <h2 class="mt-6 text-center text-4xl font-bold text-gray-900">
                Account erstellen
            </h2>
        </template>

        <jet-validation-errors class="mb-4"/>
        <div class="py-8 px-4">
            <form class="space-y-6" @submit.prevent="submit">
                <div class="text-2xl font-bold text-black">
                    <p>ArtWork.tools</p>
                </div>
                <div>
                    <label for="first_name" class="block text-sm font-bold text-gray-700">
                        Vorname
                    </label>
                    <div class="mt-1">
                        <input
                            v-model="form.first_name"
                            id="first_name" name="name" type="text" required
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"/>
                    </div>
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-bold text-gray-700">
                        Nachname
                    </label>
                    <div class="mt-1">
                        <input
                            v-model="form.last_name"
                            id="last_name" name="name" type="text" required
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"/>
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-bold text-gray-700">
                        E-Mail-Adresse
                    </label>
                    <div class="mt-1">
                        <input
                            v-model="form.email" disabled
                            id="email" name="email" type="email" autocomplete="email" required
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 bg-gray-100 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"/>
                    </div>
                </div>

                <div>
                    <label for="business" class="block text-sm font-bold text-gray-700">
                        Unternehmen
                    </label>
                    <div class="mt-1">
                        <input
                            v-model="form.business"
                            id="business" type="text" required
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"/>
                    </div>
                </div>

                <div>
                    <label for="position" class="block text-sm font-bold text-gray-700">
                        Position
                    </label>
                    <div class="mt-1">
                        <input
                            v-model="form.position"
                            id="position" type="text" required
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"/>
                    </div>
                </div>

                <div>
                    <label for="phoneNumber" class="block text-sm font-bold text-gray-700">
                        Telefonnummer
                    </label>
                    <div class="mt-1">
                        <input
                            v-model="form.phone_number"
                            id="phoneNumber" type="text"
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"/>
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-bold text-gray-700">
                        Passwort
                    </label>
                    <div class="mt-1">
                        <input
                            v-model="form.password"
                            id="password" name="password" type="password" autocomplete="new-password" required
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" />
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-bold text-gray-700">
                        Passwort wiederholen
                    </label>
                    <div class="mt-1">
                        <input
                            v-model="form.password_confirmation"
                            id="password_confirmation" name="password" type="password" autocomplete="new-password" required
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" />
                    </div>
                </div>

                <div>

                    <button type="submit" :class="[this.form.email === '' || this.form.password === '' ? 'bg-gray-400': 'bg-indigo-900 hover:bg-indigo-700 focus:outline-none']" class=" inline-flex items-center px-40 py-3 border border-transparent text-base font-bold text-xl uppercase shadow-sm text-white"
                            :disabled="this.form.email === '' || this.form.password === ''">Registrieren</button>
                </div>
            </form>
        </div>
    </jet-authentication-card>
</template>

<script>
import {defineComponent} from 'vue'
import JetAuthenticationCard from '@/Jetstream/AuthenticationCard.vue'
import JetAuthenticationCardLogo from '@/Jetstream/AuthenticationCardLogo.vue'
import JetButton from '@/Jetstream/Button.vue'
import JetInput from '@/Jetstream/Input.vue'
import JetCheckbox from '@/Jetstream/Checkbox.vue'
import JetLabel from '@/Jetstream/Label.vue'
import JetValidationErrors from '@/Jetstream/ValidationErrors.vue'
import {Head, Link} from '@inertiajs/inertia-vue3';

export default defineComponent({
    components: {
        Head,
        JetAuthenticationCard,
        JetAuthenticationCardLogo,
        JetButton,
        JetInput,
        JetCheckbox,
        JetLabel,
        JetValidationErrors,
        Link,
    },
    props: ['user','email','token'],
    data() {
        return {
            form: this.$inertia.form({
                _method: 'POST',
                first_name: '',
                last_name:'',
                email: this.email,
                business:'',
                position:'',
                phone_number:'',
                password: '',
                password_confirmation: '',
                token: this.token
            })
        }
    },

    methods: {
        submit() {
            this.form.post(this.route('invitation.accept'), {
                onFinish: () => this.form.reset('password', 'password_confirmation'),
            })
        }
    }
})
</script>
