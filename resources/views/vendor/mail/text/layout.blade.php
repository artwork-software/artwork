{!! strip_tags($slot) !!}
@isset($subcopy)

{!! strip_tags($subcopy) !!}
@endisset
@php
    $emailFooter = trim(app(\Artwork\Modules\GeneralSettings\Models\GeneralSettings::class)->email_footer ?? '');
@endphp
@if($emailFooter !== '')

{!! $emailFooter !!}
@endif
