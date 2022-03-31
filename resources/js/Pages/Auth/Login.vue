<template>
    <div class="min-h-full flex">
        <div
            class="flex-1 flex min-h-screen flex-col align-items-center justify-center py-12 px-4 sm:px-6 lg:px-20 xl:px-24">
            <div class="mx-auto  w-full max-w-sm lg:w-96">
                <div>
                    <div class="text-2xl font-bold text-black">
                        <p>ArtWork.tools</p>
                    </div>
                    <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Login</h2>
                </div>

                <div class="mt-8">
                    <div class="mt-6">
                        <form class="space-y-6" @submit.prevent="submit">
                            <div>
                                <label for="email" class="block text-sm font-bold text-gray-700">
                                    E-Mail-Adresse
                                </label>
                                <div class="mt-1">
                                    <input
                                        v-model="form.email"
                                        id="email" name="email" type="email" autocomplete="email" required
                                        class="appearance-none block w-full px-4 py-3 border border-gray-300 shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"/>
                                </div>
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-bold text-gray-700">
                                    Passwort
                                </label>
                                <div class="mt-1">
                                    <input
                                        v-model="form.password"
                                        id="password" name="password" type="password" autocomplete="current-password"
                                        required
                                        class="appearance-none block w-full px-4 py-3 border border-gray-300 shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"/>
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <Checkbox class="justify-between text-sm" :item=rememberCheckbox />
                                </div>
                                <div class="text-sm">
                                    <Link v-if="canResetPassword" :href="route('password.request')"
                                          class="text-sm text-secondary subpixel-antialiased hover:font-semibold hover:text-primary">
                                        Passwort vergessen
                                    </Link>
                                </div>
                            </div>

                            <div>
                                <button type="submit"
                                        :class="[this.form.email === '' || this.form.password === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                                        class=" inline-flex items-center px-40 py-3 border border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                                        :disabled="this.form.email === '' || this.form.password === ''">Login
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="hidden lg:block relative w-0 flex-1">
            <img class="absolute inset-0 h-full w-full object-cover"
                 src="https://images.unsplash.com/photo-1505904267569-f02eaeb45a4c?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1908&q=80"
                 alt=""/>
        </div>
    </div>
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
import Checkbox from "@/Layouts/Components/Checkbox";


const rememberCheckbox = {name: 'Angemeldet bleiben', checked: false, showIcon: false}

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
        Checkbox
    },
    props: {
        canResetPassword: Boolean,
        status: String
    },

    data() {
        return {
            form: this.$inertia.form({
                email: '',
                password: '',
                remember: false
            })
        }
    },

    methods: {
        submit() {
            this.form
                .transform(data => ({
                    ...data,
                    remember: this.rememberCheckbox.checked ? 'on' : ''
                }))
                .post(this.route('login'), {
                    onFinish: () => this.form.reset('password'),
                })
        }
    },
    setup() {
        return {
            rememberCheckbox
        }
    }
})
</script>
