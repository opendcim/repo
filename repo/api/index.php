<?php
	require_once( "../db.inc.php" );
	require_once( "../repo.inc.php" );
	require_once( "../Slim/Slim.php" );

	\Slim\Slim::registerAutoloader();

	$app = new \Slim\Slim();
	
	// $app->get( '/cdutemplate', 'getCDUTemplate' );
	// $app->get( '/cdutemplate/byid/:cdutemplateid', 'getCDUTemplateByID' );
	$app->get( '/devicetemplate', 'getDeviceTemplate' );
	$app->get( '/devicetemplate/byid/:templateid', 'getDeviceTemplateByID' );
	$app->get( '/devicetemplate/bymanufacturer/:manufacturerid', 'getDeviceTemplateByManufacturer' );
	$app->get( '/manufacturer', 'getManufacturer' );
	$app->get( '/manufacturer/byid/:manufacturerid', 'getManufacturerByID' );
	$app->get( '/manufacturer/pending', 'getPendingManufacturer' );
	$app->get( '/manufacturer/pending/byid/:requestid', 'getPendingManufacturerByID' );
	
	$app->put( '/manufacturer', 'authenticate', 'queueManufacturer' );
	$app->put( '/manufacturer/approve', 'authenticate', 'approveManufacturer' );

	$app->put( '/devicetemplate', 'authenticate', 'queueDeviceTemplate' );

/**
 * Need to accept all options requests for PUT calls to work via jquery
 */
	$app->options('/(:name+)', function() use ($app) {
		$app->response()->header('Access-Control-Allow-Origin','*');
		$app->response()->header('Access-Control-Allow-Headers', 'X-Requested-With, X-authentication, X-client, UserID, APIKey');
	});
	

/**
 * Verifying required params posted or not
 */
	function verifyRequiredParams($required_fields) {
		$error = false;
		$error_fields = "";
		$request_params = array();
		$request_params = $_REQUEST;
		// Handling PUT request params
		if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
			$app = \Slim\Slim::getInstance();
			parse_str($app->request()->getBody(), $request_params);
		}
		foreach ($required_fields as $field) {
			if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
				$error = true;
				$error_fields .= $field . ', ';
			}
		}
	 
		if ($error) {
			// Required field(s) are missing or empty
			// echo error json and stop the app
			$response = array();
			$app = \Slim\Slim::getInstance();
			$response["error"] = true;
			$response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
			echoRespnse(400, $response);
			$app->stop();
		}
	}
  
/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
	function echoRespnse($status_code, $response) {
		$app = \Slim\Slim::getInstance();
		// Http response code
		$app->status($status_code);
	 
		// setting response content type to json
		$app->contentType('application/json');
	 
		echo json_encode($response);
	}

/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
	function authenticate(\Slim\Route $route) {
		global $currUser;
		$currUser = new Users();

		// If being called from the same server, short circuit this process
		if ( $_SERVER["REMOTE_ADDR"] == "127.0.0.1" ) {
			return;
		}

		// If the Session variable 'userid' exists, this is an interactive session
		// Rights are adminstered by the UI, rather than the API
		if ( isset( $_SESSION['userid'] ) ) {
			$currUser->UserID = $_SESSION['userid'];
			return;
		}

		// Getting request headers
		$headers = apache_request_headers();
		$response = array();
		$app = \Slim\Slim::getInstance();
		

		// Verifying Authorization Header
		if (isset($headers['APIKey'])) {
			// get the api key
			$apikey = $headers['APIKey'];
			$ipaddress = $_SERVER["REMOTE_ADDR"];
			// validating api key
					
			// An API key was passed, so check to see if it's real or not
			if (! $currUser->verifyAPIKey($apikey, $ipaddress)) {
				// api key is not present in users table
				$response["error"] = true;
				$response["errorcode"] = 401;
				$response["message"] = "Access Denied. Invalid Api key";
				echoRespnse(401, $response);
				$app->stop();
			}
		} else {
			// api key is missing in header
			$response["error"] = true;
			$response["errorcode"] = 400;
			$response["message"] = "Api key is misssing";
			echoRespnse(400, $response);
			$app->stop();
		}
	}


	function getCDUTemplate() {
	}
	
	function getCDUTemplateByID() {
	}

	function getDeviceTemplate() {
		$dt = new DeviceTemplates();
		$dtList = $dt->getDeviceTemplate();

		$response['error'] = false;
		$response['errorcode'] = 200;
		$response['devicetemplates'] = array();
		foreach ( $dtList as $devtmp ) {
			$tmp = array();
			foreach ( $devtmp as $prop=>$value ) {
				$tmp[$prop] = $value;
			}
			array_push( $response['devicetemplates'], $tmp );
		}

		echoRespnse( 200, $response );
	}

	function getDeviceTemplateById( $templateid ) {
		$dt = new DeviceTemplates();
		$dtList = $dt->getDeviceTemplateById( $templateid );

                $response['error'] = false;
                $response['errorcode'] = 200;
                $response['devicetemplates'] = array();
                $response['error'] = false;
                $response['errorcode'] = 200;
                $response['devicetemplates'] = array();
               	foreach ( $dtList as $devtmp ) {
			$tmp = array();
			foreach ( $devtmp as $prop=>$value ) {
				$tmp[$prop] = $value;
			}
			array_push( $response['devicetemplates'], $tmp );
		}

                echoRespnse( 200, $response );
 
	}

	function getDeviceTemplateByManufacturer( $manufacturerid ) {
		$dt = new DeviceTemplates();
		$dtList = $dt->getDeviceTemplateByMFG( $manufacturerid );

		$response['error'] = false;
		$response['errorcode'] = 200;
		$response['devicetemplates'] = array();
		$response['error'] = false;
		$response['errorcode'] = 200;
		$response['devicetemplates'] = array();
		foreach ( $dtList as $devtmp ) {
				$tmp = array();
				foreach ( $devtmp as $prop=>$value ) {
						$tmp[$prop] = $value;
				}
				array_push( $response['devicetemplates'], $tmp );
		}

		echoRespnse( 200, $response );
	}

