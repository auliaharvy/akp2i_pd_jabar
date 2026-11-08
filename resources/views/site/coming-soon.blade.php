<x-site.layout :title="$title">
    <x-site.breadcrumb-header :title="$title" :crumbs="[$title]" />
    <div class="container-site py-12">
        <x-site.empty-state :title="$title . ' segera hadir'" icon="calendar">
            Halaman ini sedang disiapkan. Sementara itu, hubungi <a href="{{ route('site.contact') }}" class="text-brand-secondary underline">sekretariat</a> untuk informasi.
        </x-site.empty-state>
    </div>
</x-site.layout>
