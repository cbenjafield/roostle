<?php

use App\Mail\LoginCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

function requestLoginCode(string $email): string
{
    Mail::fake();

    test()->post(route('login.store'), ['email' => $email]);

    $code = null;
    Mail::assertSent(LoginCodeMail::class, function (LoginCodeMail $mail) use (&$code, $email) {
        $code = $mail->code;

        return $mail->hasTo(mb_strtolower($email));
    });

    return $code;
}

test('login screen can be rendered', function () {
    $this->get(route('login'))->assertOk();
});

test('requesting a code emails a six digit code and moves to the code screen', function () {
    $code = requestLoginCode('new@example.com');

    expect($code)->toMatch('/^\d{6}$/');

    $this->get(route('login.code'))->assertOk();
});

test('an email address is required and must be valid', function () {
    $this->post(route('login.store'), ['email' => 'nope'])
        ->assertSessionHasErrors('email');
});

test('existing users are logged in with a valid code', function () {
    $user = User::factory()->unverified()->create(['email' => 'jane@example.com']);
    $code = requestLoginCode('Jane@Example.com');

    $this->post(route('login.code.verify'), ['code' => $code])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

test('new users must enter their name after verifying their code', function () {
    $code = requestLoginCode('new@example.com');

    $this->post(route('login.code.verify'), ['code' => $code])
        ->assertRedirect(route('login.name'));

    $this->assertGuest();
    expect(User::count())->toBe(0);

    $this->post(route('login.name.store'), ['name' => 'New Person'])
        ->assertRedirect(route('dashboard', absolute: false));

    $user = User::firstWhere('email', 'new@example.com');
    expect($user->name)->toBe('New Person');
    expect($user->email_verified_at)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

test('the name screen is unavailable until a code has been verified', function () {
    $this->get(route('login.name'))->assertRedirect(route('login'));

    $this->post(route('login.name.store'), ['name' => 'Sneaky'])
        ->assertRedirect(route('login'));

    expect(User::count())->toBe(0);
});

test('a wrong code does not log in', function () {
    User::factory()->create(['email' => 'jane@example.com']);
    $code = requestLoginCode('jane@example.com');
    $wrong = $code === '000000' ? '111111' : '000000';

    $this->post(route('login.code.verify'), ['code' => $wrong])
        ->assertSessionHasErrors('code');

    $this->assertGuest();
});

test('a code is invalidated after too many wrong attempts', function () {
    User::factory()->create(['email' => 'jane@example.com']);
    $code = requestLoginCode('jane@example.com');
    $wrong = $code === '000000' ? '111111' : '000000';

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.code.verify'), ['code' => $wrong]);
    }

    $this->post(route('login.code.verify'), ['code' => $code])
        ->assertSessionHasErrors('code');

    $this->assertGuest();
});

test('a code can only be used once', function () {
    User::factory()->create(['email' => 'jane@example.com']);
    $code = requestLoginCode('jane@example.com');

    $this->post(route('login.code.verify'), ['code' => $code]);
    $this->post(route('logout'));

    $this->post(route('login.store'), ['email' => 'jane@example.com']);
    $this->post(route('login.code.verify'), ['code' => $code])
        ->assertSessionHasErrors('code');
});

test('a code can be resent', function () {
    requestLoginCode('jane@example.com');
    Mail::fake();

    $this->post(route('login.code.resend'))->assertRedirect(route('login.code'));

    Mail::assertSent(LoginCodeMail::class, 1);
});

test('requesting codes is rate limited', function () {
    Mail::fake();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['email' => 'jane@example.com']);
    }

    $this->post(route('login.store'), ['email' => 'jane@example.com'])
        ->assertTooManyRequests();
});

test('users can logout', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
});
