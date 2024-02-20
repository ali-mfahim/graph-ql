<?php

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Http;

function generateSignatureWithRaw($params)
{

    $app_secret = config("sync.pat_pat_app_secret");
    $data  = $params;
    $sortedParams = [];
    foreach ($data as $key => $value) {
        $sortedParams[] = $key . json_encode($value);
    }
    sort($sortedParams);
    $signatureFactor = join('', $sortedParams);
    $signature = strtoupper(bin2hex(hash_hmac('sha1', $signatureFactor, $app_secret, true)));
    return $signature;
}
function getAuthorizationSignature($app_id = null, $app_secret = null)
{
    $app_id = isset($app_id) && !empty($app_id) ? $app_id : config("sync.pat_pat_app_id");
    $app_secret = isset($app_secret) && !empty($app_secret) ? $app_secret : config("sync.pat_pat_app_secret");
    $signature = md5($app_id . $app_secret);
    return $signature;
}
function getAccessToken($signature = null)
{
    try {
        $signature = isset($signature) && !empty($signature) ? $signature : getAuthorizationSignature();
        $action = config("sync.pat_pat_auth_base_url") . config('sync.pat_pat_app_id') . "?_signature=" . $signature;

        $client = new Client(['verify' => false]);
        $response = $client->request("GET", $action);
        $responseBody = json_decode($response->getBody()->getContents());
        if (isset($responseBody->status) && !empty($responseBody->status)) {
            if ($responseBody->status == 200 || $responseBody->msg == "success") {
                $content = isset($responseBody->content) && !empty($responseBody->content) ? $responseBody->content : null;
                if (isset($content) && !empty($content) && isset($content->access_token) && !empty($content->access_token)) {
                    $accessToken = $content->access_token ?? null;
                    return $accessToken;
                }
            } else {
                dd($responseBody->msg);
            }
        }
    } catch (BadResponseException $e) {
        $error = json_decode($e->getResponse()->getBody());
        dd($error);
    } catch (Exception $e) {
        $error = $e->getMessage();
        dd($error);
    } catch (ClientException $e) {
        $response = $e->getResponse();
        $response = json_decode($response->getBody()->getContents());
        if (isset($response->message) && !empty($response->message)) {
            $error = $response->message ?? null;
            dd($error);
        }
    }
}

function getProductSaleInfo($params)
{
    try {
        if (isset($params) && !empty($params)) {
            $signature = generateSignatureWithRaw($params);
            $paramsArray = [
                "_signature" => $signature
            ];
            $paramsArray = array_merge($paramsArray, $params);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://open.patpat.com/api/shopify/getProductSalesInfo',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($paramsArray),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer 2RyDiQTd9mAhbHxaqxbD2tqokBcE5QGOytlkLGzZ",
                    "Content-Type: application/json"
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            $result = json_decode($response);
            if (isset($result->status) && !empty($result->status) && $result->status == 200) {
                return ['success' => true, 'msg' => $result->msg, 'data' => $result->content];
            } else {
                return ['success' => false, 'msg' => $result->msg];
            }
        } else {
            return ['success' => false, 'msg' => 'Please add product ids in array'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'msg' => $e->getMessage()];
    }
}
function formatProductDescriptionFromPatPat($description)
{
    $htmlString = '';
    $productData = json_decode($description, true);
    if (!empty($productData)) {

        foreach ($productData as $key => $value) {
            if ($key != "Key Features") {
                // Remove * and • characters, and trim spaces
                $cleanedValue = trim(str_replace(['*', '•'], '', $value));
                $htmlString .= "<p>{$cleanedValue}</p>";
            }
        }


        if (isset($productData['Key Features']) && !empty($productData['Key Features'])) {
            $keyFeaturesList = array_map(function ($item) {
                // Remove * and • characters, and trim spaces
                return trim(str_replace(['*', '•'], '', $item));
            }, array_filter(explode("\n", $productData['Key Features'])));

            $keyFeaturesHTML = '<ul><li>' . implode('</li><li>', $keyFeaturesList) . '</li></ul>';
            $htmlString .= "<p>{$keyFeaturesHTML}</p>";
        }
    }

    return $htmlString;
}
function getProductTags($patpatProduct)
{
    $tags = "";
    if (!empty($patpatProduct)) {
        $tags .= $patpatProduct->productId;
        $tags .= "-patpat-product-id";
        $tags .= "," . $patpatProduct->productCode  . "-patpat-product-code";
        $tags .= "," . "new-products-import";
        $tags .= "," . Carbon::now()->toDateString();
        $tags .= "," . Carbon::now()->toTimeString();
    }
    return $tags;
}
function getStoreDetails()
{
    return (object) [
        "base_url" => config("project.shopify.base_url"),
        "domain" => config("project.shopify.domain"),
        "access_token" => config("project.shopify.access_token"),
        "app_key" => config("project.shopify.app_key"),
        "app_secret" => config("project.shopify.app_secret"),
        "api_version" => config("project.shopify.app_version"),
        "store_currency" => config("project.shopify.store_currency"),
    ];
}
function formatMutationForShopifyProduct($patPatProduct, $batchProduct)
{
    $project = getStoreDetails();
    $url = $project->base_url . $project->api_version . "/graphql.json";
    $productName = isset($patPatProduct->productName) && !empty($patPatProduct->productName) ? $patPatProduct->productName :  "Product Name not found from Patpat";
    $description = isset($patPatProduct->description) && !empty($patPatProduct->description) ?  formatProductDescriptionFromPatPat($patPatProduct->description) : "-";
    $tags = getProductTags($patPatProduct);
    $handle = $patPatProduct->productId . "-" .  Str::slug($productName, "-");
    $variants = generateVariants($patPatProduct);
    if (!empty($variants)) {
        $mutation = 'mutation {
            productCreate(input: {
                title: "' . $productName . '",
                descriptionHtml: "' . $description . '",
                productType: "Clothing",
                vendor: "Patpat",
                tags: "' . $tags  . '",
                status : ACTIVE,
                options: ["size", "color"],
                variants: [' . generateVariants($patPatProduct) . ']
            } , media: [' . generateMedia($patPatProduct) . ']) {
                product {
                    id
                    title
                  }
                  userErrors {
                    field
                    message
                  }
            }
        }';
    } else {
        $mutation = 'mutation {
            productCreate(input: {
                title: "' . $productName . '",
                descriptionHtml: "' . $description . '",
                productType: "Clothing",
                vendor: "Patpat",
                tags: "' . $tags  . '"
                status : DRAFT,
            } , media: [' . generateMedia($patPatProduct) . ']) {
                product {
                    id
                    title
                  }
                  userErrors {
                    field
                    message
                  }
            }
        }';
    }
    $mediaInput = [];
    foreach ($patPatProduct->colors as $media) {
        foreach ($media as $image) {
            $mediaItem = [
                'alt' => 'NA',
                'mediaContentType' => 'IMAGE',
                'originalSource' => $image
            ];
            $mediaInput[] = $mediaItem;
        }
    }
    $url = $project->base_url . $project->api_version . "/graphql.json";
    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'X-Shopify-Access-Token' => $project->access_token,
    ])->post($url, [
        'query' => $mutation,
    ]);
    $data = $response->json();
    if (isset($data['data']['productCreate']['product']) && !empty($data['data']['productCreate']['product'])) {
        $product = $data['data']['productCreate']['product'];
        $product_id = $product['id'];
        $parts = explode('/', $product_id);
        $id = end($parts);
        $old_count = $batchProduct->update_count ?? 0;
        $batchProduct->update([
            "gid" => $product_id,
            "shopify_product_id" =>  $id,
            'type' => 1,
            'is_store' => 1,
            'images_updated' => 1,
            'update_count' => $old_count + 1,
            'status' => 1,
        ]);
        return  $product;
    } else {
        return $data;
    }
}



