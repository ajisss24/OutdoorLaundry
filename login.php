<?php
session_start();

/*
|--------------------------------------------------------------------------
| Dependency Inversion Principle (DIP)
|--------------------------------------------------------------------------
| High-level module tidak bergantung langsung pada detail redirect.
| Keduanya bergantung pada abstraction/interface.
|--------------------------------------------------------------------------
*/

interface RedirectServiceInterface
{
    public function redirectByRole(string $role): void;
}

class RoleRedirectService implements RedirectServiceInterface
{
    private array $routes;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function redirectByRole(string $role): void
    {
        $defaultRoute = $this->routes['customer'];

        header('Location: ' . ($this->routes[$role] ?? $defaultRoute));
        exit;
    }
}

class AuthChecker
{
    private RedirectServiceInterface $redirectService;

    public function __construct(RedirectServiceInterface $redirectService)
    {
        $this->redirectService = $redirectService;
    }

    public function handleSession(): void
    {
        if (!isset($_SESSION['user_id'])) {
            return;
        }

        $role = $_SESSION['role'] ?? 'customer';

        $this->redirectService->redirectByRole($role);
    }
}

/*
|--------------------------------------------------------------------------
| Dependency Injection
|--------------------------------------------------------------------------
*/

$redirectService = new RoleRedirectService([
    'admin'    => 'dashboard_admin.php',
    'kurir'    => 'dashboard_kurir.php',
    'customer' => 'dashboard_customer.php',
]);

$authChecker = new AuthChecker($redirectService);
$authChecker->handleSession();

$errorMessage = isset($_GET['error'])
    ? 'Email atau password salah! Silakan periksa kembali.'
    : null;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Outdoor Laundry</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Poppins:wght@500;700;800&display=swap"
        rel="stylesheet"
    >

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#FFF8F5',
                            100: '#FDF4F0',
                            500: '#F05023',
                            600: '#D9451D',
                            700: '#B83512',
                            900: '#7A2209',
                        }
                    }
                }
            }
        };
    </script>
</head>

<body class="bg-brand-50 min-h-screen flex items-center justify-center p-4 font-sans">

    <main class="w-full max-w-[1000px] overflow-hidden rounded-[2rem] bg-white shadow-xl flex flex-col md:flex-row">

        <!-- Left -->
        <section class="relative hidden w-1/2 overflow-hidden bg-brand-500 md:block">

            <img
                src="https://images.unsplash.com/photo-1551632811-561732d1e306?q=80&w=2070&auto=format&fit=crop"
                alt="Hiking Mountain"
                class="absolute inset-0 h-full w-full object-cover opacity-30 mix-blend-multiply grayscale"
            >

            <div class="absolute inset-0 bg-gradient-to-t from-brand-900/90 to-transparent"></div>

            <div class="relative z-10 flex h-full flex-col justify-end p-12 text-white">

                <a
                    href="index.php"
                    class="absolute top-8 left-8 flex items-center text-white/80 hover:text-white transition"
                >
                    ← Kembali
                </a>

                <h2 class="font-display text-4xl font-bold mb-4 leading-tight">
                    Bersihkan Gearmu,<br>
                    Siap Bertualang Lagi.
                </h2>

                <p class="max-w-sm text-sm text-brand-100">
                    Layanan laundry profesional khusus peralatan outdoor.
                </p>

            </div>
        </section>

        <!-- Right -->
        <section class="w-full md:w-1/2 p-10 sm:p-14 flex flex-col justify-center bg-white">

            <div class="mb-10">

                <div class="flex items-center mb-6 text-brand-500">
                    <span class="font-display text-xl font-bold tracking-tight text-slate-900">
                        OutdoorLaundry
                    </span>
                </div>

                <h1 class="font-display text-3xl font-bold text-slate-900">
                    Selamat Datang
                </h1>

                <p class="text-slate-500 mt-2 text-sm">
                    Silakan login untuk mengelola layanan laundry gear Anda.
                </p>

            </div>

            <?php if ($errorMessage): ?>
                <div class="mb-6 rounded-2xl border border-red-100 bg-red-50 p-4 text-sm font-medium text-red-600">
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <form action="auth_process.php" method="POST" class="space-y-5">

                <input type="hidden" name="action" value="login">

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5 ml-1">
                        Email Address
                    </label>

                    <input
                        type="email"
                        name="email"
                        required
                        placeholder="pendaki@example.com"
                        class="w-full rounded-full border border-slate-100 bg-slate-50 px-5 py-3.5 text-sm outline-none transition-all focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5 ml-1">
                        Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        required
                        placeholder="••••••••"
                        class="w-full rounded-full border border-slate-100 bg-slate-50 px-5 py-3.5 text-sm outline-none transition-all focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full rounded-full bg-brand-500 py-3.5 font-bold text-white shadow-lg shadow-brand-500/30 transition-all hover:-translate-y-0.5 hover:bg-brand-600"
                >
                    Masuk ke Dashboard
                </button>

            </form>

            <p class="mt-8 text-center text-sm text-slate-500">
                Belum memiliki akun?

                <a
                    href="register.php"
                    class="font-bold text-brand-500 hover:text-brand-600 transition"
                >
                    Daftar sekarang
                </a>
            </p>

        </section>

    </main>

</body>
</html>
