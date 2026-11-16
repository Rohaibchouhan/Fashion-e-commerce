<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Sign In | WearDay Fashion</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="h-full flex items-center justify-center p-6 text-slate-800">
    <div class="w-full max-w-md bg-white rounded-xl shadow-2xl overflow-hidden border border-slate-100">
        <div class="p-8 text-center bg-slate-950 text-white">
            <div class="w-12 h-12 bg-white text-slate-950 rounded-lg flex items-center justify-center mx-auto mb-3 font-bold text-xl tracking-wider">WD</div>
            <h2 class="text-xl font-bold tracking-tight">WearDay Atelier Admin</h2>
            <p class="text-xs text-slate-400 mt-1">Authorized store management credentials only</p>
        </div>

        <div class="p-8">
            @if($errors->any())
            <div class="mb-5 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs">
                {{ $errors->first() }}
            </div>
            @endif

            <form action="{{ route('admin.login.submit', absolute: false) }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Email Address</label>
                    <input type="email" name="email" value="{{ old('email', 'admin@wearday.com') }}" required
                           class="w-full px-3 py-2.5 rounded-lg border focus:outline-none focus:ring-2 @error('email') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-300 focus:ring-slate-900 text-slate-800 @enderror">
                    @error('email')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Password</label>
                    <input type="password" name="password" value="Admin1234!" required
                           class="w-full px-3 py-2.5 rounded-lg border focus:outline-none focus:ring-2 @error('password') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-300 focus:ring-slate-900 text-slate-800 @enderror">
                    @error('password')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center space-x-2 text-slate-600">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-slate-900">
                        <span>Remember credentials</span>
                    </label>
                </div>

                <button type="submit" class="w-full mt-4 bg-slate-950 hover:bg-slate-800 text-white font-semibold py-2.5 rounded-lg transition tracking-wide text-xs">
                    Access Admin Portal
                </button>
            </form>

            <div class="mt-6 pt-4 border-t border-slate-100 text-center text-[11px] text-slate-400">
                Default: <code>admin@wearday.com</code> / <code>Admin1234!</code>
            </div>
        </div>
    </div>
</body>
</html>

