<?php
	require_once( "../db.inc.php" );
	require_once( "../repo.inc.php" );
	require_once( "../Slim/Slim.php" );

	\Slim\Slim::registerAutoloader();

	$app = new \Slim\Slim();
	
	$app->get( '/template', 'getTemplate' );
	$app->get( '/template/:templateid', 'getTemplateByID' );
	$app->get( '/template/byid/:templateid', 'getTemplateByID' );
	$app->get( '/template/bymanufacturer/:manufacturerid', 'getTemplateByManufacturer' );
	$app->get( '/template/pending/', 'authenticate', 'getPendingTemplate' );
	$app->get( '/template/pending/:requestid', 'authenticate', 'getPendingTemplateByID' );

	$app->get( '/manufacturer', 'getManufacturer' );
	$app->get( '/manufacturer/:manufacturerid', 'getManufacturerByID' );
	$app->get( '/manufacturer/byid/:manufacturerid', 'getManufacturerByID' );
	$app->get( '/manufacturer/pending/', 'authenticate', 'getPendingManufacturer' );
	$app->get( '/manufacturer/pending/:requestid', 'authenticate', 'getPendingManufacturerByID' );
	$app->get( '/manufacturer/pending/byid/:requestid', 'authenticate', 'getPendingManufacturerByID' );
	
	$app->put( '/manufacturer', 'authenticate', 'queueManufacturer' );

	$app->post( '/manufacturer/approve', 'authenticate', 'approveManufacturer' );
	$app->post( '/manufacturer/pending/delete/:requestid', 'authenticate', 'deletePendingManufacturer' );
	$app->post( '/template/approve', 'authenticate', 'approveTemplate' );

	$app->put( '/template', 'authenticate', 'queueTemplate' );
	$app->put( '/templatealt', 'authenticate', 'queueTemplateAlt' );
	$app->post( '/template/pending/delete/:requestid', 'authenticate', 'deletePendingTemplate' );
	$app->post( '/template/addpictures/:requestid', 'authenticate', 'queuePictures' );

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
		if ( isset( $_SESSION['userid'] ) ) {
			$currUser->UserID = $_SESSION['userid'];
			$currUser->verifyLogin( $_SERVER["REMOTE_ADDR"] );
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
			$currUser->UserID = $currUser->verifyAPIKey($apikey, $ipaddress);
			if ( $currUser == false ) {
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

	function buildTemplateResponse( $tmp ) {
                $dt = new DeviceTemplates();
		$ct = new CDUTemplates();
		$sen = new SensorTemplates();
                $ts = new ChassisSlots();
                $tp = new TemplatePorts();
                $tpp = new TemplatePowerPorts();

                $tmpl = array();
                foreach( $tmp as $prop=>$value ) {
                        $tmpl[$prop] = $value;
                }
		if ( $tmp->FrontPictureFile != "" ) {
			$tmpl["FrontPictureFile"] = "https://repository.opendcim.org/images/approved/" . $tmp->TemplateID . "." . $tmp->FrontPictureFile;
		}
		if ( $tmp->RearPictureFile != "" ) {
			$tmpl["RearPictureFile"] = "https://repository.opendcim.org/images/approved/" . $tmp->TemplateID . "." . $tmp->RearPictureFile;
		}
                if ( $tmp->DeviceType == "Chassis" ) {
                        $sList = $ts->getSlots( $tmp->TemplateID );
                        $tmpl['slots'] = array();
                        foreach ( $sList as $slot ) {
                        	$tmpSlot = array();
                                foreach ( $slot as $prop=>$value ) {
                                        $tmpSlot[$prop] = $value;
                                }
                                $tmpl['slots'][] = $tmpSlot;
                        }
                }
		if ( $tmp->DeviceType == "CDU" ) {
			$ct->getTemplate( $tmp->TemplateID );

                        $tmpl['cdutemplate'] = array();
                        foreach ( $ct as $prop=>$value ) {
                                $tmpl['cdutemplate'][$prop] = $value;
                        }
		}
                if ( $tmp->DeviceType == "Sensor" ) {
                        $tmpl['sensortemplate'] = array();

                        $sen->getTemplate( $tmp->TemplateID );
                        foreach ( $sen as $prop=>$val ) {
                                $tmpl["sensortemplate"][$prop] = $val;
                        }
                }
                $pList = $tp->getPorts( $tmp->TemplateID );
                $tmpl['ports'] = array();
                foreach( $pList as $port ) {
                        $tmpPort = array();
                        foreach( $port as $prop=>$value ) {
                                $tmpPort[$prop] = $value;
                        }
                        $tmpl['ports'][] = $tmpPort;
                }

                $ppList = $tpp->getPorts( $tmp->TemplateID );
                $tmpl['powerports'] = array();
                foreach ( $ppList as $pp ) {
                        $tmpPwr = array();
                        foreach ( $pp as $prop=>$value ) {
                                $tmpPwr[$prop] = $value;
                        }
                        $tmpl['powerports'][] = $tmpPwr;
                }

		return $tmpl;
	}

	function getTemplate() {
		$dt = new DeviceTemplates();
		$dtList = $dt->getDeviceTemplate();

		$response['error'] = false;
		$response['errorcode'] = 200;
		$response['templates'] = array();
		foreach ( $dtList as $tmp ) {
			array_push( $response['templates'], buildTemplateResponse( $tmp ) );
		}

		echoRespnse( 200, $response );
	}

	function getTemplateByID( $templateid ) {
                 // This is the "meat" of the templates, so any ancillary objects need to be returned as well
                $dt = new DeviceTemplates();
		$ct = new CDUTemplates();
		$sen = new SensorTemplates();
                $ts = new ChassisSlots();
                $tp = new TemplatePorts();
                $tpp = new TemplatePowerPorts();

                $dtList = $dt->getDeviceTemplate( $templateid );

                $response['error'] = false;
                $response['errorcode'] = 200;
                $response['templates'] = array();
                foreach( $dtList as $tmp ) {
                        array_push( $response['templates'], buildTemplateResponse( $tmp ) );
                }

                echoRespnse( 200, $response );
	}

	function getTemplateByManufacturer( $manufacturerid ) {
                $dt = new DeviceTemplates();
		$ct = new CDUTemplates();
		$sen = new SensorTemplates();
                $ts = new ChassisSlots();
                $tp = new TemplatePorts();
                $tpp = new TemplatePowerPorts();
		$dtList = $dt->getDeviceTemplateByMFG( $manufacturerid );

		$response['error'] = false;
		$response['errorcode'] = 200;
		$response['templates'] = array();
		foreach ( $dtList as $tmp ) {
			array_push( $response['templates'], buildTemplateResponse( $tmp ) );
		}

		echoRespnse( 200, $response );
	}

	function getPendingTemplate() {
		// This is more of a high level list, for picking what to drill down to, so none of the ancillary objects are returned
		$dt = new DeviceTemplatesQueue();
		$dtList = $dt->viewStatus();

		$response['error'] = false;
		$response['errorcode'] = 200;
		$response['templatequeue'] = array();
		foreach( $dtList as $tmp ) {
			$tmpl = array();
			foreach( $tmp as $prop=>$value ) {
				$tmpl[$prop] = $value;
			}
			array_push( $response['templatequeue'], $tmpl );
		}

		echoRespnse( 200, $response );
	}

	function getPendingTemplateByID( $requestid ) {
		// This is the "meat" of the templates, so any ancillary objects need to be returned as well
		$dt = new DeviceTemplatesQueue();
		$ct = new CDUTemplatesQueue();
		$sen = new SensorTemplatesQueue();
		$ts = new ChassisSlotsQueue();
		$tp = new TemplatePortsQueue();
		$tpp = new TemplatePowerPortsQueue();

		$dtList = $dt->viewStatus( $requestid );

		$response['error'] = false;
		$response['errorcode'] = 200;
		$response['templatequeue'] = array();
                foreach( $dtList as $tmp ) {
                        $tmpl = array();
                        foreach( $tmp as $prop=>$value ) {
                                $tmpl[$prop] = $value;
                        }
			if ( $tmp->DeviceType == "Chassis" ) {
				$sList = $ts->getSlots( $requestid );
				$tmpl['slots'] = array();
				foreach ( $sList as $slot ) {
					$tmpSlot = array();
					foreach ( $slot as $prop=>$value ) {
						$tmpSlot[$prop] = $value;
					}
					$tmpl['slots'][] = $tmpSlot;
				}
			}
			if ( $tmp->DeviceType == "CDU" ) {
				$ct->getTemplate( $requestid );

				$tmpl['cdutemplate'] = array();
				foreach ( $ct as $prop=>$value ) {
					$tmpl['cdutemplate'][$prop] = $value;
				}
			}
			if ( $tmp->DeviceType == "Sensor" ) {
				$tmpl['sensortemplate'] = array();

				$sen->getTemplate( $requestid );
				foreach ( $sen as $prop=>$val ) {
					$tmpl["sensortemplate"][$prop] = $val;
				}
			}
			$pList = $tp->getPorts( $requestid );
			$tmpl['ports'] = array();
			foreach( $pList as $port ) {
				$tmpPort = array();
				foreach( $port as $prop=>$value ) {
					$tmpPort[$prop] = $value;
				}
				$tmpl['ports'][] = $tmpPort;
			}

			$ppList = $tpp->getPorts( $requestid );
			$tmpl['powerports'] = array();
			foreach ( $ppList as $pp ) {
				$tmpPwr = array();
				foreach ( $pp as $prop=>$value ) {
					$tmpPwr[$prop] = $value;
				}
				$tmpl['powerports'][] = $tmpPwr;
			}
			
                        array_push( $response['templatequeue'], $tmpl );
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
//	POST Operations (Updates to existing data)
//

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

	function approveTemplate() {
		global $currUser;

		$app = \Slim\Slim::getInstance();
		$response = array();
		$response['error'] = false;
		$response['errorcode'] = 200;
		$response['message'] = "Template has been approved.";

		$dt = new DeviceTemplatesQueue();
		$vars = json_decode( $app->request->getBody() );
		foreach ( $dt as $prop=>$value ) {
			$dt->$prop = @$vars->$prop;
		}

		if ( ! $dt->approveRequest( $currUser ) ) {
			$response['error'] = true;
			$response['errorcode'] = 400;
			$response['message'] = 'Error processing request.';
		}

		if ( is_array( @$vars->powerports ) ) {
			$pp = new TemplatePowerPortsQueue();
			$tpp = new TemplatePowerPorts;
			$tpp->flushPorts( $dt->TemplateID );
			foreach ( $vars->powerports as $pPort ) {
				foreach ( $pp as $prop=>$value ) {
					$pp->$prop = @$pPort->$prop;
				}
				$pp->TemplateID = $dt->TemplateID;
 				$pp->approveRequest();
			}
		}
		if ( is_array( @$vars->ports ) ) {
			// Flush out any existing ports
			$tp = new TemplatePorts();
			$tp->flushPorts( $dt->TemplateID );

			$p = new TemplatePortsQueue();
			foreach ( $vars->ports as $Port ) {
				foreach ( $p as $prop=>$value ) {
					$p->$prop = @$Port->$prop;
				}
				$p->TemplateID = $dt->TemplateID;
				$p->approveRequest();
			}
		}
		if ( is_array( @$vars->slots ) ) {
			$tsc = new ChassisSlots();
			$tsc->flushSlots( $dt->TemplateID );

			$s = new ChassisSlotsQueue();
			foreach( $vars->slots as $slot ) {
				foreach ( $s as $prop=>$value ) {
					$s->$prop = @$slot->$prop;
				}
				$s->TemplateID = $dt->TemplateID;
				$s->approveRequest();
			}
		}
		if ( $dt->DeviceType == "CDU" && is_object( $vars->cdutemplate ) ) {
			$ct = new CDUTemplatesQueue();
			foreach ( $vars->cdutemplate as $prop=>$val ) {
				$ct->$prop = $val;
			}
			$ct->TemplateID = $dt->TemplateID;

			$ct->approveRequest();
		}
		if ( $dt->DeviceType == "Sensor" && is_object( $vars->sensortemplate ) ) {
			$sen = new SensorTemplatesQueue();
			foreach ( $vars->sensortemplate as $prop=>$val ) {
				$sen->$prop = $val;
			}
			$sen->TemplateID = $dt->TemplateID;

			$sen->approveRequest();
		}

		echoRespnse( $response['errorcode'], $response );
	}

	function deletePendingTemplate( $RequestID ) {
		global $currUser;

		$response = array();
		$response['error'] = false;
		$response['errorcode'] = 200;

		$t = new DeviceTemplatesQueue();
		if ( $currUser->Administrator ) {
			if ( ! $t->deleteRequest( $RequestID ) ) {
				$response['error'] = true;
				$response['errorcode'] = 403;
				$response['message'] = 'Error processing request.';
			}
		}

		echoRespnse( $response['errorcode'], $response );
	}

	function deletePendingManufacturer( $RequestID ) {
		global $currUser;

		$response = array();
		$response['error'] = false;
		$response['errorcode'] = 200;

		$m = new ManufacturersQueue();
		if ( $currUser->Administrator ) {
			if ( ! $m->deleteRequest( $RequestID ) ) {
				$response['error'] = true;
				$response['errorcode'] = 403;
				$response['message'] = 'Error processing request.';
			}
		}

		echoRespnse( $response['errorcode'], $response );
	}

	function queuePictures( $RequestID ) {
		global $currUser;
		$app = \Slim\Slim::getInstance();

		$response = array();
		$response['error'] = false;
		$response['errorcode'] = 200;
		$response['files']=$_FILES;

		if ( isset( $_FILES["front"] ) ) {
			$fn = '/home/dcim/repo/repo/images/submitted/' . $RequestID . "." . $_FILES["front"]["name"];
			if ( ! move_uploaded_file($_FILES["front"]["tmp_name"], $fn ) ){
				error_log( "Error moving file " . $fn );
				$response['error'] = true;
				$response['errorcode'] = 400;
				$response['message'] = 'Unable to relocate temporary file.';
				error_log( "Error saving file submission " . $_FILES["front"]["name"] );
			}
		}

		if ( isset( $_FILES['rear'] ) ) {
			$fn = '/home/dcim/repo/repo/images/submitted/' . $RequestID . '.' . $_FILES["rear"]["name"];
			if ( ! move_uploaded_file($_FILES["rear"]["tmp_name"], $fn ) ) {
				error_log( "Rear file " . $_FILES['rear']['name'] . " uploaded." );
				$response['error'] = true;
				$response['errorcode'] = 400;
				$response['message'] = 'Unable to relocate temporary file.';
				error_log( "Error saving file submission " . $_FILES["rear"]["name"] );
			}
		}

		echoRespnse( $response['errorcode'], $response );

	}

//
//	PUT Operations (Creation of New Data)
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
			$response['message'] = 'Manufacturer name already in pending submission queue.';
			echoRespnse( 403, $response );
		}
	}

	function queueTemplate() {
		global $currUser;
		$app = \Slim\Slim::getInstance();
		$vars = json_decode( $app->request()->getBody() );
		$dType = @$vars->template->DeviceType;

		$response = array();
		$response['error'] = false;
		$response['errorcode'] = 200;

		$t = new DeviceTemplatesQueue();
		$tp = new TemplatePortsQueue();
		$sc = new ChassisSlotsQueue();
		$pp = new TemplatePowerPortsQueue();

		foreach ( $t as $prop => $value ) {
			$t->$prop = isset( $vars->template->$prop ) ? $vars->template->$prop : '';
		}

		$t->TemplateID = @$vars->template->GlobalID;

		$t->SubmittedBy = $currUser->UserID;

		if ( $t->queueDeviceTemplate() ) {
			$response['error'] = false;
			$response['errorcode'] = 200;
			$response['message'] = 'Device template queued for approval.';
			$response['template'] = array( "RequestID" => $t->RequestID );
		} else {
			$response['error'] = true;
			$response['errorcode'] = 403;
			$response['message'] = 'Error processing request.';
		}

		$sc->RequestID = $t->RequestID;
		$sc->TemplateID = $t->TemplateID;
		if ( $dType == "Chassis" ) {
			if ( is_array( @$vars->slots ) ) {
				$sc->queueSlots( $vars->slots );
			}
		}

		if ( $dType == "CDU" ) {
			$ct = new CDUTemplatesQueue();
			if ( @is_object( $vars->cdutemplate ) ) {
				foreach ( $vars->cdutemplate as $prop=>$value ) {
					$ct->$prop = $value;
				}

				$ct->RequestID = $t->RequestID;
				$ct->TemplateID = $t->TemplateID;

				$ct->queueTemplate();
			}
		}

		if ( $dType == "Sensor" ) {
			$sen = new SensorTemplatesQueue();
			if ( @is_object( $vars->sensortemplate ) ) {
				foreach ( $vars->sensortemplate as $prop=>$val ) {
					$sen->$prop = $val;
				}

				$sen->RequestID = $t->RequestID;
				$sen->TemplateID = $t->TemplateID;

				$sen->queueTemplate();
			}
		}

		$tp->RequestID = $t->RequestID;
		$tp->TemplateID = $t->TemplateID;
		if ( @is_array( $vars->templateports ) ) {
			$tp->queuePorts( $vars->templateports );
		}

		$pp->RequestID = $t->RequestID;
		$pp->TemplateID = $t->TemplateID;
		if ( @is_array( $vars->templatepowerports ) ) {
			$pp->queuePorts( $vars->templatepowerports );
		}

		echoRespnse( $response['errorcode'], $response );
	}

	function queueTemplateAlt() {
		global $currUser;
		$app = \Slim\Slim::getInstance();
		// Convert submitted data into objects
		$vars=new StdClass();
		foreach($app->request()->put() as $i => $arr){
			$vars->$i=(object) $arr;
		}
		$dType = @$vars->template->DeviceType;

		$response = array();
		$response['error'] = false;
		$response['errorcode'] = 200;

		$t = new DeviceTemplatesQueue();
		$tp = new TemplatePortsQueue();
		$sc = new ChassisSlotsQueue();
		$pp = new TemplatePowerPortsQueue();

		foreach ( $t as $prop => $value ) {
			$t->$prop = isset( $vars->template->$prop ) ? $vars->template->$prop : '';
		}

		$t->TemplateID = @$vars->template->GlobalID;

		$t->SubmittedBy = $currUser->UserID;

		if ( $t->queueDeviceTemplate() ) {
			$response['error'] = false;
			$response['errorcode'] = 200;
			$response['message'] = 'Device template queued for approval.';
			$response['template'] = array( "RequestID" => $t->RequestID );
		} else {
			$response['error'] = true;
			$response['errorcode'] = 403;
			$response['message'] = 'Error processing request.';
		}

		$sc->RequestID = $t->RequestID;
		$sc->TemplateID = $t->TemplateID;
		if ( $dType == "Chassis" ) {
			if ( is_object( @$vars->slots ) ) {
				$sc->queueSlots( $vars->slots );
			}
		}

		if ( $dType == "CDU" ) {
			$ct = new CDUTemplatesQueue();
			if ( @is_object( $vars->cdutemplate ) ) {
				foreach ( $vars->cdutemplate as $prop=>$value ) {
					$ct->$prop = $value;
				}

				$ct->RequestID = $t->RequestID;
				$ct->TemplateID = $t->TemplateID;

				$ct->queueTemplate();
			}
		}

		if ( $dType == "Sensor" ) {
			$sen = new SensorTemplatesQueue();
			if ( @is_object( $vars->sensortemplate ) ) {
				foreach ( $vars->sensortemplate as $prop=>$val ) {
					$sen->$prop = $val;
				}

				$sen->RequestID = $t->RequestID;
				$sen->TemplateID = $t->TemplateID;

				$sen->queueTemplate();
			}
		}

		$tp->RequestID = $t->RequestID;
		$tp->TemplateID = $t->TemplateID;
		if ( @is_object( $vars->templateports ) ) {
			$tp->queuePorts( $vars->templateports );
		}

		$pp->RequestID = $t->RequestID;
		$pp->TemplateID = $t->TemplateID;
		if ( @is_object( $vars->templatepowerports ) ) {
			$pp->queuePorts( $vars->templatepowerports );
		}

		echoRespnse( $response['errorcode'], $response );
	}
$app->run();
?>
