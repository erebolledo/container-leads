<?php

namespace App\Http\Controllers;

use LaravelGoogleAds\Services\AdWordsService;
use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSessionBuilder;
use Google\AdsApi\AdWords\v201609\cm\CampaignService;
use Google\AdsApi\AdWords\v201609\cm\OrderBy;
use Google\AdsApi\AdWords\v201609\cm\Paging;
use Google\AdsApi\AdWords\v201609\cm\Selector;


class AdwordsController extends Controller
{
    public $token = "GOQx0tWIKsR1s4Pn5HXdZA";
    public $idOauth = "725672751241-9lfk4ao1m5hdiv2k9gop27f7krsfdoqo.apps.googleusercontent.com";
    public $keyOauth = "fCYFMPtdAvIZVIFiVK0jGLr3";

    /** @var AdWordsService */
    protected $adWordsService;
    
    /**
     * @param AdWordsService $adWordsService
     */
    public function __construct(AdWordsService $adWordsService)
    {
        $this->adWordsService = $adWordsService;
    }
    
    public function test(){
        $customerClientId = 'xxx-xxx-xx';
        $campaignService = $this->adWordsService->getService(CampaignService::class, $customerClientId);
        
        return "i'am ok";
    }
}
