<?php

/**
 * Bootstrap class
 */
class Bootstrap {

    /**
     * Bootstrap the application and handle the client request
     * 
     * @param Request $request
     * 
     * @throws Exception
     */
    public static function run(Request $request) {
		
        $controller = ucfirst($request->getController()) . 'Controller';
		
        $method = $request->getMethod();
		
        $args = $request->getArgs();
				
		
        $controllerPath = 'apps/' . $request->getController() . '/controllers/' . $controller . '.php';
		
	
          
              	
        if (is_readable($controllerPath)) {
			
            
            require_once $controllerPath;
            
            
            $controller = new $controller();
           
            
            
            $controller->setBasePath('apps/' . $request->getController());
            
//            <pre>
//                       
//           // print_r("controller ");
//              //DIE;
//            </pre>
//              
            if (is_callable(array($controller, $method))) {
                $method = $request->getMethod();
				
            } else {
                $method = 'index';
            }
               
               
            //Execute the user function
            if (isset($args)) {
                call_user_func_array(array($controller, $method), $args); // para llamar el metodo del controller
				
            } else {
                call_user_func_array(array($controller, $method));
            }
        } else {
            throw new Exception(sprintf("The controller '%s' could not be found.", $controller));
        }
    }

}
