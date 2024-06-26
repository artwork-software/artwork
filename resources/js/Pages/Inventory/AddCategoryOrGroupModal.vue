<template>
    <BaseModal v-if="show" @closed="close">
        <div class="flex flex-col mx-4 gap-y-2">
            <h1 v-if="typeIsCategory()"
                class="headline1">
                {{ $t('Kategorie hinzufügen') }}
            </h1>
            <h1 v-if="typeIsGroup()"
                class="headline1">
                {{ $t('Gruppe hinzufügen') }}
            </h1>
            <div id="new-column-form"
                 class="flex flex-col gap-y-2">
                <TextInputComponent v-if="typeIsCategory()"
                                    id="new-category-name"
                                    v-model="newCategoryOrGroupForm.name"
                                    :label="$t('Kategoriename*')"
                />
                <TextInputComponent v-if="typeIsGroup()"
                                    id="new-group-name"
                                    v-model="newCategoryOrGroupForm.name"
                                    :label="$t('Gruppenname*')"
                />
                <span v-if="showInvalidNameErrorText"
                      class="text-xs subpixel-antialiased text-error">
                    {{ $t('Es muss ein Name angegeben werden.') }}
                </span>
                <div class="w-full flex flex-row justify-center mt-4">
                    <FormButton :text="$t('Save')"
                                @click="saveNewCategoryOrGroup()"/>
                </div>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>
import BaseModal from "@/Components/Modals/BaseModal.vue";
import {useForm} from "@inertiajs/vue3";
import {ref} from "vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";

const emits = defineEmits(['closed']),
    props = defineProps({
        resourceId: {
            type: Number,
            required: true
        },
        show: {
            type: Boolean,
            required: true
        },
        type: {
            type: String,
            required: true
        }
    }),
    showInvalidNameErrorText = ref(false),
    newCategoryOrGroupForm = useForm({
        craftId: null,
        categoryId: null,
        order: 0,
        name: ''
    }),
    typeIsCategory = () => {
        return props.type === 'category';
    },
    typeIsGroup = () => {
        return props.type === 'group';
    },
    validateNewCategoryOrGroupForm = () => {
        showInvalidNameErrorText.value = !newCategoryOrGroupForm.name.length > 0;

        return !showInvalidNameErrorText.value;
    },
    saveNewCategoryOrGroup = () => {
        if (!validateNewCategoryOrGroupForm()) {
            return;
        }

        if (typeIsCategory()) {
            newCategoryOrGroupForm.craftId = props.resourceId;
            newCategoryOrGroupForm.post(
                route('inventory-management.inventory.category.create'),
                {
                    preserveScroll: true,
                    onSuccess: close
                }
            );

            return;
        }

        if (typeIsGroup()) {
            newCategoryOrGroupForm.categoryId = props.resourceId;
            newCategoryOrGroupForm.post(
                route('inventory-management.inventory.group.create'),
                {
                    preserveScroll: true,
                    onSuccess: close
                }
            );
        }
    },
    close = () => {
        newCategoryOrGroupForm.reset();
        emits.call(this, 'closed');
    };
</script>
