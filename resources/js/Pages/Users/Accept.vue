<template>
    <div class="py-8 px-64">
        <form class="space-y-6" @submit.prevent="submit">
            <div class="text-2xl font-bold text-black">
                <p>ArtWork.tools</p>
            </div>
            <div class="flex items-center">
                <h2 class="mt-6 text-3xl font-lexend font-bold text-primary">Registrierung</h2>
                <SvgCollection svgName="arrowRight" class="mt-12 ml-2"/>
            </div>
            <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-3">
                    <div class="mt-1">
                        <input
                            v-model="form.first_name"
                            id="first_name" name="name" type="text" required placeholder="Vorname"
                            class="shadow-sm placeholder-secondary subpixel-antialiased focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-200"/>
                    </div>
                </div>
                <div class="sm:col-span-3">
                    <div class="mt-1">
                        <input
                            v-model="form.last_name"
                            id="last_name" name="name" type="text" required placeholder="Name"
                            class="shadow-sm placeholder-secondary subpixel-antialiased focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-200"/>
                    </div>
                </div>
                <div class="sm:col-span-3">
                    <div class="mt-1">
                        <input
                            v-model="form.email" disabled
                            id="email" name="email" type="email" autocomplete="email" required placeholder="E-Mail"
                            class="shadow-sm placeholder-secondary bg-gray-100 subpixel-antialiased focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-200"/>
                    </div>
                </div>
                <div class="sm:col-span-3">
                    <div class="mt-1">
                        <input
                            v-model="form.phone_number"
                            id="phoneNumber" type="text" placeholder="Telefonnummer"
                            class="shadow-sm placeholder-secondary subpixel-antialiased focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-200"/>
                    </div>
                </div>
                <div class="sm:col-span-3">
                    <div class="mt-1">
                        <input
                            v-model="form.business"
                            id="business" type="text" required placeholder="Unternehmen"
                            class="shadow-sm placeholder-secondary subpixel-antialiased focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-200"/>
                    </div>
                </div>

                <div class="sm:col-span-3">
                    <div class="mt-1">
                        <input
                            v-model="form.position"
                            id="position" type="text" required placeholder="Position"
                            class="shadow-sm placeholder-secondary subpixel-antialiased focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-200"/>
                    </div>
                </div>

            </div>

            <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-3">
                    <div class="mt-1">
                        <input
                            v-model="form.password"
                            id="password" name="password" type="password" autocomplete="new-password" required placeholder="Passwort"
                            class="shadow-sm placeholder-secondary subpixel-antialiased focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-200"/>
                    </div>

                </div>
                <div class="sm:col-span-3">
                    <!-- TODO: HIER PASSWORTBALKEN + HINWEIS EINBAUEN -->
                </div>
                <div class="sm:col-span-3">
                    <div class="mt-1">
                        <input
                            v-model="form.password_confirmation"
                            id="password_confirmation" name="password" type="password" autocomplete="new-password" required placeholder="Passwort wiederholen"
                            class="shadow-sm placeholder-secondary subpixel-antialiased focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-200"/>
                    </div>
                </div>
            </div>
            <div>

                <button type="submit"
                        class=" inline-flex items-center px-28 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                        :class="[this.form.email === '' || this.form.password === '' || this.form.first_name === '' || this.form.last_name === '' || this.form.password_confirmation === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                        :disabled="this.form.email === '' || this.form.password === '' || this.form.first_name === '' || this.form.last_name === '' || this.form.password_confirmation === ''"
                >
                    Registrieren
                </button>
            </div>
        </form>
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
import SvgCollection from "@/Layouts/Components/SvgCollection";


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
        SvgCollection
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
