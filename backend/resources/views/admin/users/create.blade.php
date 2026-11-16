@extends('admin.layouts.app')

@section('title', 'Add New User')
@section('page_title', 'Create User Account')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 text-xs">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.users') }}" class="text-slate-500 hover:text-slate-900 transition flex items-center gap-1.5 font-medium mb-1">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Back to Users List</span>
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Add New User</h1>
            <p class="text-xs text-slate-500">Create an administrative staff member or customer account in the system database.</p>
        </div>
    </div>

    @if($errors->any())
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 space-y-1">
        <div class="font-bold flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span>Please correct the errors indicated below:</span>
        </div>
        <ul class="list-disc list-inside text-[11px] text-rose-700">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Account Basics Card -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs space-y-5">
            <h3 class="font-bold text-sm text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                <i class="fa-solid fa-id-card text-indigo-600"></i>
                <span>Identity & Contact Details</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">First Name *</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" placeholder="e.g. Liam"
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-1 @error('first_name') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-200 focus:ring-slate-900 @enderror">
                    @error('first_name')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Last Name *</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="e.g. Vance"
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-1 @error('last_name') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-200 focus:ring-slate-900 @enderror">
                    @error('last_name')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Email Address *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="liam@example.com"
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-1 @error('email') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-200 focus:ring-slate-900 @enderror">
                    @error('email')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @else
                        <p class="text-[11px] text-slate-400 mt-1">Must be unique across all accounts.</p>
                    @enderror
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Phone Number</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" placeholder="+1 (555) 234-5678"
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-1 @error('phone') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-200 focus:ring-slate-900 @enderror">
                    @error('phone')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Security & Role Assignment Card -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs space-y-5">
            <h3 class="font-bold text-sm text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                <i class="fa-solid fa-lock text-indigo-600"></i>
                <span>Security Credentials & Access Roles</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Account Password *</label>
                    <input type="password" name="password" required minlength="8" placeholder="Minimum 8 characters"
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-1 @error('password') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-200 focus:ring-slate-900 @enderror">
                    @error('password')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @else
                        <p class="text-[11px] text-slate-400 mt-1">Hashed securely via bcrypt/Argon2.</p>
                    @enderror
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Confirm Password *</label>
                    <input type="password" name="password_confirmation" required minlength="8" placeholder="Re-enter password"
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-1 @error('password_confirmation') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-200 focus:ring-slate-900 @enderror">
                    @error('password_confirmation')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Assign Role *</label>
                    <select name="role_id" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-1 bg-white @error('role_id') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-200 focus:ring-slate-900 @enderror">
                        <option value="">-- Select Assigned Role --</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                {{ $role->name }} ({{ $role->slug }})
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @else
                        <p class="text-[11px] text-slate-400 mt-1">Determines administrative access permissions.</p>
                    @enderror
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Account Status *</label>
                    <select name="status" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-1 bg-white @error('status') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-200 focus:ring-slate-900 @enderror">
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active (Immediate access)</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive (Login disabled)</option>
                        <option value="pending_activation" {{ old('status') === 'pending_activation' ? 'selected' : '' }}>Pending (Awaiting verification)</option>
                        <option value="suspended" {{ old('status') === 'suspended' ? 'selected' : '' }}>Suspended (Security locked)</option>
                    </select>
                    @error('status')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Optional Primary Address Card -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs space-y-5">
            <h3 class="font-bold text-sm text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                <i class="fa-solid fa-map-location-dot text-indigo-600"></i>
                <span>Initial Shipping Destination (Optional)</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block font-semibold text-slate-700 mb-1">Street Address</label>
                    <input type="text" name="street" value="{{ old('street') }}" placeholder="742 Evergreen Terrace"
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-1 @error('street') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-200 focus:ring-slate-900 @enderror">
                    @error('street')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Apartment / Suite</label>
                    <input type="text" name="apartment" value="{{ old('apartment') }}" placeholder="Suite 4B"
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-1 @error('apartment') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-200 focus:ring-slate-900 @enderror">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">City</label>
                    <input type="text" name="city" value="{{ old('city') }}" placeholder="Springfield"
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-1 @error('city') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-200 focus:ring-slate-900 @enderror">
                    @error('city')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">State / Region</label>
                    <input type="text" name="state" value="{{ old('state') }}" placeholder="OR"
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-1 @error('state') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-200 focus:ring-slate-900 @enderror">
                    @error('state')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Postal / ZIP Code</label>
                    <input type="text" name="zip_code" value="{{ old('zip_code') }}" placeholder="97477"
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-1 @error('zip_code') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-200 focus:ring-slate-900 @enderror">
                    @error('zip_code')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            <div class="w-full sm:w-1/3">
                <label class="block font-semibold text-slate-700 mb-1">Country</label>
                <input type="text" name="country" value="{{ old('country', 'United States') }}" placeholder="United States"
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-1 @error('country') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-200 focus:ring-slate-900 @enderror">
                @error('country')
                    <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                        <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                    </p>
                @enderror
            </div>
        </div>

        <!-- Submit Bar -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.users') }}" class="px-5 py-2.5 border border-slate-200 text-slate-700 rounded-lg font-semibold hover:bg-slate-50 transition">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg font-semibold transition shadow-xs flex items-center gap-2">
                <i class="fa-solid fa-check text-xs"></i>
                <span>Save New User</span>
            </button>
        </div>
    </form>
</div>
@endsection
