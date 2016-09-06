<?php

/**
 * Class to create JSON Response
 */
class JsonResponse extends Response {

    /**
     * Constructor
     * 
     * @param mixed $data   The response data
     * @param type $status  The HTTP status code
     * @param type $description The status code response description
     */
    public function __construct($data = null, $status = 200, $description = null) {
        //Pass argument through parent class
        parent::__construct($data, $status, $description);
    }

    /**
     * Factory method to create a response object and return a reference of this 
     * class for chaining purpose
     * 
     * @param mixed $data   The response data
     * @param type $status  The HTTP status code
     * @param type $description The status code response description
     * 
     * @return Response
     */
    public static function create($data, $status = 200, $description = null) {
        return new JsonResponse($data, $status, $description);
    }

    /**
     * Return the JSON string of this object
     * 
     * @return string
     */
    public function __toString() {
        //Building the response
        $template = array();
        $template['version'] = '1.0';
        $template['status'] = array(
            'code' => $this->getStatusCode(),
            'description' => $this->getDescription(),
        );
        $template['data'] = $this->getData();

        return json_encode($template);
    }

}
