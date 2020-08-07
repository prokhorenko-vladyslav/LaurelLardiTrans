<?php
    return [
        'api_token' => '',
        'api_url' => 'https://api.lardi-trans.com/v2/references/',
        'models' => [
            /**
             * Counries
             */
            'country' => [
                'model' => null,
                'name_field' => 'name',
                'sign_field' => 'sign',
                'lardi_trans_id_field' => 'lardi_trans_id'
            ],
            /**
             * Areas
             */
            'region' => [
                'model' => null,
                'name_field' => 'name',
                'lardi_trans_id_field' => 'lardi_trans_id',
                'country_relation_method' => 'country'
            ],
            /**
             * Cities
             */
            'city' => [
                'model' => null,
                'name_field' => 'name',
                'lardi_trans_id_field' => 'lardi_trans_id',
                'latitude_field' => 'latitude',
                'longitude_field' => 'longitude',
                'country_relation_method' => 'country',
                'region_relation_method' => 'region'
            ],
        ]
    ];
