<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor App</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
     @vite('resources/css/app.css')
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <!-- Header -->
    <header class="bg-blue-600 text-white py-4 shadow">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <h1 class="text-xl font-bold">Doctor App</h1>
            @auth('doctor')
                <span>Welcome, {{ auth('doctor')->user()->name }}</span>

                
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">Logout</button>
                </form>


            @endauth 

        
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-6">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-3 text-center">
        &copy; {{ date('Y') }} Doctor App. All rights reserved.
    </footer>

    <!-- JS -->
    @vite('resources/js/app.js')
</body>
</html>