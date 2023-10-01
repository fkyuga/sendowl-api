<?php

namespace SendOwl;

use Requests;
use GuzzleHttp\Client;

/**
 * Class SendOwlAPI
 * @package AtmMarketing\SendOwlAPI
 */
class SendOwlAPI {

	private $orders_endpoint = 'https://www.sendowl.com/api/v1_3/orders';
	private $products_endpoint = 'https://www.sendowl.com/api/v1/products';
	private $subscriptions_endpoint = 'https://www.sendowl.com/api/v1/subscriptions';
	private $upload_endpoint = 'https://upload.sendowl.com/api/v1/products';
	const PRODUCT_TYPE_DIGITAL = 'digital';
	
	/**
	 * @var string
	 */
	private $key;

	/**
	 * @var string
	 */
	private $secret;
	/**
	 * @var array|null
	 */
	private $options;
	// guzzle
	private $Client;
	
	/**
	 * SendOwlAPI constructor.
	 *
	 * @param string $key
	 * @param string $secret
	 * @param array|null $options
	 */
	public function __construct( $key='', $secret='', $options = [] ) {
		$this->key             = $key;
		$this->secret          = $secret;
		$this->options         = $options;
		$this->options['auth'] = array( $this->get_key(), $this->get_secret() );
		$this->Client = new Client([
			'auth' => [$this->get_key(), $this->get_secret()] ,
			'headers' => ['Accept' => 'application/json']
		]);
	}

   /**
	* Retrieves all possible subscriptions
	*
	* @return array
	* @throws SendOwlAPIException
	*/
	public function get_subscriptions() {
		$response = $this->Client->request('GET', $this->subscriptions_endpoint);
	    return json_decode($response->getBody(), true);
	}

	/**
	 * Retrieve API Key
	 *
	 * @return string
	 */
	public function get_key() {
		return $this->key;
	}

	/**
	 * Retrieve API Secret
	 *
	 * @return string
	 */
	public function get_secret() {
		return $this->secret;
	}

	/**
	 * Create a new product
	 *
	 * @param int $product_id
	 *
	 * @return array
	 * @throws SendOwlAPIException
	 */
	public function create_product( $fields = [] ) {
		$headers  = [
			'Accept' => 'application/json',
			'Content-Type' => 'multipart/form-data'
		];
		
		//echo '$fields=';print_r($fields);
		//$response = Requests::post( $this->products_endpoint , $headers, $fields, $this->options );
		$multipart = [];
		foreach ($fields as $k => $v) {
			
			if($k == 'attachment'){
				$v = fopen( $v, 'r' );
			}
			
			$multipart[] = ['name' => 'product[' . $k . ']', 'contents' => $v ];
			
			
			
		}
		
		
		$response = $this->Client->request('POST',$this->upload_endpoint, 
			['multipart' => $multipart]
		);
		
		//echo '$response=', print_r($response,1);
		
		return json_decode( $response->getBody(), true );

		throw new SendOwlAPIException( $response->body, $response->status_code );
	}

	/**
	 * Retrieves products
	 *
	 * @param int $per_page Default is 10
	 * @param int $page Default is 1
	 *
	 * @return array
	 * @throws SendOwlAPIException
	 */
	public function get_products( $per_page = 10, $page = 1 ) {
		$headers     = [ 'Accept' => 'application/json' ];
		$per_page = $per_page > 0 ? $per_page: 10;
		$page = $page >= 1 ? $page : 1;
		$query_array = [ 'per_page' => $per_page, 'page' => $page ];
		$query       = http_build_query( $query_array );
		$response    = Requests::get( $this->products_endpoint .'/?' . $query, $headers, $this->options );
		if ( $response->success ) {
			return json_decode( $response->body, true );
		}
		throw new SendOwlAPIException( $response->body, $response->status_code );
	}

