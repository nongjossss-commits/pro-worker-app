{{-- resources/views/tickets/partials/_basket_form_inputs.blade.php --}}

{{--
    This partial generates the hidden inputs required for form submission.
    It is separated from the display templates to ensure cleaner DOM generation
    and avoid potential issues with nested UI elements.
--}}

<!-- Existing Employees Inputs -->
<template x-for="(item, index) in basket.existing_employees" :key="'input-e-' + item.id">
    <input type="hidden" :name="'attachments[existing_employees][' + index + ']'" :value="item.id">
</template>

<!-- External Employees Inputs (V2.5-S17) -->
<template x-for="(item, index) in basket.external_employees" :key="'input-ext-' + item.id">
    <input type="hidden" :name="'attachments[external_employees][' + index + ']'" :value="item.id">
</template>

<!-- New Employees Inputs -->
<template x-for="(item, index) in basket.new_employees" :key="'input-n-' + index">
    <input type="hidden" :name="'attachments[new_employees][' + index + ']'" :value="JSON.stringify(item)">
</template>

<!-- Files Inputs -->
<template x-for="(item, index) in basket.files" :key="'input-f-' + index">
    <div>
        <input type="hidden" :name="'attachments[files][' + index + '][path]'" :value="item.path">
        <input type="hidden" :name="'attachments[files][' + index + '][name]'" :value="item.name">
        <input type="hidden" :name="'attachments[files][' + index + '][size]'" :value="item.size">
    </div>
</template>
