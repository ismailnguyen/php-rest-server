<?php
	require_once "http_constants.php";
	
	abstract class ControllerBase
	{
		private $responseCode = Http::HTTP_OK; // Response code
		private $responseData = array(); // Response datas
		private $requestData = array(); // Request datas
		private $outputFormat; // Output format: xml, json
		
		protected $authentication = array(); // Authentication datas (username, and password) if provided

		public function __construct($useAuthentifaction=!USE_AUTHENTICATION)
		{
			try
			{
				$this->fetchRequest();

				$this->outputFormat = ($this->getRequest("output") != null && $this->getRequest("output") == XML_FORMAT)
										? XML_FORMAT
										: JSON_FORMAT;
										
				if ($useAuthentifaction)
					$this->handleAuthentication();
			}
			catch(Exception $e)
			{
				$this->addData(array("error" => $e->getMessage()));
				$this->setCode(Http::SERVICE_UNAVAILABLE);
				$this->response();
			}
		}
		
		/*
		 * Concrete class should override this method to handle authentification if needed
		 *
		*/
		protected function isAuthenticationValid()
		{
			return false;
		}			
		
		private function handleAuthentication()
		{
			if ($this->isAuthenticationValid())
				return;
			
			$this->addData(array("msg" => "Invalid authentication"));
			$this->setCode(Http::UNAUTHORIZED);
			$this->response();
		}

		public function addData($data)
		{
			if($data == null)
				$this->responseData["content"] = "null";
			
			foreach($data as $key => $value)
				$this->responseData[$key] = $value;
		}

		public function getMethod()
		{
			return $this->requestData["method"];
		}

		public function getRequest($_key)
		{
			try
				return array_key_exists($_key, $this->requestData) 
						? htmlentities($this->requestData[$_key]) 
						: null;
			
			catch(Exception $e)
				throw new Exception($e->getMessage());
		}

		/*
		 * Parse HTTP request contents from POST, GET, PUT and DELETE verbs
		 * Put all those values into an key/value array
		 */
		private function parseRequest()
		{
			try
			{
				if(isset($_SERVER['PHP_AUTH_USER']))
					$this->authentication["username"] = $_SERVER['PHP_AUTH_USER'];
				
				if(isset($_SERVER['PHP_AUTH_PW']))
					$this->authentication["password"] = $_SERVER['PHP_AUTH_PW'];
			
				$this->requestData["method"] = $_SERVER['REQUEST_METHOD'];
				
				if($this->requestData["method"] == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER))
				{
					if($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE')
					{
						$this->requestData["method"] = 'DELETE';
					}
					elseif($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
						$this->requestData["method"] = 'PUT';
					}
					else
					{
						throw new Exception("Unexpected Header");
					}
				}
			
				switch($this->requestData["method"])
				{
					case "DELETE":
					case "POST":
						$_method = $this->cleanInputs($_POST);
						break;
						
					case "PUT":
					case "GET":
						$_method = $this->cleanInputs($_GET);
						break;
						
					default:
						$this->setCode(Http::NOT_ALLOWED);
						break;
				}

				foreach($_method as $_key => $_value)
				{
					$this->requestData[$_key] = $_value;
				
			}
			catch(Exception $e)
			{
				$this->addData(array("error" => $e->getMessage()));
				$this->setCode(Http::BAD_REQUEST);
				$this->response();
			}
		}
		
		private function cleanInputs($responseData) {
			$clean_input = Array();
			
			if (is_array($responseData))
				foreach ($responseData as $k => $v)
					$clean_input[$k] = $this->cleanInputs($v);
			
			else
				$clean_input = trim(strip_tags($responseData));
			
			return $clean_input;
		}

		public function setCode($code)
		{
			$this->responseCode = $code;
		}

		private function getError($type)
		{
			$status = array(		
							Http::HTTP_OK => array(
										'code' => 200,
										'status' => 'OK'
									),

							Http::HTTP_CREATED => array(
										'code' => 201,
										'status' => 'Created'
									),

							Http::HTTP_ACCEPTED => array(
										'code' => 202,
										'status' => 'Accepted'
									),

							Http::BAD_REQUEST => array(
										'code' => 400,
										'status' => 'Bad Request'
									),

							Http::UNAUTHORIZED => array(
										'code' => 401,
										'status' => 'Unauthorized'
									),

							Http::FORBIDDEN => array(
										'code' => 403,
										'status' => 'Forbidden'
									),

							Http::NOT_FOUND => array(
										'code' => 404,
										'status' => 'Not Found'
									),

							Http::NOT_ALOWED => array(
										'code' => 405,
										'status' => 'Method Not Allowed'
									),

							Http::NOT_ACCEPTABLE => array(
										'code' => 406,
										'status' => 'Not Acceptable'
									),

							Http::CONFLICT => array(
										'code' => 409,
										'status' => 'Conflict'
									),

							Http::PRECONDITION_FAILED => array(
										'code' => 412,
										'status' => 'Precondition Failed'
									),
							
							Http::EXPECTATION_FAILED => array(
										'code' => 417,
										'status' => 'Expectation Failed'
									),
							
							Http::INTERNAL_SERVER_ERROR => array(
										'code' => 500,
										'status' => 'Internal Server Error'	
									),
							
							Http::NOT_IMPLEMENTED => array(
										'code' => 501,
										'status' => 'Not Implemented'
									),
							
							Http::BAD_GATEWAY => array(
										'code' => 502,
										'status' => 'Bad Gateway'
									),
							
							Http::SERVICE_UNAVAILABLE => array(
										'code' => 503,
										'status' => 'Service Unavailable'
									)
						);

			return isset($status[$this->responseCode]) 
					? $status[$this->responseCode][$type] 
					: $status[Http::NOT_IMPLEMENTED][$type];
		}

		public function response()
		{
			if($this->outputFormat == XML_FORMAT)
			{
				$this->createXmlResponse();
			}
			else
			{
				$this->createJsonResponse();
			}
			
			die(); // End all operation
		}
		
		private function createXmlResponse()
		{
			header("HTTP/1.1 ".$this->getError('code')." ".$this->getError('status'));
			header('Content-type: text/xml');
			
			echo '<?xml version="1.0"?>';
			
			echo '<response>';
			
			echo '<code>';
			echo $this->responseCode;
			echo '</code>';
			
			echo '<result>';
			
			if($this->responseData != null)
			{
				foreach($this->responseData as $index => $post)
				{
					echo '<'.$index.'>';
					
					if(is_array($post))
					{
						foreach($post as $key => $value)
						{
							echo '<'.$key.'>';
							
							if(is_array($value))
							{
								foreach($value as $tag => $val)
								{
									echo '<'.$tag.'>'.htmlentities($val).'</'.$tag.'>';
								}
							}
							else
							{
								echo htmlentities($value);
							}
							
							echo '</'.$key.'>';
						}
					}
					else
					{
						echo htmlentities($post);
					}
					
					echo '</'.$index.'>';
				}
			}
			else
			{
				echo 'null';
			}
			
			echo '</result>';
			
			echo '</response>';
		}
		
		private function createJsonResponse()
		{
			header("HTTP/1.1 ".$this->getError('code')." ".$this->getError('status'));
			header("Content-Type: application/json");
		
			echo json_encode(
					array(
						"code" => $this->responseCode,
						"result" => $this->responseData
					)
				);
		}
	}
?>
