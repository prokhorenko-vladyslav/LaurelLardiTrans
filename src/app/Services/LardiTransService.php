<?php

namespace Laurel\LardiTrans\App\Services;

use App\Models\Location\PostCode;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Prophecy\Exception\Doubler\ClassNotFoundException;
use Psy\Exception\TypeErrorException;

/**
 * Adapter for LardiTrans api
 *
 * Class LardiTransService
 * @package Laurel\LardiTrans\App\Services
 */
class LardiTransService
{
    /**
     * Token of the LardiApi
     *
     * @var string
     */
    protected $apiToken;

    /**
     * Url of the LardiApi
     *
     * @var string
     */
    protected $apiUrl;

    /**
     * Classname of the country model
     *
     * @var string
     */
    protected $countryModel;

    /**
     * Classname of the region model (area in the LardiApi)
     *
     * @var string
     */
    protected $regionModel;


    /**
     * Classname of the city model
     *
     * @var string
     */
    protected $cityModel;

    /**
     * Classname of the post code model
     *
     * @var string
     */
    protected $postCodeModel;

    /**
     * LardiTransService constructor.
     */
    public function __construct()
    {
        $this->apiToken = config('laurel.lardi_trans.api_token');
        $this->apiUrl = config('laurel.lardi_trans.api_url');
    }

    /**
     * Returns true, if models for package has been loaded
     *
     * @return bool
     */
    protected function isModelLoaded() : bool
    {
        return $this->models_loaded;
    }

    protected function loadModelsIfNotLoaded()
    {
        if (!$this->isModelLoaded()) {
            $this->loadModels();
        }
    }

    /**
     * Loads names of the model classes from config
     *
     */
    protected function loadModels()
    {
        $this->loadSingleModel('countryModel', config('laurel.lardi_trans.models.country.model'));
        $this->loadSingleModel('regionModel', config('laurel.lardi_trans.models.region.model'));
        $this->loadSingleModel('cityModel', config('laurel.lardi_trans.models.city.model'));
        $this->loadSingleModel('postCodeModel', config('laurel.lardi_trans.models.post_code.model'));
    }

    /**
     * Loads single classname of the model from config
     *
     * @param $modelAlias
     * @param $modelClass
     */
    protected function loadSingleModel($modelAlias, $modelClass)
    {
        if (
            !class_exists($modelClass)
        ) {
            throw new ClassNotFoundException("Class \"{$modelClass}\" has not been found", $modelClass);
        } else {
            $this->$modelAlias = $modelClass;
        }
    }

    /**
     * Method fetches countries from LardiTrans api
     *
     * @param array $signs
     * @param string|null $language
     * @return Collection
     * @throws Exception
     */
    public function fetchCountries(array $signs = [], ?string $language = null) : Collection
    {
        $this->loadModelsIfNotLoaded();
        $language = $language ?? App::getLocale();
        $signField = config('laurel.lardi_trans.models.country.sign_field');

        $countries = collect([]);
        foreach ($signs as $signIndex => $sign) {
            $country = $this->countryModel::where($signField, $sign)->first();
            if ($country) {
                $countries->push($country);
                unset($signs[$signIndex]);
            }
        }

        if (!empty($signs)) {
            $predictions = $this->sendLardiRequest('countries', [
                'signs' => implode(", ", $signs),
                'language' => $language
            ]);
            $predictedCountries = $this->saveCountryPredictions($predictions);
            $countries->merge($predictedCountries);
        }

        return $countries;
    }

    /**
     * Saves predictions and returns collection with countries models
     *
     * @param array $predictions
     * @return Collection
     */
    protected function saveCountryPredictions(array $predictions) : Collection
    {
        $countries = collect([]);

        foreach ($predictions as $prediction) {
            $country= $this->saveSaveSingleCountry($prediction);
            $countries->push($country);
        }

        return $countries;
    }

    /**
     * Saves single prediction in the database
     *
     * @param $prediction
     * @return Model
     */
    protected function saveSaveSingleCountry(array $prediction) : Model
    {
        $signField = config('laurel.lardi_trans.models.country.sign_field');
        $nameField = config('laurel.lardi_trans.models.country.name_field');
        $lardiTransIdField = config('laurel.lardi_trans.models.country.lardi_trans_id_field');
        $country = $this->countryModel::firstOrNew([
            $lardiTransIdField => $prediction['id']
        ]);
        $country->$signField = $prediction['sign'];
        $country->$nameField = $prediction['name'];
        $country->$lardiTransIdField = $prediction['id'];
        $country->save();

        return $country;
    }

    /**
     * Fetches regions from the LardiTrans api
     *
     * @param array $ids
     * @param string|null $language
     * @return Collection
     * @throws Exception
     */
    public function fetchRegions(array $ids = [], ?string $language = null)
    {
        $this->loadModelsIfNotLoaded();
        $language = $language ?? App::getLocale();

        $predictions = $this->sendLardiRequest('areas', [
            'ids' => implode(",", $ids),
            'language' => $language
        ]);

        return $this->saveRegionPredictions($predictions);
    }

