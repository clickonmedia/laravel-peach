<?php

namespace Clickonmedia\Peach;

use Illuminate\Support\Facades\Http;

class Peach
{
    protected $http;
    protected $baseUrl;

    public function __construct()
    {
        $this->http = Http::withToken(config('peach.api_key'))->asJson();
        $this->baseUrl = config('peach.base_url');
    }

    public function getCampaigns()
    {
        return $this->http->get("{$this->baseUrl}/campaigns");
    }

    public function createCampaign(string $reference, string $advertiser, string $brand)
    {
        return $this->http->post("{$this->baseUrl}/campaigns", compact('reference', 'advertiser', 'brand'));
    }

    public function removeCampaign(int $campaignId)
    {
        return $this->http->delete("{$this->baseUrl}/campaigns/{$campaignId}");
    }

    public function getCampaign(int $campaignId)
    {
        return $this->http->get("{$this->baseUrl}/campaigns/{$campaignId}");
    }

    public function getAds(int $campaignId)
    {
        return $this->http->get("{$this->baseUrl}/campaigns/{$campaignId}/ads");
    }

    public function createAd(int $campaignId, string $name, int $seconds)
    {
        abort_if($seconds > 180, 406, 'Duration cannot be more than 180 seconds or 3 minutes.');

        $mins = floor($seconds / 60 % 60);
        $secs = floor($seconds % 60);

        $duration = sprintf('%02d:%02d:00', $mins, $secs);

        return $this->http
            ->post("{$this->baseUrl}/campaigns/{$campaignId}/ads", [
                [
                    'reference'  => [
                        'id'   => $name, // REQUIRED: unique id for each ad
                        'type' => 'Clock ID', // REQUIRED: "Clock ID" is for GB
                    ],
                    'duration'   => $duration, // REQUIRED: format: 00:00:00
                    'title'      => $name,  // OPTIONAL: but we should pass it
                    'adType'     => '16:9 Digital ad', // REQUIRED: "16:9 Digital ad" is for MP4
                    'regionCode' => config('peach.region_code'),// REQUIRED
                ],
            ]);
    }

    public function getAd(int $adId)
    {
        return $this->http->get("{$this->baseUrl}/ads/{$adId}");
    }

    public function removeAd(int $adId)
    {
        return $this->http->delete("{$this->baseUrl}/ads/{$adId}");
    }

    public function createAsset(int $adId, string $name, int $size, string $url, $mediaType = 'video')
    {
        return $this->http
            ->post("{$this->baseUrl}/ads/{$adId}/assets",
                [
                    [
                        'name'      => $name, // REQUIRED: unique name for each asset.
                        'size'      => $size, // REQUIRED: size of the asset.
                        'url'       => $url,  // OPTIONAL: pass the S3 url.
                        'mediaType' => $mediaType // REQUIRED: "video"
                    ],
                ]);
    }

    public function getAssetInfo($assetId)
    {
        return $this->http->get("{$this->baseUrl}/assets/{$assetId}/inspect");
    }

    public function removeAsset($assetId)
    {
        return $this->http->delete("{$this->baseUrl}/assets/{$assetId}");
    }
}
