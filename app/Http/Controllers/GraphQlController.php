<?php

namespace App\Http\Controllers;

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


    public function createProduct(Request $request)
    {
        // Get necessary data for the request
        $project = $this->getStoreDetails();
        $url = $project->base_url . $project->api_version . "/graphql.json";

        // Construct the GraphQL mutation for creating a product
        $mutation = '
            mutation {
                productCreate(input: {
                    title: "' . $request->title . '",
                    descriptionHtml: "' . $request->description . '",
                    productType:"TEST PRODUCT",
                  
                    variants: [
                        {
                            title: "' . $request->variant_title . '",
                            price: "' . $request->price . '"
                        }
                    ],
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
            }
        ';

        // Make the request to Shopify GraphQL API
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Shopify-Access-Token' => $project->access_token,
        ])->post($url, [
            'query' => $mutation,
        ]);

        // Return the response from Shopify API
        $data = $response->json();
        if(isset($data['data']['productCreate']['product']) && !empty($data['data']['productCreate']['product'])) {
          $product = $data['data']['productCreate']['product'];
          $product_id = $product['id'];
          $mediaUrls = [
            "https://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/000000000000/63dc80e2b9b4b.jpg",
            "https://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/000000000000/63dc80e29e094.jpg",
            // Add more image URLs as needed
        ];  
          $saveImage = $this->createProductMedia($product_id, $mediaUrls);
          return [
            $saveImage
          ];
        }else{
          return $data['data']['productCreate']['userErrors'];
        }
        return ;
    }


      public function createProductMedia($productId, $mediaUrls)
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


}
