from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.text import WD_BREAK
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_CELL_VERTICAL_ALIGNMENT
from docx.enum.section import WD_SECTION, WD_ORIENT
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.enum.style import WD_STYLE_TYPE
import random
from pathlib import Path


OUT = Path('/home/ilhamzp/Projects/school_suite')


def set_cell_shading(cell, fill):
    tc_pr = cell._tc.get_or_add_tcPr()
    shd = tc_pr.find(qn('w:shd'))
    if shd is None:
        shd = OxmlElement('w:shd')
        tc_pr.append(shd)
    shd.set(qn('w:fill'), fill)


def set_cell_margins(cell, top=50, start=70, bottom=50, end=70):
    tc = cell._tc
    tc_pr = tc.get_or_add_tcPr()
    tc_mar = tc_pr.first_child_found_in('w:tcMar')
    if tc_mar is None:
        tc_mar = OxmlElement('w:tcMar')
        tc_pr.append(tc_mar)
    for m, v in [('top', top), ('start', start), ('bottom', bottom), ('end', end)]:
        node = tc_mar.find(qn(f'w:{m}'))
        if node is None:
            node = OxmlElement(f'w:{m}')
            tc_mar.append(node)
        node.set(qn('w:w'), str(v))
        node.set(qn('w:type'), 'dxa')


def set_column_count(section, count=2, space=180):
    sect_pr = section._sectPr
    cols = sect_pr.find(qn('w:cols'))
    if cols is None:
        cols = OxmlElement('w:cols')
        sect_pr.append(cols)
    cols.set(qn('w:num'), str(count))
    cols.set(qn('w:space'), str(space))


def set_keep(paragraph, together=True, with_next=False):
    p_pr = paragraph._p.get_or_add_pPr()
    if together:
        k = OxmlElement('w:keepLines')
        p_pr.append(k)
    if with_next:
        k = OxmlElement('w:keepNext')
        p_pr.append(k)


def target_letters(seed):
    rng = random.Random(seed)
    remaining = {letter: 10 for letter in 'ABCD'}
    result = []
    previous = None
    for _ in range(40):
        candidates = [letter for letter, count in remaining.items() if count and letter != previous]
        rng.shuffle(candidates)
        candidates.sort(key=lambda letter: remaining[letter], reverse=True)
        chosen = candidates[0]
        result.append(chosen)
        remaining[chosen] -= 1
        previous = chosen
    if any(remaining.values()) or any(result[i] == result[i-1] for i in range(1, len(result))):
        raise RuntimeError('Could not create non-consecutive answer sequence')
    return result


def make_options(correct, distractors, target):
    choices = [None] * 4
    idx = 'ABCD'.index(target)
    choices[idx] = correct
    di = 0
    for i in range(4):
        if choices[i] is None:
            choices[i] = distractors[di]
            di += 1
    return choices


def q(level, material, stem, correct, *distractors):
    return {'level': level, 'material': material, 'stem': stem, 'correct': correct, 'distractors': list(distractors)}


