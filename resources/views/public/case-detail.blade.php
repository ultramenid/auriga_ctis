@extends('layouts.main')

@php
    $trans = $case->translations->firstWhere('locale', app()->getLocale()) ?? $case->translations->first();
    $pageTitle = strip_tags($trans?->title ?? 'Detail Kasus') . ' — Auriga CTIS';
    $pageDescription = 'Kasus #' . ($case->case_number ?? '') . ' — ' . strip_tags($trans?->title ?? '') . '. Status: ' . ($case->status->name ?? '-') . '. Lokasi: ' . ($location['province'] ?? '-') . '. Lacak perkembangan kasus hukum lingkungan ini.';
    $ogTitle = $pageTitle;
    $ogDescription = $pageDescription;
    $ogImage = $case->bukti && is_array($case->bukti) && count($case->bukti) > 0 ? asset('storage/' . $case->bukti[0]) : asset('img/image.png');
    $ogType = 'article';
@endphp

@section('structured-data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "NewsArticle",
    "headline": {!! json_encode(strip_tags($trans?->title ?? 'Detail Kasus')) !!},
    "description": {!! json_encode($pageDescription) !!},
    "image": "{{ $ogImage }}",
    "datePublished": "{{ optional($case->published_at)->toIso8601String() }}",
    "dateModified": "{{ optional($case->updated_at)->toIso8601String() }}",
    "author": {
        "@@type": "Organization",
        "name": "Auriga CTIS",
        "url": "{{ url('/') }}"
    },
    "publisher": {
        "@@type": "Organization",
        "name": "Auriga CTIS",
        "logo": {
            "@@type": "ImageObject",
            "url": "{{ asset('img/image.png') }}"
        }
    },
    "mainEntityOfPage": {
        "@@type": "WebPage",
        "@@id": "{{ url()->current() }}"
    },
    "articleSection": "Hukum Lingkungan",
    "keywords": "kasus lingkungan, hukum lingkungan, {{ $case->case_number ?? '' }}, {{ $location['province'] ?? '' }}"
}
</script>
@endsection

@section('content')

<style>
.case-summary img,
.case-summary video,
.case-summary iframe {
    max-width: 100%;
    height: auto;
}
.case-summary {
    word-break: break-word;
    overflow-wrap: break-word;
}

/* monochrome map tiles */
#map .leaflet-tile,
#mapFull .leaflet-tile { filter: grayscale(1); }

/* collapsible long sections */
.collapsible.clamped {
    max-height: 26rem;
    overflow: hidden;
    position: relative;
}
.collapsible.clamped::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 5rem;
    background: linear-gradient(to top, #fff, transparent);
    pointer-events: none;
}

/* Custom prose styling for TinyMCE content */
.prose-content p { margin-bottom: 1rem; }
.prose-content p:last-child { margin-bottom: 0; }
.prose-content h1,
.prose-content h2,
.prose-content h3,
.prose-content h4 {
    font-weight: 700;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}
