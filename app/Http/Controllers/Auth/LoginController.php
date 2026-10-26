<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\IssueLoginCode;
use App\Actions\Auth\VerifyLoginCode;
use App\Concerns\ProfileValidationRules;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    use ProfileValidationRules;

    private const EMAIL_KEY = 'login.email';

    private const VERIFIED_EMAIL_KEY = 'login.verified_email';

    /**
     * Show the email entry form.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Login');
    }

    /**
     * Send a login code to the submitted email address.
     */
    public function store(Request $request, IssueLoginCode $issueLoginCode): RedirectResponse
    {
        $email = $this->normalizeEmail($request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ])['email']);

        $issueLoginCode->handle($email);

        $request->session()->forget(self::VERIFIED_EMAIL_KEY);
        $request->session()->put(self::EMAIL_KEY, $email);

        return to_route('login.code');
    }

    /**
     * Show the code entry form.
     */
    public function code(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has(self::EMAIL_KEY)) {
            return to_route('login');
        }

        return Inertia::render('auth/LoginCode', [
            'email' => $request->session()->get(self::EMAIL_KEY),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Resend a fresh code to the email address in progress.
     */
    public function resend(Request $request, IssueLoginCode $issueLoginCode): RedirectResponse
    {
        $email = $request->session()->get(self::EMAIL_KEY);

        if ($email === null) {
            return to_route('login');
        }

        $issueLoginCode->handle($email);

        return to_route('login.code')->with('status', 'A new code has been sent to your email address.');
    }

    /**
     * Verify the code, then log in an existing user or ask a new user for their name.
     */
    public function verify(Request $request, VerifyLoginCode $verifyLoginCode): RedirectResponse
    {
        $email = $request->session()->get(self::EMAIL_KEY);

        if ($email === null) {
            return to_route('login');
        }

        $code = $request->validate([
            'code' => ['required', 'digits:6'],
        ])['code'];

        if (! $verifyLoginCode->handle($email, $code)) {
            throw ValidationException::withMessages([
                'code' => 'The code is invalid or has expired.',
            ]);
        }

        $request->session()->forget(self::EMAIL_KEY);

        $user = User::where('email', $email)->first();

        if ($user === null) {
            $request->session()->put(self::VERIFIED_EMAIL_KEY, $email);

            return to_route('login.name');
        }

        return $this->signIn($request, $user);
    }

    /**
     * Show the name form for a newly verified email address.
     */
    public function name(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has(self::VERIFIED_EMAIL_KEY)) {
            return to_route('login');
        }

        return Inertia::render('auth/LoginName', [
            'email' => $request->session()->get(self::VERIFIED_EMAIL_KEY),
        ]);
    }

    /**
     * Create the account for a verified email address and log in.
     */
    public function register(Request $request): RedirectResponse
    {
        $email = $request->session()->get(self::VERIFIED_EMAIL_KEY);

        if ($email === null) {
            return to_route('login');
        }

        $validated = $request->validate(['name' => $this->nameRules()]);

        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $validated['name'], 'email_verified_at' => now()],
        );

        $request->session()->forget(self::VERIFIED_EMAIL_KEY);

        return $this->signIn($request, $user);
    }

    /**
     * Log the user out.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home');
    }

    private function signIn(Request $request, User $user): RedirectResponse
    {
        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::login($user, remember: true);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
