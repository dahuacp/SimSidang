<?php

namespace App\Helpers;

use Illuminate\Http\Request;

class MenuHelper
{
    public static function getMenuItems(?Request $request = null)
    {
        $request ??= request();

        return [
            [
                'icon' => 'dashboard',
                'name' => 'Dashboard Admin',
                'path' => route('admin.dashboard'),
                'active' => $request->routeIs('admin.dashboard'),
            ],
            [
                'icon' => 'cog',
                'name' => 'Master Data',
                'subItems' => $masterData = self::subitems([
                    ['icon' => 'user', 'name' => 'Pengguna', 'route' => 'admin.users.index', 'patterns' => ['admin.users.*']],
                    ['icon' => 'building', 'name' => 'Fakultas', 'route' => 'admin.fakultas.index', 'patterns' => ['admin.fakultas.*']],
                    ['icon' => 'academic-cap', 'name' => 'Program Studi', 'route' => 'admin.prodis.index', 'patterns' => ['admin.prodis.*']],
                    ['icon' => 'calendar', 'name' => 'Jenis Sidang', 'route' => 'admin.jenis-sidangs.index', 'patterns' => ['admin.jenis-sidangs.*']],
                    ['icon' => 'clipboard', 'name' => 'Template Penilaian', 'route' => 'admin.assessment-templates.index', 'patterns' => ['admin.assessment-templates.*']],
                ], $request),
                'active' => self::anyActive($masterData),
            ],
            [
                'icon' => 'document-text',
                'name' => 'Transaksi',
                'subItems' => $transaksi = self::subitems([
                    ['icon' => 'calendar', 'name' => 'Jadwal', 'route' => 'admin.schedules.index', 'patterns' => ['admin.schedules.*']],
                    ['icon' => 'document', 'name' => 'Submission', 'route' => 'admin.submissions.index', 'patterns' => ['admin.submissions.*']],
                ], $request),
                'active' => self::anyActive($transaksi),
            ],
            [
                'icon' => 'chart-bar',
                'name' => 'Reporting',
                'subItems' => $reporting = self::subitems([
                    ['icon' => 'chart-bar', 'name' => 'Rekap', 'route' => 'admin.rekap', 'patterns' => ['admin.rekap', 'admin.rekap.export-*']],
                    ['icon' => 'chart-pie', 'name' => 'Rekap Nilai', 'route' => 'admin.rekap.nilai', 'patterns' => ['admin.rekap.nilai*']],
                    ['icon' => 'printer', 'name' => 'Cetak Penilaian', 'route' => 'admin.rekap.cetak-penilaian', 'patterns' => ['admin.rekap.cetak-penilaian']],
                ], $request),
                'active' => self::anyActive($reporting),
            ],
            [
                'icon' => 'robot',
                'name' => 'Asisten',
                'path' => route('admin.assistant.index'),
                'active' => $request->routeIs('admin.assistant*'),
            ],
        ];
    }

    private static function anyActive(array $items): bool
    {
        foreach ($items as $item) {
            if (! empty($item['active'])) {
                return true;
            }
        }

        return false;
    }

    private static function subitems(array $items, Request $request): array
    {
        return array_map(fn ($item) => [
            'icon' => $item['icon'],
            'name' => $item['name'],
            'path' => route($item['route']),
            'active' => $request->routeIs(...$item['patterns']),
        ], $items);
    }

    public static function getIconSvg(string $iconName): string
    {
        $icons = [
            'cog' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h0a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h0a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v0a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>',

            'user' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',

            'building' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 21v-4h6v4"/><path d="M9 10h.01M15 10h.01M9 14h.01M15 14h.01"/></svg>',

            'academic-cap' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>',

            'calendar' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',

            'clipboard' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4M12 16h4M8 11h.01M8 16h.01"/></svg>',

            'document-text' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8M16 13H8M16 17H8"/></svg>',

            'document' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>',

            'chart-bar' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>',

            'chart-pie' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 11-18 0h4a5 5 0 008.9 2.88 5 5 0 001.1-2.88z"/></svg>',

            'printer' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>',

            'dashboard' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1" ry="1"/><rect x="14" y="3" width="7" height="5" rx="1" ry="1"/><rect x="14" y="12" width="7" height="9" rx="1" ry="1"/><rect x="3" y="16" width="7" height="5" rx="1" ry="1"/></svg>',

            'robot' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect x="4" y="8" width="16" height="12" rx="2" ry="2"/><path d="M2 14h2M20 14h2M15 13v2M9 13v2"/></svg>',
        ];

        return $icons[$iconName] ?? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>';
    }
}
