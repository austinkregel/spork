<?php
declare(strict_types=1);
namespace App\Services\Geocoding;

use App\Contracts\Services\GeocodingServiceContract;
use GuzzleHttp\Client;

class GoogleMapsGeocodingService implements GeocodingServiceContract
{
    public function geocode(string $address): array
    {
        $client = new Client(); // GuzzleHttp\Client
        $baseURL = 'https://maps.googleapis.com/maps/api/geocode/json?address=';
        $addressURL = urlencode($address).'&key='.env('GOOGLE_MAPS_API_KEY');
        $url = $baseURL.$addressURL;
        $response = cache()->remember($address, now()->addDay(), fn () => $client->request('GET', $url)->getBody()->getContents());
        $response = json_decode($response);

        if ($response->status === 'ZERO_RESULTS') {
            return ['latitude' => null, 'longitude' => null, 'address' => $address];
        }

        try {
            $latitude = $response->results[0]->geometry->location->lat;
            $longitude = $response->results[0]->geometry->location->lng;

            $address = $response->results[0]->formatted_address;

            return compact('latitude', 'longitude', 'address');
        } catch (\Throwable $e) {
            info('Failed to gecode '.$address, [
                'address' => $address,
                'exception' => $e,
            ]);

            dd($response, $address);
        }
    }
}
