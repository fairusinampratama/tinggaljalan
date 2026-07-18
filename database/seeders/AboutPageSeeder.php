<?php

namespace Database\Seeders;

use App\Models\AboutPage;
use Illuminate\Database\Seeder;

class AboutPageSeeder extends Seeder
{
    public function run(): void
    {
        $attributes = [
            'is_published' => true,
            'section_visibility' => ['story' => true, 'values' => true, 'milestones' => true, 'team' => true, 'workflow' => true, 'profile' => true, 'cta' => true],
            'hero' => [
                'eyebrow' => $this->l('Tentang TinggalJalan', 'About TinggalJalan', '关于 TinggalJalan'),
                'title' => $this->l('Dari Malang, kami menyiapkan perjalanan sampai detail terakhir.', 'From Malang, we prepare every journey down to the last detail.', '每一段旅程背后的本地团队。'),
                'intro' => $this->l(
                    'TinggalJalan Indonesia Tours adalah tim perjalanan di Malang yang mengatur perjalanan privat dan grup kecil ke Bromo, Tumpak Sewu, serta rute pilihan di Indonesia. Kami menangani penjemputan, kendaraan, jip 4x4, pemandu, dan komunikasi perjalanan dalam satu koordinasi.',
                    'TinggalJalan Indonesia Tours is a Malang travel team arranging private and small-group journeys to Bromo, Tumpak Sewu, and selected routes across Indonesia. We coordinate pickup, transport, 4x4 jeeps, guides, and trip communication in one place.',
                    'TinggalJalan 是一支扎根玛琅的旅行团队，通过清晰的行程规划和本地直接协调，帮助旅行者探索布罗莫、东爪哇及印度尼西亚各地。'
                ),
                'image' => '/images/about/seed/bromo-jeep-convoy.jpg',
                'image_alt' => $this->l('Konvoi jip di kawasan Gunung Bromo, Jawa Timur', 'A convoy of jeeps in the Mount Bromo area, East Java', 'TinggalJalan 团队照片占位图'),
                'facts' => [
                    ['icon' => 'map-pin', 'label' => $this->l('Berbasis di', 'Based in', '所在地'), 'value' => $this->l('Malang, Jawa Timur', 'Malang, East Java', '东爪哇玛琅')],
                    ['icon' => 'users', 'label' => $this->l('Pendekatan', 'Approach', '服务方式'), 'value' => $this->l('Koordinasi tim lokal', 'Local team coordination', '本地团队协调')],
                    ['icon' => 'compass', 'label' => $this->l('Perjalanan', 'Journeys', '旅行类型'), 'value' => $this->l('Pengalaman privat Indonesia', 'Private Indonesia experiences', '印度尼西亚私人旅行')],
                ],
            ],
            'story' => [
                'eyebrow' => $this->l('Siapa kami', 'Who we are', '关于我们'),
                'title' => $this->l('Perjalanan yang baik dimulai jauh sebelum mesin jip menyala.', 'A good journey begins long before the jeep engine starts.', '旅行规划应当清晰而有温度。'),
                'body' => $this->l(
                    "Banyak perjalanan Bromo dimulai ketika kota masih tidur. Karena itu, pekerjaan kami sudah berjalan sejak sebelumnya: memastikan lokasi penjemputan di hotel, stasiun, atau bandara; mencocokkan kendaraan dan jip; memeriksa rute; serta menyampaikan apa yang perlu dibawa.\n\nDari basis kami di Malang, TinggalJalan menghubungkan tamu dengan pengemudi, pemandu, dan mitra lokal. Kami juga membantu menyusun perjalanan ke Tumpak Sewu dan destinasi Indonesia lainnya dengan detail yang jelas dan satu jalur komunikasi yang mudah dihubungi.",
                    "Many Bromo journeys begin while the city is still asleep. Our work starts earlier: confirming pickup at a hotel, station, or airport; matching transport and jeeps; checking the route; and explaining what guests should bring.\n\nFrom our Malang base, TinggalJalan connects guests with drivers, guides, and local partners. We also arrange journeys to Tumpak Sewu and other Indonesian destinations with clear details and one dependable line of communication.",
                    "TinggalJalan 在旅程前和旅程中为旅行者提供实用的本地信息。从选择路线到确认接送、车辆、向导和时间安排，我们的团队让旅程的每个环节都更容易理解。\n\n我们以布罗莫和东爪哇为重点，并持续围绕本地协调、坦诚沟通和每位旅行者的需求打造旅行体验。"
                ),
                'image' => '/images/about/seed/bromo-travelers.jpg',
                'image_alt' => $this->l('Wisatawan dan jip di kawasan Gunung Bromo', 'Travelers with a jeep in the Mount Bromo area', 'TinggalJalan 运营照片占位图'),
                'quote' => $this->l('Tamu seharusnya tahu siapa yang menjemput, ke mana mereka pergi, dan siapa yang bisa dihubungi.', 'Guests should know who is picking them up, where they are going, and who they can contact.', '我们希望旅行者在出发前真正了解自己的旅程。'),
                'quote_author' => 'TinggalJalan Team',
            ],
            'values_section' => [
                'eyebrow' => $this->l('Cara kami bekerja', 'What guides us', '我们的原则'),
                'title' => $this->l('Nilai yang terlihat dalam tindakan.', 'Values expressed through action.', '以行动体现我们的价值。'),
                'intro' => $this->l('Prinsip sederhana yang membentuk cara kami merencanakan dan mendampingi perjalanan.', 'Simple principles that shape how we plan and support every journey.', '这些简单原则塑造了我们规划和支持每段旅程的方式。'),
                'items' => [
                    ['icon' => 'circle-check', 'title' => $this->l('Jelas sebelum berangkat', 'Clear before you go', '出发前清楚明白'), 'text' => $this->l('Kami menjelaskan rute, penjemputan, jadwal, dan estimasi biaya agar wisatawan dapat mengambil keputusan dengan yakin.', 'We explain routes, pickup, schedules, and estimated costs so travelers can make informed decisions.', '我们会说明路线、接送、时间和预估费用，让旅行者做出明智决定。')],
                    ['icon' => 'map-pin', 'title' => $this->l('Berbasis pengetahuan lokal', 'Built around local knowledge', '扎根本地经验'), 'text' => $this->l('Koordinasi lokal membantu kami merespons kondisi destinasi dan kebutuhan praktis perjalanan.', 'Local coordination helps us respond to destination conditions and practical travel needs.', '本地协调让我们能够应对目的地状况和实际旅行需求。')],
                    ['icon' => 'heart', 'title' => $this->l('Dukungan manusia', 'Human support throughout', '全程真人支持'), 'text' => $this->l('Wisatawan dapat berkomunikasi langsung dengan tim sebelum pemesanan dan selama perjalanan.', 'Travelers can communicate directly with the team before booking and during their journey.', '旅行者在预订前和旅程中都可以直接与团队沟通。')],
                ],
            ],
            'team_section' => [
                'eyebrow' => $this->l('Tim kami', 'Meet the team', '认识团队'),
                'title' => $this->l('Orang-orang di balik perjalananmu.', 'The people behind your trip.', '旅程背后的伙伴。'),
                'intro' => $this->l('Kenali orang yang membantu merencanakan, mengonfirmasi, dan menjalankan perjalanan TinggalJalan.', 'Meet the people who plan, confirm, and operate TinggalJalan journeys.', '认识负责规划、确认和执行 TinggalJalan 旅程的团队成员。'),
                'sample_label' => $this->l('Contoh — ganti di admin', 'Sample — replace in admin', '示例—请在后台替换'),
                'category_labels' => [
                    'leadership' => $this->l('Pendiri & kepemimpinan', 'Founder & leadership', '创始人与管理'),
                    'booking' => $this->l('Pemesanan & komunikasi', 'Booking & communication', '预订与沟通'),
                    'operations' => $this->l('Operasional perjalanan', 'Trip operations', '旅行运营'),
                    'field' => $this->l('Mitra lapangan', 'Field partners', '当地合作伙伴'),
                ],
            ],
            'milestones_section' => [
                'eyebrow' => $this->l('Perjalanan kami', 'Our journey', '我们的历程'),
                'title' => $this->l('Dibangun satu perjalanan demi satu perjalanan.', 'Built one journey at a time.', '从一次次旅程中成长。'),
                'intro' => $this->l('Dari operasi Bromo hingga rute yang semakin luas, setiap tahap tumbuh dari kebutuhan nyata tamu dan kerja sama dengan mitra lokal.', 'From Bromo operations to a wider route catalogue, each chapter grew from real guest needs and collaboration with local partners.', '从布罗莫运营到更丰富的路线，每个阶段都源于客人的实际需求以及与本地伙伴的合作。'),
                'sample_label' => $this->l('Contoh — ganti di admin', 'Sample — replace in admin', '示例—请在后台替换'),
            ],
            'workflow_section' => [
                'eyebrow' => $this->l('Di balik pemesanan', 'Behind every booking', '预订背后'),
                'title' => $this->l('Bagaimana tim kami bekerja.', 'How our team works.', '我们的团队如何协作。'),
                'intro' => $this->l('Dari pertanyaan pertama hingga hari perjalanan, setiap tahap memiliki orang yang bertanggung jawab.', 'From the first question to the travel day, every stage has a person responsible for it.', '从第一次咨询到出行当天，每个阶段都有专人负责。'),
                'steps' => [
                    ['icon' => 'message-circle', 'title' => $this->l('Ceritakan rencanamu', 'Tell us your plan', '告诉我们你的计划'), 'text' => $this->l('Pilih paket atau bagikan destinasi, tanggal, jumlah peserta, dan preferensimu.', 'Choose a package or share your destination, date, group size, and preferences.', '选择套餐，或告诉我们目的地、日期、人数和偏好。')],
                    ['icon' => 'search', 'title' => $this->l('Kami memeriksa detail', 'We check the details', '我们核对细节'), 'text' => $this->l('Tim memeriksa ketersediaan, penjemputan, kendaraan, pemandu, dan jadwal lokal.', 'Our team checks availability, pickup, vehicles, guides, and local schedules.', '团队会确认可用性、接送、车辆、向导和当地时间安排。')],
                    ['icon' => 'circle-check', 'title' => $this->l('Perjalanan dikonfirmasi', 'Your trip is confirmed', '确认旅程'), 'text' => $this->l('Kamu menerima informasi rute dan harga yang jelas sebelum pembayaran diminta.', 'You receive clear itinerary and pricing information before payment is requested.', '付款前，你会收到清晰的行程和价格信息。')],
                    ['icon' => 'users', 'title' => $this->l('Tim lokal bersiap', 'The local team prepares', '本地团队准备'), 'text' => $this->l('Kami mengoordinasikan orang dan kebutuhan operasional untuk perjalananmu.', 'We coordinate the people and operational details involved in your journey.', '我们协调旅程所需的人员和运营细节。')],
                    ['icon' => 'headphones', 'title' => $this->l('Dukungan saat perjalanan', 'Support during the trip', '旅途中支持'), 'text' => $this->l('Tim tetap tersedia untuk pembaruan penjemputan dan koordinasi pada hari perjalanan.', 'The team remains available for pickup updates and travel-day coordination.', '团队会继续提供接送更新和出行当天的协调支持。')],
                ],
            ],
            'profile_section' => [
                'eyebrow' => $this->l('Profil perusahaan', 'Company profile', '公司简介'),
                'title' => $this->l('Operasi lokal yang dapat kamu hubungi.', 'A local operation you can reach.', '可以直接联系的本地团队。'),
                'intro' => $this->l('Informasi praktis tentang siapa kami, tempat kami beroperasi, dan cara menghubungi tim.', 'Practical information about who we are, where we operate, and how to reach the team.', '关于我们、服务区域以及联系方式的实用信息。'),
                'legal_name_label' => $this->l('Nama resmi', 'Legal name', '法定名称'),
                'founding_year_label' => $this->l('Tahun berdiri', 'Founded', '成立年份'),
                'registration_label' => $this->l('Registrasi', 'Registration', '注册信息'),
                'legal_name' => 'PT. TINGGAL JALAN AJA', 'founding_year' => null, 'registration' => null,
                'show_legal_name' => true, 'show_founding_year' => false, 'show_registration' => false,
                'operating_description' => $this->l('Beroperasi dari Malang untuk perjalanan privat dan grup kecil, dengan koordinasi penjemputan, transportasi, jip 4x4, pemandu lokal, serta dukungan perjalanan ke Bromo, Tumpak Sewu, Jawa Timur, dan rute Indonesia yang tersedia.', 'Operating from Malang for private and small-group travel, coordinating pickup, transport, 4x4 jeeps, local guides, and trip support for Bromo, Tumpak Sewu, East Java, and the available Indonesia routes.', '总部位于玛琅，重点服务布罗莫、东爪哇以及网站所列的印度尼西亚路线。'),
            ],
            'cta' => [
                'title' => $this->l('Sekarang kamu sudah mengenal kami, ingin pergi ke mana?', 'Now that you know us, where would you like to go?', '现在你已经了解我们，下一站想去哪里？'),
                'text' => $this->l('Jelajahi perjalanan yang tersedia atau ceritakan rencana perjalananmu kepada tim lokal kami.', 'Explore available trips or tell our local team about the journey you have in mind.', '浏览现有行程，或告诉我们的本地团队你心中的旅行计划。'),
                'primary_label' => $this->l('Jelajahi perjalanan', 'Explore trips', '探索行程'), 'primary_url' => '/routes',
                'secondary_label' => $this->l('Chat dengan tim', 'Chat with our team', '与团队沟通'), 'secondary_url' => 'whatsapp',
            ],
            'seo' => [
                'title' => $this->l('Tentang TinggalJalan | Tim Perjalanan Lokal Indonesia', 'About TinggalJalan | Local Indonesia Travel Team', '关于 TinggalJalan | 印度尼西亚本地旅行团队'),
                'description' => $this->l('Kenali TinggalJalan, tim perjalanan berbasis di Malang yang membantu merencanakan perjalanan privat ke Bromo, Jawa Timur, dan destinasi Indonesia.', 'Meet TinggalJalan, a Malang-based local team planning private trips to Bromo, East Java, and destinations across Indonesia.', '了解 TinggalJalan——一支扎根玛琅、规划布罗莫、东爪哇及印度尼西亚私人旅行的本地团队。'),
                'image' => '/images/about/seed/bromo-jeep-convoy.jpg',
            ],
        ];

        $page = AboutPage::firstOrNew(['seed_key' => 'default-about-page']);
        $heroImage = data_get($page->hero, 'image');
        $isUntouchedPreview = ! $page->exists || in_array($heroImage, [null, '/images/about/team-hero-placeholder.svg'], true);

        if ($isUntouchedPreview) {
            $page->fill($attributes)->save();
        } elseif (str_contains((string) data_get($page->milestones_section, 'intro.cn'), '?')) {
            $milestonesSection = $page->milestones_section;
            $milestonesSection['intro']['cn'] = data_get($attributes, 'milestones_section.intro.cn');
            $page->update(['milestones_section' => $milestonesSection]);
        }
    }

    private function l(string $id, string $us, string $cn): array
    {
        return compact('id', 'us', 'cn');
    }
}