X = [
    q('L1','Voli','Saat melakukan passing bawah, bidang pantul bola yang paling tepat dibentuk oleh ...','kedua lengan bawah yang dirapatkan','telapak tangan yang terbuka lebar','ujung jari tangan yang ditekuk','satu bahu yang diarahkan ke bola'),
    q('L1','Voli','Operan menggunakan ujung jari di atas dahi dalam permainan voli disebut ...','passing atas','passing bawah','servis bawah','smash'),
    q('L1','Voli','Tujuan utama servis dalam permainan bola voli adalah ...','memulai rally dengan mengirim bola ke area lawan','menghentikan rally sebelum bola melewati net','menggantikan tugas libero di area belakang','memberi umpan langsung kepada spiker sendiri'),
    q('L1','Voli','Lutut ditekuk saat passing bawah terutama untuk ...','menjaga keseimbangan dan membantu mengontrol arah bola','membuat bola selalu melambung sangat tinggi','mengurangi jangkauan gerak lengan','menghindari bola menyentuh kedua lengan'),
    q('L1','Voli','Jumlah pemain setiap regu yang berada di lapangan saat pertandingan voli adalah ...','enam orang','empat orang','lima orang','tujuh orang'),
    q('L1','Basket','Operan yang dilakukan dari depan dada dengan dorongan kedua tangan disebut ...','chest pass','bounce pass','overhead pass','baseball pass'),
    q('L1','Basket','Dribbling yang benar dilakukan dengan cara ...','memantulkan bola berulang-ulang menggunakan satu tangan','menendang bola ke depan lalu mengejarnya','membawa bola dengan kedua tangan sambil berlari','melempar bola ke lantai hanya satu kali'),
    q('L1','Basket','Dalam gerakan pivot, pemain ...','mempertahankan satu kaki sebagai poros sementara kaki lain bergerak','menggiring bola dengan dua tangan secara bersamaan','melompat lalu mendarat dengan kedua kaki bergantian','berputar tanpa menyentuh lantai dengan kaki'),
    q('L1','Basket','Ciri utama lay-up shoot adalah ...','diawali langkah mendekati ring lalu menembak dari jarak dekat','dilakukan dari garis tengah dengan dua tangan','selalu dilakukan sambil membelakangi ring','bola harus dipantulkan ke papan sebelum melangkah'),
    q('L1','Basket','Jumlah pemain satu tim bola basket yang bermain di lapangan adalah ...','lima orang','empat orang','enam orang','tujuh orang'),
    q('L1','Futsal','Jumlah pemain setiap tim futsal yang berada di lapangan, termasuk penjaga gawang, adalah ...','lima orang','empat orang','enam orang','tujuh orang'),
    q('L1','Futsal','Jika bola keluar melalui garis samping dalam futsal, permainan dilanjutkan dengan ...','tendangan ke dalam menggunakan kaki','lemparan ke dalam menggunakan dua tangan','bola lambung dari titik tengah lapangan','tendangan penalti dari garis enam meter'),
    q('L1','Futsal','Bagian kaki yang sering digunakan untuk menghentikan bola bergulir dalam futsal adalah ...','telapak kaki','tumit kaki','ujung sepatu bagian luar','lutut'),
    q('L1','Futsal','Salah satu tujuan melakukan passing dalam futsal adalah ...','memindahkan bola kepada teman sambil mempertahankan penguasaan','menghentikan semua pergerakan teman satu tim','membuat bola keluar lapangan agar waktu berjalan','menghindari komunikasi antarpemain'),
    q('L1','Bulu Tangkis','Dalam sistem rally point, pemain memperoleh angka ...','setiap kali memenangkan rally','hanya ketika melakukan servis','hanya ketika melakukan smash','setelah memenangkan dua servis berturut-turut'),
    q('L1','Bulu Tangkis','Satu gim bulu tangkis umumnya dimenangkan oleh pemain yang lebih dahulu mencapai ...','21 poin dengan keunggulan sesuai aturan','11 poin tanpa memperhatikan selisih','15 poin tepat tanpa deuce','25 poin dalam semua keadaan'),
    q('L1','Bulu Tangkis','Pukulan forehand dilakukan pada sisi tubuh yang ...','searah dengan tangan yang memegang raket','berlawanan dengan tangan yang memegang raket','selalu berada di belakang tubuh','berada tepat di atas kepala saja'),
    q('L1','Bulu Tangkis','Pukulan yang mengarahkan shuttlecock tinggi dan jauh ke belakang lapangan disebut ...','clear','dropshot','netting','drive pendek'),
    q('L1','Bulu Tangkis','Sikap siap yang baik sebelum menerima shuttlecock adalah ...','lutut sedikit ditekuk dan raket berada di depan tubuh','kedua lutut dikunci dan raket diletakkan di belakang','berdiri tegak tanpa memandang lawan','duduk rendah dengan raket menyentuh lantai'),
    q('L1','Bulu Tangkis','Tujuan utama footwork dalam bulu tangkis adalah ...','mencapai posisi pukul dengan cepat dan tetap seimbang','membuat lawan tidak boleh bergerak','mengurangi kebutuhan membaca arah shuttlecock','memukul shuttlecock tanpa berpindah tempat'),
    q('L2','Voli','Saat menerima servis pendek yang jatuh di depan, tindakan paling efektif adalah ...','bergerak cepat ke depan lalu mengarahkan passing kepada pengumpan','menunggu bola memantul agar lebih mudah dipukul','mundur ke garis belakang supaya ruang depan kosong','langsung melakukan smash dari posisi menerima servis'),
    q('L2','Voli','Passing pertama diarahkan tinggi dan dekat net agar ...','pengumpan memiliki beberapa pilihan serangan','lawan dapat melakukan serangan tanpa hambatan','bola segera keluar lapangan','pemain belakang tidak perlu berkomunikasi'),
    q('L2','Basket','Ketika penjaga lawan menutup ruang gerak pembawa bola, pilihan taktis yang paling tepat adalah ...','mengoper kepada teman yang lebih bebas lalu melakukan cut','memaksa menggiring bola menembus dua penjaga','berhenti total sambil memegang bola selama mungkin','menembak dari belakang garis tengah tanpa melihat ring'),
    q('L2','Basket','Fast break paling tepat dilakukan ketika ...','tim memperoleh bola dan pertahanan lawan belum tertata','semua pemain lawan sudah berada di bawah ring','tim sedang tidak menguasai bola','pemain pembawa bola berada di luar gedung'),
    q('L2','Futsal','Pemain futsal yang menerima bola di sudut lapangan dan ditekan lawan sebaiknya ...','menggunakan tubuh untuk melindungi bola lalu mencari teman pendukung','menggiring bola lurus ke arah garis tanpa melihat situasi','menendang bola keras ke tengah tanpa sasaran','membelakangi teman dan menunggu lawan merebut bola'),
    q('L2','Futsal','Pertahanan lawan sangat rapat di tengah. Cara menyerang yang tepat adalah ...','memperlebar posisi dan mempercepat perpindahan bola','menumpuk semua pemain di tengah lapangan','mengurangi pergerakan tanpa bola','selalu mengirim bola tinggi ke luar lapangan'),
    q('L2','Bulu Tangkis','Dalam tunggal, lawan terus berdiri di belakang lapangan. Pukulan yang dapat memaksanya bergerak ke depan adalah ...','dropshot','clear tinggi ke belakang','smash ke arah belakang yang sama','servis panjang berulang-ulang'),
    q('L2','Bulu Tangkis','Dalam ganda, kedua lawan berdiri terlalu jauh di belakang. Pilihan yang dapat membuka ruang depan adalah ...','pukulan pendek dekat net','smash keras ke arah badan sendiri','clear tinggi ke sudut belakang lawan','drive mendatar ke luar lapangan'),
    q('L3','Voli','Tim sering gagal menerima servis float karena dua pemain menunggu bola yang sama. Perbaikan paling tepat adalah ...','menetapkan area tanggung jawab, memanggil bola, dan menyesuaikan posisi penerima','menambah tenaga pukulan tanpa mengubah formasi','membiarkan bola jatuh agar tidak terjadi tabrakan','memindahkan semua pemain ke dekat net'),
    q('L3','Voli','Lawan sering menyerang melalui arah diagonal tajam dari posisi 4. Strategi blok yang paling logis adalah ...','menutup sudut diagonal dengan koordinasi blok dan pertahanan belakang','membuka tangan selebar mungkin tanpa membaca arah serangan','meminta semua pemain berdiri di garis belakang','mengabaikan spiker dan hanya menjaga setter'),
    q('L3','Basket','Tim tertinggal dua angka dan memiliki waktu serangan yang cukup. Lawan menggunakan man-to-man ketat. Keputusan terbaik adalah ...','menggunakan screen untuk menciptakan keunggulan lalu memilih tembakan berpeluang tinggi','segera melakukan tembakan jauh meskipun penjaga rapat','menggiring bola di sudut sampai waktu habis','mengoper bola ke lawan agar dapat mengatur pertahanan'),
    q('L3','Basket','Pemain lawan sangat kuat melakukan drive ke kanan. Rencana bertahan yang paling tepat adalah ...','mengarahkan drive ke sisi lemahnya sambil menyiapkan bantuan dari pemain terdekat','membuka jalur kanan agar ia cepat mencetak angka','meninggalkan semua pemain lain untuk menjaga satu orang','menutup mata terhadap gerak bola dan hanya mengikuti lawan'),
    q('L3','Basket','Tim lawan berkali-kali mendapat offensive rebound. Kesalahan yang paling mungkin terjadi dan solusinya adalah ...','pemain tidak melakukan box out; setiap pemain harus mengunci lawan sebelum mengejar bola','pemain terlalu sering melakukan passing; semua passing harus dihentikan','pemain terlalu dekat dengan ring; semua pemain harus menjauh','pemain melakukan dribbling terlalu rendah; tinggi dribbling harus dinaikkan'),
    q('L3','Futsal','Saat menghadapi high press, penjaga gawang menerima bola dan semua jalur umpan pendek tertutup. Solusi tim yang paling rasional adalah ...','membentuk dukungan segitiga, memindahkan bola cepat, dan mengganti sisi serangan','menendang bola keras ke depan tanpa melihat posisi teman','menumpuk seluruh pemain di sekitar gawang sendiri','menunggu lawan mendekat agar bola semakin sulit dimainkan'),
    q('L3','Futsal','Tim unggul satu gol dengan sisa waktu dua menit. Strategi yang paling aman adalah ...','mempertahankan penguasaan melalui dukungan dekat dan menghindari dribbling berisiko','melakukan tembakan dari jarak jauh setiap kali menerima bola','mengirim semua pemain ke depan tanpa pengamanan','sengaja memberikan bola kepada lawan di area berbahaya'),
    q('L3','Bulu Tangkis','Lawan tunggal memiliki backhand lemah tetapi smash forehand kuat. Rencana permainan yang paling tepat adalah ...','menekan sudut backhand dengan variasi arah dan tempo agar lawan sulit memakai forehand','selalu mengangkat bola ke forehand agar lawan bebas melakukan smash','menghindari semua pukulan ke belakang lapangan','memukul shuttlecock ke tengah tanpa memperhatikan posisi lawan'),
    q('L3','Bulu Tangkis','Dalam ganda, pasangan lawan berdiri di depan setelah melakukan netting. Pukulan yang paling efektif untuk mengubah situasi adalah ...','angkat tinggi dan dalam ke belakang untuk memaksa lawan mundur','netting pendek lagi ke pemain yang sama tanpa perubahan','servis pendek dari tengah lapangan','pukulan mendatar ke arah luar lapangan sendiri'),
    q('L3','Bulu Tangkis','Skor tunggal 19–19. Pemain ingin tetap menyerang tetapi sering gagal karena terburu-buru. Keputusan terbaik adalah ...','memilih serangan terarah dengan risiko terkendali dan tetap menjaga keseimbangan posisi','melakukan smash dari setiap bola tanpa melihat kualitas pengembalian','mengubah semua pukulan menjadi clear tinggi meskipun lawan siap','berdiri di sudut lapangan agar lawan sulit menemukan sasaran'),
    q('L3','Bulu Tangkis','Seorang pemain selalu kembali ke tengah setelah memukul, tetapi sering terlambat menghadapi shuttlecock ke sudut forehand. Evaluasi yang tepat adalah ...','posisi dasar perlu disesuaikan dengan arah pukulan dan pemulihan harus menuju titik antisipasi','pemain harus berhenti kembali ke tengah setelah memukul','pemain harus selalu berdiri di sudut forehand sepanjang rally','masalah hanya dapat diatasi dengan mengganti raket'),
    q('L3','Voli','Setter melihat blocker lawan terlambat menutup sisi kanan. Keputusan serangan yang paling efektif adalah ...','mengirim umpan cepat ke sisi kanan sebelum blok terbentuk','menunggu blok lengkap lalu tetap menyerang ke arah yang sama','mengirim bola ke belakang lapangan sendiri','meminta spiker menghentikan awalan serangan'),
]


