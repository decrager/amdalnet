<?php
/*
 * Service for calling OSS API
 */

namespace App\Services;

use App\Entity\Initiator;
use App\Entity\OssNib;
use App\Entity\SubProject;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OssService
{
    public static function receiveLicense($project = null, $fileUrl = '', $statusCode = '50')
    {
        /*
            ! DEPRECATED
        */
        $dataSubProject = OssService::getSubProjects($project);
        $initiator = $dataSubProject['initiator'];
        $ossNib = $dataSubProject['ossNib'];
        $subProjects =  $dataSubProject['subProjects'];
        $subProjectsAmdalnetIdProyeks = $dataSubProject['subProjectsAmdalnetIdProyeks'];

        foreach ($subProjects as $dataProject) {
            $dataProduct = null;
            $idProduct = 0;
            if (count($dataProject['data_proyek_produk']) > 0) {
                $dataProduct = $dataProject['data_proyek_produk'][0];
                $idProduct = $dataProduct['id_produk'];
            }
            if (in_array($dataProject['id_proyek'], $subProjectsAmdalnetIdProyeks)) {
                // call endpoint receiveLicense
                $statusIzin = OssService::getStatusIzin($project, $fileUrl, $statusCode);
                $data = [
                    'IZINFINAL' => [
                        'nib' => $initiator->nib,
                        'id_produk' => $idProduct,
                        'id_proyek' => $dataProject['id_proyek'],
                        'oss_id' => $ossNib->oss_id,
                        'id_izin' => $ossNib->id_izin,
                        'kd_izin' => $ossNib->kd_izin,
                        'kd_daerah' => $ossNib->kd_daerah,
                        'kewenangan' => $ossNib->kewenangan,
                        'nomor_izin' => null, // NIP pemroses (TUK) ? Need confirm
                        'tgl_terbit_izin' => null,  // nambah kolom di tabel amdalnet
                        'tgl_berlaku_izin' => (string)$project->updated_at,
                        'nama_ttd' => null, // nambah kolom di tabel amdalnet
                        'nip_ttd' => null, // nambah kolom di tabel amdalnet
                        'jabatan_ttd' => null, // nambah kolom di tabel amdalnet
                        'status_izin' => $statusIzin['status_izin'],
                        'file_izin' => $statusIzin['file_izin'],
                        'keterangan' => $statusIzin['keterangan'],
                        'file_lampiran' => $statusIzin['file_lampiran'],
                        'nomenklatur_nomor_izin' => $statusIzin['nomenklatur_nomor_izin'],
                        'bln_berlaku_izin' => $statusIzin['bln_berlaku_izin'],
                        'thn_berlaku_izin' => $statusIzin['thn_berlaku_izin'],
                        'data_pnbp' => [
                            [
                                'kd_akun' => null,
                                'kd_penerimaan' => null,
                                'nominal' => null,
                            ],
                        ]
                    ]
                ];
                // print_r($data);
                $response = Http::withHeaders([
                    'user_key' => env('OSS_USER_KEY'),
                ])->post(env('OSS_ENDPOINT') . '/receiveLicense', $data);
                $respJson = $response->json();
                print_r($respJson);
                // if ((int)$respJson['responreceiveLicense']['kode'] == 200) {

                // }
            }
        }
        return true;
    }

    private static function getStatusIzin($project, $fileUrl, $statusCode)
    {
        return [
            'status_izin' => $statusCode,
            'file_izin' => $fileUrl, // skkl
            'keterangan' => OssService::getStatusNameOss($statusCode),
            'file_lampiran' => null,
            'nomenklatur_nomor_izin' => null,
            'bln_berlaku_izin' => null,
            'thn_berlaku_izin' => null,
        ];
    }

    private static function getStatusNameOss($statusCode)
    {
        $dict = [
            '10' => 'Dokumen Lengkap',
            '11' => 'Dokumen Belum Lengkap',
            '20' => 'Validasi',
            '30' => 'Verifikasi Pembayaran',
            '31' => 'Menunggu pembayaran PNBP',
            '32' => 'Menunggu verifikasi bukti bayar PNBP',
            '33' => 'Persetujuan Pembayaran',
            '40' => 'Inspeksi',
            '41' => 'Konfirmasi Persyaratan',
            '42' => 'Data Konfirmasi Persyaratan',
            '43' => 'Re-Konfirmasi Persyaratan',
            '44' => 'Data Re-Konfirmasi Persyaratan',
            '45' => 'Persyaratan terverifikasi',
            '46' => 'Perlu perbaikan persyaratan',
            '47' => 'Perlu perbaikan persyaratan',
            '48' => 'Persetujuan Perizinan',
            '60' => 'Peringatan',
            '70' => 'Penghentian Sementara Kegiatan Berusaha',
            '80' => 'Pengenaan Denda Administratif',
            '93' => 'Penolakan Persyaratan',
            '50' => 'Izin terbit/SS terverifikasi',
            '90' => 'Ditolak',
        ];

        return $dict[$statusCode];
    }

    private static function getStatusNameAmdalnet($statusCode)
    {
        $dict = [
            '10' => 'Berkas Formulir Kerangka Acuan Sesuai',
            '11' => 'Berkas Formulir Kerangka Acuan Dikembalikan',
            '20' => 'Hasil Penapisan',
            '40' => 'Penilaian Substansi Formulir KA/Andal RKL-RPL/UKL-UPL',
            '45' => 'Berkas Administrasi Andal RKL-RPL/UKL-UPL Dinyatakan Lengkap dan Benar',
            '46' => 'Perbaikan Andal RKL-RPL/UKL-UPL',
            '48' => 'Uji Kelayakan Andal RKL-RPL/UKL-UPL',
            '93' => 'Uji Administrasi Andal RKL-RPL Dikembalikan',
            '50' => 'Penerbitan SKKL/PKPLH',
            '90' => 'Penerbitan SKKL/PKPLH Ditolak',
        ];

        return $dict[$statusCode];
    }

    private static function getSubProjects($project)
    {
        $initiator = Initiator::find($project->id_applicant);
        if (!$initiator) {
            Log::error('Initiator not found');
            return false;
        }
        $ossNib = OssNib::where('nib', $initiator->nib)->first();
        if (!$ossNib) {
            Log::error('OSSNib not found');
            return false;
        }
        $jsonContent = $ossNib->json_content;
        $subProjects = $jsonContent['data_proyek'];
        $subProjectsAmdalnet = SubProject::where('id_project', $project->id)->get();
        if (empty($subProjectsAmdalnet)) {
            Log::error('Sub projects not found');
            return false;
        }
        $subProjectsAmdalnetIdProyeks = [];
        foreach ($subProjectsAmdalnet as $sp) {
            array_push($subProjectsAmdalnetIdProyeks, $sp->id_proyek);
        }

        return [
            'ossNib' => $ossNib,
            'initiator' => $initiator,
            'subProjects' => $subProjects,
            'subProjectsAmdalnetIdProyeks' => $subProjectsAmdalnetIdProyeks,
        ];
    }

    public static function receiveLicenseStatus($project = null, $statusCode)
    {
        $dataSubProject = OssService::getSubProjects($project);
        $ossNib = $dataSubProject['ossNib'];
        $subProjects =  $dataSubProject['subProjects'];
        $subProjectsAmdalnetIdProyeks = $dataSubProject['subProjectsAmdalnetIdProyeks'];
        $jsonContent = $ossNib->json_content;
        $idIzin = $ossNib->id_izin;
        $dataChecklist = $jsonContent['data_checklist'];

        foreach ($subProjects as $dataProject) {
            $idProduct = null;
            if (count($dataProject['data_proyek_produk']) > 0) {
                $dataProduct = $dataProject['data_proyek_produk'][0];
                $idProduct = $dataProduct['id_produk'];
            }
            if (in_array($dataProject['id_proyek'], $subProjectsAmdalnetIdProyeks)) {
                // foreach ($dataChecklist as $c) {
                //     if ($c['id_proyek'] == $dataProject['id_proyek']) {
                //         $idIzin = $c['id_izin'];
                //         break;
                //     }
                // }
                $subProjectAmdalnet = SubProject::where('id_proyek', $dataProject['id_proyek'])
                    ->orderBy('created_at', 'desc')
                    ->first();
                $kdIzinNew = $ossNib->kd_izin;
                // mapping kode izin:
                // 029000000002	Persetujuan SKKL
                // 029000000010	SPPL
                // 029000000001	Persetujuan PKPLH
                if ($subProjectAmdalnet) {
                    if ($subProjectAmdalnet->result == 'AMDAL') {
                        $kdIzinNew = '029000000002';
                    } else if ($subProjectAmdalnet->result == 'UKL-UPL') {
                        $kdIzinNew = '029000000001';
                    } else if ($subProjectAmdalnet->result == 'SPPL') {
                        $kdIzinNew = '029000000010';
                    }
                }
                // kewenangan:
                // 00 : kewenangan pusat
                // 01 : kewenangan provinsi
                // 02 : kewenangan kabupaten
                $authorityNew = $ossNib->kewenangan;
                if (strtolower($project->authority) == 'pusat') {
                    $authorityNew = '00';
                } else if (strtolower($project->authority) == 'provinsi') {
                    $authorityNew = '01';
                } else if (strtolower($project->authority) == 'kabupaten') {
                    $authorityNew = '02';
                }
                // $data = [
                //     'IZINSTATUS' => [
                //         'nib' => $ossNib->nib,
                //         'id_produk' => $idProduct,
                //         'id_proyek' => $dataProject['id_proyek'],
                //         'oss_id' => $ossNib->oss_id,
                //         'id_izin' => $idIzin,
                //         'kd_izin' => $ossNib->kd_izin,
                //         'kd_instansi' => $dataProject['sektor'],
                //         'kd_status' => $statusCode,
                //         'tgl_status' => (string)$project->updated_at,
                //         'nip_status' => null, // NULL
                //         'nama_status' => OssService::getStatusNameOss($statusCode),
                //         'keterangan' => OssService::getStatusNameAmdalnet($statusCode),
                //         'id_daerah' => $jsonContent['kd_daerah'],
                //         'kewenangan' => $jsonContent['kewenangan'],
                //         'kewenangan_new' => $authorityNew,
                //         'kd_izin_new' => $kdIzinNew,
                //     ],
                // ];
                $data = [
                    'IZINSTATUS' => [
                        'nib' => $ossNib->nib,
                        'id_produk' => $idProduct,
                        'id_proyek' => $dataProject['id_proyek'],
                        'oss_id' => $ossNib->oss_id,
                        'id_izin' => $idIzin,
                        'kd_izin' => $ossNib->kd_izin,
                        'kd_instansi' => $dataProject['sektor'],
                        'kd_status' => $statusCode,
                        'tgl_status' => (string)$project->updated_at,
                        'nip_status' => null, // NULL
                        'nama_status' => OssService::getStatusNameOss($statusCode),
                        'keterangan' => OssService::getStatusNameAmdalnet($statusCode),
                        // 'id_daerah' => $jsonContent['kd_daerah'],
                        // 'kewenangan' => $jsonContent['kewenangan'],
                        // 'kewenangan_new' => $authorityNew,
                        // 'kd_izin_new' => $kdIzinNew,
                        'data_pnbp' => [
                            [
                                'kd_akun' => null,
                                'kd_penerimaan' => null,
                                'kd_billing' => null,
                                'tgl_billing' => null,
                                'tgl_expire' => null,
                                'nominal' => null,
                                'url_dokumen' => null,
                            ],
                        ]
                    ],
                ];
                $response = Http::withHeaders([
                    'user_key' => env('OSS_USER_KEY'),
                ])->post(env('OSS_ENDPOINT') . '/receiveLicenseStatusLingkungan', $data);
                $respJson = $response->json();
                // print_r(json_encode($data));
                // print_r($respJson);
                Log::debug(json_encode($data));
                Log::debug(json_encode($respJson));
                $idIzinNew = null;
                if (isset($respJson['responreceiveLicenseStatus']['id_izin_new'])) {
                    $idIzinNew = $respJson['responreceiveLicenseStatus']['id_izin_new'];
                }
                if (!empty($idIzinNew) && $idIzinNew != $idIzin) {
                    // update id_izin
                    $ossNib->id_izin = $idIzinNew;
                    $jsonContent['id_izin'] = $idIzinNew;
                    $ossNib->json_content = $jsonContent;
                    $ossNib->save();
                    Log::debug('Upgrade kewenangan: oss_nibs .'
                        . $ossNib->nib . ' updated with id_izin_new = ' . $idIzinNew);
                }
            }
        }
        return true;
    }

    public static function inqueryFileDS($nib, $idIzin)
    {
        $data = [
            "INQUERYFILEDS" => [
                "id_permohonan_izin" => $idIzin,
            ]
        ];
        $sha1 = sha1(env('OSS_REQUEST_USERNAME') . env('OSS_REQUEST_PASSWORD') . $nib . date('Ymd'));
        $response = Http::withHeaders([
            'user_key' => env('OSS_USER_KEY'),
            'token' => $sha1,
        ])->post(env('OSS_ENDPOINT') . '/inqueryFileDS', $data);
        $respJson = $response->json();
        // print_r($respJson);
        Log::debug(json_encode($data));
        Log::debug(json_encode($respJson));
        return $respJson;
    }

    public static function inqueryNIB($nib)
    {
        $data = [
            "INQUERYNIB" => [
                "nib" => $nib,
            ]
        ];
        $sha1 = sha1(env('OSS_REQUEST_USERNAME') . env('OSS_REQUEST_PASSWORD') . $nib . date('Ymd'));
        $response = Http::withHeaders([
            'user_key' => env('OSS_USER_KEY'),
            'token' => $sha1,
        ])->post(env('OSS_ENDPOINT') . '/inqueryNIB', $data);
        $respJson = $response->json();
        // Log::debug(json_encode($data));
        // Log::debug(json_encode($respJson));
        return $respJson;
    }
}
?>
