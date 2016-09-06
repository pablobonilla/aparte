<?php

/**
 * Class to create response
 */
class Response {

    /**
     * The response data
     *
     * @var    mixed
     */
    protected $data = null;

    /**
     * The status code
     *
     * @var    integer
     */
    protected $statusCode = null;

    /**
     * The status code description
     *
     * @var    string
     */
    protected $description = null;

    /**
     * Constructor
     * 
     * @param mixed $data   The response data
     * @param type $status  The HTTP status code
     * @param type $description The status code response description
     */
    public function __construct($data = null, $status = 200, $description = null) {
        $this->data = $data;
        $this->statusCode = $status;
        $this->description = $description === null ? 'Operation succeed.' : $description;
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
        return new Response($data, $status, $description);
    }

    /**
     * Get the response data
     * 
     * @return mixed
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Get the response status code
     * 
     * @return integer
     */
    public function getStatusCode() {
        return $this->statusCode;
    }

    /**
     * The status code description
     * 
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Set the response content data
     * 
     * @param mixed $data
     * 
     * @return Response
     */
    public function setData($data) {
        $this->data = $data;

        return $this;
    }

    /**
     * Set the response status code
     * 
     * @param integer $status
     * 
     * @return Response
     */
    public function setStatusCode($status) {
        $this->statusCode = $status;

        return $this;
    }

    /**
     * Set the response status code description
     * 
     * @param string $description
     * 
     * @return Response
     */
    public function setDescription($description) {
        $this->description = $description;

        return $this;
    }

}