.prose-content h1 { font-size: 1.875rem; }
.prose-content h2 { font-size: 1.5rem; }
.prose-content h3 { font-size: 1.25rem; }
.prose-content ul,
.prose-content ol {
    margin: 1rem 0;
    padding-left: 1.5rem;
}
.prose-content ul { list-style-type: disc; }
.prose-content ol { list-style-type: decimal; }
.prose-content li { margin-bottom: 0.5rem; }
.prose-content a { color: #111827; text-decoration: underline; }
.prose-content strong { font-weight: 700; }
.prose-content em { font-style: italic; }
.prose-content img { max-width: 100%; height: auto; }
</style>

    <div class="bg-white text-gray-900 mt-16">

        @php
            $trans = $case->translations->firstWhere('locale', app()->getLocale()) ?? $case->translations->first();

            $categoryTrans =
                $case->category?->translations->firstWhere('locale', app()->getLocale()) ??
                $case->category?->translations->first();

            $noTransMsg = '<em class="text-gray-400">Konten tidak tersedia dalam bahasa ini</em>';

            // Pemetaan status ke tahapan proses hukum
            $statusSteps = [
                'open' => ['label' => 'Terbuka', 'step' => 1],
                'verified' => ['label' => 'Terverifikasi', 'step' => 2],
                'published' => ['label' => 'Dipublikasikan', 'step' => 3],
                'penyelidikan' => ['label' => 'Penyelidikan', 'step' => 4],
                'investigation' => ['label' => 'Investigasi', 'step' => 4],
                'penyidikan' => ['label' => 'Penyidikan', 'step' => 5],
                'prosecution' => ['label' => 'Penuntutan', 'step' => 6],
                'trial' => ['label' => 'Persidangan', 'step' => 7],
                'vonis' => ['label' => 'Vonis', 'step' => 8],
                'Berkekuatan hukum tetap' => ['label' => 'Berkekuatan Hukum', 'step' => 9],
                'executed' => ['label' => 'Putusan Dijalankan', 'step' => 10],
                'completed' => ['label' => 'Selesai', 'step' => 11],
                'closed' => ['label' => 'Ditutup', 'step' => 12],
                'rejected' => ['label' => 'Ditolak', 'step' => 12],
                'converted' => ['label' => 'Dikonversi', 'step' => 12],
            ];

            $currentKey = $case->status?->key ?? '';
            $currentStep = $statusSteps[$currentKey]['step'] ?? 0;

            $visibleSteps = [
                1 => [
                    'label' => 'Terbuka',
                    'desc' => 'Laporan atau kasus baru masuk dan telah didaftarkan ke sistem.',
                ],
                3 => [
                    'label' => 'Dipublikasikan',
                    'desc' => 'Informasi kasus telah dipublikasikan dan dapat diakses oleh publik.',
                ],
                4 => [
                    'label' => 'Penyelidikan',
                    'desc' => 'Penegak hukum sedang mengumpulkan bukti awal untuk menentukan apakah ada tindak pidana.',
                ],
                5 => [
                    'label' => 'Penyidikan',
                    'desc' => 'Bukti sudah cukup. Penyidik resmi mengusut tersangka dan mengumpulkan alat bukti.',
                ],
                6 => [
                    'label' => 'Penuntutan',
                    'desc' => 'Jaksa menyusun surat dakwaan dan mempersiapkan kasus untuk dibawa ke pengadilan.',
                ],
                7 => [
                    'label' => 'Persidangan',
                    'desc' => 'Kasus sedang disidangkan di pengadilan. Hakim memeriksa bukti dan keterangan saksi.',
                ],
                8 => [
                    'label' => 'Vonis',
                    'desc' => 'Hakim telah menjatuhkan putusan bersalah atau tidak bersalah kepada terdakwa.',
                ],
                9 => [
                    'label' => 'Hukum Tetap',
                    'desc' => 'Putusan telah berkekuatan hukum tetap (inkracht) — tidak bisa digugat lagi.',
                ],
                10 => [
                    'label' => 'Dijalankan',
                    'desc' => 'Terpidana sedang menjalani hukuman sesuai putusan pengadilan.',
                ],
                11 => ['label' => 'Selesai', 'desc' => 'Seluruh proses hukum telah selesai.'],
            ];

            // Penjelasan status dalam bahasa sederhana
            $statusExplainer = [
                'open' => 'Kasus ini baru saja masuk dan sedang menunggu proses lebih lanjut dari penegak hukum.',
                'verified' => 'Laporan kasus ini sudah diverifikasi kebenarannya oleh tim kami.',
                'published' => 'Kasus ini sudah dipublikasikan agar masyarakat dapat mengikuti perkembangannya.',
                'penyelidikan' => 'Penegak hukum sedang mengumpulkan bukti awal. Belum ada tersangka resmi saat ini.',
                'investigation' => 'Penegak hukum sedang mengumpulkan bukti awal. Belum ada tersangka resmi saat ini.',
                'penyidikan' => 'Sudah ada tersangka. Penyidik resmi sedang mengumpulkan alat bukti untuk pelimpahan ke jaksa.',
                'prosecution' => 'Jaksa sedang menyusun surat dakwaan. Kasus ini akan segera masuk ke pengadilan.',
                'trial' => 'Sidang sedang berlangsung di pengadilan. Hakim, jaksa, dan pengacara memeriksa fakta-fakta kasus.',
                'vonis' => 'Hakim telah menjatuhkan putusan. Terdakwa dinyatakan bersalah atau bebas.',
                'Berkekuatan hukum tetap' => 'Putusan sudah final dan tidak bisa digugat lagi. Proses banding atau kasasi telah selesai.',
                'executed' => 'Terpidana sedang menjalani hukuman — baik penjara, denda, maupun hukuman lainnya.',
                'completed' => 'Seluruh proses hukum pada kasus ini telah selesai.',
                'closed' => 'Kasus ini telah ditutup.',
                'rejected' => 'Kasus ini ditolak karena tidak memenuhi syarat atau tidak cukup bukti.',
                'converted' => 'Status kasus ini telah dikonversi ke proses atau jalur hukum lain.',
            ];
            $explainer = $statusExplainer[$currentKey] ?? null;

            $shareUrl = urlencode(request()->fullUrl());
            $shareTitle = urlencode(strip_tags($trans?->title ?? 'Detail Kasus'));

            $kategori = $categories
                ->map(function ($cat) {
                    $t = $cat->translations->firstWhere('locale', app()->getLocale()) ?? $cat->translations->first();
                    return $t?->name ?? '-';
                })
                ->join(', ');

            // Perkembangan: bisa berupa JSON timeline atau HTML bebas
            $perkembangan = $trans->perkembangan ?? null;
            $perkembanganArr = null;
            if (is_string($perkembangan)) {
                $json = json_decode($perkembangan, true);
                if (is_array($json)) {
                    $perkembanganArr = $json;
                }
            }
        @endphp

        {{-- ===================== HEADER ===================== --}}
        <header class="border-b border-gray-900">
            <div class="max-w-6xl mx-auto px-6 py-10">
                <div class="flex items-center gap-3 flex-wrap text-[11px] tracking-widest uppercase text-gray-500">
                    <span>Kasus {{ $case->case_number }}</span>
                    @if ($categoryTrans?->name)
                        <span class="text-gray-300">/</span>
                        <span>{{ $categoryTrans->name }}</span>
                    @endif
                </div>
                <h1 class="mt-3 text-3xl md:text-5xl font-bold leading-tight tracking-tight max-w-4xl">
                    {!! $trans?->title ?? $noTransMsg !!}
                </h1>
                <div class="mt-5 flex items-center gap-3 flex-wrap text-sm">
                    <span class="px-2.5 py-1 bg-gray-900 text-white text-xs font-semibold">{{ $case->status->name ?? 'Status' }}</span>
                    @if ($explainer)
                        <p class="text-gray-600">{{ $explainer }}</p>
                    @endif
                </div>
            </div>
        </header>

        {{-- ===================== DOSSIER ===================== --}}
        <div class="max-w-6xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-[320px_1fr]">

            {{-- ========== SIDEBAR ========== --}}
            <aside class="py-10 lg:pr-10 lg:border-r lg:border-gray-200">
                <div class="lg:sticky lg:top-24 space-y-8">

                    {{-- tahapan: vertical rail --}}
                    <section>
                        <h2 class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 mb-4">Tahapan Kasus</h2>
                        <ol class="relative border-l border-gray-200 ml-[5px] space-y-4">
                            @foreach ($visibleSteps as $step => $info)
                                @php
                                    $active = $currentStep === $step;
                                    $reached = $currentStep >= $step;
                                @endphp
                                <li class="relative pl-5">
                                    <span class="absolute -left-[5.5px] top-1 w-2.5 h-2.5 rounded-full
                                        {{ $active ? 'bg-gray-900 ring-4 ring-gray-200' : ($reached ? 'bg-gray-900' : 'bg-white border border-gray-300') }}"></span>
                                    <p class="group/step relative inline-block text-xs leading-snug cursor-default {{ $active ? 'font-bold' : ($reached ? 'font-medium text-gray-700' : 'text-gray-400') }}">
                                        {{ $info['label'] }}
                                        {{-- tooltip: anchored to the label text itself --}}
                                        <span class="hidden lg:block absolute left-full top-1/2 -translate-y-1/2 ml-3 w-56 bg-gray-900 text-white font-normal text-xs px-3 py-2 leading-snug
                                            opacity-0 group-hover/step:opacity-100 pointer-events-none transition-opacity duration-200 z-30 shadow-xl">
                                            {{ $info['desc'] }}
                                            <span class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-gray-900"></span>
                                        </span>
                                    </p>
                                    @if ($active)
                                        <p class="mt-1 text-xs text-gray-500 leading-relaxed">{{ $info['desc'] }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </section>

                    {{-- fakta --}}
                    <section>
                        <h2 class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 mb-4">Fakta Kasus</h2>
                        <dl class="text-xs divide-y divide-gray-100 border-y border-gray-100">
                            @foreach ([
                                'Nomor Kasus' => $case->case_number,
                                'Tanggal Kejadian' => $case->event_date ?? '-',
                                'Kategori' => $kategori ?: '-',
                                'Provinsi' => $location['province'] ?? '-',
                                'Kab/Kota' => $location['district'] ?? '-',
                                'Desa/Kelurahan' => $location['village'] ?? '-',
                                'Dipublikasikan' => optional($case->published_at)->format('d M Y') ?? '-',
                                'Diperbarui' => optional($case->updated_at)->format('d M Y, H:i') . ' WIB',
                            ] as $label => $value)
                                <div class="py-2 flex justify-between gap-4">
                                    <dt class="text-gray-400 flex-shrink-0">{{ $label }}</dt>
                                    <dd class="font-medium text-right break-words">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>

                    {{-- peta --}}
                    @if ($case->latitude && $case->longitude)
                        <section>
                            <h2 class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 mb-4">Lokasi</h2>
                            <div class="relative">
                                <div id="map" class="h-52 w-full border border-gray-200"></div>
                                <button onclick="openMapModal()" aria-label="Perbesar peta" title="Perbesar peta"
                                    class="absolute top-2 right-2 z-[1000] w-8 h-8 bg-white border border-gray-300 text-gray-600 hover:border-gray-900 hover:text-gray-900 transition-colors flex items-center justify-center cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-2 text-xs text-gray-500">
                                {{ collect([$location['village'] ?? null, $location['district'] ?? null, $location['province'] ?? null])->filter()->join(', ') ?: 'Lokasi tidak diketahui' }}
                            </p>
                        </section>
                    @endif

                    {{-- bagikan --}}
                    <section>
                        <h2 class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 mb-4">Bagikan</h2>
                        <div class="flex flex-wrap gap-2">
                            <a href="https://wa.me/?text={{ $shareTitle }}%20{{ $shareUrl }}" target="_blank" aria-label="Bagikan ke WhatsApp"
                                class="inline-flex items-center justify-center w-9 h-9 border border-gray-300 text-gray-600 hover:border-gray-900 hover:text-gray-900 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" />
                                    <path d="M12 0C5.373 0 0 5.373 0 12c0 2.127.558 4.126 1.535 5.858L.057 23.5l5.797-1.522A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.894a9.878 9.878 0 01-5.031-1.376l-.361-.214-3.741.981.999-3.648-.235-.374A9.865 9.865 0 012.106 12C2.106 6.58 6.58 2.106 12 2.106c5.421 0 9.894 4.474 9.894 9.894 0 5.421-4.473 9.894-9.894 9.894z" />
                                </svg>
                            </a>
                            <a href="https://twitter.com/intent/tweet?text={{ $shareTitle }}&url={{ $shareUrl }}" target="_blank" aria-label="Bagikan ke Twitter/X"
                                class="inline-flex items-center justify-center w-9 h-9 border border-gray-300 text-gray-600 hover:border-gray-900 hover:text-gray-900 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.737-8.835L1.254 2.25H8.08l4.259 5.63L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117L17.083 19.77z" />
                                </svg>
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" aria-label="Bagikan ke Facebook"
                                class="inline-flex items-center justify-center w-9 h-9 border border-gray-300 text-gray-600 hover:border-gray-900 hover:text-gray-900 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                </svg>
                            </a>
                            <button onclick="copyLink()" id="copyBtn"
                                class="inline-flex items-center gap-1.5 h-9 px-3 border border-gray-300 text-xs font-semibold text-gray-600 hover:border-gray-900 hover:text-gray-900 transition-colors cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <span id="copyLabel">Salin Link</span>
                            </button>
                        </div>
                    </section>

                    {{-- ikuti --}}
                    @if (! in_array($case->status?->key, ['completed', 'closed']))
                        <section class="border border-gray-900 p-4">
                            <h2 class="text-[11px] font-semibold uppercase tracking-widest mb-1">Ikuti Kasus Ini</h2>
                            <p class="text-xs text-gray-500 mb-3">
                                Dapatkan notifikasi email setiap ada perkembangan terbaru pada kasus ini.
                            </p>
                            @livewire('case-subscribe-form', ['caseId' => $case->id])
                        </section>
                    @endif
                </div>
            </aside>

            {{-- ========== DOCUMENT ========== --}}
            <main class="py-10 lg:pl-10 min-w-0">
                <article class="max-w-3xl space-y-12">

                    {{-- Kronologi --}}
                    <section id="kronologi">
                        <h2 class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 pb-3 border-b border-gray-900 mb-5">Kronologi</h2>
                        @if ($trans?->description)
                            <div class="collapsible prose prose-gray max-w-none case-summary">{!! $trans->description !!}</div>
                        @else
                            <p class="text-sm text-gray-400 italic">Kronologi belum tersedia.</p>
                        @endif
                    </section>

                    {{-- Perkembangan --}}
                    <section id="perkembangan">
                        <h2 class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 pb-3 border-b border-gray-900 mb-5">Perkembangan</h2>
                        @if ($perkembanganArr)
                            <ol class="collapsible relative border-l border-gray-200 ml-[5px] space-y-8">
                                @forelse ($perkembanganArr as $entry)
                                    <li class="relative pl-6">
                                        <span class="absolute -left-[5.5px] top-1.5 w-2.5 h-2.5 rounded-full bg-gray-900"></span>
                                        @if (!empty($entry['created_at']))
                                            <p class="text-[11px] text-gray-400 mb-1">
                                                {{ \Carbon\Carbon::parse($entry['created_at'])->format('d M Y H:i') }}
                                            </p>
                                        @endif
                                        <h3 class="font-semibold text-sm">{{ $entry['title'] ?? 'Perkembangan Kasus' }}</h3>
                                        <p class="text-sm text-gray-600 mt-1 leading-relaxed">{{ $entry['notes'] ?? '' }}</p>
                                    </li>
                                @empty
                                    <p class="text-sm text-gray-400 italic">Belum ada perkembangan.</p>
                                @endforelse
                            </ol>
                        @elseif(!empty($perkembangan))
                            <div class="collapsible prose prose-gray max-w-none case-summary">{!! $perkembangan !!}</div>
                        @else
                            <p class="text-sm text-gray-400 italic">Belum ada perkembangan.</p>
                        @endif
                    </section>

                    {{-- Dugaan Permasalahan --}}
                    <section id="permasalahan">
                        <h2 class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 pb-3 border-b border-gray-900 mb-5">Dugaan Permasalahan</h2>
                        @if ($trans?->dugaan_permasalahan)
                            <div class="collapsible prose-content text-gray-700 leading-relaxed text-sm case-summary">{!! $trans->dugaan_permasalahan !!}</div>
                        @else
                            <p class="text-sm text-gray-400 italic">Dugaan permasalahan belum tersedia.</p>
                        @endif
                    </section>

                    {{-- Pembelajaran --}}
                    <section id="pembelajaran">
                        <h2 class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 pb-3 border-b border-gray-900 mb-5">Lesson Learning</h2>
                        @if ($trans?->pembelajaran)
                            <div class="collapsible prose-content text-gray-700 leading-relaxed text-sm case-summary">{!! $trans->pembelajaran !!}</div>
                        @else
                            <p class="text-sm text-gray-400 italic">Pembelajaran belum tersedia.</p>
                        @endif
                    </section>

                    {{-- Status narasi --}}
                    <section id="status">
                        <h2 class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 pb-3 border-b border-gray-900 mb-5">Status</h2>
                        @if ($case->status_narasi)
                            <div class="collapsible prose-content text-gray-700 leading-relaxed text-sm case-summary">{!! $case->status_narasi !!}</div>
                        @else
                            <p class="text-sm text-gray-400 italic">Status belum tersedia.</p>
                        @endif
                    </section>
                </article>
            </main>
        </div>

        {{-- ===================== TERKAIT ===================== --}}
        @php
            $hasArtikels = isset($artikels) && count($artikels);
            $hasRelated = isset($relatedCases) && $relatedCases->count();
        @endphp
        @if ($hasArtikels || $hasRelated)
            <div class="border-t border-gray-900">
                <div class="max-w-6xl mx-auto px-6 py-12 grid grid-cols-1 lg:grid-cols-2 gap-x-16 gap-y-10">

                    {{-- artikel terkait --}}
                    @if ($hasArtikels)
                        <section>
                            <h2 class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 mb-2">Artikel Terkait</h2>
                            <div class="divide-y divide-gray-200">
                                @foreach ($artikels as $c)
                                    <a href="{{ $c->type === 'internal' ? route('public.artikel.detail', ['slug' => $c->slug]) : $c->link }}"
                                        @if ($c->type !== 'internal') target="_blank" rel="noopener" @endif
                                        class="group flex items-center gap-4 py-4">
                                        <img src="{{ asset('storage/' . $c->image) }}" alt="{{ strip_tags($c->title) }}"
                                            class="w-20 h-14 object-cover flex-shrink-0 border border-gray-200 grayscale group-hover:grayscale-0 transition duration-300">
                                        <div class="min-w-0">
                                            @if ($c->category_name)
                                                <p class="text-[10px] uppercase tracking-widest text-gray-400">{{ $c->category_name }}</p>
                                            @endif
                                            <h3 class="mt-0.5 text-sm font-bold leading-snug group-hover:underline">
                                                {!! strip_tags($c->title) !!}
                                            </h3>
                                            <p class="mt-1 text-xs text-gray-500 truncate">{{ Str::limit(strip_tags($c->excerpt), 90) }}</p>
                                        </div>
                                        <span class="ml-auto text-gray-300 group-hover:text-gray-900 transition-colors flex-shrink-0">→</span>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    {{-- kasus terkait --}}
                    @if ($hasRelated)
                        <section>
                            <h2 class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 mb-2">Kasus Terkait</h2>
                            <div class="divide-y divide-gray-200">
                                @foreach ($relatedCases as $related)
                                    @php
                                        $relatedTrans =
                                            $related->translations->firstWhere('locale', app()->getLocale()) ??
                                            $related->translations->first();
                                    @endphp
                                    <a href="{{ route('public.case.detail', ['slug' => $related->slug]) }}" class="group flex items-start gap-4 py-4">
                                        <div class="min-w-0">
                                            <p class="text-[10px] uppercase tracking-widest text-gray-400">
                                                {{ $related->case_number }} · {{ $related->status->name ?? '-' }} · {{ $related->location['province'] ?? '-' }}
                                            </p>
                                            <h3 class="mt-0.5 text-sm font-bold leading-snug group-hover:underline">
                                                {{ $relatedTrans?->title ?? 'Tanpa judul' }}
                                            </h3>
                                            <p class="mt-1 text-xs text-gray-500">{{ Str::limit(strip_tags($relatedTrans?->summary ?? ''), 100) }}</p>
                                        </div>
                                        <span class="ml-auto text-gray-300 group-hover:text-gray-900 transition-colors flex-shrink-0">→</span>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>
            </div>
        @endif

        {{-- map modal (di level root halaman agar tidak terjebak stacking context sidebar sticky) --}}
        @if ($case->latitude && $case->longitude)
            <div id="mapModal" class="hidden fixed inset-0 z-[99999] bg-black/60 flex items-center justify-center p-4" onclick="if (event.target === this) closeMapModal()">
                <div class="bg-white w-full max-w-3xl flex flex-col border border-gray-900 shadow-2xl">
                    <div class="flex items-center justify-between gap-4 px-4 py-3 border-b border-gray-200">
                        <p class="text-[11px] uppercase tracking-widest text-gray-500 truncate">
                            Lokasi Kasus — {{ collect([$location['village'] ?? null, $location['district'] ?? null, $location['province'] ?? null])->filter()->join(', ') ?: 'Tidak diketahui' }}
                        </p>
                        <button onclick="closeMapModal()" aria-label="Tutup peta"
                            class="flex-shrink-0 w-8 h-8 border border-gray-300 text-gray-600 hover:border-gray-900 hover:text-gray-900 transition-colors flex items-center justify-center cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div id="mapFull" class="h-[60vh]"></div>
                </div>
            </div>
        @endif

    </div>

@endsection

@push('scripts')
    <script>
        // ===== Map =====
        const caseLat = {{ $case->latitude ?? 'null' }};
        const caseLng = {{ $case->longitude ?? 'null' }};

        document.addEventListener('DOMContentLoaded', function() {
            if (!caseLat || !caseLng || !document.getElementById('map')) return;
            const map = L.map('map', {
                scrollWheelZoom: false
            }).setView([caseLat, caseLng], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
            L.marker([caseLat, caseLng]).addTo(map);
        });

        // ===== Fullscreen map modal =====
        let fullMap = null;
        function openMapModal() {
            const modal = document.getElementById('mapModal');
            if (!modal || !caseLat || !caseLng) return;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            if (!fullMap) {
                fullMap = L.map('mapFull').setView([caseLat, caseLng], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(fullMap);
                L.marker([caseLat, caseLng]).addTo(fullMap);
            }
            setTimeout(() => fullMap.invalidateSize(), 50);
        }
        function closeMapModal() {
            const modal = document.getElementById('mapModal');
            if (!modal || modal.classList.contains('hidden')) return;
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeMapModal();
        });

        // ===== Collapsible long sections =====
        document.addEventListener('DOMContentLoaded', function() {
            const CLAMP = 26 * 16; // keep in sync with .collapsible.clamped max-height
            document.querySelectorAll('.collapsible').forEach(el => {
                if (el.scrollHeight <= CLAMP + 80) return; // not long enough to bother
                el.classList.add('clamped');
                const btn = document.createElement('button');
                btn.textContent = 'Selengkapnya';
                btn.setAttribute('aria-expanded', 'false');
                btn.className = 'mt-4 px-3 py-1.5 border border-gray-300 text-xs font-semibold text-gray-600 hover:border-gray-900 hover:text-gray-900 transition-colors cursor-pointer';
                btn.addEventListener('click', () => {
                    const clamped = el.classList.toggle('clamped');
                    btn.textContent = clamped ? 'Selengkapnya' : 'Tutup';
                    btn.setAttribute('aria-expanded', String(!clamped));
                    if (clamped) el.closest('section')?.scrollIntoView({ block: 'start' });
                });
                el.after(btn);
            });
        });

        // ===== Copy Link =====
        function copyLink() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                const btn = document.getElementById('copyBtn');
                const label = document.getElementById('copyLabel');
                label.textContent = 'Tersalin!';
                btn.classList.add('bg-gray-900', 'text-white', 'border-gray-900');
                setTimeout(() => {
                    label.textContent = 'Salin Link';
                    btn.classList.remove('bg-gray-900', 'text-white', 'border-gray-900');
                }, 2000);
            });
        }
    </script>
@endpush