XI = [
    q('L1','Voli','Saat lengan bawah dirapatkan dan diarahkan ke sasaran, teknik yang sedang dilakukan adalah ...','passing bawah','passing atas','servis atas','blocking'),
    q('L1','Voli','Pukulan keras menukik ke area lawan dalam voli disebut ...','smash','passing','servis','rotasi'),
    q('L1','Voli','Pemain yang bertugas mengatur umpan serangan biasanya disebut ...','setter','libero','server','blocker'),
    q('L1','Voli','Gerakan menahan serangan lawan di dekat net disebut ...','block','dig','set','serve receive'),
    q('L1','Voli','Rotasi pemain dilakukan ketika regu ...','memenangkan rally saat menerima servis','melakukan kesalahan servis sendiri','kehilangan kesempatan melakukan smash','meminta pergantian bola'),
    q('L1','Basket','Operan yang dipantulkan ke lantai sebelum diterima teman disebut ...','bounce pass','chest pass','overhead pass','hook pass'),
    q('L1','Basket','Pelanggaran berjalan terjadi apabila pemain ...','melangkah tanpa melakukan dribble sesuai ketentuan','mengoper bola kepada teman yang bebas','menjaga lawan dengan posisi rendah','melakukan pivot dengan satu kaki poros'),
    q('L1','Basket','Tembakan yang dilakukan sambil melompat biasanya disebut ...','jump shot','free throw','chest pass','dribble'),
    q('L1','Basket','Rebound adalah usaha untuk ...','menguasai bola pantul dari ring atau papan','mengoper bola dari dada','menghalangi lawan tanpa bergerak','menghentikan waktu pertandingan'),
    q('L1','Basket','Free throw dilakukan dari ...','garis lemparan bebas','garis tengah lapangan','sudut lapangan','garis belakang ring'),
    q('L1','Futsal','Teknik mengumpan dengan sisi dalam kaki dipilih karena ...','lebih mudah mengarahkan bola secara akurat','selalu menghasilkan bola melambung tinggi','hanya dapat digunakan penjaga gawang','membuat bola tidak mungkin direbut'),
    q('L1','Futsal','Pemain yang memiliki tugas utama menjaga gawang disebut ...','goalkeeper','pivot','flank','anchor'),
    q('L1','Futsal','Pemain yang menjadi tumpuan serangan di depan sering disebut ...','pivot','anchor','referee','fixo'),
    q('L1','Futsal','Sikap badan saat menggiring bola sebaiknya ...','sedikit condong dan pandangan bergantian pada bola serta situasi','tegak kaku dan hanya melihat bola','membelakangi arah permainan sepanjang waktu','selalu menunduk tanpa melihat rekan'),
    q('L1','Bulu Tangkis','Pukulan yang dilakukan dekat net dengan sentuhan halus disebut ...','netting','clear','smash','lob servis'),
    q('L1','Bulu Tangkis','Pukulan keras dan menukik ke bidang permainan lawan disebut ...','smash','drive','clear','servis pendek'),
    q('L1','Bulu Tangkis','Servis pendek bertujuan agar shuttlecock ...','jatuh sedekat mungkin di depan garis servis lawan','melambung tinggi ke belakang lapangan sendiri','keluar melewati garis samping','langsung menyentuh net dan berhenti'),
    q('L1','Bulu Tangkis','Pukulan drive memiliki ciri lintasan ...','cepat dan mendatar','tinggi dan sangat melambung','pendek lalu jatuh vertikal di dekat net','selalu menyilang ke luar lapangan'),
    q('L1','Bulu Tangkis','Pegangan raket yang rileks membantu pemain ...','mengubah arah pukulan dan mengontrol shuttlecock','memukul tanpa perlu menggerakkan lengan','membuat shuttlecock berhenti di udara','mengurangi kebutuhan bergerak kaki'),
    q('L2','Voli','Setter melihat blok lawan menumpuk di sisi kiri. Pilihan yang dapat memanfaatkan situasi itu adalah ...','mengalihkan umpan ke sisi kanan yang lebih longgar','tetap mengumpan ke blok yang sama tanpa variasi','mengirim bola langsung ke luar lapangan','meminta semua pemain berhenti bergerak'),
    q('L2','Voli','Dalam menerima servis keras, pemain sebaiknya ...','mengurangi ayunan lengan dan menggunakan sudut platform untuk mengarahkan bola','memukul bola sekuat-kuatnya ke arah belakang','menutup mata agar tidak takut terkena bola','membiarkan bola mengenai badan'),
    q('L2','Basket','Saat rekan melakukan drive dan dua pemain bertahan menutupnya, rekan lain sebaiknya ...','bergerak ke ruang kosong untuk memberi pilihan operan','berdiri di belakang pemain yang membawa bola','mendekati ring tanpa memperhatikan penjaga','keluar lapangan agar area menjadi luas'),
    q('L2','Basket','Jika lawan menerapkan pertahanan zona, serangan yang efektif adalah ...','memindahkan bola cepat dan mengisi ruang di antara zona','menggiring bola terus di satu sudut','menunggu semua pemain lawan keluar area','menembak tanpa melakukan pergerakan tanpa bola'),
    q('L2','Futsal','Setelah kehilangan bola di dekat area lawan, tindakan awal yang tepat adalah ...','melakukan tekanan singkat untuk memperlambat serangan balik','langsung berlari ke depan meninggalkan lawan','berhenti dan menunggu instruksi tanpa menjaga ruang','membuka jalur umpan lawan selebar mungkin'),
    q('L2','Futsal','Pemain tanpa bola melakukan overlap untuk ...','menambah pilihan umpan dan menciptakan keunggulan di sisi lapangan','mengurangi jumlah pemain yang menyerang','menjaga bola tetap berada di belakang','menggantikan tugas wasit'),
    q('L2','Bulu Tangkis','Lawan berdiri terlalu dekat dengan net setelah servis pendek. Pukulan yang dapat mengeksploitasi ruang belakang adalah ...','clear atau lob tinggi ke belakang','netting pendek ke depan lagi','servis pendek ke bidang sendiri','pukulan yang sengaja menyentuh net'),
    q('L2','Bulu Tangkis','Pemain sering terlambat menghadapi smash lawan. Penyesuaian yang paling tepat adalah ...','mengambil posisi siap lebih rendah dan mempersingkat ayunan blok','berdiri lebih tegak agar tidak lelah','memegang raket di belakang kepala','menunggu shuttlecock memantul dari lantai'),
    q('L3','Voli','Lawan membaca serangan karena umpan selalu diarahkan ke spiker yang sama. Evaluasi taktis yang tepat adalah ...','memvariasikan arah dan tempo umpan sambil membaca posisi blok','menambah tenaga smash tanpa mengubah pola','mengurangi komunikasi agar lawan bingung','mengirim semua bola kepada libero'),
    q('L3','Voli','Tim sering kehilangan angka setelah melakukan servis karena formasi bertahan belum siap. Solusi terbaik adalah ...','setiap pemain segera kembali ke tanggung jawab area dan membangun komunikasi blok-pertahanan','semua pemain mengejar bola yang sama','pemain server tetap berdiri di belakang garis','blok ditiadakan agar pemain lebih bebas'),
    q('L3','Basket','Tim tertinggal satu angka dengan 12 detik tersisa. Pembawa bola dijaga ketat dan rekan memiliki jalur potong kosong. Keputusan terbaik adalah ...','menggunakan umpan atau screen untuk memindahkan penjaga lalu memilih peluang dekat ring','memaksa tembakan tiga angka dari posisi tertutup','menahan bola sampai waktu habis','mengoper ke pemain yang dijaga paling rapat'),
    q('L3','Basket','Lawan memiliki center tinggi tetapi lambat bergerak di perimeter. Strategi menyerang yang paling masuk akal adalah ...','menariknya keluar melalui pergerakan dan serangan cepat ke ruang yang ditinggalkan','mengirim semua bola ke area bawah ring yang padat','menghindari passing agar tempo lambat','menjaga center lawan alih-alih menyerang'),
    q('L3','Basket','Tim sering kehilangan bola saat melakukan fast break. Perbaikan yang paling relevan adalah ...','mengatur jarak antarpemain dan memilih operan berdasarkan posisi lawan','mempercepat semua operan tanpa melihat sasaran','mengurangi jumlah pemain yang berlari','menggiring bola melewati garis samping'),
    q('L3','Futsal','Lawan menutup pivot sehingga umpan ke depan selalu dipotong. Solusi taktis yang tepat adalah ...','melakukan rotasi posisi, memakai umpan ketiga, dan menyerang ruang yang terbuka','memaksa umpan langsung ke pivot setiap saat','menghentikan semua pergerakan tanpa bola','menarik seluruh pemain ke dekat gawang sendiri'),
    q('L3','Futsal','Tim tertinggal satu gol dan lawan bertahan sangat dalam. Cara membangun serangan yang paling efektif adalah ...','mengalirkan bola dari sisi ke sisi untuk menarik pertahanan sebelum menembus ruang','menembak dari posisi apa pun secepat mungkin','menempatkan semua pemain di satu sisi','menghindari umpan mendatar karena terlalu berisiko'),
    q('L3','Bulu Tangkis','Lawan mampu mengembalikan smash tetapi lemah pada perubahan arah di depan net. Pola serangan yang tepat adalah ...','mengombinasikan dropshot, netting, dan serangan ke ruang kosong','melakukan smash lurus berulang-ulang ke posisi yang sama','hanya memainkan clear tinggi sepanjang rally','memukul shuttlecock ke tengah tanpa variasi'),
    q('L3','Bulu Tangkis','Dalam ganda, pasangan sendiri sering berdiri sejajar ketika sedang menyerang. Evaluasi yang tepat adalah ...','menerapkan formasi depan-belakang agar satu pemain menekan net dan satu menutup belakang','mempertahankan posisi sejajar dalam semua keadaan','menempatkan kedua pemain di belakang garis akhir','meminta pemain depan meninggalkan lapangan'),
    q('L3','Bulu Tangkis','Pada skor kritis, pemain kehilangan poin karena terus mengejar garis. Keputusan yang lebih baik adalah ...','memilih sasaran dengan margin aman dan memaksa lawan bergerak','tetap mengejar garis pada setiap pukulan','mengurangi komunikasi dengan pelatih atau pasangan','mengubah semua pukulan menjadi servis'),
    q('L3','Bulu Tangkis','Lawan memiliki smash kuat tetapi sering terlambat ke sudut belakang. Rencana yang paling logis adalah ...','menggeser lawan ke belakang dengan clear terarah lalu menggunakan variasi dropshot','memberi bola tinggi ke posisi favorit lawan','menghindari sudut belakang karena sulit dijangkau sendiri','memukul semua bola mendatar ke tengah'),
    q('L1','Voli','Bola yang menyentuh garis lapangan dalam permainan voli dinyatakan ...','masuk','keluar','ulang tanpa keputusan','pelanggaran servis'),
    q('L3','Futsal','Lawan menutup jalur tengah dan memaksa tim bermain di sisi lapangan. Solusi terbaik untuk menciptakan peluang adalah ...','menggunakan rotasi pemain dan umpan diagonal ke ruang di belakang penjaga','memaksa dribbling melewati dua pemain di sisi yang sama','menghentikan pergerakan pemain tanpa bola','mengirim bola terus-menerus ke penjaga gawang'),
]


