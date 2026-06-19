<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->safe()->except('email_logo');
        $previousLogoPath = $user->email_logo_path;
        $newLogoPath = null;

        if ($request->hasFile('email_logo')) {
            $newLogoPath = $request->file('email_logo')->store('email-logos', 'public');

            if (! $newLogoPath) {
                Log::error('Profile email logo storage failed.', ['user_id' => $user->id]);

                return Redirect::route('profile.edit')
                    ->withErrors(['email_logo' => 'No se pudo guardar el logo. Inténtalo de nuevo.']);
            }

            $validated['email_logo_path'] = $newLogoPath;
        }

        try {
            $user->fill($validated);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();
        } catch (Throwable $exception) {
            if ($newLogoPath) {
                Storage::disk('public')->delete($newLogoPath);
            }

            Log::error('Profile update failed after storing email identity.', [
                'user_id' => $user->id,
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        if (
            $newLogoPath
            && $previousLogoPath
            && $previousLogoPath !== $newLogoPath
            && str_starts_with($previousLogoPath, 'email-logos/')
            && ! Storage::disk('public')->delete($previousLogoPath)
        ) {
            Log::warning('Previous profile email logo could not be deleted.', [
                'user_id' => $user->id,
                'logo_path' => $previousLogoPath,
            ]);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
