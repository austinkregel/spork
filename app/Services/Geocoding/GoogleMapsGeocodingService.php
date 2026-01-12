<?php

declare(strict_types=1);

namespace App\Services\Geocoding;

use App\Contracts\Services\GeocodingServiceContract;
use GuzzleHttp\Client;

class GoogleMapsGeocodingService implements GeocodingServiceContract
{
    protected Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client;
    }

    public function geocode(string $address): array
    {
        $api_key = config('services.google_maps.api_key');
        $base_url = config('services.google_maps.geocode_base_url', 'https://maps.googleapis.com/maps/api/geocode/json');

        if (empty($api_key)) {
            throw new \RuntimeException('Google Maps API key is not configured');
        }

        $url = sprintf(
            '%s?address=%s&key=%s',
            $base_url,
            urlencode($address),
            $api_key
        );

        $response = $this->client->request('GET', $url)->getBody()->getContents();
        $response = json_decode($response);

        if ($response->status === 'ZERO_RESULTS') {
            return ['latitude' => null, 'longitude' => null, 'address' => $address];
        }

        try {
            $latitude = $response->results[0]->geometry->location->lat;
            $longitude = $response->results[0]->geometry->location->lng;
            $formatted_address = $response->results[0]->formatted_address;

            return [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'address' => $formatted_address,
            ];
        } catch (\Throwable $e) {
            info('Failed to geocode '.$address, [
                'address' => $address,
                'exception' => $e->getMessage(),
            ]);

            return ['latitude' => null, 'longitude' => null, 'address' => $address];
        }
    }

    public function findBusinesses(string $name): array
    {
        $api_key = config('services.google_maps.api_key');
        $base_url = config('services.google_maps.places_base_url', 'https://maps.googleapis.com/maps/api/place/textsearch/json');
        $radius = config('services.google_maps.business_search_radius', 321869);
        $location = config('services.google_maps.business_search_location', 'michigan');
        $cache_ttl_days = config('services.google_maps.business_search_cache_ttl_days', 1);

        if (empty($api_key)) {
            throw new \RuntimeException('Google Maps API key is not configured');
        }

        $base_query = sprintf(
            '?fields=formatted_address,name,rating,opening_hours,geometry&query=%s&inputtype=textquery&radius=%s&location=%s',
            urlencode($name),
            $radius,
            urlencode($location)
        );
        $address_url = $base_url.$base_query.'&key='.$api_key;
        $result_set = [];

        do {
            $response = cache()->remember(
                $address_url,
                now()->addDays($cache_ttl_days),
                fn () => $this->client->request('GET', $address_url)->getBody()->getContents()
            );
            $response = json_decode($response);

            if ($response->status === 'ZERO_RESULTS') {
                return [];
            }

            foreach ($response->results as $result) {
                $result_set[] = $result;
            }

            if (isset($response->next_page_token)) {
                $address_url = sprintf('%s?key=%s&pagetoken=%s', $base_url, $api_key, $response->next_page_token);
            }
        } while (isset($response->next_page_token));

        return $result_set;
    }
}
