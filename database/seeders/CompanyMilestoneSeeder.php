<?php

namespace Database\Seeders;

use App\Models\CompanyMilestone;
use Illuminate\Database\Seeder;

class CompanyMilestoneSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'beginning-placeholder',
                ['id' => 'Bab pertama', 'us' => 'First chapter', 'cn' => '第一篇章'],
                ['id' => 'Berawal dari kebutuhan perjalanan Bromo', 'us' => 'Built around the realities of Bromo travel', 'cn' => '从布罗莫旅行的实际需求出发'],
                [
                    'id' => 'Koordinasi dimulai dari hal-hal praktis: penjemputan dini hari dari Malang, kendaraan menuju kawasan Bromo, titik temu jip, dan informasi cuaca dingin untuk tamu.',
                    'us' => 'The operation grew around practical details: early pickup from Malang, transport toward Bromo, jeep meeting points, and preparing guests for the cold weather.',
                    'cn' => '运营从实际细节开始：玛琅清晨接送、前往布罗莫的交通、吉普集合点，以及提醒客人准备御寒衣物。',
                ],
            ],
            [
                'connections-placeholder',
                ['id' => 'Jaringan lokal', 'us' => 'Local network', 'cn' => '本地网络'],
                ['id' => 'Menghubungkan orang di balik perjalanan', 'us' => 'Connecting the people behind each trip', 'cn' => '连接每段旅程背后的人'],
                [
                    'id' => 'Alur kerja berkembang bersama pengemudi, operator jip 4x4, pemandu, dan mitra destinasi agar satu rencana pemesanan dapat berjalan di lapangan.',
                    'us' => 'The workflow developed with drivers, 4x4 jeep operators, guides, and destination partners so one booking plan could work smoothly on the ground.',
                    'cn' => '团队与司机、四驱吉普运营方、向导和目的地伙伴建立协作，让每份预订计划都能在现场顺利执行。',
                ],
            ],
            [
                'beyond-placeholder',
                ['id' => 'Rute bertambah', 'us' => 'More routes', 'cn' => '更多路线'],
                ['id' => 'Dari Bromo menuju pengalaman Jawa Timur', 'us' => 'From Bromo to wider East Java experiences', 'cn' => '从布罗莫拓展至东爪哇'],
                [
                    'id' => 'Pilihan perjalanan bertambah dengan Tumpak Sewu, Goa Tetes, kombinasi rute Jawa Timur, dan destinasi Indonesia lain yang dapat disesuaikan untuk perjalanan privat.',
                    'us' => 'The catalogue expanded to Tumpak Sewu, Goa Tetes, combined East Java routes, and other Indonesian destinations that can be arranged privately.',
                    'cn' => '行程逐步扩展至赛武瀑布、Goa Tetes、东爪哇组合路线，以及可私人定制的其他印度尼西亚目的地。',
                ],
            ],
            [
                'today-placeholder',
                ['id' => 'Hari ini', 'us' => 'Today', 'cn' => '今天'],
                ['id' => 'Satu jalur koordinasi dari rencana sampai berangkat', 'us' => 'One line of coordination from planning to departure', 'cn' => '从计划到出发的一站式协调'],
                [
                    'id' => 'Tamu dapat memilih paket, mengirim kebutuhan perjalanan, menerima rincian harga dan rute, lalu tetap terhubung dengan tim untuk pembaruan penjemputan dan hari keberangkatan.',
                    'us' => 'Guests can choose a package, send their travel needs, receive route and price details, and stay connected with the team for pickup and departure-day updates.',
                    'cn' => '客人可以选择套餐、提交旅行需求、获取路线和价格详情，并与团队保持联系以接收接送及出发日更新。',
                ],
            ],
        ];

        foreach ($items as $index => [$key, $period, $title, $description]) {
            $milestone = CompanyMilestone::firstOrNew(['seed_key' => $key]);
            $originalTitles = [
                'The start of a local journey',
                'Growing local connections',
                'Beyond Bromo',
                'TinggalJalan today',
            ];
            $isOriginalPlaceholder = in_array(data_get($milestone->title, 'us'), $originalTitles, true);
            $hasCorruptedMandarin = str_contains((string) data_get($milestone->period, 'cn'), '?')
                || str_contains((string) data_get($milestone->title, 'cn'), '?')
                || str_contains((string) data_get($milestone->description, 'cn'), '?');

            if ($milestone->exists && ! $milestone->is_sample) {
                continue;
            }

            if ($milestone->exists && ! $isOriginalPlaceholder) {
                if ($hasCorruptedMandarin) {
                    $savedPeriod = $milestone->period;
                    $savedTitle = $milestone->title;
                    $savedDescription = $milestone->description;
                    $savedPeriod['cn'] = $period['cn'];
                    $savedTitle['cn'] = $title['cn'];
                    $savedDescription['cn'] = $description['cn'];

                    $milestone->update([
                        'period' => $savedPeriod,
                        'title' => $savedTitle,
                        'description' => $savedDescription,
                    ]);
                }

                continue;
            }

            $milestone->fill([
                'period' => $period,
                'title' => $title,
                'description' => $description,
                'is_sample' => true,
                'is_active' => true,
                'sort_order' => $index + 1,
            ])->save();
        }
    }
}
