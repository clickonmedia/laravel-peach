<?php

use Clickonmedia\Peach\Peach;
use Orchestra\Testbench\TestCase;
use Clickonmedia\Peach\PeachServiceProvider;

class PeachTest extends TestCase
{
    protected $peach;
    protected $randomName;

    protected function setUp(): void
    {
        parent::setUp();
        $this->peach = new Peach();
        $this->randomName = 'Test-' . (new DateTime())->format('d-m H:i:s');
    }

    protected function getPackageProviders($app): array
    {
        return [
            PeachServiceProvider::class,
        ];
    }

    /** @test */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(Peach::class, new Peach());
    }

    /** @test */
    public function it_can_display_list_of_all_campaigns()
    {
        $response = $this->peach->getCampaigns();

        $this->assertEquals(200, $response->status());
        $this->assertIsArray($response->json());
    }

    /** @test */
    public function it_can_create_a_campaign()
    {
        $response = $this->peach->createCampaign($this->randomName, $this->randomName, $this->randomName);

        $this->assertEquals(201, $response->status());
        $this->assertIsInt($response->json());

        // We should delete it later (already tested)
        $this->peach->removeCampaign($response->json());
    }

    /** @test */
    public function it_can_remove_a_campaign()
    {
        $campaignId = $this->peach->createCampaign($this->randomName, $this->randomName, $this->randomName)->json();
        sleep(3); // NOTE: PEACH API IS SLOW.

        $campaigns = $this->peach->getCampaigns()->json()['data']['campaigns'];

        $result = array_search($campaignId, array_column($campaigns, 'id'));

        $this->assertNotSame($result, false);

        $response = $this->peach->removeCampaign($campaignId);
        $this->assertEquals(200, $response->status());
        sleep(3); // NOTE: PEACH API IS SLOW.

        $newCampaigns = $this->peach->getCampaigns()->json()['data']['campaigns'];

        $newResult = array_search($campaignId, array_column($newCampaigns, 'id'));

        $this->assertSame($newResult, false);
    }

    /** @test */
    public function it_can_get_information_about_a_campaign()
    {
        $campaignId = $this->peach->createCampaign($ref = 'Campaign Info', $ad = 'Info', $brand = 'Campaign Info')->json();
        sleep(3); // NOTE: PEACH API IS SLOW.

        $response = $this->peach->getCampaign($campaignId);
        sleep(3); // NOTE: PEACH API IS SLOW.

        $this->assertEquals($campaignId, $response->json()['id']);
        $this->assertEquals($ref, $response->json()['reference']);
        $this->assertEquals($ad, $response->json()['advertiser']);
        $this->assertEquals($brand, $response->json()['brand']);

        // We should delete it later (already tested)
        $this->peach->removeCampaign($campaignId);
    }

    /** @test */
    public function it_can_display_list_of_adds_for_the_campaign()
    {
        $campaignId = $this->peach->createCampaign($this->randomName, $this->randomName, $this->randomName)->json();
        sleep(3);
        $ad1 = $this->peach->createAd($campaignId, 'AD One', 5)->json()[0];
        $ad2 = $this->peach->createAd($campaignId, 'AD Two', 5)->json()[0];
        sleep(3);

        $ads = $this->peach->getAds($campaignId)->json()['data']['ads'];

        $this->assertEquals($ad1['title'], $ads[1]['title']);
        $this->assertEquals($ad2['title'], $ads[0]['title']);

        $this->peach->removeAd($ad1['id']);
        $this->peach->removeAd($ad2['id']);
        $this->peach->removeCampaign($campaignId);
    }

    /** @test */
    public function it_can_create_an_ad_for_the_campaign()
    {
        $campaignId = $this->peach->createCampaign($this->randomName, $this->randomName, $this->randomName)->json();
        $ad = $this->peach->createAd($campaignId, $adName = 'Created Ad', 8)->json()[0];

        $this->assertEquals($campaignId, $ad['campaignId']);
        $this->assertEquals($adName, $ad['title']);

        $this->peach->removeAd($ad['id']);
        $this->peach->removeCampaign($campaignId);
    }

    /** @test */
    public function it_can_delete_an_ad()
    {
        $campaignId = $this->peach->createCampaign($this->randomName, $this->randomName, $this->randomName)->json();
        sleep(3);
        $ad = $this->peach->createAd($campaignId, 'Must Delete Ad', 5)->json()[0];
        $response = $this->peach->removeAd($ad['id']);
        $this->assertEquals(200, $response->status());

        $this->peach->removeCampaign($campaignId);
    }

    /** @test */
    public function it_can_get_information_about_an_ad()
    {
        $campaignId = $this->peach->createCampaign($this->randomName, $this->randomName, $this->randomName)->json();
        sleep(3);
        $ad = $this->peach->createAd($campaignId, 'Example Ad', 3)->json()[0];
        sleep(3);
        $adInfo = $this->peach->getAd($ad['id'])->json();

        $this->assertEquals($ad['id'], $adInfo['id']);

        $this->peach->removeAd($ad['id']);
        $this->peach->removeCampaign($campaignId);
    }

    /** @test */
    public function it_can_create_and_read_and_remove_an_asset()
    {
        $campaignId = $this->peach->createCampaign($this->randomName, $this->randomName, $this->randomName)->json();
        sleep(3);
        $ad = $this->peach->createAd($campaignId, $name = 'small.mp4', 5)->json()[0];
        sleep(3);
        $asset = $this->peach->createAsset($ad['id'], $name, 383631, 'http://techslides.com/demos/sample-videos/small.mp4');

        $this->assertEquals(200, $asset->status());

        sleep(125); // BECAUSE WE CANNOT REMOVE AN ASSET WHILE IT IS PROCESSING.

        $response = $this->peach->removeAsset($asset->json()[0]['id']);

        $this->assertEquals(200, $response->status());

        $this->peach->removeAd($ad['id']);
        $this->peach->removeCampaign($campaignId);
    }
}
