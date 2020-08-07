<?php

namespace Laurel\LardiTrans\App\Services;


class LardiTransService
{
    private $apiToken;

    public function __construct()
    {
        $this->apiToken = config('laurel.lardi_trans.api_token');
    }

    public function autocompleteCountry(string $searchString, int $queryLimit, string $language)
    {

    }

    public function autocompleteRegion(string $searchString, int $queryLimit, string $language)
    {

    }

    public function autocompleteCity(string $searchString, int $queryLimit, string $language)
    {

    }

    public function sendLardiRequest(array $parameters, string $language = null)
    {

    }
}
