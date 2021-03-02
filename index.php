<?php

require_once "vendor/autoload.php";
use \GuzzleHttp\Client;


class ascolour{
    
    private $client;
    private $domainUrl = 'https://www.ascolour.com.au/';

    public function __construct(){
        $this->client = new Client(['base_uri' => $this->domainUrl]);
    }


    public function main($targetUrl){
        $headers['accept'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9';
        $headers['accept-encoding'] = 'gzip, deflate, br';
        $headers['accept-language'] = 'en-US,en;q=0.9';
        $headers['sec-ch-ua-mobile'] = '?0';
        $headers['sec-fetch-dest'] = 'document';
        $headers['sec-fetch-mode'] = 'navigate';
        $headers['sec-fetch-site'] = 'none';
        $headers['sec-fetch-user'] = '?1';
        $headers['upgrade-insecure-requests'] = '1';
        //change user agent periodically
        $headers['user-agent'] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.182 Safari/537.36';

        $response = $this->client->request('GET', $targetUrl,
                                            [
                                                'headers' => $headers
                                            ],
                                            //proxy here
                                        );

        $data = $response->getBody();

        preg_match_all('/property="og:image.*?products\/(\d+)/i', $data, $productID);
        preg_match_all('/property="og:image.*?.*?s-(.*?)\//i', $data, $storeHash);

        $productID = $productID[1][0];
        $storeHash = $storeHash[1][0];


        //send the request for to get variation data
        $headers['accept'] = 'application/json, text/plain, */*';
        $headers['accept-encoding'] = 'gzip, deflate, br';
        $headers['accept-language'] = 'en-US,en;q=0.9';
        $headers['origin'] = 'https://www.ascolour.com.au';
        $headers['referer'] = 'https://www.ascolour.com.au/';
        $headers['sec-ch-ua-mobile'] = '?0';
        $headers['sec-fetch-dest'] = 'empty';
        $headers['sec-fetch-mode'] = 'cors';
        $headers['sec-fetch-site'] = 'cross-site';
        $headers['store-hash'] = $storeHash;
        $headers['user-agent'] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.182 Safari/537.36';
        //change user agent periodically
        $variationUrl = 'https://u43tbzpa7g.execute-api.ap-southeast-2.amazonaws.com/production//catalog/products?product_ids='.$productID;

        $response = $this->client->request('GET', $variationUrl,
                                            [
                                                'headers' => $headers
                                            ]
                                        );
        $res = json_decode($response->getBody(), 1);
        
        $variationDatas = [];
        foreach($res[0]['variants'] as $variant){
            $color = $variant['option_values'][0]['label'];
            $size = $variant['option_values'][1]['label'];
            $inventoryLevel = $variant['inventory_level'];
            if($inventoryLevel > 100){
                $inventoryLevel = '100+';
            }elseif($inventoryLevel == '0'){
                $inventoryLevel = $variant['mpn'];
            }
            $variationDatas[$color][$size] = $inventoryLevel;
        }
        $collection['Product'] = $targetUrl;
        $collection['Product ID'] = $productID;
        $collection['Color Availability'] = $variationDatas;
        echo json_encode($collection)."\n\n\n";
    }
}

$crawler = new ascolour();

$urls = [
            'https://www.ascolour.com.au/mens-staple-tee-5001/',
            'https://www.ascolour.com.au/mens-wide-stripe-tee-5045/'
        ];

foreach($urls as $url){
    $crawler->main($url);
}

?>