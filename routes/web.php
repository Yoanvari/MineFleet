<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::redirect('/', '/login')->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Volt::route('admin/dashboard', 'pages.admin.dashboard')->name('admin.dashboard');
    Volt::route('admin/reservations', 'pages.admin.reservations')->name('admin.reservations');
    Volt::route('admin/drivers', 'pages.admin.drivers')->name('admin.drivers');
    Volt::route('admin/vehicles', 'pages.admin.vehicles')->name('admin.vehicles');
    Volt::route('admin/fuel-logs', 'pages.admin.fuel-logs')->name('admin.fuel-logs');
    Volt::route('admin/service-records', 'pages.admin.service-records')->name('admin.service-records');
    Volt::route('admin/reports', 'pages.admin.reports')->name('admin.reports');
});

Route::middleware(['auth', 'role:approver'])->group(function () {
    Volt::route('approver/dashboard', 'pages.approver.dashboard')->name('approver.dashboard');
    Volt::route('approver/pending-approvals', 'pages.approver.pending-approvals')->name('approver.pending-approvals');
    Volt::route('approver/approval-history', 'pages.approver.approval-history')->name('approver.approval-history');
});

require __DIR__.'/auth.php';
