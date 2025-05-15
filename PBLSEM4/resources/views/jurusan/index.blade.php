@extends('layouts.template')

@section('content')
    <div class="card card-outline card-primary shadow-sm">
        <div class="card-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                <h3 class="card-title mb-2 mb-md-0">{{ $page->title }}</h3>
                <div class="btn-toolbar flex-wrap gap-2" role="toolbar" aria-label="Aksi Jurusan">
                    <button onclick="modalAction('{{ url('/jurusan/import') }}')" class="btn btn-info btn-sm shadow-sm">
                        <i class="fa fa-upload me-1"></i> Import
                    </button>
                    <a href="{{ url('/jurusan/export_excel') }}" class="btn btn-primary btn-sm shadow-sm">
                        <i class="fa fa-file-excel me-1"></i> Export Excel
                    </a>
                    <a href="{{ url('/jurusan/export_pdf') }}" class="btn btn-warning text-dark btn-sm shadow-sm">
                        <i class="fa fa-file-pdf me-1"></i> Export PDF
                    </a>
                    <button onclick="modalAction('{{ url('/jurusan/create_ajax') }}')" class="btn btn-success btn-sm shadow-sm">
                        <i class="fa fa-plus-circle me-1"></i> Tambah
                    </button>
                </div>
            </div>
            <!-- untuk Filter data -->
            <div id="filter" class="form-horizontal filter-date p-2 border-bottom mb-2">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group form-group-sm row text-sm mb-0">
                            <label for="filter_kampus" class="col-md-2 col-form-label">Filter Kampus</label>
                            <div class="col-md-4">
                                <select name="filter_kampus" id="filter_kampus" class="form-control form-control-sm filter_kampus">
                                    <option value="">- Semua Kampus -</option>
                                    @foreach($kampusList as $kampus)
                                        <option value="{{ $kampus->kampus_id }}">{{ $kampus->kampus_nama }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Pilih Kampus untuk memfilter jurusan</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover table-sm" id="table_jurusan">
                    <thead class="table-primary text-center">
                        <tr>
                            <th style="width: 40px;">No</th>
                            <th>Kode Jurusan</th>
                            <th>Nama Jurusan</th>
                            <th>Nama Kampus</th>
                            <th style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="myModal" class="modal fade animate shake" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" data-width="75%" aria-hidden="true"></div>
@endsection

@push('js')
    <script>
    function modalAction(url = '') {
        $('#myModal').load(url, function () {
            $('#myModal').modal('show');
        });
    }

    $(document).ready(function () {
        var dataJurusan = $('#table_jurusan').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ url('jurusan/list') }}",
                type: "POST",
                data: function (d) {
                    d.filter_kampus = $('#filter_kampus').val(); // <== perbaiki di sini
                    d._token = '{{ csrf_token() }}';
                }
            },
            columns: [
                { data: "DT_RowIndex", className: "text-center", orderable: false, searchable: false },
                { data: "jurusan_kode", className: "text-nowrap" },
                { data: "jurusan_nama", className: "text-nowrap" },
                { data: "kampus.kampus_nama", className: "text-nowrap" },
                { data: "aksi", className: "text-center text-nowrap", orderable: false, searchable: false }
            ]
        });

        $('#filter_kampus').on('change', function () { // jQuery juga pakai id yang sama
            dataJurusan.ajax.reload();
        });
    });
</script>
@endpush
