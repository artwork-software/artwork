<template>
    <ArtworkBaseModal :title="$t('Invite {count} people', { count: people.length })"
                      :description="$t('They join with the role you pick here; Artwork-Tickets sends each of them an e-mail with a link. Rights can be refined on its team page afterwards.')"
                      modal-size="sm:max-w-xl" @close="$emit('close')">
        <div class="flex flex-col gap-5 text-[13px]">
            <div class="flex flex-wrap gap-1.5">
                <span v-for="person in people" :key="person.id" class="inline-flex h-[26px] items-center gap-1.5 rounded-full border border-border-subtle bg-surface-sunken pl-1.5 pr-2.5 text-[12.5px] text-text">
                    <span class="grid size-4 place-items-center rounded-full bg-accent-100 text-[9px] font-semibold text-accent-700">{{ initials(person.name) }}</span>
                    {{ person.name }}
                </span>
            </div>

            <fieldset class="m-0 flex flex-col gap-2 border-0 p-0">
                <legend class="mb-2 p-0 font-lexend text-xs font-medium text-[#3F424A]">{{ $t('Invite as') }}</legend>
                <label v-for="option in options" :key="option.preset" :for="`invite-role-${option.preset}`"
                       class="flex cursor-pointer items-start gap-3 rounded-lg border px-3.5 py-3 transition-colors"
                       :class="modelValue === option.preset ? 'border-accent-600 bg-accent-50' : 'border-border-subtle bg-surface hover:bg-surface-sunken'">
                    <input :id="`invite-role-${option.preset}`" type="radio" name="invite-role" :value="option.preset" :checked="modelValue === option.preset" class="sr-only" @change="$emit('update:modelValue', option.preset)" />
                    <span class="mt-0.5 box-border size-4 shrink-0 rounded-full border bg-surface" :class="modelValue === option.preset ? 'border-[5px] border-accent-600' : 'border-border'"></span>
                    <span class="flex flex-col gap-0.5">
                        <span class="font-lexend text-[13px] font-medium text-text">{{ option.label }}</span>
                        <span class="text-[12.5px] leading-[18px] text-text-muted">{{ option.hint }}</span>
                    </span>
                </label>
            </fieldset>
        </div>

        <template #footer>
            <button type="button" class="ui-button" :disabled="processing" @click="$emit('close')">{{ $t('Cancel') }}</button>
            <button type="button" class="ui-button-add" :disabled="processing || people.length === 0" @click="$emit('submit')">
                <IconMailForward class="size-4" />
                {{ processing ? $t('Inviting…') : $t('Send {count} invitations as {role}', { count: people.length, role: roleLabel }) }}
            </button>
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed } from 'vue'
import { IconMailForward } from '@tabler/icons-vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'

/* The second step of an invitation: who is settled, this decides as what. The role
   cards carry the same one-line hints as the team page of Artwork-Tickets. */
const props = defineProps({
    people: { type: Array, required: true },
    /** [{ preset, label, hint }] in the order they are offered. */
    options: { type: Array, required: true },
    modelValue: { type: String, required: true },
    processing: { type: Boolean, default: false },
})

defineEmits(['update:modelValue', 'close', 'submit'])

const roleLabel = computed(() => props.options.find((o) => o.preset === props.modelValue)?.label ?? props.modelValue)

function initials(name) {
    return String(name ?? '').split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0].toUpperCase()).join('')
}
</script>
