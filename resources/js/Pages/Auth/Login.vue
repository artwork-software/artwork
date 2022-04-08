<template>
    <div class="min-h-full flex">
        <div
            class="flex-1 flex min-h-screen flex-col align-items-center justify-center py-12 px-4 sm:px-6 lg:px-20 xl:px-24">
            <div class="mx-auto  w-full max-w-sm lg:w-96">
                <div>
                    <div class="text-2xl font-bold text-black">
                        <p>ArtWork.tools</p>
                    </div>
                    <div class="flex items-center">
                    <h2 class="mt-6 text-3xl font-lexend font-bold text-primary">Login</h2>
                        <SvgCollection svgName="arrowRight" class="mt-12 ml-2"/>
                    </div>
                </div>

                <div class="mt-8">
                    <div class="mt-6">
                        <form class="space-y-6" @submit.prevent="submit">
                            <div class="relative w-full mr-4">
                                <input id="email" v-model="form.email" type="text" class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent" placeholder="placeholder" />
                                <label for="email" class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">E-Mail</label>
                            </div>

                            <div class="relative w-full mr-4">
                                <input id="password" v-model="form.password" type="text" class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent" placeholder="placeholder" />
                                <label for="password" class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Passwort</label>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <Checkbox class="justify-between text-sm" :item=rememberCheckbox />
                                </div>
                                <div class="text-sm">
                                    <Link v-if="canResetPassword" :href="route('password.request')"
                                          class="text-xs text-secondary subpixel-antialiased hover:font-semibold hover:text-primary">
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
import SvgCollection from "@/Layouts/Components/SvgCollection";


const rememberCheckbox = {name: 'Angemeldet bleiben', checked: false, showIcon: false}

export default defineComponent({
    components: {
        SvgCollection,
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
