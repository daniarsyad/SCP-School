<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - SCP School</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-blue-700 px-8 py-6 text-center">
                <h1 class="text-2xl font-bold text-white">SCP School</h1>
                <p class="text-blue-100 text-sm mt-1">Reset Password</p>
            </div>

            <div class="px-8 py-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-2">Lupa Kata Sandi</h2>
                <p class="text-sm text-gray-500 mb-6">Masukkan username dan password baru Anda</p>

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-5 text-sm">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('password.request') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" name="username" value="{{ old('username') }}" required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Masukkan username">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                        <input type="password" name="password" required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Minimal 6 karakter">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition">
                        Ubah Password
                    </button>
                </form>

                <p class="text-center text-sm text-gray-600 mt-6">
                    <a href="{{ route('login') }}" class="text-blue-600 font-medium hover:underline">← Kembali ke Login</a>
                </p>
            </div>
        </div>
    </div>

</body>
</html>