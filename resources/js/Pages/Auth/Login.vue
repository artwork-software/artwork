<template>

    <Head>
        <link rel="icon" type="image/png" :href="$page.props.small_logo" />
        <title>{{ $t('Login') }} - {{ $page.props.page_title }}</title>
    </Head>
    <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-2">
        <div class="flex flex-col items-center justify-center h-screen">
            <div class="min-h-96">
                <div class="">
                    <div class="my-10">
                        <div class="text-2xl font-bold text-black font-lexend flex items-center gap-x-4">
                            <img :src="$page.props.big_logo" class="max-w-lg h-fit" alt="Big artwork logo"/>
                            <span>
                                {{ $t('Login') }}
                            </span>
                        </div>
                    </div>
                    <form class="space-y-10 my-4 card white px-4 py-6" @submit.prevent="submit">
                        <div class="space-y-4">
                            <BaseInput id="email" v-model="form.email" :label="$t('Email') + '*'" required/>
                            <BaseInput id="password" type="password" v-model="form.password" :label="$t('Password') + '*'" required/>
                        </div>
                        <jet-input-error :message="errors.email" class="mt-2"/>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <Checkbox class="justify-between text-sm" :item="rememberCheckbox"/>
                            </div>
                            <div class="text-sm">
                                <Link v-if="canResetPassword" :href="route('password.request')"
                                      class="!text-xs subpixel-antialiased hover:font-semibold hover:text-primary">
                                    {{ $t('Forgot your password?') }}
                                </Link>
                            </div>
                        </div>
                        <div>
                            <ArtworkBaseModalButton :disabled="this.form.email === '' || this.form.password === ''" class="!px-44" vertical-padding="py-4" variant="primary" type="submit">
                                {{ $t('Login') }}
                            </ArtworkBaseModalButton>
                        </div>
                    </form>
                </div>

            </div>
            <div class="flex gap-x-4 mt-5 subpixel-antialiased text-sm tracking-wide">
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
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import ArtworkBaseButton from "@/Artwork/Buttons/ArtworkBaseButton.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";

export default defineComponent({
    mixins: [Permissions],
    components: {
        ArtworkBaseModalButton,
        ArtworkBaseButton,
        BaseInput,
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
