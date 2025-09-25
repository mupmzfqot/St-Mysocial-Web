<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertStatus(200);
});

test('email verification redirects to login when not authenticated', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->get($verificationUrl);

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('message', 'Please login to complete email verification.');
    $response->assertSessionHas('pending_email_verification');
});

test('email verification completes after login and redirects to change password if first login', function () {
    $user = User::factory()->unverified()->create([
        'last_login' => null // First time login
    ]);

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    // First, visit verification link to set session
    $this->get($verificationUrl);

    // Then login
    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
        'recaptcha_token' => 'test-token'
    ]);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    expect($user->fresh()->is_active)->toBeTrue();
    expect($user->fresh()->last_login)->toBeNull(); // Should still be null
    $response->assertRedirect(route('change-password.index'));
});

test('email verification completes after login and redirects to homepage if not first login', function () {
    $user = User::factory()->unverified()->create([
        'last_login' => now()->subDays(1) // Not first time login
    ]);

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    // First, visit verification link to set session
    $this->get($verificationUrl);

    // Then login
    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
        'recaptcha_token' => 'test-token'
    ]);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    expect($user->fresh()->is_active)->toBeTrue();
    expect($user->fresh()->last_login)->not->toBeNull(); // Should be updated
    $response->assertRedirect(route('homepage', absolute: false).'?verified=1');
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $response = $this->get($verificationUrl);

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['email' => 'Invalid verification link.']);
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    expect($user->fresh()->is_active)->toBeFalse();
});
