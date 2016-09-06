<?php

/**
 * Request class
 */
class Request {

    /**
     * Controller name
     * 
     * @var string
     */
    private $controller;

    /**
     * The method name
     * 
     * @var string
     */
    private $method;

    /**
     * The default layout name
     * 
     * @var string
     */
    private $layout = 'index';
    
    /**
     * The default layout name
     * 
     * @var string
     */
    private $format = 'html';
    
    /**
     * Arguments to pass to the method
     * 
     * @var array
     */
    private $args = array();

    /**
     * Constructor
     */
    public function __construct() {
        $query = urldecode(filter_input(INPUT_SERVER, 'QUERY_STRING', FILTER_SANITIZE_URL));
        $request_http = explode('&', $query);
        $data = array();
        
        foreach ($request_http as $value) {
            $parts = explode('=', $value, 2);
            if ($parts && count($parts) >= 2) {
                $data[$parts[0]] = $parts[1];
            }
        }
        
        $this->controller = (isset($data['c'])) ? $data['c'] : 'index';
        $this->method = (isset($data['m'])) ? $data['m'] : 'index';
        $this->layout = (isset($data['l'])) ? $data['l'] : 'index';
        $this->format = (isset($data['format'])) ? $data['format'] : 'html';
		
        unset($data['c']);
        unset($data['m']);
        unset($data['l']);
        unset($data['format']);
		
        if (count($data) > 0) {
            $this->args = $data;
        }
    }

    /**
     * The the request controller
     * 
     * @return string
     */
    public function getController() {
        return $this->controller;
    }

    /**
     * Get the method name to call to this controller
     * 
     * @return string
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * Get the layout name to call to this controller
     * 
     * @return string
     */
    public function getLayout() {
        return $this->layout;
    }
    
    /**
     * Get the page output format
     * 
     * @return string
     */
    public function getFormat() {
        return $this->format;
    }

    /**
     * The get arguments to pass to the method
     * 
     * @return array
     */
    public function getArgs() {
        return $this->args;
    }

}
