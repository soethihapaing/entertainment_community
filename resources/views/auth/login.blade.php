@extends('layouts.app')

@section('title', $mode === 'register' ? 'Sign up' : 'Log in')
@section('body_class', 'page-auth')
@section('hide_chrome', true)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/auth.css') }}">
    <link rel="stylesheet" href="{{ asset('css/intro.css') }}">
@endpush

@push('scripts')
    {{-- Data for the "Enter Point of View" sequence (posters, optional joker image / audio files) --}}
    <script type="application/json" id="introData">@json($introAssets)</script>
    <script src="{{ asset('js/intro-sound.js') }}"></script>
    <script src="{{ asset('js/intro.js') }}"></script>
    <script src="{{ asset('js/auth.js') }}"></script>
@endpush

@php
    $isRegister = $mode === 'register';
    $quotes = [
        ['text' => 'Every story changes a little depending on who is watching. That is why we share ours.'],
        ['text' => 'A great ending is not the end of the story. It is the start of the conversation.'],
        ['text' => 'We don\'t just watch stories. We carry them with us, and we love to talk about them.'],
    ];
@endphp

@section('content')
<div class="auth">

    {{-- =====================================================================
         LEFT: animated poster wall + rotating quote
         (on small screens it becomes the page background)
         ===================================================================== --}}
    <aside class="auth__visual" aria-label="About Point of View">
        <x-poster-background variant="panel" />

        <div class="auth__visual-content">
            <a class="pov-brand" href="{{ route('home') }}" data-load style="--delay: .05s">
                <span class="pov-brand__icon" aria-hidden="true"><i class="bi bi-eye-fill"></i></span>
                <span>Point <em>of</em> View</span>
            </a>

            {{-- Floating "live" chips for a cinematic feel --}}
            <div class="auth__floaters" aria-hidden="true">
                <span class="floater glass float-slow" style="top: 8%; right: 6%">
                    <i class="bi bi-fire"></i> #Frieren is trending
                </span>
                <span class="floater glass float-medium" style="top: 34%; left: 4%; animation-delay: -2s">
                    <i class="bi bi-star-fill"></i> 4.9 · Your Name
                </span>
                <span class="floater glass float-fast" style="top: 54%; right: 12%; animation-delay: -1s">
                    <i class="bi bi-chat-dots-fill"></i> 318 new comments
                </span>
            </div>

            <div>
                <figure class="auth__quote" data-quote-rotator data-load style="--delay: .25s">
                    <i class="bi bi-quote auth__quote-mark" aria-hidden="true"></i>
                    @foreach ($quotes as $quote)
                        <blockquote class="auth__quote-text {{ $loop->first ? 'is-active' : '' }}" data-quote>
                            {{ $quote['text'] }}
                        </blockquote>
                    @endforeach
                    <figcaption class="auth__quote-by">— The Point of View community</figcaption>
                </figure>

                <div class="auth__members" data-load style="--delay: .4s">
                    <div class="avatar-stack" aria-hidden="true">
                        <span class="pov-avatar pov-avatar--sm" style="--avatar-hue: 18">MT</span>
                        <span class="pov-avatar pov-avatar--sm" style="--avatar-hue: 220">LM</span>
                        <span class="pov-avatar pov-avatar--sm" style="--avatar-hue: 330">AC</span>
                        <span class="pov-avatar pov-avatar--sm" style="--avatar-hue: 265">JP</span>
                    </div>
                    <span>Join <strong>12,500+</strong> fans already sharing their takes.</span>
                </div>
            </div>
        </div>
    </aside>

    {{-- =====================================================================
         RIGHT: glassmorphism card that flips between Login and Register
         ===================================================================== --}}
    <div class="auth__panel">
        <div class="auth__topbar" data-load style="--delay: .05s">
            <a class="pov-brand d-lg-none" href="{{ route('home') }}">
                <span class="pov-brand__icon" aria-hidden="true"><i class="bi bi-eye-fill"></i></span>
                <span>Point <em>of</em> View</span>
            </a>
            <div class="d-flex align-items-center gap-3">
                {{-- Mute / unmute the intro sounds (choice is remembered in localStorage) --}}
                <button type="button" class="sound-toggle" data-sound-toggle aria-pressed="false" aria-label="Mute intro sound">
                    <i class="bi bi-volume-up"></i>
                </button>
                <a href="{{ route('home') }}" class="auth__back">
                    <i class="bi bi-arrow-left"></i> <span>Back to home</span>
                </a>
            </div>
        </div>

        <div class="auth-scene" data-load style="--delay: .15s">
            <div
                class="auth-card glass"
                data-auth-card
                data-mode="{{ $mode }}"
                data-login-url="{{ route('login') }}"
                data-register-url="{{ route('register') }}"
                data-success-url="{{ route('feed') }}"
            >
                {{-- Login / Sign up segmented tabs --}}
                <div class="auth-tabs" role="tablist" aria-label="Choose a form" data-active="{{ $mode }}">
                    <button type="button" role="tab" id="tab-login" class="auth-tabs__btn" data-auth-switch="login"
                            aria-controls="panel-login" aria-selected="{{ $isRegister ? 'false' : 'true' }}">Log in</button>
                    <button type="button" role="tab" id="tab-register" class="auth-tabs__btn" data-auth-switch="register"
                            aria-controls="panel-register" aria-selected="{{ $isRegister ? 'true' : 'false' }}">Sign up</button>
                    <span class="auth-tabs__pill" aria-hidden="true"></span>
                </div>

                {{-- ---------------------------- LOGIN ---------------------------- --}}
                <section class="auth-panel" id="panel-login" role="tabpanel" aria-labelledby="tab-login" @if ($isRegister) hidden @endif>
                    <header class="auth-panel__head">
                        <h1 class="auth-panel__title">Welcome back</h1>
                        <p class="mb-0">Log in to continue the conversation.</p>
                    </header>

                    {{--
                        TODO (Backend): connect to Laravel auth:
                        <form method="POST" action="{{ route('login.attempt') }}"> @csrf ...
                        and show server errors with @error('email') ... @enderror
                    --}}
                    <form class="auth-form" data-auth-form="login" novalidate>
                        <div class="pov-field" data-field>
                            <div class="pov-field__control">
                                <input type="text" class="pov-input" id="login-identifier" name="identifier" placeholder=" "
                                       autocomplete="username" data-validate="identifier" aria-describedby="login-identifier-error">
                                <label class="pov-label" for="login-identifier">Email or username</label>
                                <i class="bi bi-person pov-field__icon" aria-hidden="true"></i>
                                <span class="pov-field__line" aria-hidden="true"></span>
                            </div>
                            <p class="pov-field__error" id="login-identifier-error" aria-live="polite"></p>
                        </div>

                        <div class="pov-field" data-field>
                            <div class="pov-field__control">
                                <input type="password" class="pov-input" id="login-password" name="password" placeholder=" "
                                       autocomplete="current-password" data-validate="password" aria-describedby="login-password-error">
                                <label class="pov-label" for="login-password">Password</label>
                                <i class="bi bi-lock pov-field__icon" aria-hidden="true"></i>
                                <span class="pov-field__line" aria-hidden="true"></span>
                                <button type="button" class="pov-field__toggle" data-password-toggle aria-label="Show password" aria-pressed="false">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <p class="pov-field__error" id="login-password-error" aria-live="polite"></p>
                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                            <label class="pov-check">
                                <input type="checkbox" name="remember"> Remember me
                            </label>
                            {{-- TODO (Backend): link to route('password.request') --}}
                            <a href="#" class="auth-link small" data-toast="Password reset emails will work once the backend is connected." data-toast-title="Forgot password">Forgot password?</a>
                        </div>

                        <button type="submit" class="btn-pov btn-pov-primary btn-pov-lg w-100">
                            <span class="btn-label">Log in <i class="bi bi-arrow-right"></i></span>
                            <span class="btn-spinner" aria-hidden="true"></span>
                        </button>
                    </form>

                    <div class="auth-divider"><span>or continue with</span></div>

                    {{-- Social logins are UI only. TODO (Backend): Laravel Socialite --}}
                    <div class="auth-social">
                        <button type="button" class="btn-social btn-social--google" data-toast="Google login is UI only for now." data-toast-title="Google">
                            <i class="bi bi-google"></i> Google
                        </button>
                        <button type="button" class="btn-social btn-social--discord" data-toast="Discord login is UI only for now." data-toast-title="Discord">
                            <i class="bi bi-discord"></i> Discord
                        </button>
                    </div>

                    <p class="auth-switch">
                        Don't have an account?
                        <button type="button" class="auth-link" data-auth-switch="register">Sign up</button>
                    </p>
                </section>

                {{-- --------------------------- REGISTER --------------------------- --}}
                <section class="auth-panel" id="panel-register" role="tabpanel" aria-labelledby="tab-register" @unless ($isRegister) hidden @endunless>
                    <header class="auth-panel__head">
                        <h1 class="auth-panel__title">Join the story</h1>
                        <p class="mb-0">Create your free account in under a minute.</p>
                    </header>

                    {{--
                        TODO (Backend): <form method="POST" action="{{ route('register.store') }}"> @csrf ...
                        Validate on the server too (unique:users,email / unique:users,username).
                    --}}
                    <form class="auth-form" data-auth-form="register" novalidate>
                        <div class="pov-field" data-field>
                            <div class="pov-field__control">
                                <input type="text" class="pov-input" id="register-username" name="username" placeholder=" "
                                       autocomplete="username" maxlength="20" data-validate="username" aria-describedby="register-username-error">
                                <label class="pov-label" for="register-username">Username</label>
                                <i class="bi bi-at pov-field__icon" aria-hidden="true"></i>
                                <span class="pov-field__line" aria-hidden="true"></span>
                            </div>
                            <p class="pov-field__error" id="register-username-error" aria-live="polite"></p>
                        </div>

                        <div class="pov-field" data-field>
                            <div class="pov-field__control">
                                <input type="email" class="pov-input" id="register-email" name="email" placeholder=" "
                                       autocomplete="email" data-validate="email" aria-describedby="register-email-error">
                                <label class="pov-label" for="register-email">Email address</label>
                                <i class="bi bi-envelope pov-field__icon" aria-hidden="true"></i>
                                <span class="pov-field__line" aria-hidden="true"></span>
                            </div>
                            <p class="pov-field__error" id="register-email-error" aria-live="polite"></p>
                        </div>

                        <div class="pov-field" data-field>
                            <div class="pov-field__control">
                                <input type="password" class="pov-input" id="register-password" name="password" placeholder=" "
                                       autocomplete="new-password" data-validate="new-password" data-strength-input aria-describedby="register-password-error register-strength">
                                <label class="pov-label" for="register-password">Password</label>
                                <i class="bi bi-lock pov-field__icon" aria-hidden="true"></i>
                                <span class="pov-field__line" aria-hidden="true"></span>
                                <button type="button" class="pov-field__toggle" data-password-toggle aria-label="Show password" aria-pressed="false">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="strength" id="register-strength" data-strength data-level="0">
                                <div class="strength__bars" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
                                <span class="strength__label" data-strength-label>Use 8+ characters with letters and numbers.</span>
                            </div>
                            <p class="pov-field__error" id="register-password-error" aria-live="polite"></p>
                        </div>

                        <div class="pov-field" data-field>
                            <div class="pov-field__control">
                                <input type="password" class="pov-input" id="register-confirm" name="password_confirmation" placeholder=" "
                                       autocomplete="new-password" data-validate="confirm" data-confirm-of="register-password" aria-describedby="register-confirm-error">
                                <label class="pov-label" for="register-confirm">Confirm password</label>
                                <i class="bi bi-shield-lock pov-field__icon" aria-hidden="true"></i>
                                <span class="pov-field__line" aria-hidden="true"></span>
                            </div>
                            <p class="pov-field__error" id="register-confirm-error" aria-live="polite"></p>
                        </div>

                        <div class="pov-field mb-4" data-field>
                            <label class="pov-check">
                                <input type="checkbox" name="terms" data-validate="terms" aria-describedby="register-terms-error">
                                <span>I agree to the <a href="#" class="auth-link">community guidelines</a></span>
                            </label>
                            <p class="pov-field__error" id="register-terms-error" aria-live="polite"></p>
                        </div>

                        <button type="submit" class="btn-pov btn-pov-primary btn-pov-lg w-100">
                            <span class="btn-label">Create account <i class="bi bi-stars"></i></span>
                            <span class="btn-spinner" aria-hidden="true"></span>
                        </button>
                    </form>

                    <div class="auth-divider"><span>or sign up with</span></div>

                    <div class="auth-social">
                        <button type="button" class="btn-social btn-social--google" data-toast="Google sign-up is UI only for now." data-toast-title="Google">
                            <i class="bi bi-google"></i> Google
                        </button>
                        <button type="button" class="btn-social btn-social--discord" data-toast="Discord sign-up is UI only for now." data-toast-title="Discord">
                            <i class="bi bi-discord"></i> Discord
                        </button>
                    </div>

                    <p class="auth-switch">
                        Already have an account?
                        <button type="button" class="auth-link" data-auth-switch="login">Log in</button>
                    </p>
                </section>
            </div>
        </div>
    </div>
</div>
@endsection
