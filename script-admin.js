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
        if ($.fn.DataTable.isDataTable('#managerTable')) {
            $('#managerTable').DataTable().destroy();
        }

        $('#managerTable').DataTable({
            "responsive": true,
            "pageLength": 10,
            "order": [[ 0, "asc" ]],
            "dom": '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            "language": {
                "search": "Quick Search:",
                "lengthMenu": "_MENU_ entries",
                "paginate": {
                    "previous": "<i class='fas fa-chevron-left'></i>",
                    "next": "<i class='fas fa-chevron-right'></i>"
                }
            }
        });
    }

    // --- 3. AUTO-FORMAT & AUTO-SLUG (Input Helpers) ---
    // Hapus angka 0 di depan nomor telepon kantor
    $(document).on('input', '.office-format', function() {
        let val = $(this).val();
        if (val.startsWith('0')) {
            $(this).val(val.replace(/^0+/, ''));
        }
    });

    // --- 4. VISUALISASI CHART (Gradient Mode) ---
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { 
            legend: { display: false } // Legend dimatikan agar lebih clean
        },
        scales: {
            y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#888' } },
            x: { grid: { display: false }, ticks: { color: '#888' } }
        }
    };

    // Weekly Chart dengan Gradien Emas
    if (document.getElementById('weeklyChart')) {
        const ctxWeekly = document.getElementById('weeklyChart').getContext('2d');
        const gradGold = ctxWeekly.createLinearGradient(0, 0, 0, 300);
        gradGold.addColorStop(0, 'rgba(212, 175, 55, 0.5)');
        gradGold.addColorStop(1, 'rgba(212, 175, 55, 0)');

        new Chart(ctxWeekly, {
            type: 'line',
            data: {
                labels: weeklyLabels,
                datasets: [{
                    label: 'Hits',
                    data: weeklyData,
                    borderColor: '#D4AF37',
                    borderWidth: 3,
                    pointBackgroundColor: '#D4AF37',
                    pointBorderColor: 'rgba(255,255,255,0.5)',
                    pointRadius: 5,
                    fill: true,
                    backgroundColor: gradGold,
                    tension: 0.4
                }]
            },
            options: chartOptions
        });
    }

    // Monthly Chart (Bar)
    if (document.getElementById('monthlyChart')) {
        new Chart(document.getElementById('monthlyChart'), {
            type: 'bar',
            data: {
                labels: monthlyLabels,
                datasets: [{ 
                    label: 'Hits', 
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
    if(document.getElementById('edit-id')) {
        document.getElementById('edit-id').value = data.id;
        document.getElementById('edit-name').value = data.name;
        document.getElementById('edit-title').value = data.title;
        document.getElementById('edit-email').value = data.email;
        document.getElementById('edit-phone-p').value = data.phone_personal;
        
        // Bersihkan prefix +62 agar tidak double
        let cleanOffice = data.phone_office.replace('+62 ', '').trim();
        document.getElementById('edit-phone-o').value = cleanOffice;
        
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editModal'));
        modal.show();
    }
}

// --- 6. QR CODE & COPY LINK ---
function copyCardLink(slug) {
    // Memastikan path benar ke index.php
    const fullUrl = `${window.location.origin}${window.location.pathname.replace('admin.php', 'index.php')}?name=${slug}`;
    
    navigator.clipboard.writeText(fullUrl).then(() => {
        Swal.fire({ 
            icon: 'success', 
            title: 'Link Copied!', 
            text: 'Profile link ready to share.',
            background: '#1a1a1a', 
            color: '#D4AF37', 
            timer: 1500, 
            showConfirmButton: false 
        });
    });
}

async function downloadQRWithLogo(slug, name) {
    Swal.fire({ 
        title: 'Generating QR...', 
        background: '#1a1a1a', 
        color: '#fff', 
        didOpen: () => Swal.showLoading() 
    });

    try {
        const canvas = document.createElement("canvas");
        const ctx = canvas.getContext("2d");
        const size = 1000;
        canvas.width = size; 
        canvas.height = size;

        const profileUrl = `${window.location.origin}${window.location.pathname.replace('admin.php', 'index.php')}?name=${slug}`;
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=1000x1000&data=${encodeURIComponent(profileUrl)}&ecc=H`;
        
        const qrImg = new Image();
        qrImg.crossOrigin = "anonymous";
        qrImg.src = qrUrl;

        qrImg.onload = function() {
            // Background Putih
            ctx.fillStyle = "white";
            ctx.fillRect(0, 0, size, size);
            ctx.drawImage(qrImg, 0, 0, size, size);

            const logo = new Image();
            logo.src = "pics/Logo.png"; // Pastikan file ini ada
            
            logo.onload = function() {
                const lSize = size * 0.22;
                const pos = (size - lSize) / 2;
                
                ctx.fillStyle = "white";
                ctx.beginPath();
                if(ctx.roundRect) {
                    ctx.roundRect(pos-15, pos-15, lSize+30, lSize+30, 30);
                } else {
                    ctx.fillRect(pos-15, pos-15, lSize+30, lSize+30);
                }
                ctx.fill();
                
                ctx.drawImage(logo, pos, pos, lSize, lSize);
                saveCanvas(canvas, name);
            };
            logo.onerror = () => saveCanvas(canvas, name);
        };
    } catch (e) { 
        Swal.fire('Error', 'QR Generation failed', 'error'); 
    }
}

function saveCanvas(canvas, name) {
    const link = document.createElement("a");
    const safeName = name.replace(/\s+/g, '-').toLowerCase();
    link.download = `QR-TRANS-${safeName}.png`;
    link.href = canvas.toDataURL("image/png");
    link.click();
    Swal.close();
}