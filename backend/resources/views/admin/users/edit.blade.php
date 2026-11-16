@extends('admin.layouts.app')

@section('title', 'Edit User: ' . $user->name)
@section('page_title', 'Edit User Account: ' . $user->name)

@section('content')
<div class="max-w-4xl mx-auto space-y-6 text-xs">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.users') }}" class="text-slate-500 hover:text-slate-900 transition flex items-center gap-1.5 font-medium mb-1">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Back to Users List</span>
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit User Account</h1>
            <p class="text-xs text-slate-500">Update identity, assign security roles, or alter account standing.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.users.show', $user->id) }}" class="px-3.5 py-2 border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-lg font-semibold transition">
                View Full Profile
            </a>
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

    <form action="{{ route('admin.users.update', $user->id) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Identity & Contact Card -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs space-y-5">
            <h3 class="font-bold text-sm text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                <i class="fa-solid fa-id-card text-indigo-600"></i>
                <span>User Profile & Contact Information</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-1 @error('name') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-200 focus:ring-slate-900 @enderror">
                    @error('name')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Phone Number</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+1 (555) 234-5678"
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-1 @error('phone') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-200 focus:ring-slate-900 @enderror">
                    @error('phone')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Email Address *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-1 @error('email') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-200 focus:ring-slate-900 @enderror">
                    @error('email')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Reset Password</label>
                        <input type="password" name="password" minlength="8" placeholder="Leave blank to keep"
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-1 @error('password') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-200 focus:ring-slate-900 @enderror">
                        @error('password')
                            <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                                <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Confirm Reset</label>
                        <input type="password" name="password_confirmation" minlength="8" placeholder="Re-enter password"
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-1 @error('password_confirmation') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-200 focus:ring-slate-900 @enderror">
                        @error('password_confirmation')
                            <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                                <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Role & Status Assignment Card -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs space-y-5">
            <h3 class="font-bold text-sm text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                <i class="fa-solid fa-user-shield text-indigo-600"></i>
                <span>Role & Account Status</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Assigned Role *</label>
                    <select name="role_id" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-1 bg-white @error('role_id') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-200 focus:ring-slate-900 @enderror"
                            {{ ($user->isSuperAdmin() && $user->id === auth()->id()) ? 'disabled' : '' }}>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id', $user->roles->first()?->id) == $role->id ? 'selected' : '' }}>
                                {{ $role->name }} ({{ $role->slug }})
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                    @if($user->isSuperAdmin() && $user->id === auth()->id())
                        <input type="hidden" name="role_id" value="{{ $user->roles->first()?->id }}">
                        <p class="text-[11px] text-amber-600 mt-1">You cannot remove your own Super Administrator role.</p>
                    @else
                        <p class="text-[11px] text-slate-400 mt-1">Controls administrative dashboard permissions and features.</p>
                    @endif
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Account Status *</label>
                    <select name="status" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-1 bg-white @error('status') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-200 focus:ring-slate-900 @enderror"
                            {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                        <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $user->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="pending_activation" {{ old('status', $user->status) === 'pending_activation' ? 'selected' : '' }}>Pending Verification</option>
                        <option value="suspended" {{ old('status', $user->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                    @error('status')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                    @if($user->id === auth()->id())
                        <input type="hidden" name="status" value="{{ $user->status }}">
                        <p class="text-[11px] text-amber-600 mt-1">You cannot suspend or deactivate your own logged-in account.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Submit Bar -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.users') }}" class="px-5 py-2.5 border border-slate-200 text-slate-700 rounded-lg font-semibold hover:bg-slate-50 transition">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg font-semibold transition shadow-xs flex items-center gap-2">
                <i class="fa-solid fa-check text-xs"></i>
                <span>Update User Record</span>
            </button>
        </div>
    </form>
</div>
@endsection
