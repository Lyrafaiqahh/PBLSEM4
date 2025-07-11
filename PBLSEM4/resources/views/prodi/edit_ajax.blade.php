@empty($prodi)
    <div id="modal-master" class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Kesalahan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <h5><i class="icon fas fa-ban"></i> Kesalahan!!!</h5>
                    Data program studi yang Anda cari tidak ditemukan.
                </div>
                <a href="{{ url('/prodi') }}" class="btn btn-warning">Kembali</a>
            </div>
        </div>
    </div>
@else
    <form action="{{ url('/prodi/' . $prodi->prodi_id . '/update_ajax') }}" method="POST" id="form-edit-prodi">
        @csrf
        @method('PUT')
        <div id="modal-master" class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Edit Data Program Studi</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Kode Prodi</label>
                        <input value="{{ $prodi->prodi_kode }}" type="text" name="prodi_kode" id="prodi_kode"
                               class="form-control" required>
                        <small id="error-prodi_kode" class="error-text form-text text-danger"></small>
                    </div>
                    <div class="form-group">
                        <label>Nama Prodi</label>
                        <input value="{{ $prodi->prodi_nama }}" type="text" name="prodi_nama" id="prodi_nama" class="form-control"
                               required>
                        <small id="error-prodi_nama" class="error-text form-text text-danger"></small>
                    </div>
                    <div class="form-group">
                        <label>Nama Jurusan</label>
                        <select name="jurusan_id" id="jurusan_id" class="form-control" required>
                            <option value="">- Pilih Jurusan -</option>
                            @foreach($jurusan as $l)
                                <option value="{{ $l->jurusan_id }}">{{ $l->jurusan_nama }}</option>
                            @endforeach
                        </select>
                        <small id="error-jurusan_id" class="error-text form-text text-danger"></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-warning">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </div>
    </form>

    <script>
       $(document).ready(function() {
        $("#form-edit-prodi").validate({
            rules: {
                prodi_kode: {
                    required: true,
                    minlength: 3,
                    maxlength: 20
                },
                prodi_nama: {
                    required: true,
                    minlength: 3,
                    maxlength: 100
                },
                jurusan_id: {
                    required: true,
                }
            },
            submitHandler: function(form) {
                $.ajax({
                    url: form.action,
                    type: 'POST',
                    data: $(form).serialize(),
                    success: function(response) {
                        if (response.status) {
                            // Tutup modal setelah berhasil
                            $('#modal-master').modal('hide');

                            // Menampilkan pesan sukses
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message
                            }).then(function() {
                                // Reload halaman setelah edit berhasil
                                location.reload();
                            });

                            // Reset form setelah modal ditutup
                            $('#form-edit-prodi')[0].reset();
                            $('.error-text').text(''); // Clear error messages

                        } else {
                            $('.error-text').text('');
                            $.each(response.msgField, function(prefix, val) {
                                $('#error-' + prefix).text(val[0]);
                            });
                            Swal.fire({
                                icon: 'error',
                                title: 'Terjadi Kesalahan',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: 'Terjadi kesalahan saat memperbarui data prodi.'
                        });
                    }
                });
                return false;
            },
            errorElement: 'span',
            errorPlacement: function(error, element) {
                error.addClass('invalid-feedback');
                element.closest('.form-group').append(error);
            },
            highlight: function(element) {
                $(element).addClass('is-invalid');
            },
            unhighlight: function(element) {
                $(element).removeClass('is-invalid');
            }
        });

        // Reset form dan hapus error saat modal ditutup
        $('#modal-master').on('hidden.bs.modal', function () {
            $('#form-edit-prodi')[0].reset();
            $('.error-text').text('');
            $('#form-edit-prodi').find('.is-invalid').removeClass('is-invalid');
        });
    });
    </script>
@endempty
