<?php

namespace Database\Seeders;

use App\Models\HeroSlide;
use Illuminate\Database\Seeder;

class HeroSlideSeeder extends Seeder
{
    public function run(): void
    {
        HeroSlide::updateOrCreate(
            ['admin_label' => 'Main Bromo Slide'],
            [
                'desktop_image' => 'admin/hero/hero-bromo.jpg',
                'mobile_image' => 'admin/hero/hero-bromo.jpg',
                'image_alt' => ['us' => 'Mount Bromo sunrise', 'id' => 'Matahari terbit di Gunung Bromo', 'cn' => '布罗莫火山日出'],
                'eyebrow' => ['us' => 'Discover Java', 'id' => 'Jelajahi Jawa', 'cn' => '探索爪哇'],
                'heading' => ['us' => 'Unforgettable Bromo Sunrise', 'id' => 'Matahari Terbit Bromo yang Tak Terlupakan', 'cn' => '难忘的布罗莫日出'],
                'description' => ['us' => 'Experience the majestic beauty of Mount Bromo and its surrounding volcanic landscapes.', 'id' => 'Rasakan keindahan megah Gunung Bromo dan pemandangan vulkanik sekitarnya.', 'cn' => '体验布罗莫火山及其周围火山景观的壮丽之美。'],
                'primary_cta_label' => ['us' => 'Explore Packages', 'id' => 'Jelajahi Paket', 'cn' => '探索套餐'],
                'primary_cta_url' => '/destinations/bromo',
                'secondary_cta_label' => ['us' => 'Contact Us', 'id' => 'Hubungi Kami', 'cn' => '联系我们'],
                'secondary_cta_url' => '/about-us',
                'text_alignment' => 'center',
                'focal_position' => 'center',
                'overlay_strength' => 40,
                'sort_order' => 10,
                'is_active' => true,
            ]
        );

        HeroSlide::updateOrCreate(
            ['admin_label' => 'Tumpak Sewu Waterfall'],
            [
                'desktop_image' => 'admin/hero/destination-tumpak-sewu.jpg',
                'mobile_image' => 'admin/hero/destination-tumpak-sewu.jpg',
                'image_alt' => ['us' => 'Tumpak Sewu Waterfall', 'id' => 'Air Terjun Tumpak Sewu', 'cn' => 'Tumpak Sewu瀑布'],
                'eyebrow' => ['us' => 'Hidden Gems', 'id' => 'Permata Tersembunyi', 'cn' => '隐藏的宝石'],
                'heading' => ['us' => 'Tumpak Sewu Waterfall', 'id' => 'Air Terjun Tumpak Sewu', 'cn' => 'Tumpak Sewu瀑布'],
                'description' => ['us' => 'A thousand streams of water falling from the jungle canyon.', 'id' => 'Seribu aliran air jatuh dari tebing hutan.', 'cn' => '千条水流从丛林峡谷中倾泻而下。'],
                'primary_cta_label' => ['us' => 'View Destination', 'id' => 'Lihat Destinasi', 'cn' => '查看目的地'],
                'primary_cta_url' => '/destinations/tumpak-sewu',
                'text_alignment' => 'left',
                'focal_position' => 'center',
                'overlay_strength' => 30,
                'sort_order' => 20,
                'is_active' => true,
            ]
        );
    }
}
