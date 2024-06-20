<template>
    <Head title="Forgot Password" />

    <jet-authentication-card>
        <div class="text-4xl my-6 flex justify-center font-bold text-black">
            <img src="/Svgs/Logos/artwork_logo_big.svg"/>
        </div>
        <h2 class="my-6 text-2xl text-center font-bold text-gray-900">
            {{  $t('Forgot your password?') }}
        </h2>
        <div class="mb-4 text-md font-semibold text-gray-600">
            {{$t('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.')}}
        </div>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <jet-validation-errors class="mb-4" />

        <form @submit.prevent="submit">
            <div>
                <jet-label for="email" value="E-Mail-Adresse" class="font-bold" />
                <jet-input id="email" type="email" class="mt-1 block w-full" v-model="form.email" required autofocus />
            </div>
            <div class="flex items-center justify-end mt-4">
                <jet-button class="bg-artwork-buttons-create hover:bg-artwork-buttons-hover" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    {{ $t('Send reset link')}}
                </jet-button>
            </div>
        </form>
    </jet-authentication-card>
</template>

<script>
    import { defineComponent } from 'vue'
    import { Head } from '@inertiajs/vue3';
    import JetAuthenticationCard from '@/Jetstream/AuthenticationCard.vue'
    import JetAuthenticationCardLogo from '@/Jetstream/AuthenticationCardLogo.vue'
    import JetButton from '@/Jetstream/Button.vue'
    import JetInput from '@/Jetstream/Input.vue'
    import JetLabel from '@/Jetstream/Label.vue'
    import JetValidationErrors from '@/Jetstream/ValidationErrors.vue'
    import Permissions from "@/Mixins/Permissions.vue";

    export default defineComponent({
        mixins: [Permissions],
        components: {
            Head,
            JetAuthenticationCard,
            JetAuthenticationCardLogo,
            JetButton,
            JetInput,
            JetLabel,
            JetValidationErrors
        },

        props: {
            status: String
        },

        data() {
            return {
                form: this.$inertia.form({
                    email: ''
                })
            }
        },

        methods: {
            submit() {
                this.form.post(this.route('password.email'))
            }
        }
    })
</script>