//
//	URL:  /api/manufacturer/pending
//	Method: GET
//	Params:  None
//	Returns:  Array of all pending requests to the ManufacturersQueue
//
	function getPendingManufacturer() {
		$m = new ManufacturersQueue();
		$mfgList = $m->viewStatus();

		$response['error'] = false;
		$response['errorcode'] = 200;
		$response['manufacturersqueue'] = array();
		foreach ( $mfgList as $mfg ) {
			$tmp = array();
			foreach( $mfg as $prop=>$value ) {
				$tmp[$prop] = $value;
			}
			array_push( $response['manufacturersqueue'], $tmp );
		}

		echoRespnse( 200, $response );
	}

//
// URL:	/api/manufacturer/byrequest/:requestid
// Method: GET
// Params: RequestID
// Returns:  Array of a single record (if found) matching supplied requestid
//
	function getPendingManufacturerByID($RequestID) {
		$m = new ManufacturersQueue();
		$mfgList = $m->viewStatus( intval( $RequestID ) );

		$response['error'] = false;
		$response['errorcode'] = 200;
		$response['manufacturersqueue'] = array();
		foreach ( $mfgList as $mfg ) {
			$tmp = array();
			foreach( $mfg as $prop=>$value ) {
				$tmp[$prop] = $value;
			}
			array_push( $response['manufacturersqueue'], $tmp );
		}

		echoRespnse( 200, $response );
	}

	function getManufacturer() {
                $m = new Manufacturers();
                $mfgList = $m->getManufacturer();

                $response['error'] = false;
                $response['errorcode'] = 200;
                $response['manufacturers'] = $mfgList;
                echoRespnse( 200, $response );

	}

	function getManufacturerByID($ManufacturerID) {
		$m = new Manufacturers();
		$mfgList = $m->getManufacturer( $ManufacturerID );

		if ( sizeof( $mfgList ) < 1 ) {
			$response['error'] = true;
			$response['errorcode'] = 404;
			$response['message'] = 'ManufacturerID not found.';
			echoRespnse( 404, $response );
		} else {
			$response['error'] = false;
			$response['errorcode'] = 200;
			$response['manufacturers'] = array();
			foreach ( $mfgList as $mfg ) {
				$tmp = array();
				foreach( $mfg as $prop=>$value ) {
					$tmp[$prop] = $value;
				}
				array_push( $response['manufacturers'], $tmp );
			}

			echoRespnse( 200, $response );
		}
	}

//
//	URL:  /api/manufacturer
//	Method: PUT
//	Params: JSON array manufacturer of all attributes defined in database table
//	Returns: 200 if successful
//
	function queueManufacturer() {
		$app = \Slim\Slim::getInstance();
		$response = array();
		$m = new ManufacturersQueue();
		$m->Name = $app->request->put('Name');
		if ( $m->queueManufacturer() ) {
			$response['error'] = false;
			$response['errorcode'] = 200;
			$response['message'] = 'Manufacturer addition has been submitted for approval.';
			$response['manufacturer'] = array( 'RequestID'=>$m->RequestID, 'Name'=>$m->Name );
			echoRespnse( 200, $response );
		} else {
			$response['error'] = true;
			$response['errorcode'] = 403;
			$response['message'] = 'Manufacturer name already exists in the database.';
			echoRespnse( 403, $response );
		}
	}

	function approveManufacturer() {
		global $currUser;

		$app = \Slim\Slim::getInstance();
		$response = array();
		$m = new ManufacturersQueue();
		$vars = json_decode( $app->request->getBody() );
		$m->Name = $vars->Name;
		$m->RequestID = $vars->RequestID;
		if ( $m->approveRequest( $currUser ) ) {
			$response['error'] = false;
			$response['errorcode'] = 200;
			$response['message'] = 'Manufacturer has been approved.';
			echoRespnse( 200, $response );
		} else {
			$response['error'] = true;
			$response['errorcode'] = 403;
			$response['message'] = 'Error processing request.';
			echoRespnse( 403, $response );
		}
	}

	function queueDeviceTemplate() {
		global $currUser;
		$app = \Slim\Slim::getInstance();
		
		$response = array();
		$t = new DeviceTemplatesQueue();
		$t->ManufacturerID = $app->request->put('ManufacturerID');
		$t->Model = $app->request->put('Model');
		$t->Height = $app->request->put('Height');
		$t->Weight = $app->request->put('Weight');
		$t->Wattage = $app->request->put('Wattage');
		$t->DeviceType = $app->request->put('DeviceType');
		$t->PSCount = $app->request->put('PSCount');
		$t->NumPorts = $app->request->put('NumPorts');
		$t->Notes = $app->request->put('Notes');
		$t->FrontPictureFile = $app->request->put('FrontPictureFile');
		$t->RearPictureFile = $app->request->put('RearPictureFile');
		$t->ChassisSlots = $app->request->put('ChassisSlots');
		$t->RearChassisSlots = $app->request->put('RearChassisSlots');
		$t->SubmittedBy = $currUser->UserID;

		if ( $t->queueDeviceTemplate() ) {
			$response['error'] = false;
			$response['errorcode'] = 200;
			$response['message'] = 'Device template queued for approval.';
		} else {
			$response['error'] = true;
			$response['errorcode'] = 403;
			$response['message'] = 'Error processing request.';
		}

		echoRespnse( $response['errorcode'], $response );
	}

$app->run();
?>
