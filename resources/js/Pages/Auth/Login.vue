<template>
    <div class="min-h-full flex">
        <img :src="$page.props.big_logo" class="w-20 h-20 ml-12 mt-12 absolute rounded-full" />
        <div
            class="flex-1 flex min-h-screen flex-col align-items-center justify-center py-12 px-4 sm:px-6 lg:px-20 xl:px-24">
            <div class="mx-auto  w-full max-w-sm lg:w-96">

                <div>
                    <div class="text-2xl mb-8 font-bold text-black">
                        <img src="/Svgs/Logos/artwork_logo_big.svg"/>
                    </div>
                    <div class="flex items-center mb-12">
                    <h2 class="mt-6 text-3xl font-lexend font-bold text-primary">Login</h2>
                        <SvgCollection svgName="arrowRight" class="mt-12 ml-2"/>
                    </div>
                </div>

                <div class="mt-8">
                    <div class="mt-6">
                        <form class="space-y-6" @submit.prevent="submit">
                            <div class="relative w-full mr-4">
                                <input id="email" v-model="form.email" type="text" class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent" placeholder="placeholder" />
                                <label for="email" class="absolute left-0 text-sm -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">E-Mail*</label>
                            </div>

                            <div class="relative w-full mr-4">
                                <input id="password" v-model="form.password" type="password" class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent" placeholder="placeholder" />
                                <label for="password" class="absolute left-0 text-sm -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Passwort*</label>
                            </div>
                            <jet-input-error :message="errors.email" class="mt-2"/>
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
                                <button :disabled="this.form.email === '' || this.form.password === ''" :class="[this.form.email === '' || this.form.password === '' ? 'bg-secondary hover:bg-secondary' : '']" type="submit"
                                        class="flex px-44 py-4 mt-1 items-center border border-transparent rounded-full shadow-sm text-white bg-buttonBlue hover:shadow-blueButton hover:bg-buttonHover focus:outline-none">
                                    <p class="text-sm">Login</p>
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
                <div class=" absolute bottom-0 mb-20 text-secondary subpixel-antialiased text-sm tracking-wide">
                    <a v-if="this.$page.props.impressumLink !== ''" target="_blank" :href="this.$page.props.impressumLink">
                        Impressum
                    </a>
                    <a target="_blank" v-else :href="this.$page.props.impressumLink">
                        Impressum
                    </a>
                    |
                    <a target="_blank" v-if="this.$page.props.privacyLink !== ''" :href="this.$page.props.privacyLink">
                        Datenschutz
                    </a>
                    <a target="_blank" v-else :href="this.$page.props.privacyLink">
                        Datenschutz
                    </a>
                    |
                    <a target="_blank" href="https://artwork.software/">
                        Über das Tool
                    </a>
                </div>
            </div>
        </div>
        <div class="hidden lg:block relative w-0 flex-1">
            <img class="absolute inset-0 h-full w-full object-cover"
                 :src="$page.props.banner"
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
import AddButton from "@/Layouts/Components/AddButton";
import JetInputError from "@/Jetstream/InputError.vue";
import Permissions from "@/mixins/Permissions.vue";


const rememberCheckbox = {name: 'Angemeldet bleiben', checked: false, showIcon: false}

export default defineComponent({
    mixins: [Permissions],
    components: {
        SvgCollection,
        Head,
        JetAuthenticationCard,
        JetAuthenticationCardLogo,
        JetButton,
        JetInput,
        JetInputError,
        JetCheckbox,
        JetLabel,
        JetValidationErrors,
        Link,
        Checkbox,
        AddButton
    },
    props: {
        canResetPassword: Boolean,
        status: String
    },
    computed: {
        errors() {
            return this.$page.props.errors;
        }
    },
    data() {
        return {
            form: this.$inertia.form({
                email: '',
                password: '',
                remember: false,
                error:'',
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
