<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * B12: semua DataTable server-side. Request dibangun dari definisi kolom JS
 * di blade, dengan pencarian + urut di setiap kolom.
 */
class DatatableServerSideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** @return array<string, string[]> route => nama kolom */
    private function tables(): array
    {
        $tables = [];
        foreach (Finder::create()->in(resource_path('views/dashboard'))->name('*.blade.php') as $file) {
            $src = $file->getContents();
            preg_match_all("/ajax:\s*\"\{\{\s*route\('([^']+)'\)\s*\}\}\"(.*?)\]/s", $src, $m, PREG_SET_ORDER);
            foreach ($m as [, $route, $cols]) {
                preg_match_all("/data:\s*'([^']+)'/", $cols, $c);
                $tables[$route] = $c[1];
            }
        }

        return $tables;
    }

    public function test_every_datatable_handles_search_and_sort_server_side()
    {
        $admin = User::where('email', 'superadmin@mail.com')->firstOrFail();
        $tables = $this->tables();
        $this->assertNotEmpty($tables);

        foreach ($tables as $route => $columns) {
            if (! Route::has($route)) {
                continue;
            }
            $query = ['draw' => 1, 'start' => 0, 'length' => 5, 'search' => ['value' => 'a', 'regex' => 'false']];
            foreach ($columns as $i => $col) {
                $query['columns'][$i] = ['data' => $col, 'name' => $col, 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']];
            }
            $query['order'][0] = ['column' => min(1, count($columns) - 1), 'dir' => 'asc'];

            $response = $this->actingAs($admin)->getJson(route($route, $query), ['X-Requested-With' => 'XMLHttpRequest']);

            $this->assertSame(200, $response->status(), "{$route}: " . substr($response->getContent(), 0, 300));
            $this->assertArrayHasKey('recordsFiltered', $response->json(), $route);
        }
    }
}
