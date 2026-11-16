<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('customer.{id}', function (User $user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('admin.orders', function (User $user) {
    return $user->isAdminStaff();
});

