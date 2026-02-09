/* script-admin.js */
$(document).ready(function() {
    // Inisialisasi DataTables
    if ($('#managerTable').length) {
        $('#managerTable').DataTable({ 
            "order": [[ 0, "asc" ]], 
            "language": { "search": "Quick Search:" } 
        });
    }

    // Auto-remove angka '0' di depan pada input Office Line
    $(document).on('input', '.office-format', function() {
        let val = $(this).val();
        if (val.startsWith('0')) {
            $(this).val(val.substring(1));
        }
    });
});

// Fungsi Modal Edit
function openEditModal(data) {
    document.getElementById('edit-id').value = data.id;
    document.getElementById('edit-name').value = data.name;
    document.getElementById('edit-title').value = data.title;
    document.getElementById('edit-email').value = data.email;
    document.getElementById('edit-phone-p').value = data.phone_personal;
    
    // Hilangkan prefix +62 agar tidak ganda di input
    let cleanOffice = data.phone_office.replace('+62 ', '');
    document.getElementById('edit-phone-o').value = cleanOffice;
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// Copy Link Profile
function copyCardLink(slug) {
    const fullUrl = window.location.origin + window.location.pathname.replace('admin.php', 'index.php') + '?name=' + slug;
    navigator.clipboard.writeText(fullUrl).then(() => {
        Swal.fire({ icon: 'success', title: 'Link Copied!', background: '#111', color: '#fff', timer: 1500, showConfirmButton: false });
    });
}

// Generate & Download QR Code dengan Logo
async function downloadQRWithLogo(slug, name) {
    Swal.fire({ title: 'Generating QR...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

    const canvas = document.createElement("canvas");
    const ctx = canvas.getContext("2d");
    const size = 1000; 
    canvas.width = size;
    canvas.height = size;

    const profileUrl = window.location.origin + window.location.pathname.replace('admin.php', 'index.php') + '?name=' + slug;
    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=1000x1000&data=${encodeURIComponent(profileUrl)}`;
    
    const qrImg = new Image();
    qrImg.crossOrigin = "anonymous";
    qrImg.src = qrUrl;

    qrImg.onload = function() {
        ctx.fillStyle = "white";
        ctx.fillRect(0, 0, size, size);
        ctx.drawImage(qrImg, 0, 0, size, size);

        const logo = new Image();
        logo.src = "pics/Logo.png"; 
        
        logo.onload = function() {
            const logoSize = size * 0.22; 
            const pos = (size - logoSize) / 2;
            
            ctx.fillStyle = "white";
            ctx.beginPath();
            if(ctx.roundRect) {
                ctx.roundRect(pos - 10, pos - 10, logoSize + 20, logoSize + 20, 20);
            } else {
                ctx.fillRect(pos - 10, pos - 10, logoSize + 20, logoSize + 20);
            }
            ctx.fill();
            ctx.drawImage(logo, pos, pos, logoSize, logoSize);

            const link = document.createElement("a");
            link.download = `QR-TRANS-${name.replace(/\s+/g, '-')}.png`;
            link.href = canvas.toDataURL("image/png");
            link.click();
            Swal.close();
        };
        logo.onerror = () => { 
            Swal.fire('Error', 'Logo.png not found. Downloading without logo.', 'warning').then(() => {
                const link = document.createElement("a");
                link.download = `QR-TRANS-${name.replace(/\s+/g, '-')}.png`;
                link.href = canvas.toDataURL("image/png");
                link.click();
            });
        };
    };
}