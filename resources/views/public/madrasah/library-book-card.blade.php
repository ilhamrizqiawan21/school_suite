@php
    $digital = $book['has_ebook_file'] || !empty($book['ebook_url']);
    $ebookUrl = $book['has_ebook_file'] ? route('library.collections.download', $book['id']) : ($book['ebook_url'] ?? '');
@endphp
<article class="bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
    <div>
        <div class="bg-slate-50 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
            <span class="absolute top-2 right-2 bg-amber-500 text-white text-[10px] font-extrabold px-2 py-0.5 rounded shadow-sm">{{ $book['category_label'] }}</span>
            <img src="{{ ($contentBlocks['copy.blade_library_book_card']['src_005'] ?? '/madrasah-theme/perpustakaan/database.png') }}" alt="{{ $book['title'] }}" class="h-40 mx-auto object-contain" loading="lazy">
        </div>
        <span class="text-[10px] font-extrabold text-kemenag-700">{{ $book['code'] }}</span>
        <h3 class="text-sm font-bold text-slate-900 mt-1">{{ $book['title'] }}</h3>
        <p class="text-xs text-slate-500 mt-1">{{ $book['author'] ?: '—' }}</p>
        <p class="text-xs text-slate-500 mt-2">{{ $book['available_copies'] }} / {{ $book['total_copies'] }} eksemplar tersedia</p>
    </div>
    <div class="grid gap-2 mt-4">
        @if($digital)
            <button type="button" class="btn-open-reader w-full bg-kemenag-700 text-white font-bold py-2 rounded-lg text-xs" data-file="{{ $ebookUrl }}" data-judul="{{ $book['title'] }}">{{ ($contentBlocks['copy.blade_library_book_card']['text_001'] ?? 'Baca E-Book') }}</button>
        @elseif($book['available_copies'] > 0)
            <button type="button" class="btn-book-reserve w-full bg-amber-500 text-white font-bold py-2 rounded-lg text-xs" data-id="{{ $book['id'] }}" data-judul="{{ $book['title'] }}">{{ ($contentBlocks['copy.blade_library_book_card']['text_002'] ?? 'Booking Pinjam') }}</button>
        @else
            <button type="button" disabled class="w-full bg-slate-100 text-slate-500 py-2 rounded-lg text-xs">{{ ($contentBlocks['copy.blade_library_book_card']['text_003'] ?? 'Stok Habis') }}</button>
        @endif
        <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="{{ $book['id'] }}"><i class="fa-solid fa-circle-info mr-1"></i> {{ ($contentBlocks['copy.blade_library_book_card']['text_004'] ?? 'Detail Lengkap') }}</button>
    </div>
</article>
