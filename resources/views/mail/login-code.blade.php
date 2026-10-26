<x-mail::message>
# Your login code

Enter this code to continue to {{ config('app.name') }}:

<x-mail::panel>
**{{ $code }}**
</x-mail::panel>

This code expires in {{ $minutes }} minutes. If you didn't request it, you can safely ignore this email.
</x-mail::message>
