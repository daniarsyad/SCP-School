<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - SCP School</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-8 pt-8 pb-4 text-center border-b border-slate-100">
           
            <img src="{{ asset('images/logo-smkn1.png') }}" alt="Logo SMKN 1 Bantul" class="h-16 mx-auto mb-3">
            <h1 class="text-xl font-semibold text-slate-900">SCP School</h1>
            <p class="text-sm text-slate-500">SMK Negeri 1 Bantul</p>
        </div>

        <div class="px-8 py-7">
            <h2 class="text-lg font-semibold text-slate-900">Lupa kata sandi</h2>
            <p class="text-sm text-slate-500 mt-1 mb-5">
                @if(session('otp_verified'))
                    OTP benar. Buat password baru.
                @elseif(session('otp_sent'))
                    Masukkan kode OTP yang dikirim ke email.
                @else
                    Masukkan email untuk menerima OTP.
                @endif
            </p>

            @if(session('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg mb-5 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-5 text-sm">
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
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900">
                    </div>
                    <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-medium py-2.5 rounded-lg">
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
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input type="text" value="{{ session('email') }}" disabled
                               class="w-full px-4 py-2.5 border border-slate-200 bg-slate-50 rounded-lg text-slate-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Kode OTP (6 digit)</label>
                        <input type="text" name="otp" maxlength="6" inputmode="numeric" required
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-lg tracking-widest focus:outline-none focus:ring-2 focus:ring-slate-900"
                               placeholder="000000">
                    </div>
                    <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-medium py-2.5 rounded-lg">
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
                        <label class="block text-sm font-medium text-slate-700 mb-1">Password baru</label>
                        <input type="password" name="password" required minlength="6"
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Konfirmasi password</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900">
                    </div>
                    <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-medium py-2.5 rounded-lg">
                        Ubah Password
                    </button>
                </form>
            @endif

            <p class="text-center text-sm text-slate-500 mt-6">
                <a href="{{ route('login') }}" class="hover:underline">Kembali ke login</a>
            </p>
        </div>
    </div>
</body>
</html>