ESSAY_X = [
    ('Voli','Jelaskan tahapan melakukan passing bawah yang benar dan sebutkan dua kesalahan umum beserta cara memperbaikinya.'),
    ('Basket','Jelaskan perbedaan penggunaan chest pass, bounce pass, dan overhead pass dalam situasi permainan yang berbeda.'),
    ('Futsal','Sebuah tim menghadapi tekanan tinggi dari lawan. Jelaskan minimal tiga solusi taktis agar tim dapat keluar dari tekanan tersebut.'),
    ('Bulu Tangkis','Buat strategi permainan tunggal untuk menghadapi lawan yang memiliki backhand lemah tetapi smash forehand kuat.'),
    ('Gabungan','Pilih salah satu materi olahraga, lalu susun rancangan latihan 20 menit yang memuat pemanasan, latihan inti, pendinginan, dan indikator keberhasilan.'),
]

ESSAY_XI = [
    ('Voli','Analisis mengapa variasi arah dan tempo umpan penting dalam menghadapi blok lawan. Berikan contoh penerapannya.'),
    ('Basket','Jelaskan cara memanfaatkan screen, cut, dan ruang kosong untuk menciptakan peluang tembakan yang lebih baik.'),
    ('Futsal','Tim tertinggal satu gol dan menghadapi pertahanan rapat. Rancang pola serangan yang melibatkan rotasi dan perpindahan bola.'),
    ('Bulu Tangkis','Evaluasi strategi ganda depan-belakang dan sejajar. Kapan masing-masing formasi sebaiknya digunakan?'),
    ('Gabungan','Pilih salah satu cabang olahraga, buat analisis singkat dua kelemahan lawan, lalu jelaskan cara mengeksploitasinya secara sportif.'),
]

