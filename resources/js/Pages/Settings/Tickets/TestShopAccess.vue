<template>
    <div class="rounded-lg border border-border-subtle bg-surface px-4 py-3.5 text-[13px]">
        <div class="mb-2 flex items-center justify-between gap-3">
            <span class="font-lexend font-semibold text-text">{{ $t('Test shop access') }}</span>
            <button v-if="access && !editing" type="button" class="text-xs font-medium text-accent-600 hover:underline" @click="startEditing">{{ $t('Change') }}</button>
        </div>
        <p class="mb-3 text-xs leading-[18px] text-text-subtle">{{ $t('Whoever should try the shop needs this login. Your team in Artwork-Tickets gets in without it.') }}</p>

        <p v-if="!access" class="text-xs text-text-subtle">{{ $t('Artwork-Tickets is not answering right now; the login appears here again once it does.') }}</p>

        <form v-else-if="editing" class="flex flex-col gap-3" @submit.prevent="save">
            <BaseInput id="test-shop-user" v-model="form.user" :label="$t('Username')" required />
            <BaseInput id="test-shop-password" v-model="form.password" :label="$t('Password')" required />
            <p v-if="form.errors.user || form.errors.password" class="text-xs text-danger">{{ form.errors.user ?? form.errors.password }}</p>
            <p class="text-xs text-text-subtle">{{ $t('Browsers that signed in with the old password have to sign in again.') }}</p>
            <div class="flex justify-end gap-2">
                <button type="button" class="ui-button" :disabled="form.processing" @click="editing = false">{{ $t('Cancel') }}</button>
                <button type="submit" class="ui-button-add" :disabled="form.processing">{{ $t('Save') }}</button>
            </div>
        </form>

        <template v-else>
            <dl class="grid grid-cols-[100px_minmax(0,1fr)] gap-x-3 gap-y-1.5">
                <dt class="text-text-subtle">{{ $t('Shop') }}</dt>
                <dd class="truncate"><a :href="shopUrl" target="_blank" rel="noopener" class="font-mono text-xs text-accent-700 hover:underline">{{ shopUrl }}</a></dd>
                <dt class="text-text-subtle">{{ $t('Username') }}</dt>
                <dd class="font-mono text-xs text-text">{{ access.user }}</dd>
                <dt class="text-text-subtle">{{ $t('Password') }}</dt>
                <dd class="flex items-center gap-2 font-mono text-xs text-text">
                    {{ revealed ? access.password : '••••••••••' }}
                    <button type="button" class="text-text-subtle hover:text-text" :aria-label="revealed ? $t('Hide password') : $t('Show password')" @click="revealed = !revealed">
                        <component :is="revealed ? IconEyeOff : IconEye" class="size-3.5" />
                    </button>
                </dd>
            </dl>
            <button type="button" class="ui-button mt-3" @click="copy">
                <component :is="copied ? IconCheck : IconCopy" class="size-3.5" />{{ copied ? $t('Copied') : $t('Copy link and login') }}
            </button>
        </template>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { IconCheck, IconCopy, IconEye, IconEyeOff } from '@tabler/icons-vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'

/* The login of the shop while the house tests: shown, copied in one go to pass on, changed here. */

const props = defineProps({
    /** `{ user, password }`; null while Artwork-Tickets does not answer. */
    access: { type: Object, default: null },
    shopUrl: { type: String, required: true },
})

const { t } = useI18n()

const revealed = ref(false)
const copied = ref(false)
const editing = ref(false)
const form = useForm({ user: '', password: '' })

function startEditing() {
    form.user = props.access.user
    form.password = props.access.password
    form.clearErrors()
    editing.value = true
}

function save() {
    form.put(route('settings.tickets.shop-access'), {
        preserveScroll: true,
        onSuccess: () => { editing.value = false },
    })
}

async function copy() {
    await navigator.clipboard.writeText([
        props.shopUrl,
        `${t('Username')}: ${props.access.user}`,
        `${t('Password')}: ${props.access.password}`,
    ].join('\n'))
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
}
</script>
