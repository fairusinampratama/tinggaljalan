<?php

namespace Database\Seeders;

use App\Models\TeamMember;
use Illuminate\Database\Seeder;

class TeamMemberSeeder extends Seeder
{
    public function run(): void
    {
        $members = [
            [
                'founder-placeholder',
                'Raka Pradana',
                'leadership',
                '/images/about/seed/demo-trip-planner.jpg',
                ['id' => 'Lead Trip Designer', 'us' => 'Lead Trip Designer', 'cn' => '首席行程设计师'],
                [
                    'id' => 'Menyusun arah pengalaman TinggalJalan, meninjau rute, dan memastikan setiap paket dapat dijalankan dengan jelas oleh tim lokal.',
                    'us' => 'Shapes the TinggalJalan experience, reviews routes, and makes sure every itinerary can be delivered clearly by the local team.',
                    'cn' => '负责规划 TinggalJalan 的旅行体验、审核路线，并确保本地团队能够清晰执行每套行程。',
                ],
            ],
            [
                'booking-placeholder',
                'Nadia Putri',
                'booking',
                '/images/about/seed/demo-guest-coordinator.jpg',
                ['id' => 'Koordinator Tamu', 'us' => 'Guest Coordinator', 'cn' => '客人协调员'],
                [
                    'id' => 'Menjawab pertanyaan tamu, mencatat titik penjemputan dan kebutuhan peserta, lalu menjelaskan detail sebelum pemesanan dikonfirmasi.',
                    'us' => 'Answers guest questions, records pickup points and group needs, and explains practical details before a booking is confirmed.',
                    'cn' => '解答客人问题，记录接送地点与团队需求，并在确认预订前说明实际行程细节。',
                ],
            ],
            [
                'operations-placeholder',
                'Salsa Maharani',
                'operations',
                '/images/about/seed/demo-operations-coordinator.jpg',
                ['id' => 'Koordinator Operasional', 'us' => 'Operations Coordinator', 'cn' => '运营协调员'],
                [
                    'id' => 'Mencocokkan jadwal dengan kendaraan, jip, pemandu, dan pengemudi, lalu memantau kesiapan menjelang hari perjalanan.',
                    'us' => 'Matches schedules with transport, jeeps, guides, and drivers, then monitors readiness before travel day.',
                    'cn' => '协调日程、车辆、吉普、向导和司机，并在出行日前检查各项准备。',
                ],
            ],
            [
                'field-placeholder',
                'Dimas Wicaksono',
                'field',
                '/images/about/seed/demo-field-coordinator.jpg',
                ['id' => 'Koordinator Lapangan Bromo', 'us' => 'Bromo Field Coordinator', 'cn' => '布罗莫现场协调员'],
                [
                    'id' => 'Membantu koordinasi penjemputan dini hari, titik temu jip, kondisi rute, dan komunikasi dengan mitra lapangan di kawasan Bromo.',
                    'us' => 'Supports early-morning pickups, jeep meeting points, route checks, and communication with field partners around Bromo.',
                    'cn' => '负责清晨接送、吉普集合点、路线状况检查，以及与布罗莫当地伙伴的沟通。',
                ],
            ],
        ];

        foreach ($members as $index => [$key, $name, $category, $portrait, $role, $biography]) {
            $member = TeamMember::firstOrNew(['seed_key' => $key]);
            $isOriginalPlaceholder = $member->portrait === '/images/about/team-placeholder.svg';
            $hasCorruptedMandarin = str_contains((string) data_get($member->role, 'cn'), '?')
                || str_contains((string) data_get($member->biography, 'cn'), '?')
                || str_contains((string) data_get($member->portrait_alt, 'cn'), '?');

            if ($member->exists && ! $member->is_sample) {
                continue;
            }

            if ($member->exists && ! $isOriginalPlaceholder) {
                if ($hasCorruptedMandarin) {
                    $savedRole = $member->role;
                    $savedBiography = $member->biography;
                    $savedPortraitAlt = $member->portrait_alt;
                    $savedRole['cn'] = $role['cn'];
                    $savedBiography['cn'] = $biography['cn'];
                    $savedPortraitAlt['cn'] = "{$name} 资料的示例照片";

                    $member->update([
                        'role' => $savedRole,
                        'biography' => $savedBiography,
                        'portrait_alt' => $savedPortraitAlt,
                    ]);
                }

                continue;
            }

            $member->fill([
                'name' => $name,
                'role' => $role,
                'biography' => $biography,
                'portrait' => $portrait,
                'portrait_alt' => [
                    'id' => "Foto contoh untuk profil {$name}",
                    'us' => "Sample photograph for the {$name} profile",
                    'cn' => "{$name} 资料的示例照片",
                ],
                'category' => $category,
                'location' => 'Malang, East Java',
                'languages' => ['Indonesian', 'English'],
                'is_featured' => $index === 0,
                'is_sample' => true,
                'is_active' => true,
                'sort_order' => $index + 1,
            ])->save();
        }
    }
}
