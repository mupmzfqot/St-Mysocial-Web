<x-mail::message>
# Registration Succeeded

Hello {{ $user->name }}!

Thank you for registering with us.

@if($generatedPassword)
Your account has been created with a generated password.

**Your generated password is: {{ $generatedPassword }}**

Please save this password securely as it will not be shown again.
@endif

Before you can login, your account must be manually activated by an administrator.<br>
Please wait, this can take some time.

@if($generatedPassword)
After your account is activated, you can login using your email and the generated password above.
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
