<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_email_identity_and_logo_can_be_updated(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'email_sender_name' => 'Consulta Laura',
                'email_logo' => UploadedFile::fake()->image('logo.png', 600, 240),
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Consulta Laura', $user->email_sender_name);
        $this->assertNotNull($user->email_logo_path);
        $this->assertStringStartsWith('email-logos/', $user->email_logo_path);
        Storage::disk('public')->assertExists($user->email_logo_path);
    }

    public function test_uploading_a_new_logo_replaces_and_deletes_the_previous_logo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('email-logos/previous.png', 'old logo');
        $user = User::factory()->create([
            'email_logo_path' => 'email-logos/previous.png',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'email_logo' => UploadedFile::fake()->image('replacement.jpg', 600, 240),
            ]);

        $response->assertSessionHasNoErrors();

        $user->refresh();

        Storage::disk('public')->assertMissing('email-logos/previous.png');
        Storage::disk('public')->assertExists($user->email_logo_path);
    }

    public function test_invalid_logo_is_rejected_without_changing_the_existing_logo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('email-logos/current.png', 'current logo');
        $user = User::factory()->create([
            'email_logo_path' => 'email-logos/current.png',
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'email_logo' => UploadedFile::fake()->create('logo.svg', 50, 'image/svg+xml'),
            ]);

        $response
            ->assertSessionHasErrors('email_logo')
            ->assertRedirect('/profile');

        $this->assertSame('email-logos/current.png', $user->refresh()->email_logo_path);
        Storage::disk('public')->assertExists('email-logos/current.png');
    }

    public function test_existing_logo_is_preserved_when_no_replacement_is_uploaded(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('email-logos/current.png', 'current logo');
        $user = User::factory()->create([
            'email_logo_path' => 'email-logos/current.png',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'email_sender_name' => 'Gabinete Laura',
            ]);

        $response->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame('Gabinete Laura', $user->email_sender_name);
        $this->assertSame('email-logos/current.png', $user->email_logo_path);
        Storage::disk('public')->assertExists('email-logos/current.png');
    }

    public function test_current_email_logo_is_served_through_the_public_laravel_route(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('email-logos/current.png', 'logo-content');
        User::factory()->create([
            'email_logo_path' => 'email-logos/current.png',
        ]);

        $response = $this->get(route('email-logo.show', ['filename' => 'current.png']));

        $response->assertOk();
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=604800', $cacheControl);
        $this->assertStringContainsString('immutable', $cacheControl);
        $this->assertSame('logo-content', $response->streamedContent());
    }

    public function test_unknown_or_replaced_email_logo_is_not_publicly_served(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('email-logos/old.png', 'old-logo');
        User::factory()->create([
            'email_logo_path' => 'email-logos/current.png',
        ]);

        $this->get(route('email-logo.show', ['filename' => 'old.png']))
            ->assertNotFound();
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