function generateVariants($patPatProduct)
{
    $defaultLocation = getDefaultLocationOfStore();
    $location_id = isset($defaultLocation->data->locations[0]->admin_graphql_api_id) && !empty($defaultLocation->data->locations[0]->admin_graphql_api_id) ? $defaultLocation->data->locations[0]->admin_graphql_api_id : "0";
    $variants = '';
    if (!empty($patPatProduct->colors)) {
        foreach ($patPatProduct->colors as $color) {
            foreach ($color->skuList as $sku) {
                $variant = '{
                options: ["' . $color->color . '", "' . $sku->size . '"],
                price: "' . $sku->wholesPrice . '",
                compareAtPrice: "0",
                inventoryItem: {
                    cost: "0.00",
                    tracked: true
                },
                inventoryManagement: SHOPIFY,
                inventoryPolicy: CONTINUE,
                inventoryQuantities: [{
                    availableQuantity: ' . $sku->stock . ',
                    locationId:  "' . $location_id  . '"
                }],
                sku: "' . $sku->skuCode . '",
                mediaSrc: ["' . $color->colorIcon . '"]
            },';
                $variants .= $variant;
            }
        }
    }
    return rtrim($variants, ',');
}

function generateMedia($patPatProduct)
{
    $media = '';
    foreach ($patPatProduct->colors as $color) {
        foreach ($color->images as $image) {
            $mediaItem = '{
                alt: "NA",
                mediaContentType: IMAGE,
                originalSource: "' . $image->url . '"
            },';
            $media .= $mediaItem;
        }
    }
    return rtrim($media, ',');
}

function jsonResponse($success = null, $data = null, $message = null, $code = null)
{
    return (object) ['success' => $success, 'data' => $data ?? null, 'message' => $message];
}
function getDefaultLocationOfStore()
{
    $project = getStoreDetails();
    $url = $project->base_url . $project->api_version . '/locations.json';

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'X-Shopify-Access-Token: ' . $project->access_token,
            'Content-Type: application/json',
        ),
    ));
    // curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        echo 'Curl error: ' . curl_error($curl);
    }
    curl_close($curl);

    if (!empty($response)) {
        $response = json_decode($response);
        return jsonResponse(true, $response, "Default location of store", 200);
    } else {
        return jsonResponse(false, "", "Location not found of store", 200);
    }
}
