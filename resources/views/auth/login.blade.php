<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SCP School</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">

            {{-- Header --}}
            <div class="px-8 pt-8 pb-6 text-center border-b border-slate-100">
                <img src="{{ asset('images/logo-smkn1.png') }}"
                     alt="SMK Negeri 1 Bantul"
                     class="mx-auto h-16 w-auto object-contain mb-3">
                <h1 class="text-lg font-semibold text-slate-800 tracking-tight">SCP School</h1>
                <p class="text-xs text-slate-500 mt-0.5">SMK Negeri 1 Bantul</p>
            </div>

            {{-- Form --}}
            <div class="px-8 py-7">
                <h2 class="text-base font-semibold text-slate-800 mb-1">Masuk ke akun</h2>
                <p class="text-xs text-slate-500 mb-5">Panel Waka Kurikulum</p>

                @if(session('success'))
                    <div class="mb-4 px-3.5 py-2.5 rounded-lg bg-emerald-50 text-emerald-700 text-sm border border-emerald-200">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 px-3.5 py-2.5 rounded-lg bg-red-50 text-red-700 text-sm border border-red-200">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('login') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Username</label>
                        <input type="text" name="username" value="{{ old('username') }}" required autofocus
                               placeholder="Masukkan username"
                               class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg text-slate-800
                                      focus:outline-none focus:border-slate-500
                                      placeholder:text-slate-400">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Password</label>
                        <input type="password" name="password" required
                               placeholder="Masukkan password"
                               class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg text-slate-800
                                      focus:outline-none focus:border-slate-500
                                      placeholder:text-slate-400">
                    </div>

                    <button type="submit"
                            class="w-full mt-1 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium
                                   py-2.5 rounded-lg transition">
                        Masuk
                    </button>
                </form>

                <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                    <a href="{{ route('password.request') }}"
                       class="inline-block text-xs text-slate-500 hover:text-slate-800 hover:underline">
                        Lupa kata sandi?
                    </a>
                </div>
            </div>
        </div>

        <p class="text-center text-[11px] text-slate-400 mt-5">
            &copy; {{ date('Y') }} SMK Negeri 1 Bantul · SCP School
        </p>
    </div>

</body>
</html>
