$(document).ready(function() {
    
    // --- 1. ANIMASI COUNT-UP (Angka Berjalan) ---
    $('.total-count').each(function () {
        const $this = $(this);
        const target = parseInt($this.text());
        $({ Counter: 0 }).animate({ Counter: target }, {
            duration: 2000,
            easing: 'swing',
            step: function () {
                $this.text(Math.ceil(this.Counter));
            }
        });
    });

    // --- 2. DATATABLES ---
    if ($('#managerTable').length) {
        $('#managerTable').DataTable({
            "responsive": true,
            "pageLength": 10,
            "order": [[ 0, "asc" ]],
            "language": {
                "search": "Quick Search:",
                "paginate": {
                    "previous": "<i class='fas fa-chevron-left'></i>",
                    "next": "<i class='fas fa-chevron-right'></i>"
                }
            }
        });
    }

    if ($('#logTable').length) {
        $('#logTable').DataTable({
            "order": [[ 0, "desc" ]],
            "pageLength": 25
        });
    }

    // --- 3. VALIDATOR ANGKA & AUTO-FORMAT ---
    $(document).on('input', '.office-format, [name="phone_personal"]', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        // Hapus angka 0 di awal jika ada
        if (this.value.startsWith('0')) {
            this.value = this.value.replace(/^0+/, '');
        }
    });

    // --- 4. VISUALISASI CHART ---
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { 
                beginAtZero: true,
                grid: { color: 'rgba(255,255,255,0.05)' }, 
                ticks: { color: '#888', stepSize: 1 } 
            },
            x: { grid: { display: false }, ticks: { color: '#888' } }
        }
    };

    // Weekly Chart
    if (document.getElementById('weeklyChart') && typeof weeklyLabels !== 'undefined') {
        const ctxWeekly = document.getElementById('weeklyChart').getContext('2d');
        new Chart(ctxWeekly, {
            type: 'line',
            data: {
                labels: weeklyLabels,
                datasets: [{
                    data: weeklyData,
                    borderColor: '#D4AF37',
                    backgroundColor: 'rgba(212, 175, 55, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: chartOptions
        });
    }

    // Monthly Chart
    if (document.getElementById('monthlyChart') && typeof monthlyLabels !== 'undefined') {
        new Chart(document.getElementById('monthlyChart'), {
            type: 'bar',
            data: {
                labels: monthlyLabels,
                datasets: [{ 
                    data: monthlyData, 
                    backgroundColor: '#D4AF37',
                    borderRadius: 5
                }]
            },
            options: chartOptions
        });
    }
});

// --- 5. FUNGSI MODAL EDIT ---
function openEditModal(data) {
    // 1. Ambil elemen modal
    const modalElement = document.getElementById('editModal');
    if (!modalElement) {
        console.error("Elemen editModal tidak ditemukan!");
        return;
    }

    // 2. Isi data ke dalam input modal
    document.getElementById('edit-id').value = data.id || '';
    document.getElementById('edit-name').value = data.name || '';
    document.getElementById('edit-title').value = data.title || '';
    document.getElementById('edit-email').value = data.email || '';
    
    // Bersihkan prefix +62 agar tidak double saat diedit
    let waPersonal = (data.phone_personal || '').replace('+62 ', '').trim();
    let officeLine = (data.phone_office || '').replace('+62 ', '').trim();
    
    document.getElementById('edit-phone-p').value = waPersonal;
    document.getElementById('edit-phone-o').value = officeLine;

    // 3. Tampilkan modal menggunakan Bootstrap 5
    const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
    modalInstance.show();
}

// --- 6. QR CODE & COPY LINK ---
function copyCardLink(slug) {
    const fullUrl = `${window.location.origin}${window.location.pathname.replace('admin.php', 'index.php')}?name=${slug}`;
    navigator.clipboard.writeText(fullUrl).then(() => {
        Swal.fire({ 
            icon: 'success', 
            title: 'Link Copied!', 
            background: '#1a1a1a', 
            color: '#D4AF37', 
            timer: 1500, 
            showConfirmButton: false 
        });
    });
}

async function downloadQRWithLogo(slug, name) {
    Swal.fire({ title: 'Generating...', background: '#1a1a1a', color: '#fff', didOpen: () => Swal.showLoading() });
    try {
        const canvas = document.createElement("canvas");
        const ctx = canvas.getContext("2d");
        const size = 1000;
        canvas.width = size; canvas.height = size;

        const profileUrl = `${window.location.origin}${window.location.pathname.replace('admin.php', 'index.php')}?name=${slug}`;
        const qrImg = new Image();
        qrImg.crossOrigin = "anonymous";
        qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=1000x1000&data=${encodeURIComponent(profileUrl)}&ecc=H`;

        qrImg.onload = function() {
            ctx.fillStyle = "white";
            ctx.fillRect(0, 0, size, size);
            ctx.drawImage(qrImg, 0, 0, size, size);

            const logo = new Image();
            logo.src = "pics/Logo.png"; 
            logo.onload = function() {
                const lSize = size * 0.22;
                const pos = (size - lSize) / 2;
                ctx.fillStyle = "white";
                ctx.fillRect(pos-15, pos-15, lSize+30, lSize+30);
                ctx.drawImage(logo, pos, pos, lSize, lSize);
                saveCanvas(canvas, name);
            };
            logo.onerror = () => saveCanvas(canvas, name);
        };
    } catch (e) { Swal.fire('Error', 'Failed to generate QR', 'error'); }
}

function saveCanvas(canvas, name) {
    const link = document.createElement("a");
    link.download = `QR-${name.replace(/\s+/g, '-').toLowerCase()}.png`;
    link.href = canvas.toDataURL("image/png");
    link.click();
    Swal.close();
}

// --- 7. FILE VALIDATOR (Auto-Compress Notice) ---
/**
 * Fungsi Validasi Format dan Ukuran File secara Instan
 *
 */
function validateFile(input) {
    const file = input.files[0];
    const allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    const maxSize = 20 * 1024 * 1024; // Batas 20MB sesuai diskusi sebelumnya

    if (file) {
        const fileName = file.name.toLowerCase();
        const fileExtension = fileName.split('.').pop();

        // 1. Cek Format/Ekstensi
        if (!allowedExtensions.includes(fileExtension)) {
            Swal.fire({
                icon: 'error',
                title: 'Format Salah',
                text: 'Hanya diizinkan format JPG, JPEG, PNG, atau WEBP.',
                background: '#1a1a1a',
                color: '#D4AF37',
                confirmButtonColor: '#D4AF37'
            });
            input.value = ''; // Reset input agar file salah tidak terkirim
            return false;
        }

        // 2. Cek Ukuran File (Opsional, batas 20MB)
        if (file.size > maxSize) {
            Swal.fire({
                icon: 'warning',
                title: 'File Terlalu Besar',
                text: 'Ukuran maksimal adalah 20MB.',
                background: '#1a1a1a',
                color: '#D4AF37',
                confirmButtonColor: '#D4AF37'
            });
            input.value = ''; // Reset input
            return false;
        }
    }
}