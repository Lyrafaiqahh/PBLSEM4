<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PendaftaranModel;
use App\Models\DetailPendaftaranModel;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache; // Import Cache Facade
use Illuminate\Support\Facades\Config; // Import Config Facade

class VerifikasiPendaftaranController extends Controller
{
    public function index()
    {
        $breadcrumb = (object) [
            'title' => 'Verifikasi Pendaftaran',
            'list' => ['Home', 'Verifikasi'],
        ];

        $page = (object) [
            'title' => 'Pendaftaran yang menunggu verifikasi',
        ];

        $activeMenu = 'verifikasi';

        // Get registration status from cache, fallback to config if not set
        $registrationStatus = Cache::rememberForever('registration_status', function () {
            return Config::get('app_settings.registration_open') ? 'open' : 'closed';
        });

        return view('verifikasi.index', compact('breadcrumb', 'page', 'activeMenu', 'registrationStatus'));
    }

    public function list(Request $request)
    {
        if ($request->ajax()) {
            $data = PendaftaranModel::whereHas('detail', function($query) {
                        $query->where('status', 'menunggu');
                    })
                    ->with(['mahasiswa', 'detail'])
                    ->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('nim', fn($row) => $row->mahasiswa->nim ?? '-')
                ->addColumn('nik', fn($row) => $row->mahasiswa->nik ?? '-')
                ->addColumn('nama', fn($row) => $row->mahasiswa->mahasiswa_nama ?? '-')
                ->addColumn('prodi', fn($row) => $row->mahasiswa->prodi->prodi_nama ?? '-')
                ->addColumn('jurusan', fn($row) => $row->mahasiswa->prodi->jurusan->jurusan_nama ?? '-')
                ->addColumn('kampus', fn($row) => $row->mahasiswa->prodi->jurusan->kampus->kampus_nama ?? '-')
                ->addColumn('aksi', function ($row) {
                    $url = route('verifikasi.show', $row->pendaftaran_id);

                    return '
                        <button onclick="modalAction(\'' . $url . '\')" class="btn btn-info btn-sm me-1">Detail</button>
                    ';
                })
                ->addColumn('status', function($row) {
                    $detail = $row->detail;
                    $status = strtolower($detail->status ?? 'menunggu');
                    $btnClass = match($status) {
                        'menunggu' => 'btn-primary',
                        'diterima' => 'btn-success',
                        'ditolak' => 'btn-danger',
                        default => 'btn-secondary'
                    };

                    return '
                    <div class="dropdown">
                        <button class="btn btn-sm '.$btnClass.' dropdown-toggle" type="button" data-toggle="dropdown">
                            '.ucfirst($status).'
                        </button>
                        <ul class="dropdown-menu">
                            <li class="dropdown-header">-- Pilih Pembaruan Status --</li>
                            <li><a class="dropdown-item" href="#" onclick="updateStatus('.$detail->detail_id.', \'menunggu\')">Menunggu</a></li>
                            <li><a class="dropdown-item" href="#" onclick="updateStatus('.$detail->detail_id.', \'diterima\')">Diterima</a></li>
                            <li><a class="dropdown-item" href="#" onclick="updateStatus('.$detail->detail_id.', \'ditolak\')">Ditolak</a></li>
                        </ul>
                    </div>';
                })
                ->rawColumns(['aksi', 'status'])
                ->make(true);
        }
    }

    public function update(Request $request, $id)
    {
        $detail = DetailPendaftaranModel::findOrFail($id);

        $detail->update([
            'status' => $request->status,
            'catatan' => $request->catatan,
        ]);
        
        // Jika status adalah "diterima", ubah keterangan mahasiswa menjadi "berbayar"
        if ($request->status === 'diterima' && $detail->pendaftaran && $detail->pendaftaran->mahasiswa) {
            $detail->pendaftaran->mahasiswa->update([
                'keterangan' => 'berbayar',
            ]);
        }
        return response()->json(['success' => true, 'message' => 'Status berhasil diperbarui!']);
    }

    public function edit($id)
    {
        $detail = DetailPendaftaranModel::with('pendaftaran.mahasiswa')->findOrFail($id);

        return view('verifikasi.edit_ajax', compact('detail'));
    }

    public function show($id)
    {
        $pendaftaran = PendaftaranModel::with([
            'mahasiswa.prodi.jurusan.kampus', // relasi bertingkat
            'detail',
            'jadwal'
        ])->findOrFail($id);

        return view('verifikasi.show_ajax', compact('pendaftaran'));
    }

    public function verifyAll(Request $request)
    {
        try {
            $catatan = $request->input('catatan', '');
            
            // Update semua data dengan status 'menunggu' menjadi 'diterima'
            $updated = DB::table('detail_pendaftaran') 
                ->where('status', 'menunggu') // atau sesuaikan dengan value status menunggu
                ->update([
                    'status' => 'diterima', // atau sesuaikan dengan value status diterima
                    'catatan' => $catatan,
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'count' => $updated,
                'message' => "Berhasil memverifikasi {$updated} data"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }    // New method for registration status
    public function updateRegistrationStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:open,closed',
        ]);

        // Store status in cache
        Cache::forever('registration_status', $request->status);

        return response()->json(['success' => true, 'message' => 'Status pendaftaran berhasil diperbarui.', 'newStatus' => $request->status]);
    }
}