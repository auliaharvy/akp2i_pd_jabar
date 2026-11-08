<x-site.layout :title="$organization->name" :description="'Pengurus ' . $organization->name . ($organization->city ? ', ' . $organization->city : '') . '.'">
    <x-site.breadcrumb-header :title="$organization->name" :crumbs="['Pengurus Cabang' => route('site.branches'), $organization->city ?? $organization->name]" />
    <div class="container-site py-12">
        <div class="mb-10 grid gap-4 rounded-lg border border-brand-border bg-brand-section-bg p-6 sm:grid-cols-3">
            <div><p class="text-sm text-gray-600">Anggota aktif</p><p class="text-2xl font-bold text-brand-heading">{{ $activeMembers }}</p></div>
            <div class="space-y-1 text-sm sm:col-span-2">
                @if ($organization->address)<p class="flex gap-2"><x-site.icon name="map-pin" class="h-5 w-5 flex-none text-brand-primary" />{{ $organization->address }}</p>@endif
                @if ($organization->phone)<p class="flex gap-2"><x-site.icon name="phone" class="h-5 w-5 flex-none text-brand-primary" />{{ $organization->phone }}</p>@endif
                @if ($organization->email)<p class="flex gap-2"><x-site.icon name="mail" class="h-5 w-5 flex-none text-brand-primary" /><a href="mailto:{{ $organization->email }}" class="text-brand-secondary underline">{{ $organization->email }}</a></p>@endif
            </div>
        </div>
        <h2 class="mb-6 text-2xl">Pengurus{{ $period ? ' Periode ' . $period->name : '' }}</h2>
        @include('site._board')
    </div>
</x-site.layout>
