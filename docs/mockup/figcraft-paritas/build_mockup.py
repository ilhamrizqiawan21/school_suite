import base64
import html
import json
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile

ROOT = Path(__file__).parent
SOURCE = ROOT.parent / "figcraft-2026" / "school-suite-calm-academic.figcraft"
SERVICES = [
    ["PTSP","PTSP Online","Pemohon","Jenis layanan|Keperluan|Nama pemohon|Kontak WhatsApp|Berkas persyaratan|Catatan","Kirim permohonan","Antrean PTSP"],
    ["SURAT","Kirim surat dinas","Instansi","Nama instansi|Narahubung dan jabatan|Nomor dan tanggal surat|Sifat dan perihal|PDF surat utama|Lampiran pendukung","Terbitkan tanda terima","Agenda surat"],
    ["TAMU","Buku tamu digital","Resepsionis","Nama pengunjung|Nomor kontak|Pegawai tujuan|Keperluan|Waktu datang|Waktu pulang","Simpan kunjungan","Riwayat kunjungan"],
    ["IZIN","Surat izin siswa","Wali siswa","Siswa terverifikasi|Jenis izin|Tanggal mulai dan akhir|Alasan izin|Bukti pendukung|Tanda tangan wali","Ajukan izin","Verifikasi izin"],
    ["MUTASI","Permohonan mutasi","Wali siswa","Mutasi masuk atau keluar|Identitas siswa|Sekolah asal dan tujuan|Periode|Alasan mutasi|Dokumen pendukung","Kirim pengajuan","Antrean mutasi"],
    ["LEGAL","Legalisir ijazah","Alumni","Identitas alumni|Jumlah lembar|Keperluan legalisir|Metode pengambilan|Data penerima kuasa|Tanda tangan pemohon","Ajukan legalisir","Register legalisir"],
    ["KUASA","Surat kuasa dan SPTJM","Alumni","Jenis dokumen|Nomor ijazah|Identitas pemberi kuasa|Identitas penerima kuasa|Keperluan|Tanda tangan pihak terkait","Pratinjau PDF","Dokumen legalisir"],
    ["SKL","Kelulusan dan SKL","Siswa","Identitas siswa|Periode kelulusan|Kode verifikasi","Cek kelulusan","Publikasi SKL"],
    ["VERIF","Verifikasi siswa dan alumni","Pengunjung","Jenis verifikasi|Nomor dokumen atau kode QR","Periksa dokumen","Verifikasi dokumen"],
    ["ALUMNI","Portal alumni","Alumni","Angkatan dan jurusan|Status studi atau kerja|Instansi|Kontak|Bukti kelulusan|Preferensi publikasi","Simpan profil","Direktori dan tracer"],
    ["PPDB","Pendaftaran peserta didik","Calon siswa","Periode dan jalur|Identitas calon siswa|Kontak wali|Sekolah asal|Berkas persyaratan|Pernyataan pengajuan","Kirim pendaftaran","Seleksi dan daftar ulang"],
    ["ADUAN","Siap Lapor","Pelapor","Kategori laporan|Pengiriman bernama atau anonim|Kronologi|Lokasi kejadian|Bukti foto atau dokumen|Kontak opsional","Kirim laporan","Antrean satgas"],
    ["PUSTAKA","Perpustakaan digital","Siswa","Pencarian judul atau penulis|Kategori koleksi|Jenis bahan|Usulan pengadaan|Presensi kunjungan|Pemeriksaan bebas pustaka","Telusuri layanan","Ruang kerja pustakawan"],
    ["PRESENSI","Kios presensi siswa","Siswa","Tempel kartu pelajar|Pindai QR kartu","Mulai pemindaian","Rekap presensi"],
    ["PIKET","Izin keluar-masuk","Piket","Cari siswa atau pindai kartu|Alasan keluar|Waktu keluar|Batas kembali|Pemberi izin","Terbitkan izin keluar","Riwayat izin keluar"],
    ["SATPAM","Validasi pos jaga","Satpam","Pindai kode izin|Input manual|Jenis pemeriksaan keluar atau kembali","Validasi izin","Riwayat pos jaga"],
    ["BK","Pencatatan pelanggaran","BK","Pindai QR atau cari siswa|Jenis pelanggaran|Waktu dan lokasi|Kronologi|Bukti terbatas|Tindak lanjut","Simpan catatan","Riwayat kasus BK"],
    ["PROPOSAL","Proposal kegiatan guru","Guru","Nama dan jenis kegiatan|Penyelenggara|Jadwal|Tujuan|Rencana anggaran|Lampiran pendukung","Ajukan proposal","Review kegiatan"],
    ["LPJ","Proposal dan LPJ mandiri","Pengaju","Proposal terkait|Revisi RAB|Realisasi anggaran|Dokumen bukti|Catatan penyelesaian|Status dana","Kirim LPJ","Review proposal dan LPJ"],
    ["TRACK","Lacak status berkas","Pemohon","Jenis layanan|Nomor resi atau tiket|Verifikasi pemohon","Lacak berkas","Hasil pelacakan"],
    ["IKM","Zona Integritas dan IKM","Pengunjung","Layanan yang dinilai|Penilaian pengalaman|Masukan opsional","Kirim penilaian","Rekap IKM"],
]
with ZipFile(SOURCE) as source:
    old = json.loads(source.read("document.json"))
    hero = old["nodes"]["552"]["fills"][-1]
    art = source.read("images/" + hero["image"])
    art_index = [entry for entry in old["imageIndex"] if entry["hash"] == hero["image"]]

