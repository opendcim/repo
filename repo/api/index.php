<?php
	require_once( "../db.inc.php" );
	require_once( "../repo.inc.php" );
	require_once( "../Slim/Slim.php" );

	\Slim\Slim::registerAutoloader();

	$app = new \Slim\Slim();

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
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
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
 * Need to accept all options requests for PUT calls to work via jquery
 */
$app->options('/(:name+)', function() use ($app) {
    $app->response()->header('Access-Control-Allow-Origin','*');
    $app->response()->header('Access-Control-Allow-Headers', 'X-Requested-With, X-authentication, X-client');
});

/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();
 
    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        // get the api key
        $apikey = $headers['Authorization'];
        // validating api key
		
		/*
        if (!APIKey::isValidKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            // get user primary key id
            $user = APIKey::getUserId($api_key);
            if ($user != NULL)
                $user_id = $user["id"];
        }
		*/
		
		global $user_id;
		$user_id='dcim';
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
  *
  *		API GET Methods go here
  *
  *		GET Methods should be for retrieving single values or a collection of values.  Not to be used for
  *			any functions that would modify data within the database.
  *
  **/

$app->get('/cdutemplate', function() {
});

$app->get('/devicetemplate', function() {
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
});

$app->get('/devicetemplate/bymanufacturer/:manufacturerid', function( $manufacturerid ) {
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
});

//
//	URL:  /api/manufacturer
//	Method: GET
//	Params:  none
//	Returns:  List of all manufacturers in the database
//
$app->get('/manufacturer', function() {
	$m = new Manufacturers();
	$mfgList = $m->getManufacturer();

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
});

$app->get( '/sensortemplate', function() {
});

$app->get('/manufacturer/:manufacturerid', function($ManufacturerID) {
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
});

//
//	URL:  /api/manufacturer
//	Method: PUT
//	Params: JSON array manufacturer of all attributes defined in database table
//	Returns: 200 if successful
//
$app->put('/manufacturer', function() use ($app) {
	$response = array();
	$m = new Manufacturers();
	$m->Name = $app->request->put('Name');
	if ( $m->addManufacturer() ) {
		$response['error'] = false;
		$response['errorcode'] = 200;
		$response['message'] = 'Manufacturer addition has been submitted for approval.';
		$response['manufacturer'] = array( 'GlobalID'=>$m->GlobalID, 'Name'=>$m->Name );
		echoRespnse( 200, $response );
	} else {
		$response['error'] = true;
		$response['errorcode'] = 403;
		$response['message'] = 'Manufacturer name already exists in the database.';
		echoRespnse( 403, $response );
	}
});

$app->put('/devicetemplate', function() use ($app) {
	$response = array();
	$t = new DeviceTemplates();
	// We'll run through MakeSafe() inside the object
	$t->ManufacturerID = $app->request->post('ManufacturerID');
	$t->Model = $app->request->post('Model');
	$t->Height = $app->request->post('Height');
	$t->Weight = $app->request->post('Weight');
	$t->Wattage = $app->request->post('Wattage');
	$t->DeviceType = $app->request->post('DeviceType');
	$t->PSCount = $app->request->post('PSCount');
	$t->NumPorts = $app->request->post('NumPorts');
	$t->Notes = $app->request->post('Notes');
	$t->FrontPictureFile = $app->request->post('FrontPictureFile');
	$t->RearPictureFile = $app->request->post('RearPictureFile');
	$t->ChassisSlots = $app->request->post('ChassisSlots');
	$t->RearChassisSlots = $app->request->post('RearChassisSlots');
	$t->SumbmittedBy = '';
	
});

$app->run();
?>
