<template>
    <Head>
        <link rel="icon" type="image/png" :href="$page.props.small_logo" />
        <title>{{ $t('Accept Invitation') }} - {{ $page.props.page_title }}</title>
    </Head>
    <div class="py-8 px-8 md:px-64">
        <form class="space-y-6" @submit.prevent="submit">
            <div class="text-2xl font-bold text-black">
                <img src="/Svgs/Logos/artwork_logo_big.svg" alt="artwork_logo_big"/>
            </div>
            <div class="flex items-center">
                <h2 class="mt-6 text-3xl font-lexend font-bold text-primary">{{$t('Accept Invitation')}}</h2>
                <SvgCollection svgName="arrowRight" class="mt-12 ml-2"/>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <BaseInput id="first_name"
                                        v-model="form.first_name"
                                        :label="getRequiredFieldLabel($t('First name'))"/>
                    <jet-input-error :message="form.errors.first_name" class="mt-2"/>
                </div>
                <div>
                    <BaseInput id="last_name"
                                        v-model="form.last_name"
                                        :label="getRequiredFieldLabel($t('Last name'))" />
                    <jet-input-error :message="form.errors.last_name" class="mt-2"/>
                </div>
                <div>
                    <BaseInput id="email"
                                        disabled
                                        v-model="form.email"
                                        :label="getRequiredFieldLabel($t('E-mail address'))" />
                    <jet-input-error :message="form.errors.email" class="mt-2"/>
                </div>
                <div>
                    <BaseInput id="phone_number"
                                        v-model="form.phone_number"
                                        :label="$t('Phone number')" />
                    <jet-input-error :message="form.errors.phone_number" class="mt-2"/>
                </div>
                <div>
                    <BaseInput id="business"
                                        v-model="form.business"
                                        :label="getRequiredFieldLabel($t('Company'))" />
                    <jet-input-error :message="form.errors.business" class="mt-2"/>
                </div>
                <div>
                    <BaseInput id="position"
                                        type="text"
                                        v-model="form.position"
                                        :label="$t('Position')" />
                    <jet-input-error :message="form.errors.position" class="mt-2"/>
                </div>
                <div>
                    <div class="relative">
                        <BaseInput id="password"
                                            :type="passwordType"
                                            v-model="form.password"
                                            :label="getRequiredFieldLabel($t('Password'))"/>
                        <div class="absolute top-3.5 right-8   z-10 group">
                            <IconEye class="w-6 h-6 text-gray-800 cursor-pointer" v-if="passwordType === 'password'" @click="showPassword"/>
                            <IconEyeClosed class="w-6 h-6 text-gray-800 cursor-pointer" v-else @click="showPassword"/>
                        </div>
                    </div>
                    <jet-input-error :message="form.errors.password" class="mt-2"/>
                    <div class="flex items-center text-secondary mt-2 ml-3">
                        <SvgCollection svgName="arrowTopLeft"/>
                        <div class="hind w-full ml-2 mt-1">
                            {{ $t('The password must be at least 10 characters long.') }}
                        </div>
                    </div>
                </div>
                <div>
                    <div class="relative">
                        <BaseInput id="password_confirmation"
                                            :type="passwordType"
                                            v-model="form.password_confirmation"
                                            :label="getRequiredFieldLabel($t('Confirm Password'))"/>
                        <div class="absolute top-3.5 right-8  z-10 group">
                            <IconEye class="w-6 h-6 text-gray-800 cursor-pointer" v-if="passwordType === 'password'" @click="showPassword"/>
                            <IconEyeClosed class="w-6 h-6 text-gray-800 cursor-pointer" v-else @click="showPassword"/>
                        </div>
                    </div>
                    <jet-input-error :message="form.errors.password_confirmation" class="mt-2"/>
                    <div class="flex items-center text-secondary mt-2 ml-3">
                        <SvgCollection svgName="arrowTopLeft"/>
                        <div class="hind w-full ml-2 mt-1">
                            {{$t('Please confirm your password here.')}}
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-xs text-gray-500">
                {{ $t('Input fields marked with * are mandatory') }}
            </div>
            <BaseButton :disabled="form.email === '' || form.password === '' || form.password_confirmation === '' || form.first_name === '' || form.last_name === ''" :class="[form.email === '' || form.password === '' || form.password_confirmation === '' || form.first_name === '' || form.last_name === '' ? 'bg-secondary hover:bg-secondary' : '']" type="submit"
                    class="flex px-44 py-4 mt-1 items-center border border-transparent rounded-full shadow-sm text-white bg-artwork-buttons-create hover:shadow-artwork-buttons-create hover:bg-artwork-buttons-hoverfocus:outline-none">
                {{ $t('Register')}}
            </BaseButton>
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
import {Head, Link} from '@inertiajs/vue3';
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import JetInputError from '@/Jetstream/InputError.vue'
import Permissions from "@/Mixins/Permissions.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

export default defineComponent({
    mixins: [Permissions, IconLib],
    components: {
        BaseInput,
        BaseButton,
        TextInputComponent,
        Head,
        JetAuthenticationCard,
        JetAuthenticationCardLogo,
        JetButton,
        JetInput,
        JetCheckbox,
        JetLabel,
        JetValidationErrors,
        Link,
        SvgCollection,
        JetInputError,
    },
    props: ['email', 'token'],
    data() {
        return {
            pw_feedback: 0,
            error: null,
            passwordType: 'password',
            passwordConfirmType: 'password',
            hovered: false,
            form: this.$inertia.form({
                _method: 'POST',
                first_name: '',
                last_name: '',
                email: this.email,
                position: '',
                phone_number: '',
                password: '',
                password_confirmation: '',
                business: this.$page.props.businessName,
                token: this.token
            })
        }
    },
    mounted() {
        this.$i18n.locale = this.$page.props.selected_language;
    },
    methods: {
        getRequiredFieldLabel(label) {
            return label + '*';
        },
        submit() {
            this.form.post(this.route('invitation.accept'), {
                onFinish: () => {
                    this.form.reset('password', 'password_confirmation')
                    this.pw_feedback = 0
                },
            })
        },
        showPassword(){
            this.passwordType = this.passwordType === 'password' ? 'text' : 'password'
        },
        showPasswordConfirm(){
            this.passwordConfirmType = this.passwordConfirmType === 'password' ? 'text' : 'password'
        }
    }
})
</script>
