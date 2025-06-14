<!-- Modal Export -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="exportModalLabel">
                    <i class="fas fa-download me-2"></i>Export Data Riwayat Pendaftaran
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="exportForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="status" class="form-label fw-bold">
                                <i class="fas fa-filter me-1"></i>Status Pendaftaran
                            </label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Semua Status</option>
                                <option value="diterima">Diterima</option>
                                <option value="ditolak">Ditolak</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="tanggal_awal" class="form-label fw-bold">
                                <i class="fas fa-calendar-alt me-1"></i>Tanggal Awal
                            </label>
                            <input type="date" class="form-control" id="tanggal_awal" name="tanggal_awal">
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle me-1"></i>Opsional - kosongkan jika tidak diperlukan
                            </small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="tanggal_akhir" class="form-label fw-bold">
                                <i class="fas fa-calendar-alt me-1"></i>Tanggal Akhir
                            </label>
                            <input type="date" class="form-control" id="tanggal_akhir" name="tanggal_akhir">
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle me-1"></i>Opsional - kosongkan jika tidak diperlukan
                            </small>
                        </div>
                    </div>
                    
                    <!-- Format Export Options -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">
                                <i class="fas fa-file-export me-1"></i>Pilih Format Export
                            </label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="format_type" id="format_pdf" value="pdf" checked>
                                    <label class="form-check-label" for="format_pdf">
                                        <i class="fas fa-file-pdf text-danger me-1"></i>PDF
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="format_type" id="format_excel" value="excel">
                                    <label class="form-check-label" for="format_excel">
                                        <i class="fas fa-file-excel text-success me-1"></i>Excel
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preview Info -->
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Informasi Export:</strong>
                        <ul class="mb-0 mt-2">
                            <li><strong>PDF:</strong> Menampilkan data lengkap dengan gambar KTP, KTM, dan Pas Foto</li>
                            <li><strong>Excel:</strong> Data dalam bentuk tabel dengan thumbnail gambar di setiap cell</li>
                            <li>Jika tanggal tidak diisi, semua data akan diekspor</li>
                            <li>Tanggal akhir harus lebih besar atau sama dengan tanggal awal</li>
                            <li>Gambar akan dimuat otomatis jika file tersedia di server</li>
                        </ul>
                    </div>
                    
                    <!-- Loading indicator -->
                    <div id="exportLoading" class="text-center d-none">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Sedang memproses export...</p>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" id="exportBtn" class="btn btn-primary">
                        <i class="fas fa-download me-1"></i> 
                        <span id="exportBtnText">Export PDF</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const exportForm = document.getElementById('exportForm');
    const tanggalAwal = document.getElementById('tanggal_awal');
    const tanggalAkhir = document.getElementById('tanggal_akhir');
    const formatPdf = document.getElementById('format_pdf');
    const formatExcel = document.getElementById('format_excel');
    const exportBtn = document.getElementById('exportBtn');
    const exportBtnText = document.getElementById('exportBtnText');
    const exportLoading = document.getElementById('exportLoading');
    const statusSelect = document.getElementById('status');

    function updateButtonText() {
        if (formatPdf.checked) {
            exportBtnText.textContent = 'Export PDF';
            exportBtn.className = 'btn btn-danger';
            exportBtn.innerHTML = '<i class="fas fa-file-pdf me-1"></i> ' + exportBtnText.textContent;
        } else {
            exportBtnText.textContent = 'Export Excel';
            exportBtn.className = 'btn btn-success';
            exportBtn.innerHTML = '<i class="fas fa-file-excel me-1"></i> ' + exportBtnText.textContent;
        }
    }

    // Event listeners for format change
    formatPdf.addEventListener('change', updateButtonText);
    formatExcel.addEventListener('change', updateButtonText);

    // Validasi tanggal
    tanggalAwal.addEventListener('change', function () {
        tanggalAkhir.min = this.value;
        if (tanggalAkhir.value && tanggalAkhir.value < this.value) {
            tanggalAkhir.value = this.value;
            showAlert('Tanggal akhir telah disesuaikan dengan tanggal awal', 'warning');
        }
    });

    tanggalAkhir.addEventListener('change', function () {
        if (tanggalAwal.value && this.value < tanggalAwal.value) {
            showAlert('Tanggal akhir harus lebih besar atau sama dengan tanggal awal', 'danger');
            this.value = tanggalAwal.value;
        }
    });

    // Alert reusable
    function showAlert(message, type = 'info') {
        const alertId = 'alert-' + type;
        const existingAlert = document.getElementById(alertId);
        if (existingAlert) existingAlert.remove();

        const alertDiv = document.createElement('div');
        alertDiv.id = alertId;
        alertDiv.className = `alert alert-${type} alert-dismissible fade show mt-2`;
        alertDiv.innerHTML = `
            <i class="fas fa-exclamation-circle me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        const modalBody = document.querySelector('#exportModal .modal-body');
        modalBody.insertBefore(alertDiv, modalBody.firstChild);

        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 3000);
    }

    // Handle form submission
    exportForm.addEventListener('submit', function (e) {
        e.preventDefault();

        // Show loading
        exportLoading.classList.remove('d-none');
        exportBtn.disabled = true;

        const tanggalAwalVal = tanggalAwal.value;
        const tanggalAkhirVal = tanggalAkhir.value;

        if (!tanggalAwalVal || !tanggalAkhirVal) {
            showAlert('Harap isi kedua tanggal terlebih dahulu.', 'warning');
            exportLoading.classList.add('d-none');
            exportBtn.disabled = false;
            return;
        }

        if (tanggalAkhirVal < tanggalAwalVal) {
            showAlert('Tanggal akhir harus lebih besar atau sama dengan tanggal awal', 'danger');
            exportLoading.classList.add('d-none');
            exportBtn.disabled = false;
            return;
        }

        const format = document.querySelector('input[name="format_type"]:checked').value;
        let url = format === 'excel'
            ? '{{ route("riwayat.export_excel") }}'
            : '{{ route("riwayat.export_pdf") }}';

        const params = new URLSearchParams();
        if (statusSelect && statusSelect.value) {
            params.append('status', statusSelect.value);
        }
        params.append('tanggal_awal', tanggalAwalVal);
        params.append('tanggal_akhir', tanggalAkhirVal);

        const downloadLink = document.createElement('a');
        downloadLink.href = url + '?' + params.toString();
        downloadLink.style.display = 'none';
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);

        showAlert(`Export ${format.toUpperCase()} berhasil dimulai. File akan segera diunduh.`, 'success');

        setTimeout(() => {
            exportLoading.classList.add('d-none');
            exportBtn.disabled = false;

            const modalInstance = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
            if (modalInstance) modalInstance.hide();
        }, 2000);
    });

    // Reset modal saat ditutup
    document.getElementById('exportModal').addEventListener('hidden.bs.modal', function () {
        exportForm.reset();
        exportLoading.classList.add('d-none');
        exportBtn.disabled = false;
        updateButtonText();

        const alerts = document.querySelectorAll('#exportModal .alert-dismissible');
        alerts.forEach(alert => alert.remove());
    });

    updateButtonText();
});
</script>

<style>
#exportModal .modal-body {
    max-height: 70vh;
    overflow-y: auto;
}

.form-check {
    padding: 0.5rem;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    transition: all 0.2s ease-in-out;
}

.form-check:hover {
    background-color: #f8f9fa;
    border-color: #0d6efd;
}

.form-check-input:checked + .form-check-label {
    color: #0d6efd;
    font-weight: 600;
}

.alert {
    border-left: 4px solid;
    padding: 0.75rem 1rem;
    margin-bottom: 0.75rem;
    font-size: 0.95rem;
}

.alert-info {
    border-left-color: #0dcaf0;
}

.alert-warning {
    border-left-color: #ffc107;
}

.alert-danger {
    border-left-color: #dc3545;
}

.alert-success {
    border-left-color: #198754;
}

#exportLoading {
    padding: 2rem;
    text-align: center;
}

.spinner-border {
    width: 3rem;
    height: 3rem;
}
</style>