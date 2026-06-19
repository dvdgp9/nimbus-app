<section class="profile-section">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="profile-description mt-1 text-sm">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="profile-help text-sm mt-2">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="profile-inline-action underline text-sm rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-400/40">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="email_sender_name" value="Nombre visible del remitente" />
            <x-text-input
                id="email_sender_name"
                name="email_sender_name"
                type="text"
                class="mt-1 block w-full"
                :value="old('email_sender_name', $user->email_sender_name)"
                autocomplete="organization"
                maxlength="255"
            />
            <p class="profile-help mt-1 text-sm">
                Si se deja vacío, los emails se enviarán con tu nombre de perfil.
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('email_sender_name')" />
        </div>

        <div>
            <x-input-label for="email_logo" value="Logo para la cabecera del email" />

            @if ($user->email_logo_path)
                <div class="mt-2 mb-3 rounded-lg border border-gray-200 bg-white p-3">
                    <img
                        src="{{ $user->email_logo_url }}"
                        alt="Logo actual"
                        class="max-h-20 max-w-full object-contain"
                    >
                </div>
            @endif

            <input
                id="email_logo"
                name="email_logo"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="profile-file-input mt-1 block w-full text-sm file:mr-4 file:rounded-md file:border-0 file:px-4 file:py-2 file:text-sm file:font-medium"
            >
            <p class="profile-help mt-1 text-sm">
                JPG, PNG o WebP, máximo 2 MB. Si subes un logo, sustituirá al nombre en la cabecera.
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('email_logo')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button class="profile-save-button">{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="profile-saved text-sm"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
