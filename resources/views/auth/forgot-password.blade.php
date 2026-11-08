<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Kata Sandi - SCP School</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-smkn1.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-smkn1.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">

        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
            <div class="px-8 pt-8 pb-6 text-center border-b border-slate-100">
                <a href="{{ route('landing') }}" class="group inline-block focus:outline-none" title="Kembali ke Beranda">
                    <img src="{{ asset('images/logo-smkn1.png') }}" alt="Logo SMKN 1 Bantul" class="h-16 w-auto object-contain mx-auto mb-3 transition transform group-hover:scale-105 duration-200">
                    <h1 class="text-lg font-semibold text-slate-800 tracking-tight group-hover:text-blue-600 transition">SCP School</h1>
                    <p class="text-xs text-slate-500 mt-0.5">SMK Negeri 1 Bantul</p>
                </a>
            </div>

            <div class="px-8 py-7">
                <h2 class="text-base font-semibold text-slate-800 mb-1">Lupa kata sandi</h2>
                <p class="text-xs text-slate-500 mb-5">
                    @if(session('otp_verified'))
                        Kode OTP terverifikasi. Silakan buat kata sandi baru.
                    @elseif(session('otp_sent'))
                        Masukkan kode OTP 6 digit yang dikirim ke email Anda.
                    @else
                        Masukkan alamat email terdaftar untuk menerima kode OTP.
                    @endif
                </p>

                @if(session('success'))
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-3.5 py-2.5 rounded-lg mb-4 text-sm">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 px-3.5 py-2.5 rounded-lg mb-4 text-sm">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Step 1: Input Email --}}
                @if(!session('otp_sent') && !session('otp_verified'))
                    <form action="{{ route('password.otp') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                   placeholder="Masukkan email terdaftar"
                                   class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg text-slate-800 focus:outline-none focus:border-slate-500">
                        </div>
                        <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium py-2.5 rounded-lg transition">
                            Kirim OTP
                        </button>
                    </form>
                @endif

                {{-- Step 2: Input OTP --}}
                @if(session('otp_sent') && !session('otp_verified'))
                    <form action="{{ route('password.verify-otp') }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="email" value="{{ session('email') }}">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Email</label>
                            <input type="text" value="{{ session('email') }}" disabled
                                   class="w-full px-3.5 py-2 text-sm border border-slate-200 bg-slate-50 rounded-lg text-slate-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Kode OTP (6 digit)</label>
                            <input type="text" name="otp" maxlength="6" inputmode="numeric" required
                                   class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg tracking-widest text-slate-800 focus:outline-none focus:border-slate-500"
                                   placeholder="000000">
                        </div>
                        <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium py-2.5 rounded-lg transition">
                            Verifikasi OTP
                        </button>
                    </form>
                @endif

                {{-- Step 3: Input Password Baru --}}
                @if(session('otp_verified'))
                    <form action="{{ route('password.reset') }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="email" value="{{ session('email') }}">
                        <input type="hidden" name="otp" value="{{ session('otp') }}">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Kata sandi baru</label>
                            <input type="password" name="password" required minlength="6"
                                   class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg text-slate-800 focus:outline-none focus:border-slate-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Konfirmasi kata sandi</label>
                            <input type="password" name="password_confirmation" required
                                   class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg text-slate-800 focus:outline-none focus:border-slate-500">
                        </div>
                        <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium py-2.5 rounded-lg transition">
                            Simpan Kata Sandi
                        </button>
                    </form>
                @endif

                <div class="mt-6 pt-5 border-t border-slate-100 flex items-center justify-between text-xs">
                    <a href="{{ route('landing') }}" class="inline-flex items-center gap-1.5 text-slate-500 hover:text-blue-600 transition">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                        </svg>
                        <span>Halaman Utama</span>
                    </a>
                    <a href="{{ route('login') }}" class="text-slate-500 hover:text-slate-800 hover:underline">
                        ← Kembali ke login
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