    /**
     * Saves predictions in the database and returns collections with regions models
     *
     * @param array $predictions
     * @return Collection
     */
    protected function saveRegionPredictions(array $predictions) : Collection
    {
        $regions = collect([]);

        foreach ($predictions as $prediction) {
            $region = $this->saveSaveSingleRegion($prediction);
            if ($region) {
                $regions->push($region);
            }
        }

        return $regions;
    }

    /**
     * Saves single prediction in the database
     *
     * @param $prediction
     * @return Model|null
     * @throws Exception
     */
    protected function saveSaveSingleRegion(array $prediction) : ?Model
    {
        $nameField = config('laurel.lardi_trans.models.region.name_field');
        $lardiTransIdField = config('laurel.lardi_trans.models.region.lardi_trans_id_field');
        $countryRelationMethod = config('laurel.lardi_trans.models.region.country_relation_method');

        $country = $this->fetchCountries([$prediction['countrySign']])->first();
        if (!$country) {
            return null;
        }

        $region = $this->regionModel::firstOrNew([
            $lardiTransIdField => $prediction['id']
        ]);
        $region->$nameField = $prediction['name'];
        $region->$lardiTransIdField = $prediction['id'];
        $region->$countryRelationMethod()->associate($country);
        $region->save();

        return $region;
    }

    /**
     * Autocomplete for cities. Method fetches predictions from LardiTransApi and returns collection with cities models
     *
     * @param string $query
     * @param int $queryLimit
     * @param string|null $language
     * @return Collection
     * @throws Exception
     */
    public function autocompleteCity(string $query, int $queryLimit = 10, ?string $language = null)
    {
        $this->loadModelsIfNotLoaded();
        $language = $language ?? App::getLocale();

        if (is_numeric($query)) {
            $predictions = $this->sendLardiRequest('towns/by/postcode', [
                'query' => $query,
                'queryLimit' => $queryLimit,
                'language' => $language
            ]);
        } else {
            $predictions = $this->sendLardiRequest('towns', [
                'query' => $query,
                'queryLimit' => $queryLimit,
                'language' => $language
            ]);
        }

        return $this->saveCityPredictions($predictions);
    }

    /**
     * Saves predictions in database and returns collection with cities models
     *
     * @param array $predictions
     * @return Collection
     */
    protected function saveCityPredictions(array $predictions) : Collection
    {
        $cities = collect([]);

        foreach ($predictions as $prediction) {
            $city = $this->saveSaveSingleCity($prediction);
            if ($city) {
                $cities->push($city);
            }
        }

        return $cities;
    }

    /**
     * Saves single prediction of the city
     *
     * @param $prediction
     * @return Model|null
     */
    protected function saveSaveSingleCity($prediction) : ?Model
    {
        $nameField = config('laurel.lardi_trans.models.city.name_field');
        $latitudeField = config('laurel.lardi_trans.models.city.latitude_field');
        $longitudeField = config('laurel.lardi_trans.models.city.longitude_field');
        $lardiTransIdField = config('laurel.lardi_trans.models.city.lardi_trans_id_field');
        $countryRelationMethod = config('laurel.lardi_trans.models.city.country_relation_method');
        $regionRelationMethod = config('laurel.lardi_trans.models.city.region_relation_method');
        $postCodeCityRelationMethod = config('laurel.lardi_trans.models.post_code.city_relation_method');

        $country = $this->fetchCountries([$prediction['countrySign']])->first();
        if (!$country) {
            return null;
        }

        $region = $this->fetchRegions([$prediction['areaId']])->first();

        $city = $this->cityModel::firstOrNew([
            $lardiTransIdField => $prediction['id']
        ]);
        $city->$nameField = $prediction['name'];
        $city->$lardiTransIdField = $prediction['id'];
        $city->$countryRelationMethod()->associate($country);
        if ($region) {
            $city->$regionRelationMethod()->associate($region);
        }
        if (!empty($latitudeField)) {
            $city->$latitudeField = $prediction['lat'];
        }

        if (!empty($longitudeField)) {
            $city->$longitudeField = $prediction['lon'];
        }

        $city->save();

        if (!empty($prediction['postcode']) && is_array($prediction['postcode'])) {
            foreach ($prediction['postcode'] as $postcode) {
                $postCodeObj = new PostCode([
                    'name' => $postcode,
                    'slug' => Str::slug($postcode),
                    'google_id' => microtime(true)
                ]);
                $postCodeObj->$postCodeCityRelationMethod()->associate($city);
                $postCodeObj->save();
            }
        }

        return $city;
    }

    /**
     * Sends request to the LardiTrans api
     *
     * @param string $route
     * @param array $parameters
     * @return array
     * @throws Exception
     */
    public function sendLardiRequest(string $route, array $parameters) : array
    {
        $response = Http::withHeaders([
            'Authorization' => $this->apiToken
        ])->get($this->apiUrl . $route, $parameters);
        Log::info('Lardi request: ' . $route);

        if ($response->ok()) {
            return $response->json();
        } elseif (intval($response->status()) === 429) {
            throw new Exception("Too many requests");
            return [];
        } else {
            Log::error("Lardi trans api has returned " . $response->status(), [
                'api_url' => $this->apiUrl . $route,
                'api_token' => $this->apiToken,
                'parameters' => $parameters,
            ]);
            return [];
        }
    }
}
