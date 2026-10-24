<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - SCP School</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">

            <div class="px-8 pt-8 pb-6 text-center border-b border-slate-100">
                <img src="{{ asset('images/logo-smkn1.png') }}"
                     alt="SMK Negeri 1 Bantul"
                     class="mx-auto h-20 w-auto object-contain mb-3">
                <h1 class="text-lg font-semibold text-slate-800 tracking-tight">SCP School</h1>
                <p class="text-xs text-slate-500 mt-0.5">SMK Negeri 1 Bantul</p>
            </div>

            <div class="px-8 py-7">
                <h2 class="text-base font-semibold text-slate-800 mb-1">Buat akun baru</h2>
                <p class="text-xs text-slate-500 mb-5">Pendaftaran akun Waka Kurikulum</p>

                @if($errors->any())
                    <div class="mb-4 px-3 py-2.5 rounded-lg bg-red-50 text-red-700 text-sm border border-red-100">
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('register') }}" method="POST" class="space-y-3.5">
                    @csrf

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Nama lengkap</label>
                        <input type="text" name="fullname" value="{{ old('fullname') }}" required
                               class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-lg
                                      focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Username</label>
                        <input type="text" name="username" value="{{ old('username') }}" required
                               class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-lg
                                      focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               placeholder="contoh@email.com"
                               class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-lg
                                      focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300
                                      placeholder:text-slate-400">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Role</label>
                        <select name="role_id" required
                                class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-lg
                                       focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300 bg-white">
                            <option value="">-- Pilih role --</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                    {{ $role->role_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Password</label>
                        <input type="password" name="password" required
                               class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-lg
                                      focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Konfirmasi password</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-lg
                                      focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300">
                    </div>

                    <button type="submit"
                            class="w-full mt-1 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium
                                   py-2.5 rounded-lg transition">
                        Daftar
                    </button>
                </form>

                <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                    <p class="text-xs text-slate-400">
                        Sudah punya akun?
                        <a href="{{ route('login') }}" class="text-slate-600 font-medium hover:text-slate-800">Masuk</a>
                    </p>
                </div>
            </div>
        </div>

        <p class="text-center text-[11px] text-slate-400 mt-5">
            &copy; {{ date('Y') }} SMK Negeri 1 Bantul · SCP School
        </p>
    </div>

</body>
</html>