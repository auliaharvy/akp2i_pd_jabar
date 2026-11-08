<?php

return [
    // PD aktif aplikasi (PRD 8.1). Diisi id organizations PD Jabar setelah seed M1.
    'organization_id' => env('AKP2I_ORGANIZATION_ID') ?: null,

    // Tautan layanan Pengurus Pusat; default bila Setting belum diisi (PRD 8.5)
    'urls' => [
        'registration' => env('AKP2I_URL_REGISTRATION', 'https://akp2i.id/pendaftaran-anggota/'),
        'member_service' => env('AKP2I_URL_MEMBER_SERVICE', 'https://akp2i.id/pelayanan-anggota/'),
        'central_site' => env('AKP2I_URL_CENTRAL', 'https://akp2i.or.id/'),
    ],

    // Batas super administrator (aturan baraasa dipertahankan, PRD 4.2)
    'max_super_admins' => 5,
];
