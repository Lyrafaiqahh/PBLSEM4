<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HasilUjianModel;
use App\Models\JadwalModel;
use App\Models\UserModel;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Log;

class HasilUjianController extends Controller
{
    public function index()
    {
        $breadcrumb = (object) [
            'title' => 'Daftar Hasil Ujian',
            'list' => ['Home', 'Hasil Ujian'],
        ];

        $page = (object) [
            'title' => 'Daftar hasil ujian peserta TOEIC',
        ];

        $activeMenu = 'hasil_ujian';

        return view('hasil_ujian.index', compact('breadcrumb', 'page', 'activeMenu'));
    }

    public function list(Request $request)
    {
        $hasil_ujian = HasilUjianModel::with([
            'jadwal',
            'user.mahasiswa',
            'user.dosen',
            'user.tendik',
        ])->select('hasil_ujian.*');

        if ($request->has('search_query') && $request->search_query != '') {
            $hasil_ujian->where(function($q) use ($request) {
                $q->whereHas('user.mahasiswa', function($q2) use ($request) {
                    $q2->where('mahasiswa_nama', 'like', '%' . $request->search_query . '%');
                })
                ->orWhereHas('user.dosen', function($q3) use ($request) {
                    $q3->where('dosen_nama', 'like', '%' . $request->search_query . '%');
                })
                ->orWhereHas('user.tendik', function($q4) use ($request) {
                    $q4->where('tendik_nama', 'like', '%' . $request->search_query . '%');
                });
            });
        }

        return DataTables::of($hasil_ujian)
            ->addIndexColumn()
            ->addColumn('nama', function ($h) {
                $user = $h->user;
                if ($user) {
                    if ($user->mahasiswa) return $user->mahasiswa->mahasiswa_nama;
                    if ($user->dosen) return $user->dosen->dosen_nama;
                    if ($user->tendik) return $user->tendik->tendik_nama;
                    // Fallback ini seharusnya sudah tidak terpicu jika relasi dan data konsisten
                    return $user->username ?? '-'; // Menggunakan username dari UserModel
                }
                return '-';
            })
            ->addColumn('tanggal_pelaksanaan', function ($h) {
                // Menggunakan tanggal_pelaksanaan dari jadwal dan menampilkan jam_mulai serta keterangan
                $display = $h->jadwal ? \Carbon\Carbon::parse($h->jadwal->tanggal_pelaksanaan)->format('d/m/Y') : '-';
                if ($h->jadwal && $h->jadwal->jam_mulai) {
                    $display .= ' - ' . $h->jadwal->jam_mulai;
                }
                return $display;
            })
            ->addColumn('nilai_listening', fn($h) => $h->nilai_listening ?? 0)
            ->addColumn('nilai_reading', fn($h) => $h->nilai_reading ?? 0)
            ->addColumn('nilai_total', fn($h) => $h->nilai_total ?? 0)
            ->addColumn('status_lulus', function($h) {
                $status = $h->status_lulus ?? 'Tidak Lulus';
                $badgeClass = strtolower($status) === 'lulus' ? 'badge bg-success' : 'badge bg-danger';
                return '<span class="' . $badgeClass . '">' . $status . '</span>';
            })
            ->addColumn('role', function ($h) {
                return $h->user ? ucfirst($h->user->role) : '-';
            })
            ->addColumn('aksi', function ($h) {
                $id = $h->hasil_id;

                $btn  = '<button onclick="modalAction(\'' . url('/hasil_ujian/' . $id . '/show_ajax') . '\')" 
                            class="btn btn-info btn-sm rounded-pill shadow-sm me-1 px-3 py-1" style="font-size: 0.85rem;">
                            <i class="fa fa-eye me-1"></i> Detail
                        </button>';

                $btn .= '<button onclick="modalAction(\'' . url('/hasil_ujian/' . $id . '/edit_ajax') . '\')" 
                            class="btn btn-warning btn-sm rounded-pill shadow-sm me-1 px-3 py-1" style="font-size: 0.85rem;">
                            <i class="fa fa-edit me-1"></i> Edit
                        </button>';

                $btn .= '<button onclick="modalAction(\'' . url('/hasil_ujian/' . $id . '/delete_ajax') . '\')" 
                            class="btn btn-danger btn-sm rounded-pill shadow-sm px-3 py-1" style="font-size: 0.85rem;">
                            <i class="fa fa-trash me-1"></i> Hapus
                        </button>';

                return $btn;
            })
            ->rawColumns(['status_lulus', 'aksi'])
            ->make(true);
    }