ESSAY_KEYS_X = [
    'Sikap siap, posisi kaki dan lutut, bidang lengan, dorongan dari kaki, arah platform, serta dua kesalahan dan koreksinya.',
    'Chest pass untuk operan cepat jarak dekat, bounce pass untuk melewati penjaga, overhead pass untuk melewati penjagaan atau mengirim bola lebih jauh.',
    'Dukungan segitiga, rotasi, perpindahan sisi, umpan ketiga, gerak tanpa bola, dan keputusan tidak memaksakan umpan berisiko.',
    'Menekan backhand, memvariasikan clear/drop/netting, menghindari pola yang memberi ruang forehand, dan tetap menjaga posisi dasar.',
    'Rancangan memuat tujuan, pemanasan, latihan inti yang spesifik, pendinginan, durasi, serta indikator keberhasilan yang dapat diamati.',
]

ESSAY_KEYS_XI = [
    'Variasi arah dan tempo mempersulit blocker membaca pola; contoh: mengombinasikan umpan cepat, umpan tinggi, dan perubahan sisi serangan.',
    'Screen menghambat atau mengalihkan penjaga, cut mengisi ruang kosong, dan spacing membuka jalur drive atau tembakan yang lebih aman.',
    'Rotasi posisi, dukungan segitiga, perpindahan sisi, umpan ketiga, dan pemanfaatan ruang di belakang atau di antara pemain bertahan.',
    'Formasi depan-belakang digunakan saat menyerang, sedangkan formasi sejajar digunakan saat bertahan atau mengantisipasi serangan lawan.',
    'Analisis memuat dua kelemahan lawan, strategi yang sesuai untuk mengeksploitasinya, serta penerapan yang tetap sportif dan aman.',
]


