<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

$active_tab = $_GET['tab'] ?? 'orders';
// Stats — selalu dimuat (ringan, dipakai di semua halaman)
$total_orders = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$total_revenue = $pdo->query("SELECT SUM(price) FROM bookings WHERE payment_status = 'Paid'")->fetchColumn();
$active_orders = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status NOT IN ('Selesai')")->fetchColumn();
// Data dimuat HANYA sesuai tab yang aktif
$bookings = [];
$treatments = [];
$kurirs = [];
$customers = [];
if ($active_tab === 'orders') {
    // Tab Order: hanya butuh bookings + kurirs
    $stmt = $pdo->prepare("SELECT b.*, g.name as gear_name, u.name as customer_name, k.name as kurir_name 
                           FROM bookings b JOIN gears g ON b.gear_id = g.id 
                           JOIN users u ON b.user_id = u.id 
                           LEFT JOIN users k ON b.kurir_id = k.id
                           ORDER BY b.created_at DESC");
    $stmt->execute();
    $bookings = $stmt->fetchAll();
    $kurirs = $pdo->query("SELECT * FROM users WHERE role = 'kurir'")->fetchAll();
} elseif ($active_tab === 'services') {
    // Tab Layanan: hanya butuh treatments
    $treatments = $pdo->query("SELECT * FROM treatments")->fetchAll();
} elseif ($active_tab === 'users') {
    // Tab User: hanya butuh kurirs + customers
    $kurirs = $pdo->query("SELECT * FROM users WHERE role = 'kurir'")->fetchAll();
    $customers = $pdo->query("SELECT * FROM users WHERE role = 'customer'")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Outdoor Laundry</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Poppins:wght@500;700;800&display=swap" rel="stylesheet">
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
        }
        
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(el => {
                el.classList.remove('border-brand-500', 'text-brand-600');
                el.classList.add('border-transparent', 'text-slate-500');
            });
            
            document.getElementById(tabId).classList.remove('hidden');
            document.getElementById('btn-' + tabId).classList.remove('border-transparent', 'text-slate-500');
            document.getElementById('btn-' + tabId).classList.add('border-brand-500', 'text-brand-600');
        }
    </script>
