/**
 * Minimal JavaScript untuk UI Interaksi
 * Tidak ada AJAX - semua API call di server-side
 */

// Auto-hide flash messages after 5 seconds
document.addEventListener('DOMContentLoaded', function () {
    const flashMessage = document.querySelector('.flash-message');
    if (flashMessage) {
        setTimeout(function () {
            flashMessage.style.opacity = '0';
            setTimeout(function () {
                flashMessage.style.display = 'none';
            }, 300);
        }, 5000);
    }

    // Scroll reveal animation for cards
    const observerOptions = {
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                // Set final state
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';

                // Clear inline transition after animation completes 
                // to allow CSS hover/active states to work normally
                setTimeout(() => {
                    entry.target.style.transition = '';
                }, 600);

                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.card, .stat-card, .list-item').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
        observer.observe(el);
    });
});

/**
 * Konfirmasi untuk aksi penting
 */
function confirmAction(message) {
    return confirm(message || 'Apakah Anda yakin?');
}

/**
 * Show modal
 */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
    }
}

/**
 * Hide modal
 */
function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
    }
}

/**
 * Close modal when clicking overlay
 */
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.add('hidden');
    }
});

/**
 * Form validation untuk disposisi
 */
function validateDisposisiForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    const tujuan = form.querySelector('[name="tujuan"]');
    const catatan = form.querySelector('[name="catatan"]');
    const deadline = form.querySelector('[name="deadline"]');

    if (!tujuan || tujuan.selectedOptions.length === 0) {
        alert('Pilih minimal satu tujuan disposisi');
        return false;
    }

    if (!catatan || !catatan.value.trim()) {
        alert('Catatan pimpinan wajib diisi');
        return false;
    }

    if (!deadline || !deadline.value) {
        alert('Deadline wajib diisi');
        return false;
    }

    return confirmAction('Kirim disposisi ini?');
}

/**
 * Form validation untuk persetujuan laporan
 */
function validateApprovalForm(action) {
    if (action === 'approve') {
        return confirmAction('Setujui laporan ini?');
    } else if (action === 'reject') {
        const catatan = prompt('Catatan pengembalian (wajib):');
        if (!catatan || !catatan.trim()) {
            alert('Catatan pengembalian wajib diisi');
            return false;
        }
        // Set catatan ke hidden field jika ada
        const catatanField = document.querySelector('[name="catatan_penolakan"]');
        if (catatanField) {
            catatanField.value = catatan;
        }
        return true;
    }
    return false;
}

/**
 * Set minimum date untuk input date (hari ini)
 */
document.addEventListener('DOMContentLoaded', function () {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    const today = new Date().toISOString().split('T')[0];

    dateInputs.forEach(function (input) {
        if (!input.hasAttribute('data-allow-past')) {
            input.setAttribute('min', today);
        }
    });
});