    public function show_ajax(string $id)
    {
        $hasil_ujian = HasilUjianModel::with(['jadwal', 'user.mahasiswa', 'user.dosen', 'user.tendik'])->find($id);
        if (!$hasil_ujian) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan']);
        }
        return view('hasil_ujian.show_ajax', ['hasil_ujian' => $hasil_ujian]);
    }

    public function getUsersByRole(Request $request)
    {
        $role = $request->role;

        $users = UserModel::with($role) // mahasiswa/dosen/tendik
            ->where('role', $role)
            ->get();

        return response()->json([
            'status' => true,
            'data' => $users
        ]);
    }

    public function create_ajax()
    {
        $jadwal = JadwalModel::all(); // Ini akan mengambil semua jadwal
        $user = UserModel::with(['mahasiswa', 'dosen', 'tendik'])
                         ->whereIn('role', ['mahasiswa', 'dosen', 'tendik'])
                         ->get();
        
        return view('hasil_ujian.create_ajax', compact('jadwal', 'user'));
    }
    
    public function store_ajax(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:user,user_id', // Validasi ini sudah diperbaiki sebelumnya
            'jadwal_id' => 'required|exists:jadwal,jadwal_id', // Ini sudah benar sesuai JadwalModel
            'nilai_listening' => 'required|numeric|min:0|max:495',
            'nilai_reading' => 'required|numeric|min:0|max:495',
            'catatan' => 'nullable|string|max:255'
        ]);

        $listening = $request->nilai_listening;
        $reading = $request->nilai_reading;
        $total = $listening + $reading;
        $status = $total >= 600 ? 'Lulus' : 'Tidak Lulus';

        // Ambil nama peserta dari relasi
        $user = UserModel::with(['mahasiswa', 'dosen', 'tendik'])->find($request->user_id);
        $nama = '';
        if ($user->role === 'mahasiswa' && $user->mahasiswa) {
            $nama = $user->mahasiswa->mahasiswa_nama;
        } elseif ($user->role === 'dosen' && $user->dosen) {
            $nama = $user->dosen->dosen_nama;
        } elseif ($user->role === 'tendik' && $user->tendik) {
            $nama = $user->tendik->tendik_nama;
        }

        HasilUjianModel::create([
            'user_id' => $request->user_id,
            'jadwal_id' => $request->jadwal_id,

            'nilai_listening' => $listening,
            'nilai_reading' => $reading,
            'nilai_total' => $total,
            'status_lulus' => $status,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Data hasil ujian berhasil disimpan.'
        ]);
    }



    public function edit_ajax($id)
    {
        $hasil_ujian = HasilUjianModel::with(['user'])->find($id);
        if (!$hasil_ujian) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan']);
        }
        
        $jadwal = JadwalModel::all();
        return view('hasil_ujian.edit_ajax', compact('hasil_ujian', 'jadwal'));
    }

    public function update_ajax(Request $request, $id)
    {
        $rules = [
            'nilai_listening' => 'required|integer|min:0|max:495',
            'nilai_reading' => 'required|integer|min:0|max:495',
            'jadwal_id' => 'required|exists:jadwal,jadwal_id',
            'user_id' => 'required|exists:users,id',
            'catatan' => 'nullable|string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi Gagal',
                'msgField' => $validator->errors(),
            ]);
        }

        $hasil_ujian = HasilUjianModel::find($id);
        if (!$hasil_ujian) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan']);
        }

        try {
            $existingResult = HasilUjianModel::where('user_id', $request->input('user_id'))
                                           ->where('jadwal_id', $request->input('jadwal_id'))
                                           ->where('hasil_id', '!=', $id)
                                           ->first();
            
            if ($existingResult) {
                return response()->json([
                    'status' => false,
                    'message' => 'Peserta sudah memiliki hasil ujian pada jadwal ini',
                ]);
            }

            $nilaiListening = $request->input('nilai_listening');
            $nilaiReading = $request->input('nilai_reading');
            $nilaiTotal = $nilaiListening + $nilaiReading;

            $hasil_ujian->update([
                'nilai_listening' => $nilaiListening,
                'nilai_reading' => $nilaiReading,
                'nilai_total' => $nilaiTotal,
                'status_lulus' => $nilaiTotal >= 600 ? 'Lulus' : 'Tidak Lulus',
                'jadwal_id' => $request->input('jadwal_id'),
                'user_id' => $request->input('user_id'),
                'catatan' => $request->input('catatan'),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Data hasil ujian berhasil diperbarui',
            ]);
        } catch (\Exception $e) {
            Log::error('Error in update_ajax: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui data: ' . $e->getMessage(),
            ]);
        }
    }

    public function confirm_ajax($id)
    {
        $hasil_ujian = HasilUjianModel::with(['jadwal', 'user.mahasiswa', 'user.dosen', 'user.tendik'])->find($id);
        if (!$hasil_ujian) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan']);
        }
        return view('hasil_ujian.confirm_ajax', compact('hasil_ujian'));
    }

    public function delete_ajax($id)
    {
        $hasil_ujian = HasilUjianModel::find($id);
        return view('hasil_ujian.delete_ajax', compact('hasil_ujian'));
    }

    public function destroy_ajax($id)
    {
        $hasil_ujian = HasilUjianModel::find($id);
        if (!$hasil_ujian) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan.']);
        }

        $hasil_ujian->delete();
        return response()->json(['status' => true, 'message' => 'Data berhasil dihapus.']);
    }


    public function export_pdf()
    {
        $hasil_ujian = HasilUjianModel::with([
            'user',
            'user.mahasiswa',
            'user.dosen',
            'user.tendik'
        ])->orderBy('hasil_id')->get();

        $pdf = Pdf::loadView('hasil_ujian.export_pdf', ['hasil_ujian' => $hasil_ujian]);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption("isRemoteEnabled", true);
        $pdf->render();

        return $pdf->stream('Data Hasil Ujian ' . '.pdf');
    }

    public function export_excel()
    {
        $hasil_ujian = HasilUjianModel::with([
            'user',
            'user.mahasiswa',
            'user.dosen',
            'user.tendik'
        ])->orderBy('hasil_id')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Nama Peserta');
        $sheet->setCellValue('C1', 'Nilai Listening');
        $sheet->setCellValue('D1', 'Nilai Reading');
        $sheet->setCellValue('E1', 'Nilai Total');
        $sheet->setCellValue('F1', 'Status Lulus');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);

        $no = 1;
        $baris = 2;
        foreach ($hasil_ujian as $value) {
            $sheet->setCellValue('A' . $baris, $no++);
            $sheet->setCellValue('B' . $baris, $value->user ? ($value->user->mahasiswa ? $value->user->mahasiswa->mahasiswa_nama : ($value->user->dosen ? $value->user->dosen->dosen_nama : ($value->user->tendik ? $value->user->tendik->tendik_nama : ''))) : '-');
            $sheet->setCellValue('C' . $baris, $value->nilai_listening);
            $sheet->setCellValue('D' . $baris, $value->nilai_reading);
            $sheet->setCellValue('E' . $baris, $value->nilai_total);
            $sheet->setCellValue('F' . $baris, $value->status_lulus);
            $baris++;
        }

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->setTitle('Data Hasil Ujian');

        $filename = 'hasil_ujian_toeic' . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    public function import()
    {
        return view('hasil_ujian.import');
    }

    public function import_ajax(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $rules = [
                'file_hasil_ujian' => ['required', 'mimes:xlsx', 'max:2025']
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi Gagal',
                    'msgField' => $validator->errors()
                ]);
            }

            try {
                $file = $request->file('file_hasil_ujian');
                $reader = IOFactory::createReader('Xlsx');
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($file->getRealPath());
                $sheet = $spreadsheet->getActiveSheet();
                $data = $sheet->toArray(null, false, true, true);

                // Validasi header
                $expectedHeaders = ['Nama Peserta', 'Jadwal', 'Nilai Listening', 'Nilai Reading', 'Nilai Total', 'Status Lulus'];
                $actualHeaders = array_map('trim', $data[1]);
                if ($actualHeaders !== $expectedHeaders) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Format header file tidak sesuai dengan template'
                    ]);
                }

                $insert = [];
                $failedRows = [];

                if (count($data) > 2) { // Header + minimal 1 data
                    for ($baris = 2; $baris <= count($data); $baris++) {
                        $value = $data[$baris];
                        if (empty($value['A'])) continue; // Skip baris kosong

                        $nama = trim($value['A']);

                        // Cari user berdasarkan nama di relasi
                        $user = UserModel::whereHas('mahasiswa', function ($q) use ($nama) {
                            $q->where('mahasiswa_nama', 'like', '%' . $nama . '%');
                        })->orWhereHas('dosen', function ($q) use ($nama) {
                            $q->where('dosen_nama', 'like', '%' . $nama . '%');
                        })->orWhereHas('tendik', function ($q) use ($nama) {
                            $q->where('tendik_nama', 'like', '%' . $nama . '%');
                        })->first();

                        if (!$user) {
                            $failedRows[] = "Baris $baris: User '$nama' tidak ditemukan";
                            continue;
                        }

                        // Parse tanggal
                        $tanggal = null;
                        try {
                            if (is_numeric($value['B'])) {
                                $tanggal = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value['B'])->format('Y-m-d');
                            } else {
                                $tanggal = \Carbon\Carbon::parse($value['B'])->format('Y-m-d');
                            }
                        } catch (\Exception $e) {
                            $failedRows[] = "Baris $baris: Format tanggal '$value[B]' salah";
                            continue;
                        }

                        // Cari jadwal_id
                        $jadwalId = JadwalModel::whereDate('tanggal_pelaksanaan', $tanggal)->value('jadwal_id');
                        if (!$jadwalId) {
                            $failedRows[] = "Baris $baris: Jadwal untuk tanggal '$tanggal' tidak ditemukan";
                            continue;
                        }

                        $listening = (int) ($value['C'] ?? 0);
                        $reading = (int) ($value['D'] ?? 0);
                        $total = (int) ($value['E'] ?? 0);
                        $statusLulus = strtoupper(trim($value['F'] ?? '')) === 'LULUS' ? 'Lulus' : 'Tidak Lulus';

                        // Validasi nilai
                        if ($listening < 0 || $listening > 495 || $reading < 0 || $reading > 495 || $total != ($listening + $reading)) {
                            $failedRows[] = "Baris $baris: Nilai tidak valid (Listening: $listening, Reading: $reading, Total: $total)";
                            continue;
                        }

                        $insert[] = [
                            'user_id' => $user->id,
                            'jadwal_id' => $jadwalId,
                            'nilai_listening' => $listening,
                            'nilai_reading' => $reading,
                            'nilai_total' => $total,
                            'status_lulus' => $statusLulus,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    if (!empty($insert)) {
                        HasilUjianModel::insert($insert); // Gunakan insert biasa untuk memastikan semua data masuk
                        $successMessage = 'Data hasil ujian berhasil diimpor. ';
                        if (!empty($failedRows)) {
                            $successMessage .= 'Beberapa baris gagal: ' . implode('; ', $failedRows);
                        }
                        return response()->json([
                            'status' => true,
                            'message' => $successMessage
                        ]);
                    }
                }

                return response()->json([
                    'status' => false,
                    'message' => 'Tidak ada data yang valid untuk diimpor. ' . (!empty($failedRows) ? 'Detail gagal: ' . implode('; ', $failedRows) : '')
                ]);
            } catch (\Throwable $e) {
                Log::error('Import Error: ' . $e->getMessage());
                return response()->json([
                    'status' => false,
                    'message' => 'Import gagal: ' . $e->getMessage()
                ]);
            }
        }

        return redirect('/');
    }

    public function download_template()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Nama Peserta');
        $sheet->setCellValue('B1', 'Jadwal');
        $sheet->setCellValue('C1', 'Nilai Listening');
        $sheet->setCellValue('D1', 'Nilai Reading');
        $sheet->setCellValue('E1', 'Nilai Total');
        $sheet->setCellValue('F1', 'Status Lulus');

        $sheet->setCellValue('A2', 'John Doe');
        $sheet->setCellValue('B2', '2025-06-02');
        $sheet->setCellValue('C2', 450);
        $sheet->setCellValue('D2', 200);
        $sheet->setCellValue('E2', 650);
        $sheet->setCellValue('F2', 'Lulus');

        $filename = 'template_hasil_ujian.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}