</head>
<body class="bg-slate-50 min-h-screen font-sans text-slate-800">
    <!-- Navbar -->
    <nav class="bg-slate-900 text-white sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="font-display font-bold text-xl tracking-tight flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-brand-500"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Admin<span class="text-slate-400 font-normal ml-1">Panel</span>
                    </span>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="hidden md:flex flex-col items-end mr-2">
                        <span class="text-sm font-semibold"><?= htmlspecialchars($_SESSION['name']) ?></span>
                        <span class="text-[10px] text-brand-400 font-bold uppercase tracking-wider mt-0.5">Administrator</span>
                    </div>
                    <a href="logout.php" class="p-2 text-slate-400 hover:text-white hover:bg-slate-800 rounded-full transition-colors" title="Logout">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        
        <!-- Statistics Widgets -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-slate-200">
                <p class="text-sm font-semibold text-slate-500 mb-1 uppercase tracking-wider">Total Order</p>
                <p class="text-3xl font-display font-bold text-slate-900"><?= number_format($total_orders) ?></p>
            </div>
            <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-slate-200">
                <p class="text-sm font-semibold text-slate-500 mb-1 uppercase tracking-wider">Total Pendapatan (Omzet)</p>
                <p class="text-3xl font-display font-bold text-brand-600">Rp <?= number_format($total_revenue ?: 0, 0, ',', '.') ?></p>
            </div>
            <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-slate-200">
                <p class="text-sm font-semibold text-slate-500 mb-1 uppercase tracking-wider">Order Aktif</p>
                <p class="text-3xl font-display font-bold text-blue-600"><?= number_format($active_orders) ?></p>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="border-b border-slate-200 mb-8">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button id="btn-tab-orders" onclick="switchTab('tab-orders')" class="tab-btn border-brand-500 text-brand-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Manajemen Order
                </button>
                <button id="btn-tab-services" onclick="switchTab('tab-services')" class="tab-btn border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Manajemen Layanan
                </button>
                <button id="btn-tab-users" onclick="switchTab('tab-users')" class="tab-btn border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Manajemen User & Kurir
                </button>
            </nav>
        </div>

        <!-- TAB: MANAJEMEN ORDER -->
        <div id="tab-orders" class="tab-content">
            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Order</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Customer & Info</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Status Pembayaran</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Status Order & Kurir</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi (Update & Assign)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <?php foreach($bookings as $b): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="font-bold text-slate-900">#ODR-<?= str_pad($b['id'], 4, '0', STR_PAD_LEFT) ?></div>
                                    <div class="text-xs text-slate-500 mt-1"><?= date('d M Y', strtotime($b['created_at'])) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="font-bold text-slate-800"><?= htmlspecialchars($b['customer_name']) ?></div>
                                    <div class="text-xs text-slate-600 mt-1"><?= htmlspecialchars($b['gear_name']) ?> (Qty: <?= $b['quantity'] ?>)</div>
                                    <div class="text-xs text-slate-500 truncate max-w-[200px]" title="<?= htmlspecialchars($b['pickup_address']) ?>">Pickup: <?= htmlspecialchars($b['pickup_date']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-slate-800 mb-1">Rp <?= number_format($b['price'], 0, ',', '.') ?></div>
                                    <?php if($b['payment_status'] == 'Paid'): ?>
                                        <span class="px-2 py-1 text-[10px] font-bold rounded-full bg-green-100 text-green-700 uppercase">Paid (<?= $b['payment_method'] ?>)</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-[10px] font-bold rounded-full bg-red-100 text-red-700 uppercase">Unpaid (<?= $b['payment_method'] ?>)</span>
                                        <form action="api_tracking.php" method="POST" class="mt-2 inline-block">
                                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                            <input type="hidden" name="action" value="validate_payment">
                                            <button type="submit" class="text-[10px] bg-brand-500 text-white px-2 py-1 rounded hover:bg-brand-600">Validasi</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 inline-flex text-[11px] leading-5 font-bold rounded-full uppercase tracking-wider bg-slate-100 text-slate-700 mb-2">
                                        <?= htmlspecialchars($b['status']) ?>
                                    </span>
                                    <div class="text-xs text-slate-600">
                                        Kurir: <span class="font-semibold"><?= $b['kurir_name'] ? htmlspecialchars($b['kurir_name']) : '<span class="text-red-500 italic">Belum Di-assign</span>' ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm border-l border-slate-100">
                                    
                                    <!-- Assign Kurir -->
                                    <form action="api_tracking.php" method="POST" class="flex flex-col items-end space-y-2 mb-3 pb-3 border-b border-slate-100">
                                        <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                        <input type="hidden" name="action" value="assign_kurir">
                                        <div class="flex items-center space-x-2">
                                            <select name="kurir_id" required class="text-xs border border-slate-200 rounded px-2 py-1">
                                                <option value="">-- Pilih Kurir --</option>
                                                <?php foreach($kurirs as $k): ?>
                                                    <option value="<?= $k['id'] ?>" <?= $b['kurir_id'] == $k['id'] ? 'selected' : '' ?>><?= htmlspecialchars($k['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="text-[10px] bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700">Assign</button>
                                        </div>
                                    </form>

                                    <!-- Update Status & Photo -->
                                    <form action="api_tracking.php" method="POST" enctype="multipart/form-data" class="flex flex-col items-end space-y-2">
                                        <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                        <input type="hidden" name="action" value="update_status">
                                        <div class="flex items-center space-x-2 w-full justify-end">
                                            <select name="status" class="text-xs border border-slate-200 rounded px-2 py-1 w-32">
                                                <option value="Diterima Admin" <?= $b['status'] == 'Diterima Admin' ? 'selected' : '' ?>>Diterima Admin</option>
                                                <option value="Dicuci" <?= $b['status'] == 'Dicuci' ? 'selected' : '' ?>>Dicuci</option>
                                                <option value="Drying" <?= $b['status'] == 'Drying' ? 'selected' : '' ?>>Drying</option>
                                                <option value="Re-coating" <?= $b['status'] == 'Re-coating' ? 'selected' : '' ?>>Re-coating</option>
                                                <option value="Selesai Dicuci" <?= $b['status'] == 'Selesai Dicuci' ? 'selected' : '' ?>>Selesai Dicuci</option>
                                                <option value="Selesai Sepenuhnya" <?= $b['status'] == 'Selesai Sepenuhnya' ? 'selected' : '' ?>>Selesai Sepenuhnya</option>
                                            </select>
                                        </div>
                                        <div class="flex items-center justify-end w-full">
                                            <input type="file" name="photo" accept="image/*" class="text-[10px] text-slate-500 w-32 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                                            <button type="submit" class="bg-slate-900 text-white px-3 py-1 rounded text-xs font-bold hover:bg-slate-800 ml-2">Simpan</button>
                                        </div>
                                    </form>

                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: MANAJEMEN LAYANAN -->
        <div id="tab-services" class="tab-content hidden">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- List -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50/80">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Nama Treatment</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Tipe</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Harga</th>
                                    <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100">
                                <?php foreach($treatments as $t): ?>
                                <tr>
                                    <td class="px-6 py-4 text-sm font-semibold text-slate-800"><?= htmlspecialchars($t['name']) ?></td>
                                    <td class="px-6 py-4 text-sm text-slate-600"><?= htmlspecialchars($t['type']) ?></td>
                                    <td class="px-6 py-4 text-sm font-bold text-brand-600">Rp <?= number_format($t['price'], 0, ',', '.') ?></td>
                                    <td class="px-6 py-4 text-right flex justify-end items-center space-x-2">
                                        <button onclick="editPrice(<?= $t['id'] ?>, '<?= htmlspecialchars($t['name']) ?>', <?= $t['price'] ?>)" class="bg-blue-50 text-blue-600 hover:bg-blue-100 px-3 py-1.5 rounded-full text-[10px] font-bold transition-colors">Edit Harga</button>
                                        <form action="api_treatments.php" method="POST" onsubmit="return confirm('Hapus treatment ini?');" class="m-0">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="treatment_id" value="<?= $t['id'] ?>">
                                            <button type="submit" class="bg-red-50 text-red-600 hover:bg-red-100 px-3 py-1.5 rounded-full text-[10px] font-bold transition-colors">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Form -->
                <div class="lg:col-span-1">
                    <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-200">
                        <h2 class="font-display font-bold text-lg mb-4 text-slate-800">Tambah Treatment Baru</h2>
                        <form action="api_treatments.php" method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="create">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Treatment</label>
                                <input type="text" name="name" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-full text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Tipe</label>
                                <select name="type" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-full text-sm">
                                    <option value="Base">Base (Cuci Dasar)</option>
                                    <option value="Condition">Condition (Berdasarkan Kondisi)</option>
                                    <option value="Material">Material (Berdasarkan Bahan)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Harga (Rp)</label>
                                <input type="number" name="price" required min="0" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-full text-sm">
                            </div>
                            <button type="submit" class="w-full bg-brand-500 text-white py-3 mt-4 rounded-full font-bold hover:bg-brand-600">Simpan Treatment</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: MANAJEMEN USER & KURIR -->
        <div id="tab-users" class="tab-content hidden">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Customers -->
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
                        <h2 class="font-bold text-slate-800">Daftar Customer</h2>
                    </div>
                    <div class="p-4 overflow-y-auto max-h-96">
                        <ul class="space-y-3 text-sm">
                            <?php foreach($customers as $c): ?>
                            <li class="p-3 border border-slate-100 rounded-lg bg-white">
                                <div class="font-bold text-slate-800"><?= htmlspecialchars($c['name']) ?></div>
                                <div class="text-slate-500 text-xs"><?= htmlspecialchars($c['email']) ?> | <?= htmlspecialchars($c['phone']) ?></div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Kurir -->
                <div>
                    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-slate-100 bg-blue-50">
                            <h2 class="font-bold text-blue-900">Daftar Akun Kurir</h2>
                        </div>
                        <div class="p-4">
                            <ul class="space-y-3 text-sm">
                                <?php foreach($kurirs as $k): ?>
                                <li class="p-4 border border-blue-100 rounded-2xl bg-white flex justify-between items-center">
                                    <div>
                                        <div class="font-bold text-slate-800"><?= htmlspecialchars($k['name']) ?></div>
                                        <div class="text-slate-500 text-xs"><?= htmlspecialchars($k['phone']) ?></div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <form action="api_users.php" method="POST">
                                            <input type="hidden" name="action" value="update_kurir_status">
                                            <input type="hidden" name="user_id" value="<?= $k['id'] ?>">
                                            <select name="kurir_status" onchange="this.form.submit()" class="text-[10px] font-bold uppercase rounded-full px-2 py-1 outline-none <?= $k['kurir_status'] == 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' ?>">
                                                <option value="active" <?= $k['kurir_status'] == 'active' ? 'selected' : '' ?>>Aktif</option>
                                                <option value="inactive" <?= $k['kurir_status'] == 'inactive' ? 'selected' : '' ?>>Libur</option>
                                            </select>
                                        </form>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-200">
                        <h2 class="font-display font-bold text-lg mb-4 text-slate-800">Tambah Akun Kurir</h2>
                        <form action="api_users.php" method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="create_kurir">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1 ml-1">Nama Lengkap</label>
                                <input type="text" name="name" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-full text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1 ml-1">Email</label>
                                <input type="email" name="email" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-full text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1 ml-1">No. HP</label>
                                <input type="text" name="phone" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-full text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1 ml-1">Alamat Domisili</label>
                                <textarea name="address" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm"></textarea>
                            </div>
                            <div class="text-xs text-amber-600 mb-2 ml-1">* Password default untuk kurir baru adalah: <strong>123456</strong></div>
                            <button type="submit" class="w-full bg-brand-500 text-white py-3 mt-4 rounded-full font-bold hover:bg-brand-600">Daftarkan Kurir</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit Price Modal -->
    <div id="editPriceModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-[2rem] p-8 max-w-md w-full shadow-2xl">
            <h2 class="font-display font-bold text-xl mb-2">Edit Harga Treatment</h2>
            <p id="editTreatmentName" class="text-slate-500 text-sm mb-6"></p>
            <form action="api_treatments.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="update_price">
                <input type="hidden" name="treatment_id" id="editTreatmentId">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1 ml-1">Harga Baru (Rp)</label>
                    <input type="number" name="price" id="editTreatmentPrice" required min="0" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-full text-sm">
                </div>
                <div class="flex justify-end space-x-2 mt-6">
                    <button type="button" onclick="document.getElementById('editPriceModal').classList.add('hidden')" class="px-5 py-2.5 bg-slate-200 text-slate-700 rounded-full text-sm font-bold hover:bg-slate-300">Batal</button>
                    <button type="submit" class="px-5 py-2.5 bg-brand-500 text-white rounded-full text-sm font-bold hover:bg-brand-600">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editPrice(id, name, price) {
            document.getElementById('editTreatmentId').value = id;
            document.getElementById('editTreatmentName').innerText = name;
            document.getElementById('editTreatmentPrice').value = price;
            document.getElementById('editPriceModal').classList.remove('hidden');
        }
    </script>
</body>
</html>
