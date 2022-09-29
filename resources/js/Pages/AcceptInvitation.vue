<template>
    <Head title="Register"/>

    <jet-authentication-card>
            <h2 class="mt-6 text-center text-3xl font-bold text-gray-900">
                Einladung annehmen
            </h2>
        <jet-validation-errors class="mb-4"/>
        <div class="py-8 px-4">
            <form class="space-y-6" @submit.prevent="submit">
                <div class="text-2xl text-center font-bold text-black">
                    <img src="/Svgs/Logos/artwork_logo_big.svg"/>
                </div>
                <div>
                    <label for="email" class="block text-sm font-bold text-gray-700">
                        Name
                    </label>
                    <div class="mt-1">
                        <input
                            v-model="form.name"
                            id="name" name="name" type="text" autocomplete="name" required
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"/>
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-bold text-gray-700">
                        E-Mail-Adresse
                    </label>
                    <div class="mt-1">
                        <input
                            v-model="form.email"
                            id="email" name="email" type="email" autocomplete="email" required
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"/>
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
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"/>
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-bold text-gray-700">
                        Passwort wiederholen
                    </label>
                    <div class="mt-1">
                        <input
                            v-model="form.password_confirmation"
                            id="password_confirmation" name="password" type="password" autocomplete="new-password"
                            required
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"/>
                    </div>
                </div>
                <div>

                    <button type="submit"
                            class="w-full flex justify-center
                    py-3 px-4 border border-transparent rounded-md shadow-sm
                    text-sm font-bold text-white
                    bg-blue-500 hover:bg-dark-primary
                    focus:outline-none focus:ring-2 focus:ring-offset-2
                    focus:ring-primary"
                            :disabled="form.processing"
                            :class="{ 'opacity-25': form.processing }"
                    >
                        Registrieren
                    </button>
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
    props: ['user'],
    data() {
        return {
            form: this.$inertia.form({
                _method: 'POST',
                name: '',
                email: '',
                business: '',
                position: '',
                phone_number: '',
                password: '',
                password_confirmation: '',
            })
        }
    },

    methods: {
        submit() {
            if (this.$refs.logo) {
                this.form.logo = this.$refs.logo.files[0]
            }
            if (this.$refs.banner) {
                this.form.banner = this.$refs.banner.files[0]
            }
            this.form.post(this.route(''), {
                onFinish: () => this.form.reset('password', 'password_confirmation'),
            })
        }
    }
})
</script>
