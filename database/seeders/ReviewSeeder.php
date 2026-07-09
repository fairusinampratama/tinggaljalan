<?php

namespace Database\Seeders;

use App\Models\Review;
use Database\Seeders\Concerns\LoadsPrototypeData;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    use LoadsPrototypeData;

    public function run(): void
    {
        Review::truncate();

        $reviews = [
            [
                'name' => 'Sarah Jenkins',
                'origin' => ['id' => 'Amerika Serikat', 'us' => 'United States', 'cn' => '美国'],
                'rating' => 5,
                'review_count' => 12,
                'source' => ['id' => 'Ulasan Turis', 'us' => 'Traveler reviews', 'cn' => '旅客点评'],
                'text' => [
                    'id' => 'Perjalanan Bromo yang sangat luar biasa. Pemandu sangat tepat waktu dan kami mendapat spot sunrise terbaik.',
                    'us' => 'An absolutely incredible Bromo trip. The guide was perfectly on time and we got the best sunrise spot.',
                    'cn' => '布罗莫之旅绝对令人难以置信。导游非常准时，我们得到了最好的日出观看点。'
                ],
            ],
            [
                'name' => 'Budi Santoso',
                'origin' => ['id' => 'Jakarta, Indonesia', 'us' => 'Jakarta, Indonesia', 'cn' => '印尼雅加达'],
                'rating' => 5,
                'review_count' => 5,
                'source' => ['id' => 'Ulasan Turis', 'us' => 'Traveler reviews', 'cn' => '旅客点评'],
                'text' => [
                    'id' => 'Pelayanan sangat profesional. Tumpak Sewu sangat menantang tapi aman berkat guide yang berpengalaman.',
                    'us' => 'Very professional service. Tumpak Sewu was challenging but safe thanks to the experienced guide.',
                    'cn' => '非常专业的服务。赛武瀑布很有挑战性，但得益于经验丰富的导游，非常安全。'
                ],
            ],
            [
                'name' => 'Mei Ling',
                'origin' => ['id' => 'Singapura', 'us' => 'Singapore', 'cn' => '新加坡'],
                'rating' => 5,
                'review_count' => 8,
                'source' => ['id' => 'Ulasan Turis', 'us' => 'Traveler reviews', 'cn' => '旅客点评'],
                'text' => [
                    'id' => 'Mobilnya bersih dan nyaman. Supir juga ramah dan tahu tempat makan lokal yang enak di Jogja.',
                    'us' => 'The car was clean and comfortable. The driver was also friendly and knew great local places to eat in Jogja.',
                    'cn' => '车子干净舒适。司机也很友好，知道日惹当地很棒的吃饭地方。'
                ],
            ],
            [
                'name' => 'David Mueller',
                'origin' => ['id' => 'Jerman', 'us' => 'Germany', 'cn' => '德国'],
                'rating' => 4.5,
                'review_count' => 3,
                'source' => ['id' => 'Ulasan Turis', 'us' => 'Traveler reviews', 'cn' => '旅客点评'],
                'text' => [
                    'id' => 'Kawah Ijen sangat sepadan dengan perjalanan malamnya! Terima kasih sudah menyediakan masker gas dan senter yang bagus.',
                    'us' => 'Ijen Crater was completely worth the night trek! Thanks for providing good gas masks and flashlights.',
                    'cn' => '宜珍火山绝对值得夜间跋涉！感谢提供良好的防毒面具和手电筒。'
                ],
            ],
            [
                'name' => 'Aisha Rahman',
                'origin' => ['id' => 'Malaysia', 'us' => 'Malaysia', 'cn' => '马来西亚'],
                'rating' => 5,
                'review_count' => 15,
                'source' => ['id' => 'Ulasan Turis', 'us' => 'Traveler reviews', 'cn' => '旅客点评'],
                'text' => [
                    'id' => 'Pengalaman Kawah Putih yang sangat santai. Anak-anak sangat menikmati perjalanannya, agen travel yang ramah keluarga.',
                    'us' => 'Very relaxing Kawah Putih experience. The kids really enjoyed the trip, very family-friendly travel agent.',
                    'cn' => '非常放松的白兰地火山体验。孩子们非常喜欢这次旅行，非常适合家庭的旅行社。'
                ],
            ],
            [
                'name' => 'John Smith',
                'origin' => ['id' => 'Australia', 'us' => 'Australia', 'cn' => '澳大利亚'],
                'rating' => 5,
                'review_count' => 22,
                'source' => ['id' => 'Ulasan Turis', 'us' => 'Traveler reviews', 'cn' => '旅客点评'],
                'text' => [
                    'id' => 'Komunikasi sangat mudah dari awal pemesanan sampai akhir tur. Harga transparan tanpa biaya tersembunyi.',
                    'us' => 'Communication was very easy from booking until the end of the tour. Transparent pricing with no hidden fees.',
                    'cn' => '从预订到行程结束，沟通非常顺畅。价格透明，没有隐藏费用。'
                ],
            ],
        ];

        foreach ($reviews as $index => $reviewData) {
            Review::updateOrCreate(
                ['name' => $reviewData['name']],
                [
                    'origin' => $this->localized($reviewData['origin']),
                    'rating' => $reviewData['rating'],
                    'review_count' => $reviewData['review_count'],
                    'source' => $this->localized($reviewData['source']),
                    'text' => $this->localized($reviewData['text']),
                    'is_active' => true,
                    'is_featured' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}