def add_run(paragraph, text, bold=False, size=8.0, color=None):
    r = paragraph.add_run(text)
    r.bold = bold
    r.font.name = 'Times New Roman'
    r.font.size = Pt(size)
    if color:
        r.font.color.rgb = RGBColor(*color)
    return r


def add_question(doc, number, item, answer_letter):
    choices = make_options(item['correct'], item['distractors'], answer_letter)
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(3)
    p.paragraph_format.line_spacing = Pt(9.4)
    p.paragraph_format.left_indent = Pt(12)
    p.paragraph_format.first_line_indent = Pt(-12)
    set_keep(p, True)
    add_run(p, f'{number}. {item["stem"]}', size=9)
    p.add_run().add_break()
    compact = max(len(text) for text in choices) <= 24
    if compact:
        p.paragraph_format.tab_stops.add_tab_stop(Pt(125))
    for i, text in enumerate(choices):
        add_run(p, f'{"ABCD"[i]}. {text}', size=9)
        if i != 3:
            if compact and i in (0, 2):
                p.add_run('\t')
            else:
                p.add_run().add_break()


def add_essay(doc, number, material, stem):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(4)
    p.paragraph_format.line_spacing = Pt(10)
    set_keep(p, True)
    add_run(p, f'{number}. {stem}', size=9)


