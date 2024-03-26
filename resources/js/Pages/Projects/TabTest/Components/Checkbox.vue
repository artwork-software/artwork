<script>
export default {
    name: "Checkbox",
    props: ['label', 'defaults']
}
</script>

<template>
    <div class="relative flex items-start">
        <div class="flex h-6 items-center">
            <input id="comments" aria-describedby="comments-description" :checked="defaults?.checked" name="comments" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600" />
        </div>
        <div class="ml-3 text-sm leading-6">
            <label for="comments" class="font-medium text-gray-900">
                {{ label }}
            </label>
        </div>
    </div>
</template>

<style scoped>

</style>