INK, MUTED, GREEN, FOREST = "#172d28", "#536964", "#166550", "#10392f"
MINT, PAPER, WHITE, LINE, GOLD = "#e3f2e9", "#f3f6f2", "#ffffff", "#d8e4dc", "#f0c679"

def paint(value):
    return [{"type": "solid", "color": dict(zip("rgba", [int(value[i:i+2], 16)/255 for i in (1,3,5)]+[1])), "opacity": 1, "visible": True, "blend": "normal"}]

nodes = {}
boards = {}
links = []
page = dict(old["nodes"]["1"], name="School Suite · Paritas layanan", children=[], flows=[])
nodes["1"] = page

def node(kind, name, parent, x, y, w, h, **extra):
    number = len(nodes) + 1
    nodes[str(number)] = dict(id=number, type=kind, name=name, parent=parent, transform=[1,0,0,1,x,y], width=w, height=h, **extra)
    nodes[str(parent)].setdefault("children", []).append(number)
    return number

def box(parent, x, y, w, h, color=WHITE, rad=0, name="Panel", image=False):
    return node("frame", name, parent, x,y,w,h, fills=paint(color)+([hero] if image else []),
                corners={"radii":[rad]*4,"smoothing":0}, clip=True)

def txt(parent, x,y,w, value, size=14, color=INK, bold=False, lines=1):
    style=dict(font_family="Inter",font_style="Semi Bold" if bold else "Regular",font_size=size,
               line_height={"unit":"percent","value":135},letter_spacing={"unit":"percent","value":0},
               decoration="none",case="original",fills=None,link=None)
    return node("text",value,parent,x,y,w,int(size*1.35*lines)+3,characters=value,style=style,
                fills=paint(color),auto_resize="height",blend="normal")

def button(parent,x,y,w,label,target=None, color=GREEN, text_color=WHITE):
    shape=box(parent,x,y,w,42,color,9,label)
    txt(shape,12,11,w-24,label,13,text_color,True)
    if target: links.append((shape,target))
    return shape

