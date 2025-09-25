<x-mail::message>
# Registration Succeeded

Hello {{ $user->name }}!

Thank you for registering with us.

Before you can login, your account must be manually activated by an administrator.<br>
Please wait, this can take some time.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
