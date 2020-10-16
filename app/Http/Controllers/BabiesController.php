<?php

namespace App\Http\Controllers;

use App\ProgressBaby;
use App\Baby;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use DateTime;
use App\Exports\BabyExport;
use Maatwebsite\Excel\Facades\Excel;

class BabiesController extends Controller
{
    public function export_excel(){
        return Excel::download(new BabyExport, 'baby.xlsx');
    }

    public function progress(Baby $baby, Request $request){
        $progress = DB::table('babies AS b')
        ->join('progress_babies AS p', 'b.id', '=', 'p.id_bayi')
        ->select('b.nama', 'b.nama_ibu', 'b.nama_ayah', 'b.tempat_lahir', 'b.tanggal_lahir', 'b.anak_ke', 'b.alamat', 'b.jenis_kelamin', 'b.golongan_darah', 'p.id_bayi', 'p.bulan_ke', 'p.panjang_bayi', 'p.berat_bayi')
        ->where('id_bayi', $baby->id)
        ->get();
        $jk = $baby->jenis_kelamin == 1 ? 'fas fa-mars' : 'fas fa-venus';
        $kelamin = $baby->jenis_kelamin == 1 ? 'Laki-Laki' : 'Perempuan';
        $i = 0;
        $bulan = null;
        foreach($progress as $d):
            $bulan[$i] = $d->bulan_ke;
            $i++;
        endforeach;
        if(count($progress) == 0){
            $progress = null;
            $panjang_bayi = $baby->panjang_bayi;
            $berat_bayi = $baby->berat_bayi;
        }else{
            $progress = $progress;
        }
        $dataProgress = $this->chartProgress($progress, $baby, $bulan);
        $session = $request->session()->get('role');
        if($progress == null || max($bulan) < 13){
            $umur = "0 - 12 Bulan";
        }else if(max($bulan) > 12 && max($bulan) <= 24){
            $umur = "1 - 2 Tahun";
        }else if(max($bulan) > 24 && max($bulan) <= 36){
            $umur = "2 - 3 Tahun";
        }else if(max($bulan) > 36 && max($bulan) <= 48){
            $umur = "3 - 4 Tahun";
        }else if(max($bulan) > 48 && max($bulan) <= 60){
            $umur = "4 - 5 Tahun";
        }
        $data = [
            'baris' => $baby,
            'progress' => $progress,
            'jk' => $jk,
            'dtProgress' => $dataProgress,
            'umur' => $umur,
            'kelamin' => $kelamin,
            'session' => $session
        ];
        echo view('progress.index', $data);
        if($baby->jenis_kelamin == 1){
            if($progress == null || max($bulan) < 13){
                echo view('progress.kms-laki', $data);
            }else if(max($bulan) > 12 && max($bulan) <= 24){
                // dd('berhasil');
                echo view('progress.kms-laki2', $data);
            }else if(max($bulan) > 24 && max($bulan) <= 36){
                echo view('progress.kms-laki3', $data);
            }else if(max($bulan) > 36 && max($bulan) <= 48){
                echo view('progress.kms-laki4', $data);
            }else if(max($bulan) > 48 && max($bulan) <= 60){
                echo view('progress.kms-laki5', $data);
            }
        }else if($baby->jenis_kelamin == 2){
            if($progress == null || max($bulan) < 13){
                echo view('progress.kms-perempuan', $data);
            }else if(max($bulan) > 12 && max($bulan) <= 24){
                echo view('progress.kms-perempuan2', $data);
            }else if(max($bulan) > 24 && max($bulan) <= 36){
                echo view('progress.kms-perempuan3', $data);
            }else if(max($bulan) > 36 && max($bulan) <= 48){
                echo view('progress.kms-perempuan4', $data);
            }else if(max($bulan) > 48 && max($bulan) <= 60){
                echo view('progress.kms-perempuan5', $data);
            }
        }
    }

    public function simpanprogress(Request $request){
        $detail = DB::table('progress_babies')->select('bulan_ke')->where('id_bayi', $request->id_bayi)->get();
        for($i=0 ; $i<count($detail) ; $i++){
            if($detail[$i]->bulan_ke == $request->bulan_ke){
                return redirect('/baby/'.$request->id_bayi.'/progress')->with('danger', "Data bulan ke-".$detail[$i]->bulan_ke ." sudah ada");
            }
        }
        
        $request->validate([
            'bulan_ke' => 'required',
            'panjang_bayi' => 'required',
            'berat_bayi' => 'required'
        ]);
        ProgressBaby::create($request->all());
        return redirect('/baby/'.$request->id_bayi.'/progress')->with('status', "Data baru berhasil ditambahkan");
    }

    public function chartProgress($progress, $baby, $bulan){
        $data[0] = $baby->berat_bayi;
        $progressBaru[0] = 0;
        if($progress != null){
            for($i = 1; $i<=count($progress) ; $i++){
                $progressBaru[$i] = $progress[$i-1];
            }
        }
        if($progress == null){
            for($i = 1; $i<=60 ; $i++){
                $data[$i] = null;
            }
            return $data;
        }else{
            if(max($bulan) >=1 && max($bulan) <= 60){
                for($i=1 ; $i<=60 ; $i++){
                    $data[$i] = null;
                    for($j=1 ; $j<=count($progress) ; $j++){
                        if($i == $progressBaru[$j]->bulan_ke){
                            $data[$i] = $progressBaru[$j]->berat_bayi;
                        }
                    }
                }
            }
            return $data;
        }
    }