def board(key,title,w=1280,h=880):
    index=len(boards)
    frame=box(1,(index%5)*1330,(index//5)*1550,w,h,PAPER,0,title)
    boards[key]=frame
    return frame

groups=[
    ("Persuratan & tamu", ["PTSP","SURAT","TAMU","ADUAN"]),
    ("Siswa & alumni", ["PPDB","IZIN","MUTASI","LEGAL","KUASA","SKL","VERIF","ALUMNI"]),
    ("Ketertiban & sarana", ["PRESENSI","PIKET","SATPAM","BK","TRACK","PUSTAKA"]),
]
labels={item[0]:item[1] for item in SERVICES}

def brand(p, w, mobile=False):
    box(p,0,0,w,33,FOREST)
    txt(p,24,7,w-48,"Portal resmi sekolah  ·  Informasi dan layanan",11,WHITE)
    box(p,0,33,w,67,WHITE)
    box(p,24,50,34,34,GREEN,8,"Monogram")
    txt(p,31,57,23,"SS",14,WHITE,True)
    txt(p,69,55,190,"School Suite",19,INK,True)
    if mobile:
        button(p,286,46,82,"Menu","M-MENU",MINT,FOREST)
    else:
        for x,caption,target,width in [(420,"Profil","INFO",100),(530,"E-Layanan","MENU",120),
                                       (665,"Informasi","INFO",110),(785,"Pusat unduh","UNDUH",125),
                                       (930,"Lacak berkas","TRACK",130),(1100,"Masuk","LOGIN",140)]:
            button(p,x,46,width,caption,target,MINT if target!="LOGIN" else GREEN,FOREST if target!="LOGIN" else WHITE)

def landing(mobile):
    w=390 if mobile else 1280
    p=board("M-HOME" if mobile else "HOME","Beranda · "+("Ponsel" if mobile else "Desktop"),w,1110 if mobile else 980)
    brand(p,w,mobile)
    box(p,0,100,w,575 if mobile else 480,FOREST)
    txt(p,24 if mobile else 54,132,320,"Informasi & layanan sekolah",14,GOLD,True)
    txt(p,24 if mobile else 54,171,342 if mobile else 575,"Layanan sekolah, lebih dekat.",38 if mobile else 58,WHITE,True,2)
    txt(p,24 if mobile else 54,286,338 if mobile else 520,"Temukan informasi sekolah, ajukan permohonan, dan pantau hasilnya dari satu tempat.",16,"#d5e8dc",False,3)
    box(p,24 if mobile else 660,420 if mobile else 134,342 if mobile else 570,222 if mobile else 411,MINT,18,"Foto kampus ilustratif",True)
    for i,(caption,target) in enumerate([("PTSP Online","PTSP"),("Lacak berkas","TRACK"),("Pendaftaran","PPDB"),("Siap Lapor","ADUAN")]):
        x=(24+(i%2)*172) if mobile else 54+(i%2)*175
        y=(342+(i//2)*44) if mobile else 384+(i//2)*52
        button(p,x,y,158,caption,target,GOLD if i==0 else "#245b4b",FOREST if i==0 else WHITE)
    top=704 if mobile else 622
    txt(p,24 if mobile else 54,top,w-(48 if mobile else 108),"Jelajahi layanan",26,INK,True)
    for i,(name,items) in enumerate(groups):
        x=24 if mobile else 54+i*394
        y=top+52+(i*80 if mobile else 0)
        panel=box(p,x,y,342 if mobile else 374,68 if mobile else 117,WHITE,12,name)
        txt(panel,14,12,315,name,17,INK,True)
        txt(panel,14,39,310," · ".join(labels[k] for k in items[:2]),12,MUTED)
        links.append((panel,"M-MENU" if mobile else "MENU"))
    button(p,24 if mobile else 54,top+(305 if mobile else 189),160,"Semua layanan","M-MENU" if mobile else "MENU")

landing(False)
landing(True)

for mobile in (False, True):
    w=390 if mobile else 1280
    p=board("M-MENU" if mobile else "MENU","Direktori E-Layanan · "+("Ponsel" if mobile else "Desktop"),w,1330 if mobile else 910)
    brand(p,w,mobile)
    x=22 if mobile else 54
    txt(p,x,121,w-2*x,"E-Layanan",29,INK,True)
    button(p,268 if mobile else 1090,122,100,"Tutup","M-HOME" if mobile else "HOME",MINT,FOREST)
    box(p,x,177,346 if mobile else 600,45,WHITE,8,"Kolom pencarian")
    txt(p,x+14,188,320,"Cari layanan atau nama dokumen",14,MUTED)
    offset=244
    for index,(name,items) in enumerate(groups):
        bw=346 if mobile else 374
        bx=x if mobile else x+index*394
        by=offset if mobile else 248
        box(p,bx,by,bw,44+len(items)*44,WHITE,13,name)
        txt(p,bx+14,by+11,bw-28,name,15,GREEN,True)
        for j,key in enumerate(items):
            mobile_key="M-"+key if mobile and key in {"PTSP","IZIN","TRACK","ADUAN","PPDB","PRESENSI","SATPAM","LEGAL"} else key
            button(p,bx+12,by+42+j*44,bw-24,labels[key],mobile_key,PAPER,INK)
        offset+=62+len(items)*44
    bottom=offset+5 if mobile else 680
    button(p,x,bottom,169,"Pusat unduh","UNDUH",MINT,FOREST)
    button(p,x+176,bottom,169,"Zona Integritas","IKM",MINT,FOREST)

def shell(key,title,role,back="MENU", h=880):
    p=board(key,title,1280,h)
    brand(p,1280)
    txt(p,54,124,870,"School Suite  /  "+role+"  /  "+title,13,MUTED)
    button(p,1093,113,132,"Kembali",back,MINT,FOREST)
    txt(p,54,161,1120,title,35,INK,True)
    return p

def status(p,x,y,text_value):
    button(p,x,y,181,text_value,None,MINT,FOREST)

def form(key,title,role,fields,action,back="MENU",dest=None,mobile=False):
    width=390 if mobile else 1280
    if mobile:
        p=board(key,title+" · Ponsel",width,1050)
        brand(p,width,True)
        txt(p,23,122,340,role,12,MUTED)
        txt(p,23,150,340,title,25,INK,True,2)
    else:
        p=shell(key,title,role,back)
    x,w,y=(22,346,225) if mobile else (54,784,234)
    box(p,x,y,w,105+len(fields)*69,WHITE,15,"Formulir")
    for i,label in enumerate(fields):
        top=y+19+i*69
        txt(p,x+19,top,w-38,label,13,INK,True)
        box(p,x+18,top+23,w-36,36,PAPER,7,label)
        value="Pilih atau unggah berkas" if any(k in label.lower() for k in ["berkas","pdf","lampiran","bukti"]) else ("Pilih opsi" if any(k in label.lower() for k in ["jenis","kategori","periode","status"]) else "Isi "+label.lower())
        txt(p,x+28,top+31,w-56,value,12,MUTED)
    button(p,x+18,y+38+len(fields)*69,200,action,dest)
    if not mobile:
        box(p,860,234,366,280,WHITE,14,"Panduan")
        txt(p,882,256,318,"Sebelum melanjutkan",18,INK,True)
        for i,message in enumerate(["Periksa identitas dan data yang diisi.","Siapkan dokumen sesuai jenis layanan.","Simpan nomor referensi setelah dikirim."]):
            txt(p,882,301+i*40,312,message,13,MUTED,False,2)
        button(p,882,448,180,"Lacak berkas","TRACK",MINT,FOREST)
    return p

def table(key,title,role,columns,rows,back="DASH",detail=None):
    p=shell(key,title,role,back)
    box(p,54,227,1172,498,WHITE,15,"Daftar pekerjaan")
    for i,(label,w) in enumerate([("Cari nomor atau nama",250),("Semua status",160),("Periode aktif",145)]):
        button(p,[74,342,520][i],249,w,label,None,PAPER,MUTED)
    box(p,74,314,1132,42,MINT,6,"Kepala tabel")
    xs=[90,312,580,810,1070]
    for i,label in enumerate(columns):
        txt(p,xs[i],325,195,label,12,GREEN,True)
    for index,row in enumerate(rows):
        y=369+index*67
        box(p,74,y+53,1132,1,LINE)
        for i,value in enumerate(row):
            txt(p,xs[i],y+11,194,value,13,INK,i==0)
        if detail: button(p,1070,y,115,"Buka detail",detail,MINT,FOREST)
    txt(p,90,663,370,"Menampilkan "+str(len(rows))+" entri contoh",12,MUTED)
    return p

for key,title,role,fields,action,queue in SERVICES:
    destination="HASIL" if key=="TRACK" else ("RESI-"+key if key in {"PTSP","SURAT","IZIN","MUTASI","LEGAL","PPDB","ADUAN"} else None)
    form(key,title,role,fields.split("|"),action,dest=destination)
    if destination and destination!="HASIL":
        p=shell(destination,"Pengajuan diterima",role,key,680)
        box(p,54,236,780,300,WHITE,14)
        status(p,76,258,"Diterima")
        txt(p,76,319,730,title,23,INK,True)
        txt(p,76,365,730,"Nomor referensi  "+key+"-2026-0048",17,GREEN,True)
        txt(p,76,408,730,"Simpan nomor ini untuk melihat status pengajuan.",14,MUTED)
        button(p,76,457,190,"Lacak status","HASIL")
    if key not in {"TRACK","IKM"}:
        table("Q-"+key,queue,"Ruang kerja",["Referensi","Layanan","Tanggal","Status","Aksi"],
            [[key+"-0048",title,"19 Nov 2026","Perlu ditinjau"],
             [key+"-0047",title,"18 Nov 2026","Dalam proses"],
             [key+"-0046",title,"17 Nov 2026","Selesai"]],detail="REVIEW" if key=="PTSP" else key)

form("M-PTSP","PTSP Online","Pemohon",SERVICES[0][3].split("|"),"Kirim permohonan",dest="RESI-PTSP",mobile=True)
p=shell("HASIL","Status permohonan","Pemohon","TRACK")
box(p,54,235,1050,460,WHITE,16)
status(p,79,257,"Dalam verifikasi")
txt(p,79,317,920,"PTSP-2026-0048  ·  Surat keterangan",24,INK,True)
for i,(title,desc) in enumerate([("Pengajuan diterima","Nomor referensi diterbitkan."),("Verifikasi berkas","Petugas memeriksa dokumen yang Anda unggah."),("Hasil layanan","Dokumen tersedia setelah diterbitkan.")]):
    yy=368+i*70
    box(p,82,yy+4,12,12,GREEN if i<2 else LINE,6)
    txt(p,109,yy,920,title,16,INK,True)
    txt(p,109,yy+25,920,desc,13,MUTED)
button(p,79,616,189,"Kembali ke pelacakan","TRACK",MINT,FOREST)

p=shell("REVIEW","Tinjau permohonan PTSP","Petugas TU","Q-PTSP")
box(p,54,235,775,454,WHITE,14)
status(p,76,256,"Perlu ditinjau")
txt(p,76,314,720,"PTSP-2026-0048  ·  Surat keterangan",23,INK,True)
for i,(caption,value) in enumerate([("Pemohon","Nadia Putri · akun terverifikasi"),("Persyaratan","Identitas.pdf · Permohonan.pdf"),("Riwayat","19 Nov 2026 · Permohonan diterima")]):
    txt(p,76,369+i*56,300,caption,13,MUTED)
    txt(p,280,369+i*56,520,value,14,INK,True)
button(p,76,580,177,"Minta perbaikan","KOREKSI",MINT,FOREST)
button(p,270,580,166,"Terbitkan hasil","TERBIT")
form("KOREKSI","Minta perbaikan berkas","Petugas TU",["Dokumen yang perlu diperbaiki","Catatan untuk pemohon"],"Kirim catatan","REVIEW","Q-PTSP")
form("TERBIT","Terbitkan hasil PTSP","Petugas TU",["Nomor dokumen","Tanggal terbit","Unggah dokumen atau template","Pengambilan digital atau fisik"],"Terbitkan","REVIEW","HASIL")

p=shell("DASH","Ruang kerja sekolah","Petugas","HOME")
for i,(k,title,role,fields,action,queue) in enumerate([s for s in SERVICES if s[0] not in {"TRACK","IKM"}]):
    x=54+i%4*294; y=230+i//4*83
    button(p,x,y,274,queue,"Q-"+k,MINT,FOREST)
for key,title,entries in [
    ("INFO","Informasi sekolah",[]),
    ("UNDUH","Pusat unduh dokumen",["Formulir PTSP","Kalender pendidikan","Tata tertib","Brosur PPDB","Maklumat pelayanan","SOP layanan"]),
    ("INTEGRASI","Pengaturan integrasi",["EMIS · Impor dan ekspor","RDM · Akses rapor","SLiMS · Katalog dan sirkulasi","Email dan WhatsApp","Pembaca RFID dan QR","Log sinkronisasi"])
]:
    p=shell(key,title,"Pengelola" if key=="INTEGRASI" else "Portal publik")
    for i,entry in enumerate(entries):
        x=54+i%3*393; y=235+i//3*96
        card=box(p,x,y,374,74,WHITE,12,entry)
        txt(card,17,20,330,entry,16,INK,True)
    if key=="INFO": button(p,54,650,170,"Zona Integritas","IKM")
form("LOGIN","Masuk ke School Suite","Akun sekolah",["Email atau ID pengguna","Kata sandi"],"Masuk","HOME","DASH")

p=shell("STATE","Komponen dan keadaan layanan","Sistem desain","DASH")
for i,(label,description,fill) in enumerate([
    ("Belum ada permohonan","Permohonan baru akan muncul di sini.",MINT),
    ("Perlu perbaikan","Unggah ulang dokumen yang diminta petugas.","#fcf0d8"),
    ("Layanan sedang ditutup","Lihat jadwal dan persyaratan sebelum periode berikutnya.","#edf0ee"),
    ("Gagal memuat hasil","Periksa koneksi, lalu coba lagi.","#f9e6e3"),
    ("Izin kedaluwarsa","Hubungi piket untuk meninjau izin yang sudah habis.","#f9e6e3"),
    ("Berkas siap diambil","Tunjukkan nomor referensi kepada petugas loket.",MINT),
]):
    x=54+i%3*394
    y=234+i//3*161
    card=box(p,x,y,374,138,fill,12,label)
    txt(card,17,16,337,label,17,INK,True)
    txt(card,17,49,337,description,13,MUTED,False,2)
    button(card,17,87,135,"Kembali","DASH",WHITE,FOREST)

button(boards["DASH"],54,692,187,"Pengaturan integrasi","INTEGRASI")
button(boards["DASH"],255,692,187,"Komponen dan status","STATE",MINT,FOREST)

DETAILS = {
    "SURAT": ("Disposisi surat masuk", "Instansi pengirim|Nomor dan perihal surat|PDF surat utama|Unit tujuan|Instruksi dan tenggat|Riwayat disposisi", "Kirim disposisi"),
    "TAMU": ("Detail kunjungan", "Pengunjung|Pegawai tujuan|Keperluan|Waktu datang|Catat waktu pulang", "Catat kepulangan"),
    "IZIN": ("Keputusan izin siswa", "Identitas siswa|Periode izin|Hasil cek tanggal bentrok|Lampiran dan tanda tangan|Pengaruh pada presensi|Alasan keputusan", "Setujui izin"),
    "MUTASI": ("Verifikasi mutasi siswa", "Arah mutasi|Sekolah asal dan tujuan|Bukti pendukung|Dampak enrollment|Surat rekomendasi atau penerimaan", "Terbitkan surat"),
    "LEGAL": ("Verifikasi dan pengambilan legalisir", "Ijazah asli dan salinan|Nomor register|Pengesahan|Pengambilan langsung atau kuasa|Penerima dan waktu pengambilan", "Catat pengambilan"),
    "KUASA": ("Pratinjau surat kuasa dan SPTJM", "Jenis dan versi template|Pemberi kuasa|Penerima kuasa|Tanda tangan kedua pihak|Pratinjau dokumen", "Unduh PDF"),
    "SKL": ("Jadwalkan publikasi SKL", "Periode kelulusan|Daftar keputusan|Pengesahan pejabat|Tanggal dan jam rilis|Versi dokumen", "Jadwalkan rilis"),
    "VERIF": ("Hasil verifikasi dokumen", "Jenis bukti|Status sah atau dicabut|Sekolah penerbit|Masa berlaku|Data yang diizinkan tampil", "Periksa kode lain"),
    "ALUMNI": ("Moderasi profil alumni", "Identitas dan bukti kelulusan|Status studi atau kerja|Preferensi publikasi|Atribut publik|Keputusan moderator", "Simpan verifikasi"),
    "PPDB": ("Verifikasi dan keputusan calon siswa", "Jalur dan kuota|Berkas calon siswa|Skor dan peringkat|Keputusan dan jadwal publikasi|Status daftar ulang", "Simpan keputusan"),
    "ADUAN": ("Telaah laporan satgas", "Kategori dan akses identitas|Kronologi dan bukti terbatas|Petugas penanggung jawab|Tindakan dan catatan|Ringkasan publik yang dimoderasi", "Simpan tindak lanjut"),
    "PUSTAKA": ("Peninjauan usulan buku", "Judul dan pengusul|Alasan pengadaan|Hasil pemeriksaan katalog|Keputusan pustakawan", "Simpan keputusan"),
    "PRESENSI": ("Hasil scan dan koreksi presensi", "Kartu dan status perangkat|Waktu pemindaian|Hasil pencatatan|Deteksi scan ulang|Alasan koreksi", "Simpan koreksi"),
    "PIKET": ("Detail izin keluar", "Siswa dan alasan|Pemberi izin|Masa berlaku|QR izin|Riwayat keluar dan kembali", "Batalkan izin"),
    "SATPAM": ("Hasil validasi pos", "Status izin dan masa berlaku|Pencocokan siswa|Pos dan petugas|Waktu keluar atau kembali|Catatan pengecualian", "Catat perlintasan"),
    "BK": ("Detail kasus terbatas", "Jenis dan waktu kejadian|Kronologi|Lampiran privat|Petugas yang ditugaskan|Tindak lanjut dan histori", "Simpan tindak lanjut"),
    "PROPOSAL": ("Review proposal kegiatan", "Tujuan dan jadwal|RAB dan lampiran|Catatan reviewer|Keputusan dan alasan", "Simpan keputusan"),
    "LPJ": ("Verifikasi realisasi dan LPJ", "Proposal dan versi RAB|Realisasi anggaran|Bukti pengeluaran|Status dana|Keputusan dan arsip", "Selesaikan review"),
}
for key,(title,fields,action) in DETAILS.items():
    form("D-"+key,title,"Petugas layanan",fields.split("|"),action,"Q-"+key,"Q-"+key)
    queue = boards["Q-"+key]
    for number,target in list(links):
        if nodes[str(number)]["parent"]==queue and target==key:
            links.remove((number,target))
            links.append((number,"D-"+key))

EXTRAS = [
    ("PROFIL","Profil dan sambutan","Identitas sekolah|Sambutan kepala|Visi dan misi|Program unggulan"),
    ("GTK","Direktori guru dan tenaga kependidikan","Cari nama|Filter unit|Nama dan jabatan publik|Bidang tugas"),
    ("PROGRAM","Program, peminatan dan fasilitas","Program akademik|Peminatan|Laboratorium dan fasilitas|Kesiswaan"),
    ("BERITA","Berita dan agenda","Kategori|Judul, tanggal dan penulis|Isi berita|Agenda terkait"),
    ("GALERI","Galeri dan video","Kategori kegiatan|Foto dan keterangan|Video kegiatan|Tautan sumber"),
    ("PRESTASI","Prestasi siswa","Bidang prestasi|Tingkat dan tanggal|Nama kegiatan|Dokumentasi"),
    ("KONTAK","Kontak dan lokasi sekolah","Alamat|Jam layanan|Telepon dan email|Peta lokasi"),
    ("SALAT","Jadwal salat","Lokasi dan zona waktu|Jadwal hari ini|Waktu berikutnya|Status sumber jadwal"),
    ("PUBLIKASI","Kelola konten publik","Jenis konten|Judul dan isi|Kategori dan media|Pratinjau|Status draft atau terbit"),
    ("ZI","Maklumat dan Zona Integritas","Maklumat layanan|Enam area perubahan|Dokumentasi|Indikator agregat|Tautan aduan dan IKM"),
    ("TRAFIK","Trafik dan kanal bantuan","Pengunjung daring|Kunjungan hari ini|Bulanan dan total|Jam kanal bantuan|Fallback kanal"),
    ("AKUN","Registrasi pemohon","Identitas pemohon|Nama lengkap|WhatsApp|Email|Kata sandi|Verifikasi kontak"),
    ("PULIH","Pulihkan akses akun","Email atau ID pengguna|Kode verifikasi|Kata sandi baru"),
    ("PERBAIKI","Perbaiki berkas pengajuan","Nomor resi|Catatan petugas|Berkas pengganti|Konfirmasi pengajuan ulang"),
    ("HASIL-SIAP","Hasil layanan tersedia","Nomor dokumen|Metode unduh atau ambil|Jadwal loket|Pratinjau hasil"),
    ("TANDA-TERIMA","Tanda terima surat dinas","Nomor agenda|Instansi pengirim|Nomor dan perihal|Tanggal diterima|Pratinjau tanda terima"),
    ("TOKEN","Token dispensasi wali kelas","Identitas siswa|Tanggal dan tujuan izin|Token sekali pakai|Masa berlaku"),
    ("SCAN","Keadaan pemindaian RFID dan QR","Kamera belum aktif|Kartu berhasil dibaca|Kartu tidak dikenal|Scan ganda|Jaringan terputus"),
    ("RILIS-SKL","Hasil kelulusan siswa","Periode|Keputusan kelulusan|Dokumen SKL|Petunjuk pengambilan"),
    ("HASIL-PPDB","Hasil seleksi dan daftar ulang","Nomor pendaftaran|Keputusan|Jadwal daftar ulang|Berkas tambahan|Bukti daftar ulang"),
    ("IKA","Direktori alumni dan E-KTA","Filter angkatan dan jurusan|Status aktivitas|Karier dan beasiswa|Pratinjau E-KTA"),
    ("LAPOR-PUB","Transparansi laporan","Filter kategori dan status|Statistik agregat|Ringkasan yang dimoderasi|Lacak tiket"),
    ("KOLEKSI","Katalog dan detail koleksi","Pencarian dan kategori|Judul dan pengarang|Ketersediaan buku|Akses e-book"),
    ("BEBAS","Pemeriksaan bebas pustaka","Verifikasi anggota|Pinjaman aktif|Status bebas pustaka|Instruksi penyelesaian"),
    ("KUNJUNG","Presensi perpustakaan","Pindai QR|Masukkan kode anggota|Hasil pencatatan"),
    ("USUL","Usul pengadaan koleksi","Judul dan penulis|Jenis koleksi|Alasan pengadaan|Kontak pemohon"),
    ("INTEGRASI-LOG","Rekonsiliasi integrasi","Sistem dan arah pertukaran|Periode dan pemetaan|Rekap berhasil atau gagal|Rincian baris gagal|Coba ulang"),
    ("AUDIT","Histori dan audit aktivitas","Peran pelaku|Waktu dan target|Aksi dan perubahan status|Filter sekolah dan periode|Ekspor terbatas"),
    ("AKSES","Matriks akses sekolah","Sekolah dan periode|Peran|Cakupan kelas atau unit|Izin aksi dan dokumen"),
    ("GAGAL","Validasi dan pemulihan formulir","Kolom wajib belum diisi|Format lampiran ditolak|Unggah gagal dan coba lagi|Pengajuan tersimpan sebagai draf"),
    ("FILTER-TRACK","Filter pelacakan terpadu","Semua layanan|Surat izin|Legalisir ijazah|Mutasi siswa|PTSP"),
    ("KONTROL-KIOS","Kontrol kios presensi","QR kamera|Gate|Layar penuh|Tempel kartu pelajar"),
    ("AKSES-POS","Akses pos jaga","Papan angka|Masukkan kode akses|Validasi akses petugas"),
    ("EDITOR-RAB","Proposal mandiri dan editor RAB","Identitas pengusul dan organisasi|Muat contoh webinar|Susunan panitia|Item kebutuhan dan kuantitas|Harga dan total|Tambah atau hapus item"),
    ("STATUS-PROPOSAL","Lacak proposal dan realisasi","Nomor referensi proposal|Status pengajuan|Realisasi dana"),
]
for key,title,fields in EXTRAS:
    form(key,title,"School Suite",fields.split("|"),"Kembali ke layanan","MENU","MENU")

for index,key in enumerate(("PROFIL","GTK","PROGRAM","BERITA","PRESTASI","GALERI","KONTAK","SALAT","TRAFIK","ZI","PUBLIKASI")):
    button(boards["INFO"],54+index%4*294,235+index//4*61,274,nodes[str(boards[key])]["name"],key,MINT,FOREST)
for index,(source,targets) in enumerate({
    "LOGIN":[("Buat akun","AKUN"),("Pulihkan akun","PULIH")],
    "HASIL":[("Perbaiki berkas","PERBAIKI"),("Hasil siap","HASIL-SIAP")],
    "IZIN":[("Token dispensasi","TOKEN"),("Validasi formulir","GAGAL")],
    "RESI-SURAT":[("Tanda terima","TANDA-TERIMA")],
    "SKL":[("Hasil kelulusan","RILIS-SKL")],
    "PPDB":[("Hasil seleksi","HASIL-PPDB")],
    "ALUMNI":[("Direktori dan E-KTA","IKA")],
    "ADUAN":[("Transparansi laporan","LAPOR-PUB")],
    "PUSTAKA":[("Katalog","KOLEKSI"),("Bebas pustaka","BEBAS"),("Presensi","KUNJUNG"),("Usul buku","USUL")],
    "INTEGRASI":[("Rekonsiliasi","INTEGRASI-LOG")],
    "DASH":[("Histori audit","AUDIT"),("Matriks akses","AKSES")],
    "TRACK":[("Filter layanan","FILTER-TRACK")],
    "PRESENSI":[("Hasil scan","SCAN"),("Kontrol kios","KONTROL-KIOS")],
    "SATPAM":[("Akses pos","AKSES-POS")],
    "LPJ":[("Editor RAB","EDITOR-RAB"),("Lacak proposal","STATUS-PROPOSAL")],
}.items()):
    height=nodes[str(boards[source])]["height"]
    for j,(label,target) in enumerate(targets):
        button(boards[source],54+j*182,height-54,170,label,target,MINT,FOREST)

for key in ("IZIN","TRACK","ADUAN","PPDB","PRESENSI","SATPAM","LEGAL"):
    row=next(item for item in SERVICES if item[0]==key)
    target={"TRACK":"HASIL","PRESENSI":"SCAN","SATPAM":"D-SATPAM"}.get(key,"RESI-"+key)
    form("M-"+key,row[1],row[2],row[3].split("|"),row[4],dest=target,mobile=True)

for number,key in links:
    if key not in boards:
        raise ValueError(key)
    nodes[str(number)]["interactions"]=[{"trigger":{"type":"onClick"},"actions":[{"type":"navigate","dest":boards[key]}],
                                         "transition":{"type":"instant"},"duration_ms":300,"easing":"easeOut"}]
for key in ("HOME","M-HOME","M-PTSP","PTSP","DASH","REVIEW","TRACK","INTEGRASI"):
    page["flows"].append({"name":nodes[str(boards[key])]["name"],"start":boards[key],"description":""})
document=dict(old,name="School Suite · Paritas layanan MAN 4 Banjar",nodes=nodes,next_id=len(nodes)+1,imageIndex=art_index)
with ZipFile(ROOT/"school-suite-paritas.figcraft","w",ZIP_DEFLATED) as output:
    output.writestr("document.json",json.dumps(document,ensure_ascii=False))
    output.writestr("images/"+hero["image"],art)

def color(fill):
    c=fill["color"]
    return "#"+"".join(f"{round(c[k]*255):02x}" for k in "rgb")

targets={number:key for key,number in boards.items()}
image="data:image/png;base64,"+base64.b64encode(art).decode()
def layer(number):
    n=nodes[str(number)]
    x,y=n["transform"][4:]
    style=f"left:{x}px;top:{y}px;width:{n['width']}px;height:{n['height']}px;"
    if n["type"]=="text":
        s=n["style"]
        style+=f"color:{color(n['fills'][0])};font-size:{s['font_size']}px;font-weight:{600 if s['font_style']=='Semi Bold' else 400};"
        return f'<div class="layer text" style="{style}">{html.escape(n["characters"])}</div>'
    style+=f"background:{color(n['fills'][0])};border-radius:{n['corners']['radii'][0]}px;"
    if len(n["fills"])>1:
        style+="background-image:var(--campus);background-size:cover;background-position:center;"
    children="".join(map(layer,n.get("children",[])))
    if n.get("interactions"):
        dest=targets[n["interactions"][0]["actions"][0]["dest"]]
        return f'<a class="layer" style="{style}" href="#{dest}" aria-label="{html.escape(n["name"],quote=True)}">{children}</a>'
    return f'<div class="layer" style="{style}">{children}</div>'

artboards=[]
for key,number in boards.items():
    n=nodes[str(number)]
    artboards.append(f'<template id="{key}"><article style="width:{n["width"]}px;height:{n["height"]}px">'+"".join(map(layer,n["children"]))+"</article></template>")
options="".join(f'<option value="{key}">{html.escape(nodes[str(number)]["name"])}</option>' for key,number in boards.items())
preview="""<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>School Suite · Pratinjau FigCraft</title>
<style>*{box-sizing:border-box}body{margin:0;background:#dce8df;color:#172d28;font-family:Inter,system-ui,sans-serif}header{position:sticky;top:0;z-index:1;background:#10392f;color:white;padding:12px 20px;display:flex;gap:16px;align-items:center;flex-wrap:wrap}select{padding:8px;border-radius:7px;max-width:100%}main{padding:20px;overflow:auto}article{position:relative;background:#f3f6f2;overflow:hidden;margin:auto;box-shadow:0 12px 40px #10392f33}.layer{position:absolute;display:block;overflow:hidden}.text{line-height:1.35;white-space:pre-wrap}a:focus-visible{outline:3px solid #c88515;outline-offset:-3px}a:hover{filter:brightness(.96)}</style>
<header><strong>School Suite · Pratinjau mockup</strong><label>Artboard <select id="screen">"""+options+"""</select></label></header><main id="canvas"></main>"""+''.join(artboards)+"""
<script>const picker=document.querySelector('#screen'), canvas=document.querySelector('#canvas');function show(){const key=location.hash.slice(1);const t=document.getElementById(key)||document.getElementById('HOME');picker.value=t.id;canvas.replaceChildren(t.content.cloneNode(true));window.scrollTo(0,0)}picker.addEventListener('change',()=>location.hash=picker.value);window.addEventListener('hashchange',show);show()</script></html>"""
(ROOT/"index.html").write_text(preview.replace("<style>",f"<style>:root{{--campus:url({image})}}"),encoding="utf-8")
print(f"{len(boards)} artboards | {len(nodes)} layers | {len(links)} links")
