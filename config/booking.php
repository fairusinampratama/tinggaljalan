<?php

return [
    'minimum_guests' => 1,
    'maximum_guests' => 999,
    'large_group_threshold' => 10,
    'traveler_type_options' => [
        [
            'value' => 'local',
            'label' => [
                'id' => 'Warga Negara Indonesia',
                'us' => 'Indonesian Citizen',
                'cn' => '印尼公民',
            ],
        ],
        [
            'value' => 'international',
            'label' => [
                'id' => 'Warga Negara Asing',
                'us' => 'International Traveler',
                'cn' => '国际旅客',
            ],
        ],
    ],
];