def add_heading(doc, text, size=9.4, space_before=2, space_after=2):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(space_before)
    p.paragraph_format.space_after = Pt(space_after)
    add_run(p, text, bold=True, size=size)
    return p


def add_column_break(doc):
    p = doc.paragraphs[-1]
    p.add_run().add_break(WD_BREAK.COLUMN)


def add_key_page(doc, items, answers, essays, essay_keys):
    sec = doc.add_section(WD_SECTION.NEW_PAGE)
    set_column_count(sec, 1, 220)
    sec.different_first_page_header_footer = False
    sec.top_margin = Inches(0.55)
    add_heading(doc, 'KUNCI JAWABAN DAN PEDOMAN PENSKORAN', 10.5, 0, 3)
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(3)
    add_run(p, 'Pilihan Ganda (2 poin per soal; skor maksimal 80)', bold=True, size=8.4)
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(4)
    p.paragraph_format.line_spacing = 1.0
    for start in range(0, 40, 10):
        add_run(p, '   '.join(f'{i+1}. {answers[i]}' for i in range(start, start+10)), size=8.0)
        p.add_run().add_break()
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(2)
    add_run(p, 'Komposisi soal PG: L1 = 20 soal (50%), L2 = 8 soal (20%), L3 = 12 soal (30%).', size=8.0)
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(4)
    add_run(p, 'Sebaran kunci: A = 10, B = 10, C = 10, D = 10; tidak ada kunci yang sama secara berurutan.', size=8.0)
    add_heading(doc, 'Pedoman Jawaban Uraian (4 poin per soal; skor maksimal 20)', 9.2, 4, 2)
    rubrics = [
        'Jawaban memuat konsep/strategi yang tepat (2 poin), langkah atau contoh penerapan yang relevan (1 poin), dan alasan/indikator keberhasilan yang jelas (1 poin).',
        'Jawaban yang sebagian benar diberi 2–3 poin sesuai kelengkapan; jawaban tidak sesuai diberi 0–1 poin.',
    ]
    for text in rubrics:
        p = doc.add_paragraph(style=None)
        p.paragraph_format.left_indent = Inches(0.12)
        p.paragraph_format.first_line_indent = Inches(-0.12)
        p.paragraph_format.space_after = Pt(1)
        add_run(p, '• ' + text, size=7.8)
    add_heading(doc, 'Pokok Jawaban Uraian', 9.2, 4, 2)
    for i, (material, stem) in enumerate(essays, 1):
        p = doc.add_paragraph()
        p.paragraph_format.space_after = Pt(2)
        p.paragraph_format.line_spacing = 0.95
        set_keep(p, True)
        expected = essay_keys[i-1]
        add_run(p, f'{i}. [{material}] ', bold=True, size=7.8)
        add_run(p, expected, size=7.8)


