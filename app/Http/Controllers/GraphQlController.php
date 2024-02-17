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
        ])->post('https://40770a-ce.myshopify.com/admin/api/2024-01/graphql.json', [
            'query' => $query,
        ]);
        $response = $response->json();
        return $response;
        dd($response);
    }


    public function createProduct(Request $request)
    {
        $project = $this->getStoreDetails();
      
        $mutation = '
            mutation {
                productCreate(input: {
                    title: "' . $request->title . '",
                    descriptionHtml: "' . $request->description . '",
                }) {
                    product {
                        id
                        title
                        description
                        
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        ';
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Shopify-Access-Token' => $project->access_token,
        ])->post('https://40770a-ce.myshopify.com/admin/api/2024-01/graphql.json', [
            'query' => $mutation,
        ]);

        return $response->json();
    }
}
