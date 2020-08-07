This package provides tools that allow add to your application location autocomplete. Search works using Lardi Trans API.

# Installing with Composer
You can install this package via Composer with this command
> composer require laurel/lardi-trans

# Installation in Laravel
To install in Laravel you need to modify the `providers` array in `config/app.php` to include the service provider
> 'providers' => [
>
>       //..
>       Laurel\LardiTrans\App\Providers\LardiTransServiceProvider::class,
>
> ],

Then run `composer update`.

After that you need to publish config files. To do this run next command:
> php artisan vendor:publish --tag=config --provider=Laurel\LardiTrans\App\Providers\LardiTransServiceProvider

Specify in the package config file models, fields and relation method for Countries, Regions, Cities and PostalCodes.

# Using
You can get LardiTrans API predictions using next code. As additional parameters, you can set query limit and language:
> $service = new \Laurel\LardiTrans\App\Services\LardiTransService;
> $cities = $service->autocompleteCity('Киев')

For fetching countries use next code. As parameters, you can set an array with regions ids and language:
> $service = new \Laurel\LardiTrans\App\Services\LardiTransService;
> $countries = $service->fetchCountries()

For fetching regions use next code. As parameters, you can set array with countries signs and language:
> $service = new \Laurel\LardiTrans\App\Services\LardiTransService;
> $regions = $service->fetchRegions()

Also, for fetching list with all countries, you can use next console command:
> php artisan laurel/lardi-trans/fetch:countries
