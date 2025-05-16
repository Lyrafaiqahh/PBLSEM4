@extends('layouts.template')

@section('content')
    <div class="card card-outline card-primary shadow-sm">
        <div class="card-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                <h3 class="card-title mb-2 mb-md-0">{{ $page->title }}</h3>
                <div class="btn-toolbar flex-wrap gap-2" role="toolbar" aria-label="Aksi Admin">
                    <button onclick="modalAction('{{ url('/admin/import') }}')" class="btn btn-info btn-sm shadow-sm">
                        <i class="fa fa-upload me-1"></i> Impor File
                    </button>
                    <a href="{{ url('/admin/export_excel') }}" class="btn btn-primary btn-sm shadow-sm">
                        <i class="fa fa-file-excel me-1"></i> Expor Excel
                    </a>
                    <a href="{{ url('/admin/export_pdf') }}" class="btn btn-warning text-dark btn-sm shadow-sm">
                        <i class="fa fa-file-pdf me-1"></i> Expor PDF
                    </a>
                    <button onclick="modalAction('{{ url('/admin/create_ajax') }}')" class="btn btn-success btn-sm shadow-sm">
                        <i class="fa fa-plus-circle me-1"></i> Tambah Data
                    </button>
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
                <table class="table table-bordered table-striped table-hover table-sm" id="table_admin">
                    <thead class="table-primary text-center">
                        <tr>
                            <th style="width: 20px;">No</th>
                            <th style="width: 40px;">Nama Admin</th>
                            <th style="width: 80px;">Nomor Telepon Admin</th>
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
            var dataAdmin = $('#table_admin').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ url('admin/list') }}",
                    type: "POST",
                    data: function (d) {
                        d.search_query = $('#searchInput').val();
                        d._token = '{{ csrf_token() }}';
                    }
                },
                columns: [
                    { data: "DT_RowIndex", className: "text-center", orderable: false, searchable: false },
                    { data: "admin_nama", className: "text-nowrap" },
                    { data: "no_telp", className: "text-nowrap" },
                    { data: "aksi", className: "text-center text-nowrap", orderable: false, searchable: false }
                ]
            });

            $('#searchInput').on('keyup', function () {
                dataAdmin.ajax.reload();
            });
        });
    </script>
@endpush
