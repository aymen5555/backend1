<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\NewUserRegistered;
use App\Services\EmailVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AuthRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_succeeds_when_verification_email_fails(): void
    {
        $this->app->bind(EmailVerificationService::class, function () {
            $service = $this->getMockBuilder(EmailVerificationService::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['issueAndSend', 'verificationUrl'])
                ->getMock();

            $service->method('issueAndSend')->willThrowException(new RuntimeException('SMTP failed'));
            $service->method('verificationUrl')->willReturn('http://localhost:4200/auth/verify-email?token=test');

            return $service;
        });

        $response = $this->postJson('/api/auth/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'new.user@example.com',
            'phone' => '123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'client',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Account created successfully. Please verify your email.');

        $this->assertDatabaseHas('users', [
            'email' => 'new.user@example.com',
        ]);
    }

    public function test_registration_creates_notification_for_super_admins(): void
    {
        $superAdmin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => 'super_admin',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->postJson('/api/auth/register', [
            'first_name' => 'New',
            'last_name' => 'Client',
            'email' => 'client.notify@example.com',
            'phone' => '123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'client',
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $superAdmin->id,
            'notifiable_type' => User::class,
            'type' => NewUserRegistered::class,
        ]);
    }
}
