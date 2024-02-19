<?php

namespace App\Http\Controllers;

use App\Models\BatchProduct;
use Carbon\Carbon;
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
    $url = $project->base_url . $project->api_version . "/graphql.json";
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
    ])->post($url, [
      'query' => $query,
    ]);
    $response = $response->json();
    return $response;
    dd($response);
  }
  function jsonResponse($success = null, $data = null, $message = null, $code = null)
  {
    return (object) ['success' => $success, 'data' => $data ?? null, 'message' => $message];
  }
  function getDefaultLocationOfStore()
  {
    $project = $this->getStoreDetails();
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
      return $this->jsonResponse(true, $response, "Default location of store", 200);
    } else {
      return $this->jsonResponse(false, "", "Location not found of store", 200);
    }
  }
  // working good
  public function createProduct(Request $request)
  {
    $project = $this->getStoreDetails();
    $url = $project->base_url . $project->api_version . "/graphql.json";
    $defaultLocation = $this->getDefaultLocationOfStore();
    $batchProducts = BatchProduct::whereNull("shopify_product_id")->where("is_store", 0)->where("skip", 0)->orderby("id", "ASC")->limit(5)->get();
    $location_id = isset($defaultLocation->data->locations[0]->admin_graphql_api_id) && !empty($defaultLocation->data->locations[0]->admin_graphql_api_id) ? $defaultLocation->data->locations[0]->admin_graphql_api_id : "0";
    if (!empty($batchProducts)) {
      // foreach ($batchProducts as $batchProduct) {
      //   $bProduct = $batchProduct;
      //   $params = [
      //     'productIds' => [$bProduct->product_id],
      //   ];
      //   $getProduct = getProductSaleInfo($params);
      //   $mutation = 'mutation {
      //     productCreate(input: {
      //       title: "' . . '",
      //       descriptionHtml: "' . $request->description . ' ---- ' . Carbon::now()->toDateTimeString() . '",
      //       productType: "Clothing",
      //       vendor: "Patpat",
      //       tags: "test123,2928102,2018202",
      //       options: [
      //         "Size",
      //         "Color"
      //       ],
      //       variants: [
      //         { 
      //           options: [
      //             "Grey",
      //             "2 years"
      //           ], 
      //           price: "10", 
      //           compareAtPrice: "20",
      //           inventoryItem: {
      //             cost: "05",
      //             tracked: true
      //           },
      //           inventoryManagement : SHOPIFY,
      //           inventoryPolicy: CONTINUE,
      //           inventoryQuantities: [
      //             {
      //               availableQuantity: 69 ,
      //               locationId: "gid://shopify/Location/74805477612"
      //             }
      //           ],
      //           sku: "12345",
      //           mediaSrc: [
      //            "http://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/oc/clothing/shooting/623bd519cebc8.jpg",
      //           ]
      //         },
      //         { 
      //           options: [
      //             "Grey",
      //             "3 years"
      //           ], 
      //           price: "10", 
      //           compareAtPrice: "20",
      //           inventoryItem: {
      //             cost: "05",
      //             tracked: true
      //           },
      //           inventoryManagement : SHOPIFY,
      //           inventoryPolicy: CONTINUE,
      //           inventoryQuantities: [
      //             {
      //               availableQuantity: 52 ,
      //               locationId: "gid://shopify/Location/74805477612"
      //             }
      //           ],
      //           sku: "654789",
      //           mediaSrc: [
      //             "http://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/oc/clothing/shooting/623bd519cebc8.jpg"
      //           ]
      //         },
      //         { 
      //           options: [
      //             "royalblue",
      //             "3 years"
      //           ], 
      //           price: "15", 
      //           compareAtPrice: "25",
      //           inventoryItem: {
      //             cost: "09",
      //             tracked: true
      //           },
      //           inventoryManagement : SHOPIFY,
      //           inventoryPolicy: CONTINUE,
      //           inventoryQuantities: [
      //             {
      //               availableQuantity: 52 ,
      //               locationId: "gid://shopify/Location/74805477612"
      //             }
      //           ],
      //           sku: "101112",
      //           mediaSrc: [
      //             "http://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/oc/clothing/shooting/61d42c4f09e1a.jpg"
      //           ]
      //         },
      //         { 
      //           options: [
      //             "royalblue",
      //             "4 years"
      //           ], 
      //           price: "15", 
      //           compareAtPrice: "25",
      //           inventoryItem: {
      //             cost: "09",
      //             tracked: true
      //           },
      //           inventoryManagement : SHOPIFY,
      //           inventoryPolicy: CONTINUE,
      //           inventoryQuantities: [
      //             {
      //               availableQuantity: 52 ,
      //               locationId: "gid://shopify/Location/74805477612"
      //             }
      //           ],
      //           sku: "131415",
      //           mediaSrc: [
      //             "http://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/oc/clothing/shooting/61d42c4f09e1a.jpg"
      //           ]
      //         },
      //         { 
      //           options: [
      //             "royalblue",
      //             "5 years"
      //           ], 
      //           price: "15", 
      //           compareAtPrice: "25",
      //           inventoryItem: {
      //             cost: "09",
      //             tracked: true
      //           },
      //           inventoryManagement : SHOPIFY,
      //           inventoryPolicy: CONTINUE,
      //           inventoryQuantities: [
      //             {
      //               availableQuantity: 52 ,
      //               locationId: "gid://shopify/Location/74805477612"
      //             }
      //           ],
      //           sku: "161718",
      //           mediaSrc: [
      //             "http://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/oc/clothing/shooting/61d42c4f09e1a.jpg"
      //           ]
      //         }
      //       ],
      //     } , 
      //     media: [
      //         {
      //             alt: "IMAGE NOT FOUND",
      //             mediaContentType: IMAGE,
      //             originalSource: "http://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/oc/clothing/shooting/623bd519cebc8.jpg"
      //         },
      //         {
      //             alt: "IMAGE NOT FOUND",
      //             mediaContentType: IMAGE,
      //             originalSource: "http://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/oc/clothing/shooting/61d42c4f09e1a.jpg"
      //         },
      //         {
      //           alt: "IMAGE NOT FOUND",
      //           mediaContentType: IMAGE,
      //           originalSource: "http://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/oc/clothing/shooting/623bd5196aaee.jpg"
      //         },
      //         {
      //           alt: "IMAGE NOT FOUND",
      //           mediaContentType: IMAGE,
      //           originalSource: "http://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/oc/clothing/shooting/623bd51abe899.jpg"
      //         },
      //         {
      //           alt: "IMAGE NOT FOUND",
      //           mediaContentType: IMAGE,
      //           originalSource: "http://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/oc/clothing/shooting/623bd519dae78.jpg"
      //         },
      //         {
      //           alt: "IMAGE NOT FOUND",
      //           mediaContentType: IMAGE,
      //           originalSource: "http://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/oc/clothing/shooting/623bd51a31a3e.jpg"
      //         }
      //     ]

      //   ) {
      //       product {
      //         id
      //         title
      //         productType
      //         descriptionHtml
      //         vendor
      //         variants(first: 10) {
      //           edges {
      //             node {
      //               id
      //               price
      //               inventoryQuantity
      //               image {
      //                 id
      //               }
      //             }
      //           }
      //         }
      //       }
      //       userErrors {
      //         field
      //         message
      //       }
      //     }
      //   }';
      //   return $getProduct;
      // }
    }

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
    // static mutation
    $mutation = 'mutation {
      productCreate(input: {
        title: "' . $request->title . ' ---- ' . Carbon::now()->toDateTimeString() . '",
        descriptionHtml: "' . $request->description . ' ---- ' . Carbon::now()->toDateTimeString() . '",
        productType: "Clothing",
        vendor: "Patpat",
        tags: "test123,2928102,2018202",
        options: [
          "Size",
          "Color"
        ],
        variants: [
          { 
            options: [
              "Grey",
              "2 years"
            ], 
            price: "10", 
            compareAtPrice: "20",
            inventoryItem: {
              cost: "05",
              tracked: true
            },
            inventoryManagement : SHOPIFY,
            inventoryPolicy: CONTINUE,
            inventoryQuantities: [
              {
                availableQuantity: 69 ,
                locationId: "gid://shopify/Location/74805477612"
              }
            ],
            sku: "12345",
            mediaSrc: [
             "http://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/oc/clothing/shooting/623bd519cebc8.jpg",
            ]
          },
          { 
            options: [
              "Grey",
              "3 years"
            ], 
            price: "10", 
            compareAtPrice: "20",
            inventoryItem: {
              cost: "05",
              tracked: true
            },
            inventoryManagement : SHOPIFY,
            inventoryPolicy: CONTINUE,
            inventoryQuantities: [
              {
                availableQuantity: 52 ,
                locationId: "gid://shopify/Location/74805477612"
              }
            ],
            sku: "654789",
            mediaSrc: [
              "http://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/oc/clothing/shooting/623bd519cebc8.jpg"
            ]
          },
          { 
            options: [
              "royalblue",
              "3 years"
            ], 
            price: "15", 
            compareAtPrice: "25",
            inventoryItem: {
              cost: "09",
              tracked: true
            },
            inventoryManagement : SHOPIFY,
            inventoryPolicy: CONTINUE,
            inventoryQuantities: [
              {
                availableQuantity: 52 ,
                locationId: "gid://shopify/Location/74805477612"
              }
            ],
            sku: "101112",
            mediaSrc: [
              "http://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/oc/clothing/shooting/61d42c4f09e1a.jpg"
            ]
          },
          { 
            options: [
              "royalblue",
              "4 years"
            ], 
            price: "15", 
            compareAtPrice: "25",
            inventoryItem: {
              cost: "09",
              tracked: true
            },
            inventoryManagement : SHOPIFY,
            inventoryPolicy: CONTINUE,
            inventoryQuantities: [
              {
                availableQuantity: 52 ,
                locationId: "gid://shopify/Location/74805477612"
              }
            ],
            sku: "131415",
            mediaSrc: [
              "http://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/oc/clothing/shooting/61d42c4f09e1a.jpg"
            ]
          },
          { 
            options: [
              "royalblue",
              "5 years"
            ], 
            price: "15", 
            compareAtPrice: "25",
            inventoryItem: {
              cost: "09",
              tracked: true
            },
            inventoryManagement : SHOPIFY,
            inventoryPolicy: CONTINUE,
            inventoryQuantities: [
              {
                availableQuantity: 52 ,
                locationId: "gid://shopify/Location/74805477612"
              }
            ],
            sku: "161718",
            mediaSrc: [
              "http://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/oc/clothing/shooting/61d42c4f09e1a.jpg"
            ]
          }
        ],
      } , 
      media: [
          {
              alt: "IMAGE NOT FOUND",
              mediaContentType: IMAGE,
              originalSource: "http://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/oc/clothing/shooting/623bd519cebc8.jpg"
          },
          {
              alt: "IMAGE NOT FOUND",
              mediaContentType: IMAGE,
              originalSource: "http://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/oc/clothing/shooting/61d42c4f09e1a.jpg"
          },
          {
            alt: "IMAGE NOT FOUND",
            mediaContentType: IMAGE,
            originalSource: "http://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/oc/clothing/shooting/623bd5196aaee.jpg"
          },
          {
            alt: "IMAGE NOT FOUND",
            mediaContentType: IMAGE,
            originalSource: "http://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/oc/clothing/shooting/623bd51abe899.jpg"
          },
          {
            alt: "IMAGE NOT FOUND",
            mediaContentType: IMAGE,
            originalSource: "http://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/oc/clothing/shooting/623bd519dae78.jpg"
          },
          {
            alt: "IMAGE NOT FOUND",
            mediaContentType: IMAGE,
            originalSource: "http://patpatwebstatic.s3.us-west-2.amazonaws.com/origin/product/oc/clothing/shooting/623bd51a31a3e.jpg"
          }
      ]

    ) {
        product {
          id
          title
          productType
          descriptionHtml
          vendor
          variants(first: 10) {
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
        userErrors {
          field
          message
        }
      }
    }';
    // static mutation

    $response = Http::withHeaders([
      'Content-Type' => 'application/json',
      'X-Shopify-Access-Token' => $project->access_token,
    ])->post($url, [
      'query' => $mutation,
    ]);

    // Process the response
    $data = $response->json();
    if (isset($data['data']['productCreate']['product']) && !empty($data['data']['productCreate']['product'])) {
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
  public function createProduct2(Request $request)
  {
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
