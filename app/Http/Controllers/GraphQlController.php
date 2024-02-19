<?php

namespace App\Http\Controllers;
use GuzzleHttp\Client;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GraphQlController extends Controller
{

    public function getStoreDetails()
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
    public function fetchProducts()
    {
      $project = $this->getStoreDetails();
      $url = $project->base_url . $project->api_version ."/graphql.json";
        $query = '
        {
            products(first: 1) {
              edges {
                node {
                  id
                  title
                  description
                  images(first: 100) {
                    edges {
                      node {
                        src
                      }
                    }
                  }
                  variants(first: 1) {
                    edges {
                      node {
                        id
                        price
                        inventoryQuantity
                        image {
                            id
                          }
                      }
                    }
                  }
                }
              }
            }
          }
        ';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Shopify-Access-Token' => $project->access_token,
        ])->post( $url , [
            'query' => $query,
        ]);
        $response = $response->json();
        return $response;
        dd($response);
    }

    // working good
    public function createProduct(Request $request)
    {
        $project = $this->getStoreDetails();
        $url = $project->base_url . $project->api_version . "/graphql.json";
        
        // $variants = [
        //   [
        //       'title' => 'Variant 1 Title',
        //       'compareAtPrice' => 10.00
        //   ],
        //   [
        //       'title' => 'Variant 2 Title',
        //       'compareAtPrice' => 15.00
        //   ]
        // ];
        // $variantsJson = json_encode($variants, JSON_UNESCAPED_UNICODE);
        $mutation = 'mutation {
          productCreate(input: {
              title: "' . $request->title . '",
              descriptionHtml: "' . $request->description . '",
              productType: "TEST PRODUCT",
              variants: [
                  { "title": "Variant 1 Title", "compareAtPrice": 10 },
                  { "title": "Variant 2 Title", "compareAtPrice": 15 }
              ]
          }) {
              product {
                  id
                  title
                  productType
                  descriptionHtml
              }
              userErrors {
                  field
                  message
              }
          }
      }';
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Shopify-Access-Token' => $project->access_token,
        ])->post($url, [
            'query' => $mutation,
        ]);

        // Process the response
        $data = $response->json();
        if(isset($data['data']['productCreate']['product']) && !empty($data['data']['productCreate']['product'])) {
            $product = $data['data']['productCreate']['product'];
            $product_id = $product['id'];
            $mediaUrls = [
                "https://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/000000000000/63dc80e2b9b4b.jpg",
                "https://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/000000000000/63dc80e29e094.jpg",
            ];  
            $saveImage = $this->createNewProductImages($product_id, $mediaUrls);
            return [
                $product,
                $saveImage
            ];
        } else {
            return $data['errors'][0];
        }
    }
    // working good

    // working good
    public function createNewProductImages($productId, $mediaUrls)
    {
        // Get necessary data for the request
        $project = $this->getStoreDetails();
        $url = $project->base_url . $project->api_version . "/graphql.json";

        // Construct the GraphQL mutation for creating product media
        $mutation = '
          mutation ($media: [CreateMediaInput!]!, $productId: ID!) {
              productCreateMedia(media: $media, productId: $productId) {
                  media {
                      id
                  }
                  mediaUserErrors {
                      code
                      field
                      message
                  }
                  product {
                      id
                      title
                      # Add more fields you want to retrieve for the product
                  }
              }
          }
      ';
        // Define variables for the mutation
        $variables = [
          'media' => [],
          'productId' => base64_encode($productId), // Encode product ID
        ];

        // Prepare media input
        foreach ($mediaUrls as $mediaUrl) {
          $variables['media'][] = [
              'mediaContentType' => 'IMAGE',
              'originalSource' => $mediaUrl, // Change 'src' to 'originalSource'
          ];
        }

        // Make the request to Shopify GraphQL API
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Shopify-Access-Token' => $project->access_token,
        ])->post($url, [
            'query' => $mutation,
            'variables' => $variables,
        ]);

        // Return the response from Shopify API
        return $response->json();
    }
    // working good

    // Function to build variant data
    public function buildVariants($variants)
    {
        $variantData = '[';
    
        foreach ($variants as $variant) {
            $variantData .= '{
                title: "' . $variant['title'] . '",
                price: "' . $variant['price'] . '"
                // Add other variant fields as needed
            },';
        }
    
        $variantData .= ']';
    
        return $variantData;
    }
  
  



    // testing
    public function createProduct2(Request $request) {
      $project = $this->getStoreDetails();
      $url = $project->base_url . $project->api_version . "/graphql.json";
  
      $mutation = 'mutation CreateProductWithVariants($productInput: ProductInput!) {
          productCreate(input: $productInput) {
              product {
                  id
                  title
                  variants {
                      edges {
                          node {
                              id
                              title
                              price
                              sku
                          }
                      }
                  }
              }
              userErrors {
                  field
                  message
              }
          }
      }';
  
      $productInput = [
          'title' => $request->title,
          'descriptionHtml' => $request->description,
          'productType' => 'TEST PRODUCT',
          'variants' => [
              [
                  'price' => '10.00',
                  'sku' => '9732910223423443712',
                  'title' => 'variant title 1',
              ],
              [
                  'price' => '15.00',
                  'sku' => '30845032312327030',
                  'title' => 'variant title 2',
              ]
          ]
      ];
  
      $variables = [
          'productInput' => $productInput
      ];
  
      $response = Http::withHeaders([
          'Content-Type' => 'application/json',
          'X-Shopify-Access-Token' => $project->access_token,
      ])->post($url, [
          'query' => $mutation,
          'variables' => $variables,
      ]);
  
      $data = $response->json();
      return $data;
  }
  

}