	/**
	 * Retrieve a product
	 *
	 * @param int $product_id
	 *
	 * @return array
	 * @throws SendOwlAPIException
	 */
	public function get_product( $product_id = 0 ) {
		$headers  = [ 'Accept' => 'application/json' ];
		//$response = Requests::get( $this->products_endpoint .'/' . $product_id, $headers, $this->options );
		$response = $this->Client->request('GET', $this->products_endpoint .'/' . $product_id );
		
		//print_r($response);
		
		
		return json_decode( $response->getBody(), true );
		
		throw new SendOwlAPIException( $response->body, $response->status_code );
	}

	/**
	 * Deletes a product
	 *
	 * @param int $product_id
	 *
	 * @return bool
	 */
	public function delete_product( $product_id = 0 ) {
		$headers  = [ 'Accept' => 'application/json' ];
		$response = Requests::delete( $this->products_endpoint .'/' . $product_id, $headers, $this->options );
		if ( $response->success ) {
			return true;
		}

		return false;
	}

	/**
	 * @param int $product_id
	 * @param array $fields
	 *
	 * @return bool
	 */
    public function update_product($product_id = 0, $fields = [])
    {
	    $headers  = [ 'Accept' => 'application/json' ];
	    $response = Requests::put( $this->products_endpoint .'/' . $product_id, $headers, $fields, $this->options );
	    if ( $response->success ) {
		    return true;
	    }

	    return false;
    }

	/**
	 * @param int $product_id
	 * @param string $license_key
	 *
	 * @return array
	 * @throws SendOwlAPIException
	 */
    public function get_license_meta_data( $product_id = 0, $license_key = '')
    {
	    $headers  = [ 'Accept' => 'application/json' ];
	    $response = Requests::get( $this->products_endpoint .'/' . $product_id . '/licenses/check_valid?key='.$license_key, $headers, $this->options );
	    if ( $response->success ) {
		    return json_decode( $response->body, true );
	    }
	    throw new SendOwlAPIException( $response->body, $response->status_code );
    }

	/**
	 * @param int $product_id
	 * @param string $license_key
	 *
	 * @return bool
	 * @throws SendOwlAPIException
	 */
    public function license_key_is_valid($product_id = 0, $license_key = '')
    {
    	$license_meta_data = $this->get_license_meta_data($product_id, $license_key);
    	if (empty($license_meta_data)) {
    		return false;
	    }
	    if ( !isset($license_meta_data[0]['license']['order_refunded']) || true === $license_meta_data[0]['license']['order_refunded']) {
    		return false;
	    }
	    return true;
    }

	/**
	 * @param int $product_id
	 *
	 * @return array
	 * @throws SendOwlAPIException
	 */
    public function get_licenses_by_product($product_id = 0)
    {
	    $headers  = [ 'Accept' => 'application/json' ];
	    $response = Requests::get( $this->products_endpoint .'/' . $product_id . '/licenses/', $headers, $this->options );
	    if ( $response->success ) {
		    return json_decode( $response->body, true );
	    }
	    throw new SendOwlAPIException( $response->body, $response->status_code );
    }

	/**
	 * @param int $product_id
	 *
	 * @return mixed
	 * @throws SendOwlAPIException
	 */
    public function get_licenses_by_order($product_id = 0)
    {
	    $headers  = [ 'Accept' => 'application/json' ];
	    $response = Requests::get( $this->products_endpoint .'/' . $product_id . '/licenses/', $headers, $this->options );
	    if ( $response->success ) {
		    return json_decode( $response->body, true );
	    }
	    throw new SendOwlAPIException( $response->body, $response->status_code );
    }

	/**
	 * @param int $order_id
	 * @return array
	 * @throws SendOwlAPIException
	 */
	public function get_order( $order_id = 0 ) {
		$headers  = [ 'Accept' => 'application/json' ];
		$response = Requests::get( $this->orders_endpoint . '/' . $order_id , $headers, $this->options );
		if ( $response->success ) {
			return json_decode( $response->body, true );
		}
		throw new SendOwlAPIException( $response->body, $response->status_code );
    }
}
