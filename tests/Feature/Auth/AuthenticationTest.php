<?php

use App\Models\User;
use Livewire\Volt\Volt as LivewireVolt;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('admins can authenticate and are redirected to admin dashboard', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'password' => bcrypt('password'),
    ]);

    LivewireVolt::test('auth.login')
        ->set('email', $admin->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.dashboard', absolute: false));

    $this->assertAuthenticatedAs($admin);
});

test('approvers can authenticate and are redirected to approver dashboard', function () {
    $approver = User::factory()->create([
        'role' => 'approver',
        'password' => bcrypt('password'),
    ]);

    LivewireVolt::test('auth.login')
        ->set('email', $approver->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('approver.dashboard', absolute: false));

    $this->assertAuthenticatedAs($approver);
});

test('users cannot authenticate with an invalid password, regardless of role', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    LivewireVolt::test('auth.login')
        ->set('email', $admin->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

test('users can log out successfully', function () {
    $user = User::factory()->create(['role' => 'approver']);

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();
});