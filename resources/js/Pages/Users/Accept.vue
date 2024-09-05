<template>
    <Head>
        <link rel="icon" type="image/png" :href="$page.props.small_logo" />
        <title>{{ $t('Accept Invitation') }} - {{ $page.props.page_title }}</title>
    </Head>
    <div class="py-8 px-8 md:px-64">
        <form class="space-y-6" @submit.prevent="submit">
            <div class="text-2xl font-bold text-black">
                <img src="/Svgs/Logos/artwork_logo_big.svg"/>
            </div>
            <div class="flex items-center">
                <h2 class="mt-6 text-3xl font-lexend font-bold text-primary">{{$t('Accept Invitation')}}</h2>
                <SvgCollection svgName="arrowRight" class="mt-12 ml-2"/>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <TextInputComponent id="first_name"  v-model="form.first_name" :label="$t('First name')" />
                </div>
                <div>
                    <TextInputComponent id="last_name"  v-model="form.last_name" :label="$t('Last name')" />
                </div>
                <div>
                    <TextInputComponent id="email" disabled v-model="form.email" :label="$t('E-mail address')" />
                </div>
                <div>
                    <TextInputComponent id="phone_number"  v-model="form.phone_number" :label="$t('Phone number')" />
                </div>
                <div>
                    <TextInputComponent id="business"  v-model="form.business" :label="$t('Company')" />
                </div>
                <div>
                    <TextInputComponent id="position" type="text" v-model="form.position" :label="$t('Position')" />
                </div>
                <div>
                    <div class="relative">
                        <TextInputComponent id="password" :type="passwordType" v-model="form.password" :label="$t('Password')" />
                        <div class="absolute top-3 right-4 z-10 group">
                            <IconEye class="w-6 h-6 text-gray-800 cursor-pointer" v-if="passwordType === 'password'" @click="showPassword"/>
                            <IconEyeClosed class="w-6 h-6 text-gray-800 cursor-pointer" v-else @click="showPassword"/>
                        </div>
                    </div>
                    <jet-input-error :message="form.errors.password" class="mt-2"/>
                    <div class="flex items-center text-secondary mt-2 ml-3">
                        <SvgCollection svgName="arrowTopLeft"/>

                        <div class="hind w-full ml-2 mt-1">
                            {{$t('The password must be at least 8 characters long, contain at least 1 digit, upper and lower case letters and special characters.')}}
                        </div>
                    </div>
                </div>
                <div>
                    <div class="relative">
                        <TextInputComponent id="password_confirmation" :type="passwordType" v-model="form.password_confirmation" :label="$t('Confirm Password')" />
                        <div class="absolute top-3 right-4 z-10 group">
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
                <div class="col-span-1 flex items-center">
                    <span class="text-sm text-secondary">{{$t('Weak')}}</span>
                    <div class="mx-6 w-full bg-gray-200 h-3 dark:bg-gray-700 rounded-lg">
                        <div :class="[pw_feedback <= 1
                                ? 'bg-error'
                                : pw_feedback < 3
                                ? 'bg-amber-400' :
                                'bg-success' ,
                                'h-3 rounded-lg']" :style="{width: pw_feedback > 0 ? `${(pw_feedback + 1) / 5 * 100}%` : '0%'}">
                        </div>
                    </div>
                    <span class="text-sm">{{ $t('Strong')}}</span>
                </div>
            </div>


            <BaseButton :disabled="form.email === '' || form.position === '' || form.password === '' || form.password_confirmation === '' || form.first_name === '' || form.last_name === ''" :class="[form.email === '' || form.position === '' || form.password === '' || form.password_confirmation === '' || form.first_name === '' || form.last_name === '' ? 'bg-secondary hover:bg-secondary' : '']" type="submit"
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

export default defineComponent({
    mixins: [Permissions, IconLib],
    components: {
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
    watch: {
        'form.password': {
            handler() {
                if (this.form.password.length > 0) {
                    axios.get('/password_feedback', {params: {password: this.form.password}})
                        .then(response => this.pw_feedback = response.data)
                }
            },
            deep: true
        },
    },
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
    methods: {
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
