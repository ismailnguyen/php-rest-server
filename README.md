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
}
```