# php-rest-server
PHP Rest server

## Usage
Extend your rest controller using ControllerBase class
```PHP
include("BaseController.php");

class MyController extends BaseController
{

	public function __construct()
	{
		parent::__construct();
	}
	
	public function myMethod()
	{
		try 
		{
			$currentHttpMethod = $this->getMethod(); // Give which HTTP verb is used
			
			// ...
			
			// Do your processing according to current http method
		}
		catch(Exception $e)
		{
			if(DEBUG) 
				$this->addData(array("msg" => $e->getMessage()));
				
			// Handle exception here
		}
		finally
		{
			// Print response (xml or json)
			$this->response();
		}
	}
	
	// Override this method to handle http authentication
	protected function isAuthenticationValid()
	{
		// ...
		// your logic to check authentication using 
		
		// authentication username and password are available through those variables:
		// username : $this->authentication["username"]
		// password : $this->authentication["password"]
	}
}
```