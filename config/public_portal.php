<?php

return [
    'theme' => env('PUBLIC_PORTAL_THEME'),
    'profile_menu' => [
        ['label' => 'Sambutan Kepala', 'href' => '/#sambutan', 'icon' => 'fa-user-tie', 'color' => 'text-kemenag-600'],
        ['label' => 'Visi & Misi', 'href' => '/profil-sekolah#arah', 'icon' => 'fa-bullseye', 'color' => 'text-kemenag-600'],
        ['label' => 'Guru & Tenaga Kependidikan', 'href' => '/direktori-gtk', 'icon' => 'fa-chalkboard-user', 'color' => 'text-kemenag-600'],
        ['label' => 'Sarana & Prasarana', 'href' => '/#fasilitas', 'icon' => 'fa-building-columns', 'color' => 'text-kemenag-600'],
        ['label' => 'Zona Integritas & Maklumat', 'href' => '/#zona-integritas', 'icon' => 'fa-shield-heart', 'color' => 'text-amber-500'],
        ['label' => 'Ikatan Alumni & Tracer', 'href' => '/alumni', 'icon' => 'fa-graduation-cap', 'color' => 'text-accent-gold'],
    ],
];