def configure_doc(doc):
    sec = doc.sections[0]
    sec.page_width = Inches(8.27)
    sec.page_height = Inches(11.69)
    sec.orientation = WD_ORIENT.PORTRAIT
    sec.top_margin = Inches(0.38)
    sec.bottom_margin = Inches(0.35)
    sec.left_margin = Inches(0.45)
    sec.right_margin = Inches(0.45)
    set_column_count(sec, 1, 180)
    styles = doc.styles
    normal = styles['Normal']
    normal.font.name = 'Arial'
    normal.font.size = Pt(8)
    normal.paragraph_format.space_after = Pt(0)
    normal.paragraph_format.line_spacing = 0.95


def create_doc(filename, grade, items, essays, essay_keys, seed):
    doc = Document()
    configure_doc(doc)
    answers = target_letters(seed)

    # Title block is intentionally compact so the two question pages remain readable.
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_after = Pt(1)
    add_run(p, f'SUMATIF AKHIR SEMESTER PJOK KELAS {grade}', bold=True, size=12)
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_after = Pt(3)
    add_run(p, 'Materi: Voli, Bola Basket, Futsal, dan Bulu Tangkis', size=8.6)

    info = doc.add_table(rows=2, cols=4)
    info.alignment = WD_TABLE_ALIGNMENT.CENTER
    info.autofit = True
    labels = [('Nama', '........................................'), ('Kelas', f'{grade}'), ('No. Absen', '.................'), ('Nilai', '.................')]
    for row in info.rows:
        for cell in row.cells:
            cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
            set_cell_margins(cell, 20, 50, 20, 50)
    for j, (label, value) in enumerate(labels):
        info.cell(0, j).text = label
        info.cell(1, j).text = value
        set_cell_shading(info.cell(0, j), 'E7E6E6')
        for row_idx in [0, 1]:
            for run in info.cell(row_idx, j).paragraphs[0].runs:
                run.font.name = 'Arial'
                run.font.size = Pt(7.1)
                run.bold = row_idx == 0
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(2)
    p.paragraph_format.space_after = Pt(2)
    add_run(p, 'Petunjuk: Pilih satu jawaban yang paling tepat. Kerjakan uraian dengan jawaban ringkas, logis, dan berbasis situasi permainan. PG bernilai 2 dan uraian bernilai 4.', size=7.6)
    add_heading(doc, 'A. Pilihan Ganda', 9.0, 1, 2)

    # Place the full-width title in a first-page header so body columns
    # have a stable origin in Word and LibreOffice.
    section = doc.sections[0]
    section.different_first_page_header_footer = True
    section.header_distance = Inches(0.25)
    section.top_margin = Inches(1.22)
    header = section.first_page_header
    for element in list(doc._element.body):
        if element.tag == qn('w:sectPr'):
            continue
        header._element.append(element)
    set_column_count(section, 2, 240)

    for i, item in enumerate(items[:24], 1):
        add_question(doc, i, item, answers[i-1])
        if i == 12:
            add_column_break(doc)

    # Start the second sheet at question 21.
    second = doc.add_section(WD_SECTION.NEW_PAGE)
    second.different_first_page_header_footer = False
    second.top_margin = Inches(0.45)
    set_column_count(second, 2, 240)
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(2)
    add_run(p, 'A. Pilihan Ganda Lanjutan', bold=True, size=9.0)
    for i, item in enumerate(items[24:], 25):
        add_question(doc, i, item, answers[i-1])
        if i == 34:
            add_column_break(doc)
    add_heading(doc, 'B. Uraian', 9.0, 2, 2)
    for i, (material, stem) in enumerate(essays, 1):
        add_essay(doc, i, material, stem)

    add_key_page(doc, items, answers, essays, essay_keys)
    doc.save(OUT / filename)


if __name__ == '__main__':
    create_doc('SAS_PJOK_Kelas_X.docx', 'X', X, ESSAY_X, ESSAY_KEYS_X, 1047)
    create_doc('SAS_PJOK_Kelas_XI.docx', 'XI', XI, ESSAY_XI, ESSAY_KEYS_XI, 2089)
    print('created')