    public function dataProgress($progress, $baby){
        $data[0] = $baby->berat_bayi;
        if($progress == null){
            for($i = 1; $i<=12 ; $i++){
                $data[$i] = null;
            }
            return $data;
        }else{
            if(count($progress) <= 12){
                for($i = 1; $i<=12 ; $i++){
                    if($i<=count($progress)){
                        $data[$i] = $progress[$i-1]->berat_bayi;
                    }else if($i > count($progress)){
                        $data[$i] = null;
                    }
                }
            }else if(count($progress) >= 13 && count($progress) <= 24){
                for($i = 1; $i<=25 ; $i++){
                    if($i<=count($progress)){
                        $data[$i] = $progress[$i-1]->berat_bayi;
                    }else if($i > count($progress)){
                        $data[$i] = null;
                    }
                }
            }else if(count($progress) >= 25 && count($progress) <= 36){
                for($i = 1; $i<=36 ; $i++){
                    if($i<=count($progress)){
                        $data[$i] = $progress[$i-1]->berat_bayi;
                    }else if($i > count($progress)){
                        $data[$i] = null;
                    }
                }
            }else if(count($progress) >= 37 && count($progress) <= 48){
                for($i = 1; $i<=48 ; $i++){
                    if($i<=count($progress)){
                        $data[$i] = $progress[$i-1]->berat_bayi;
                    }else if($i > count($progress)){
                        $data[$i] = null;
                    }
                }
            }else if(count($progress) >= 49 && count($progress) <= 60){
                for($i = 1; $i<=60 ; $i++){
                    if($i<=count($progress)){
                        $data[$i] = $progress[$i-1]->berat_bayi;
                    }else if($i > count($progress)){
                        $data[$i] = null;
                    }
                }
            }
            return $data;
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $role = $request->session()->get('role');
        $babies = Baby::all();
        if($role === 'Admin' && $role !== 'Staff' && $role !== 'Staff2'){
            return redirect('/home');
        }else if($role === 'Staff' || $role === 'Staff2' && $role !== 'Admin'){
            return view('baby', compact('babies'));
        }else{
            return redirect('login');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'nama_ibu' => 'required',
            'pekerjaan_ibu' => 'required',
            'nama_ayah' => 'required',
            'pekerjaan_ayah' => 'required',
            'tempat_lahir' => 'required',
            'tanggal_lahir' => 'required',
            'anak_ke' => 'required',
            'alamat' => 'required',
            'jenis_kelamin' => 'required',
            'golongan_darah' => 'required',
            'panjang_bayi' => 'required',
            'berat_bayi' => 'required'
        ]);
        
        $request->nama = ucwords($request->nama);
        $request->nama_ibu = ucwords($request->nama_ibu);
        $request->pekerjaan_ibu = ucwords($request->pekerjaan_ibu);
        $request->nama_ayah = ucwords($request->nama_ayah);
        $request->pekerjaan_ayah = ucwords($request->pekerjaan_ayah);
        $request->tempat_lahir = ucfirst($request->tempat_lahir);
        $request->alamat = ucfirst($request->alamat);
        
        $request->tanggal_lahir = mktime(
            (int)substr($request->tanggal_lahir, 11, 2), // jam
            (int)substr($request->tanggal_lahir, 14, 2), //menit
            00, // detik
            (int)substr($request->tanggal_lahir, 5, 2), // bulan
            (int)substr($request->tanggal_lahir, 8, 2), // tanggal
            (int)substr($request->tanggal_lahir, 0, 4) // tahun
        );
        
        Baby::create([
            'nama' => $request->nama,
            'nama_ibu' => $request->nama_ibu,
            'pekerjaan_ibu' => $request->pekerjaan_ibu,
            'nama_ayah' => $request->nama_ayah,
            'pekerjaan_ayah' => $request->pekerjaan_ayah,
            'tempat_lahir' => $request->tempat_lahir,
            'tanggal_lahir' => $request->tanggal_lahir,
            'anak_ke' => $request->anak_ke,
            'alamat' => $request->alamat,
            'jenis_kelamin' => $request->jenis_kelamin,
            'golongan_darah' => $request->golongan_darah,
            'panjang_bayi' => $request->panjang_bayi,
            'berat_bayi' => $request->berat_bayi
        ]);
        
        return redirect('/baby')->with('status', "Data '" . $request->nama . "' berhasil ditambahkan");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Baby  $baby
     * @return \Illuminate\Http\Response
     */
    public function show(Baby $baby)
    {
        $progress = DB::table('babies AS b')
        ->join('progress_babies AS p', 'b.id', '=', 'p.id_bayi')
        ->select('b.nama', 'b.nama_ibu', 'b.nama_ayah', 'b.tempat_lahir', 'b.tanggal_lahir', 'b.anak_ke', 'b.alamat', 'b.jenis_kelamin', 'b.golongan_darah', 'p.id_bayi', 'p.bulan_ke', 'p.panjang_bayi', 'p.berat_bayi')
        ->where('id_bayi', $baby->id)
        ->get();
        $i = 0;
        foreach($progress as $d):
            $bulan[$i] = $d->bulan_ke;
            $i++;
        endforeach;
        if(count($progress) == 0){
            $panjang_bayi = $baby->panjang_bayi;
            $berat_bayi = $baby->berat_bayi;
        }else{
            $detail = DB::table('progress_babies')->select('panjang_bayi', 'berat_bayi')->where('id_bayi', $baby->id)->where('bulan_ke', max($bulan))->get();
            $panjang_bayi = $detail[0]->panjang_bayi;
            $berat_bayi = $detail[0]->berat_bayi;
        }
        $umur = $this->hitung_umur(date('Y-m-d', $baby->tanggal_lahir));
        $jk = $baby->jenis_kelamin == 1 ? 'Laki-laki' : 'Perempuan';
        $data = [
            'baby' => $baby,
            'jenis_kelamin' => $jk,
            'umur' => $umur,
            'panjang_sekarang' => $panjang_bayi,
            'berat_sekarang' => $berat_bayi
        ];
        return view('show', $data);
    }

    function status($jk, $tanggal_lahir){
        $bulan = (date('Y')-date('Y', $tanggal_lahir))*12;
        $bulan += date('m')-date('m', $tanggal_lahir);
    }

    function hitung_umur($tanggal_lahir){
        $birthDate = new DateTime($tanggal_lahir);
        $today = new DateTime("today");
        if ($birthDate > $today) { 
            exit("0 tahun 0 bulan 0 hari");
        }
        $y = $today->diff($birthDate)->y;
        $m = $today->diff($birthDate)->m;
        $d = $today->diff($birthDate)->d;
        if($y > 0){
            if($m == 0 && $d ==0){
                return $y." tahun";
            }else if($m == 0){
                return $y." tahun ".$d." hari";
            }else if($d == 0){
                return $y." tahun ".$m." bulan";
            }else{
                return $y." tahun ".$m." bulan ".$d." hari";
            }
        }else if($m > 0){
            if($y == 0 && $d ==0){
                return $m." bulan";
            }else if($y == 0){
                return $m." bulan ".$d." hari";
            }else if($d == 0){
                return $y." tahun ".$m." bulan";
            }else{
                return $y." tahun ".$m." bulan ".$d." hari";
            }
        }else{
            return $d." hari";
        }
    }

    public function hitungIdeal($tanggal_lahir, Baby $baby){
        $birthDate = new DateTime($tanggal_lahir);
        $today = new DateTime("today");
        if ($birthDate > $today) { 
            exit("0 tahun 0 bulan 0 hari");
        }
        $y = $today->diff($birthDate)->y;
        $m = $today->diff($birthDate)->m;
        $d = $today->diff($birthDate)->d;
        // var_dump($m);die;
        if($y == 0 && $m >= 1 && $m <= 6){
            $hasil = (int)$baby->berat_bayi+($m*600);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Baby  $baby
     * @return \Illuminate\Http\Response
     */
    public function edit(Baby $baby)
    {
        $umur = $this->hitung_umur(date('Y-m-d', $baby->tanggal_lahir));
        $laki = '';$perempuan = '';
        switch($baby->jenis_kelamin){
            case 1: $laki = 'checked';
                break;
            case 2: $perempuan = 'checked';
                break;
        }
        $a = '';$b = '';$ab = '';$o = '';$bt = '';
        switch($baby->golongan_darah){
            case "A": $a = 'selected';
                break;
            case "B": $b = 'selected';
                break;
            case "AB": $ab = 'selected';
                break;
            case "O": $o = 'selected';
                break;
            case "BT": $bt = 'selected';
                break;
        }
        $data = [
            'baby' => $baby,
            'laki' => $laki,
            'perempuan' => $perempuan,
            'A' => $a,
            'B' => $b,
            'AB' => $ab,
            'O' => $o,
            'BT' => $bt,
            'umur' => $umur
        ];
        return view('edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Baby  $baby
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Baby $baby)
    {
        $request->validate([
            'pekerjaan_ibu' => 'required',
            'pekerjaan_ayah' => 'required',
            'alamat' => 'required',
            'golongan_darah' => 'required',
        ]);
        // update data pegawai
        DB::table('babies')->where('id',$baby->id)->update([
            'pekerjaan_ibu' => $request->pekerjaan_ibu,
            'pekerjaan_ayah' => $request->pekerjaan_ayah,
            'alamat' => $request->alamat,
            'golongan_darah' => $request->golongan_darah
        ]);
        // alihkan halaman ke halaman pegawai
        return redirect('/baby/'.$baby->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Baby  $baby
     * @return \Illuminate\Http\Response
     */
    public function destroy(Baby $baby)
    {
        Baby::destroy($baby->id);
        return redirect('/baby')->with('status', "Data '" . $baby->nama . "' berhasil dihapus");
    }
}
