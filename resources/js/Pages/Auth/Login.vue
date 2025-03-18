<template>

    <Head>
        <link rel="icon" type="image/png" :href="$page.props.small_logo" />
        <title>{{ $t('Login') }} - {{ $page.props.page_title }}</title>
    </Head>
    <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-2">
        <div class="flex flex-col items-center justify-center h-screen">
            <div class="min-h-96">
                <div class="">
                    <div class="text-2xl mb-8 font-bold text-black">
                        <img :src="$page.props.big_logo" class="max-w-lg h-fit" alt="Big artwork logo"/>
                    </div>
                    <div class="flex items-center mb-12">
                        <h2 class="mt-6 text-3xl font-lexend font-bold text-primary">{{ $t('Login') }}</h2>
                        <SvgCollection svgName="arrowRight"/>
                    </div>
                </div>
                <form class="space-y-10" @submit.prevent="submit">
                    <TextInputComponent id="email" v-model="form.email" :label="$t('Email') + '*'" required/>
                    <TextInputComponent id="password" type="password" v-model="form.password" :label="$t('Password') + '*'" required/>
                    <jet-input-error :message="errors.email" class="mt-2"/>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <Checkbox class="justify-between text-sm" :item="rememberCheckbox"/>
                        </div>
                        <div class="text-sm">
                            <Link v-if="canResetPassword" :href="route('password.request')"
                                  class="text-xs text-secondary subpixel-antialiased hover:font-semibold hover:text-primary">
                                {{ $t('Forgot your password?') }}
                            </Link>
                        </div>
                    </div>


                    <div>
                        <BaseButton :text="$t('Login')" :disabled="this.form.email === '' || this.form.password === ''"
                                    horizontal-padding="px-44" vertical-padding="py-4" type="submit"/>
                    </div>
                </form>

            </div>
            <div class="flex gap-x-4 mt-12 text-secondary subpixel-antialiased text-sm tracking-wide">
                <a v-if="this.$page.props.impressumLink !== ''" target="_blank" :href="this.$page.props.impressumLink">
                    {{ $t('Imprint') }}
                </a>
                <a target="_blank" v-else :href="this.$page.props.impressumLink">
                    {{ $t('Imprint') }}
                </a>
                |
                <a target="_blank" v-if="this.$page.props.privacyLink !== ''" :href="this.$page.props.privacyLink">
                    {{ $t('Privacy Policy') }}
                </a>
                <a target="_blank" v-else :href="this.$page.props.privacyLink">
                    {{ $t('Privacy Policy') }}
                </a>
                |
                <a target="_blank" href="https://artwork.software/">
                    {{ $t('About the tool') }}
                </a>
            </div>
        </div>
        <div class="h-screen w-full items-center justify-end hidden lg:flex">
            <img class="h-screen w-full object-cover" :src="$page.props.banner" alt=""/>
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
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import { reloadRolesAndPermissions } from 'laravel-permission-to-vuejs'

export default defineComponent({
    mixins: [Permissions],
    components: {
        TextInputComponent,
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
        Checkbox
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
                error: '',
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
                    onFinish: () => {
                        reloadRolesAndPermissions();
                        this.form.reset('password')
                    },
                })
        }
    },
})
</script>
