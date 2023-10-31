<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Shopify\Clients\Graphql;
use ERPConnectorApi;

class Store extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'shopify_stores';

    protected $client;
    private $pagination_cursor = false;
    private $has_more_products = false;

    /**
     *  Sync inventory from ERP to shopify store.
     */
    public function sync_inventory(){
      
        \Shopify\Context::initialize(
            apiKey:  env('SHOPIFY_API_KEY'),
            apiSecretKey: env('SHOPIFY_API_SECRET'),
            scopes:  env('SHOPIFY_APP_SCOPES'),
            hostName: $this->shop_url,
            sessionStorage:new \Shopify\Auth\FileSessionStorage( storage_path()),
            apiVersion: '2023-04',
            isEmbeddedApp: true,
            isPrivateApp: false,
        );

        $client = new Graphql($this->shop_url, $this->shopify_token);
        $this->client = $client;

        // 1. Get token from erp
        $token = ERPConnectorApi::getToken();
		
		// 2. Get all erp products altogether.
        $erp_products = ERPConnectorApi::getStocPret($token, '', '');
		_log(print_r($erp_products,1),'all_products_');
        $total_products = 0;
        do{

            // 3.  Get all product with barcode
            $variants = $this->get_shopify_products(); 
            $total_products+=count($variants);

            // 4.using token get inventory of barcode product from inventory
            foreach ($variants as $i => $edges) {

                // cursor = product_cursor
                // node = product variant (array)
                $p = $edges['node']; // product variant
                $this->pagination_cursor = $edges['cursor'];

                $found_key = array_search( $p['barcode'], array_column($erp_products, 'CodProdus'));
				
                if( $found_key === false ){     
                    _log(print_r($p['barcode'],1),'no_erp_products');
					continue;
                }

                $erp_product = $erp_products[ $found_key ];
				_log( print_r($erp_product,1),'erp_product_found');

                //5. update product price
                $this->update_shopify_product_price($p, $erp_product);

                //6. update product inventory
                $product_current_stock =$p['inventoryQuantity'];
                $availableDelta =  $erp_product->StocTotal - $product_current_stock;

                if( $availableDelta  != 0 ){
                    $this->update_shopify_product_inventory( $p['id'], $availableDelta);
                }
                

            }
        }while( $this->has_more_products );
        
        echo('Total products: '.$total_products);

    }    

    /**
     *  Get product variants from shopify having barcode.
     *  return array
     */
    function get_shopify_products(){

        $variants = [];
        $after = '';
     
        if( $this->pagination_cursor){
            $after = ', after:"'.$this->pagination_cursor.'"';
        }

        try{
            $query = <<<QUERY
                                    query {
                                        productVariants(first: 250 $after ,query: "barcode:>1") {
                                            edges {
                                                cursor
                                                node {
                                                    id
                                                    price
                                                    inventoryQuantity
                                                    barcode
                                                        product {
                                                            id
                                                            title
                                                        }
                                                    }
                                                }
                                                pageInfo {
                                                    hasNextPage
                                                    hasPreviousPage
                                                }
                                            }
                                            
                                        }
                                    QUERY;
                                
            $response = $this->client->query(["query" => $query]);
            $decoded_response = $response->getDecodedBody();

            if( isset($decoded_response['data']) ){
                $variants = $decoded_response['data']['productVariants']['edges'] ;
                $this->has_more_products = $decoded_response['data']['productVariants']['pageInfo']['hasNextPage'];
            }else{
                _log( $decoded_response, 'Api_error_');
                $this->has_more_products = false;
            }
          
        }catch( \Exception $e ){
            $error = $e->getMessage();
            _log( $error, 'Api_error_');
        }

        return $variants;
    }

    /**
     *  Upate product price on shopify
     *  param $variant array
     *  param $erp_product object
     *  return null
     * 
     */
    function update_shopify_product_price($variant, $erp_product){

        $pid = $variant['id'];
        $query = <<<QUERY
        mutation {
        var2: productVariantUpdate(input: {id: "$pid", price: "$erp_product->PretVanzare"})
            {
            productVariant {
                        price
                    }
            }
        }
        QUERY;

        $response = $this->client->query(["query" => $query]);
        
    }
     /**
     *  Upate product inventory on shopify
     *  param $pid string
     *  param $stock_change integer
     *  return array
     * 
     */
    function update_shopify_product_inventory( $pid, $stock_change ){

        $queryitem = <<<QUERY
                        query {
                            productVariant( id: "$pid") {
                                id
                                title
                                sku
                                legacyResourceId
                                displayName
                                inventoryItem {
                                    id
                                    legacyResourceId
                                    inventoryLevels(first: 5) {
                                        edges {
                                            node {
                                                id
                                                location {
                                                    id
                                                    name
                                                }
                                                available
                                                item {
                                                    id
                                                }
                                            }
                                        }
                                    }
                                }
                                inventoryQuantity
                                product {
                                    id
                                    title
                                }            
                            }
                        }
                     QUERY;
        $item_response = $this->client->query(["query" => $queryitem]);
        $product_variant = $item_response->getDecodedBody()['data']['productVariant'];
        $inventoryLevel = $product_variant['inventoryItem']['inventoryLevels']['edges'][0]['node'];

        $update_inventory_query = <<<QUERY
                        mutation AdjustInventoryQuantity(\$input: InventoryAdjustQuantityInput!) {
                            inventoryAdjustQuantity(input: \$input) {
                                inventoryLevel {
                                    id
                                    available
                                    incoming
                                    item {
                                        id
                                        sku
                                    }
                                    location {
                                        id
                                        name
                                    }
                                }
                            }
                        }
                    QUERY;
        $variables = [ 
            "input" => [
                "inventoryLevelId" => $inventoryLevel['id'],
                "availableDelta" => $stock_change 
                ]
        ];            

        $response = $this->client->query(["query" => $update_inventory_query, "variables" => $variables]);
        return $response->getDecodedBody();  
    }
}
