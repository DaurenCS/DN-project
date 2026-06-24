<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (!Auth::check()) {
        return redirect('/admin/login');
    }

    $user = Auth::user();

    if ($user->hasRole('admin')) {
        return redirect('/admin');
    }

    if ($user->hasRole('curator')) {
        return redirect('/curator');
    }

    return redirect('/admin/login');
});
