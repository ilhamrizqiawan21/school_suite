<?php
require getcwd().'/vendor/autoload.php';
final class DarkModeFixtures extends Tests\TestCase {
 public function collect(): void {
  $this->setUp();
  try {
   if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') throw new RuntimeException('Audit requires isolated in-memory SQLite.');
   $this->artisan('migrate', ['--force'=>true])->assertExitCode(0);
   $this->seed(Database\Seeders\DatabaseSeeder::class);
   $pages=[];
   foreach (['/','/login','/profil-sekolah','/berita','/ppdb'] as $url) $this->capture($pages,'public'.$url,$url);
   $admin=App\Models\User::where('username','admin_pilot')->firstOrFail();
   $admin->forceFill(['must_change_password'=>false])->save();
   $this->actingAs($admin);
   foreach (['/administrasi/persuratan','/administrasi/persuratan/surat-masuk','/administrasi/persuratan/surat-keluar','/administrasi/persuratan/arsip','/administrasi/sarpras','/administrasi/tugas','/pembelajaran/progress','/spmb','/spmb/pendaftar','/spmb/berkas','/spmb/seleksi','/spmb/daftar-ulang','/ujian','/akademik/kelas','/penilaian/paket-soal'] as $url) $this->capture($pages,'admin'.$url,$url);
   $exam=App\Modules\Assessment\Infrastructure\Models\Exam::first();
   if($exam) foreach (['hasil','monitor'] as $section) $this->capture($pages,'admin/ujian/'.$section,'/ujian/'.$exam->id.'/'.$section);
   foreach (['teacher','student'] as $role) {
    $user=App\Models\User::whereHas('role',fn($q)=>$q->where('name',$role))->firstOrFail();
    $user->forceFill(['must_change_password'=>false])->save();
    $this->actingAs($user);
    foreach (($role==='teacher'?['/dashboard','/pembelajaran','/penilaian/bank-soal','/penilaian/paket-soal']:['/dashboard','/siswa/tugas']) as $url) $this->capture($pages,$role.$url,$url);
   }
   file_put_contents(getcwd().'/artifacts/dark-mode/extended-fixtures.json',json_encode($pages,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
   echo json_encode(array_map(fn($v)=>['status'=>$v['status'],'component'=>$v['page']['component']??null],$pages),JSON_PRETTY_PRINT),"\n";
  } finally { /* Process-local in-memory database is discarded on exit. */ }
 }
 private function capture(array &$pages,string $key,string $url):void {
  $response=$this->get($url);
  $pages[$key]=['url'=>$url,'status'=>$response->getStatusCode()];
  if($response->getStatusCode()===200) $pages[$key]['page']=$response->viewData('page');
 }
}
(new DarkModeFixtures('collect'))->collect();
