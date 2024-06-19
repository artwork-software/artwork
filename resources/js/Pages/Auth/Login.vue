<template>

    <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-2">
        <div class="flex flex-col items-center justify-center h-screen">
            <div class="min-h-96">
                <div class="">
                    <div class="text-2xl mb-8 font-bold text-black">
                        <img :src="$page.props.big_logo" class="max-w-lg h-fit"/>
                    </div>
                    <div class="flex items-center mb-12">
                        <h2 class="mt-6 text-3xl font-lexend font-bold text-primary">{{$t('Login')}}</h2>
                        <SvgCollection svgName="arrowRight" class="mt-12 ml-2"/>
                    </div>
                </div>
                <form class="space-y-10" @submit.prevent="submit">
                    <div class="relative w-full mr-4">
                        <input id="email" v-model="form.email" type="text" class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent" placeholder="placeholder" />
                        <label for="email" class="absolute left-0 text-sm -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">{{$t('Email')}}*</label>
                    </div>

                    <div class="relative w-full mr-4">
                        <input id="password" v-model="form.password" type="password" class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent" placeholder="placeholder" />
                        <label for="password" class="absolute left-0 text-sm -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">{{ $t('Password')}}*</label>
                    </div>
                    <jet-input-error :message="errors.email" class="mt-2"/>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <Checkbox class="justify-between text-sm" :item=rememberCheckbox />
                        </div>
                        <div class="text-sm">
                            <Link v-if="canResetPassword" :href="route('password.request')"
                                  class="text-xs text-secondary subpixel-antialiased hover:font-semibold hover:text-primary">
                                {{$t('Forgot your password?')}}
                            </Link>
                        </div>
                    </div>


                    <div>
                        <BaseButton :text="$t('Login')" :disabled="this.form.email === '' || this.form.password === ''" horizontal-padding="px-44" vertical-padding="py-4" type="submit" />
                    </div>
                </form>

            </div>
            <div class="absolute bottom-10 text-secondary subpixel-antialiased text-sm tracking-wide">
                <a v-if="this.$page.props.impressumLink !== ''" target="_blank" :href="this.$page.props.impressumLink">
                    {{$t('Imprint')}}
                </a>
                <a target="_blank" v-else :href="this.$page.props.impressumLink">
                    {{$t('Imprint')}}
                </a>
                |
                <a target="_blank" v-if="this.$page.props.privacyLink !== ''" :href="this.$page.props.privacyLink">
                    {{$t('Privacy Policy')}}
                </a>
                <a target="_blank" v-else :href="this.$page.props.privacyLink">
                    {{$t('Privacy Policy')}}
                </a>
                |
                <a target="_blank" href="https://artwork.software/">
                    {{$t('About the tool')}}
                </a>
            </div>
        </div>
        <div class="h-screen w-full items-center justify-end hidden lg:flex">
            <img class="h-screen w-full object-cover" :src="$page.props.banner" alt=""/>
        </div>
    </div>



    <div class="min-h-full hidden">
        <img :src="$page.props.big_logo" class="w-20 h-20 ml-12 mt-12 absolute rounded-full" />
        <div
            class="flex-1 flex min-h-screen flex-col align-items-center justify-center py-12 px-4 sm:px-6 lg:px-20 xl:px-24">
            <div class="mx-auto  w-full max-w-sm lg:w-96">



                <div class="mt-8">
                    <div class="mt-6">
                        <form class="space-y-6" @submit.prevent="submit">
                            <div class="relative w-full mr-4">
                                <input id="email" v-model="form.email" type="text" class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent" placeholder="placeholder" />
                                <label for="email" class="absolute left-0 text-sm -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">{{$t('Email')}}*</label>
                            </div>

                            <div class="relative w-full mr-4">
                                <input id="password" v-model="form.password" type="password" class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent" placeholder="placeholder" />
                                <label for="password" class="absolute left-0 text-sm -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">{{ $t('Password')}}*</label>
                            </div>
                            <jet-input-error :message="errors.email" class="mt-2"/>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <Checkbox class="justify-between text-sm" :item=rememberCheckbox />
                                </div>
                                <div class="text-sm">
                                    <Link v-if="canResetPassword" :href="route('password.request')"
                                          class="text-xs text-secondary subpixel-antialiased hover:font-semibold hover:text-primary">
                                        {{$t('Forgot your password?')}}
                                    </Link>
                                </div>
                            </div>


                            <div>
                                <BaseButton :text="$t('Login')" :disabled="this.form.email === '' || this.form.password === ''" horizontal-padding="px-44" vertical-padding="py-4" type="submit" />
                            </div>
                        </form>

                    </div>
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
import {Head, Link} from '@inertiajs/vue3';
import Checkbox from "@/Layouts/Components/Checkbox.vue";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import Permissions from "@/Mixins/Permissions.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";


export default defineComponent({
    mixins: [Permissions],
    components: {
        BaseButton,
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
            }),
            rememberCheckbox: {name: this.$t('Remember me'), checked: false, showIcon: false}
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
})
</script>
