<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SCP School</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="bg-blue-700 px-8 py-6 text-center">
                <h1 class="text-2xl font-bold text-white">SCP School</h1>
                <p class="text-blue-100 text-sm mt-1">School Class Progress</p>
            </div>

            <!-- Form -->
            <div class="px-8 py-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-1">Masuk ke Akun</h2>
                <p class="text-sm text-gray-500 mb-6">Panel Waka Kurikulum & Mobile App</p>

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-5 text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('login') }}" method="POST" class="space-y-5">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Username</label>
                        <input type="text" name="username" value="{{ old('username') }}" required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Masukkan username">
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-1.5">
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                        </div>
                        <input type="password" name="password" required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Masukkan password">
                    </div>

                    <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition">
                        Masuk
                    </button>
                </form>

                <!-- Divider -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-3 bg-white text-gray-400">atau</span>
                    </div>
                </div>

                <!-- Lupa Kata Sandi (Dipindahkan ke sini) -->
                <p class="text-center text-sm mb-2">
                   <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:underline">Lupa kata sandi?</a>
                </p>

                <!-- Daftar -->
                <p class="text-center text-sm text-gray-600">
                    Belum punya akun?
                    <a href="{{ route('register') }}" class="text-blue-600 font-medium hover:underline">Daftar sekarang</a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-xs text-gray-400 mt-6">
            &copy; {{ date('Y') }} SCP School • Uji Kompetensi Keahlian
        </p>
    </div>

</body>
</html>
