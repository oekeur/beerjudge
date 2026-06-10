<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeerJudge — Log in</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-amber-50 flex items-center justify-center">
    <div class="w-full max-w-sm bg-white rounded-xl shadow p-8">
        <h1 class="text-2xl font-bold text-amber-900 mb-6">BeerJudge</h1>
        <form method="POST" action="/login">
            @csrf
            @if ($errors->any())
                <p class="text-red-600 text-sm mb-4">{{ $errors->first() }}</p>
            @endif
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
                    required autofocus>
            </div>
            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" id="password" name="password"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
                    required>
            </div>
            <button type="submit"
                class="w-full bg-amber-600 text-white rounded-lg py-2 text-sm font-medium hover:bg-amber-700">
                Log in
            </button>
        </form>
    </div>
</body>
</html>
