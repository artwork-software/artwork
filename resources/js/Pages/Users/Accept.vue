<template>
    <div class="py-8 px-8 md:px-64">
        <form class="space-y-6" @submit.prevent="submit">
            <div class="text-2xl font-bold text-black">
                <img src="/Svgs/Logos/artwork_logo_big.svg"/>
            </div>
            <div class="flex items-center">
                <h2 class="mt-6 text-3xl font-lexend font-bold text-primary">{{$t('Accept Invitation')}}</h2>
                <SvgCollection svgName="arrowRight" class="mt-12 ml-2"/>
            </div>
            <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-3 mt-1">
                    <label for="first_name" class="text-sm font-bold text-secondary">{{$t('First name')}}</label>
                    <input
                        v-model="form.first_name"
                        id="first_name"
                        type="text"
                        required
                        class="focus:ring-black focus:border-artwork-buttons-create border-2 w-full sm:text-sm border-gray-200"/>
                </div>
                <div class="sm:col-span-3 mt-1">
                    <label for="last_name" class="text-sm font-bold text-secondary">Name</label>
                    <input
                        v-model="form.last_name"
                        id="last_name"
                        type="text"
                        required
                        class="focus:ring-black focus:border-artwork-buttons-create border-2 w-full sm:text-sm border-gray-200"/>
                </div>
                <div class="sm:col-span-3 mt-1">
                    <label for="email" class="text-sm font-bold text-secondary">Email</label>
                    <input
                        v-model="form.email"
                        disabled
                        id="email"
                        type="email"
                        required
                        class="bg-gray-100 focus:ring-black focus:border-artwork-buttons-create border-2 w-full sm:text-sm border-gray-200"/>
                </div>
                <div class="sm:col-span-3 mt-1">
                    <label for="phoneNumber" class="text-sm font-bold text-secondary">{{$t('Phone number')}}</label>
                    <input
                        v-model="form.phone_number"
                        id="phoneNumber"
                        type="text"
                        class="focus:ring-black focus:border-artwork-buttons-create border-2 w-full sm:text-sm border-gray-200"/>
                </div>
                <div class="sm:col-span-3 mt-1">
                    <label for="phoneNumber" class="text-sm font-bold text-secondary">{{$t('Company')}}</label>
                    <input
                        v-model="form.business"
                        id="business"
                        type="text"
                        :placeholder="$page.props.businessName"
                        class="focus:ring-black focus:border-artwork-buttons-create border-2 w-full sm:text-sm border-gray-200"/>
                </div>
                <div class="sm:col-span-3 mt-1">
                    <label for="position" class="text-sm font-bold text-secondary">Position</label>
                    <input
                        v-model="form.position"
                        id="position"
                        type="text"
                        required
                        class="focus:ring-black focus:border-artwork-buttons-create border-2 w-full sm:text-sm border-gray-200"/>
                </div>
            </div>
            <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-3 mt-1">
                    <label for="password" class="text-sm font-bold text-secondary">{{ $t('Password')}}</label>
                    <input
                        v-model="form.password"
                        id="password"
                        type="password"
                        required
                        :class="[form.hasErrors ? 'border-error' : 'border-gray-200',
                                    'focus:ring-black focus:border-black border-2 w-full sm:text-sm']"/>
                    <jet-input-error :message="form.errors.password" class="mt-2"/>
                </div>
                <div v-if="form.password.length>0" class="sm:col-span-3 flex items-center">
                    <span class="text-xs text-secondary">{{$t('Weak')}}</span>
                    <div class="mx-6 mt-1 w-full bg-gray-200 h-1 dark:bg-gray-700">
                        <div :class="[pw_feedback < 1
                                ? 'bg-error'
                                : pw_feedback < 3
                                ? 'bg-amber-400' :
                                'bg-success' ,
                                'h-1']" :style="{width: `${(pw_feedback + 1) / 5 * 100}%`}">
                        </div>
                    </div>
                    <span class="text-xs">{{ $t('Strong')}}</span>
                </div>
            </div>
            <div class="flex items-center text-secondary">
                <SvgCollection svgName="arrowTopLeft" class="m-3"/>

                <div class="hind w-full">
                    {{$t('The password must be at least 10 characters long, contain at least 1 digit, upper and lower case letters and special characters.')}}
                </div>
            </div>
            <button :disabled="form.email === '' || form.position === '' || form.password === '' || form.first_name === '' || form.last_name === ''" :class="[form.email === '' || form.position === '' || form.password === '' || form.first_name === '' || form.last_name === '' ? 'bg-secondary hover:bg-secondary' : '']" type="submit"
                    class="flex px-44 py-4 mt-1 items-center border border-transparent rounded-full shadow-sm text-white bg-artwork-buttons-create hover:shadow-artwork-buttons-create hover:bg-artwork-buttons-hoverfocus:outline-none">
                {{ $t('Register')}}
            </button>
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

export default defineComponent({
    mixins: [Permissions],
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
            form: this.$inertia.form({
                _method: 'POST',
                first_name: '',
                last_name: '',
                email: this.email,
                position: '',
                phone_number: '',
                password: '',
                business: this.$page.props.businessName,
                token: this.token
            })
        }
    },
    methods: {
        submit() {
            this.form.post(this.route('invitation.accept'), {
                onFinish: () => this.form.reset('password'),
            })
        }
    }
})
</script>
