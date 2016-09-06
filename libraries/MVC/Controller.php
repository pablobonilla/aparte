<?php

/**
 * Controller class
 */
abstract class Controller {

    /**
     * The actual request object
     * 
     * @var Request
     */
    private $request = null;

    /**
     * Application view name
     * 
     * @var string
     */
    protected $view = null;

    /**
     * Set the controller base path
     * 
     * @var string
     */
    protected $basePath = '';

    /**
     * Loaded models
     * 
     * @var array
     */
    protected $models = array();

    /**
     * Constructor
     * 
     * @param array $options
     */
    public function __construct(array $options = array()) {
        $this->request = new Request();
        $this->view = new View($this->request);
    }

    /**
     * Set the app base path
     * 
     * @param string $path
     */
    public function setBasePath($path) {
        $this->basePath = $path;
    }

    /**
     * Get base path
     * 
     * @return string
     */
    public function getBasePath() {
        return $this->basePath;
    }

    /**
     * Default index page and fallback when no view is found
     */
    protected function index() {
        
    }

    /**
     * Load the actual application model
     * 
     * @param string $model
     * 
     * @return Model
     * @throws Exception
     */
    protected function getModel($model = null) {
        $model = (empty($model) ? ucfirst($this->request->getController()) : $model);
        $model = $model . 'Model';
        $modelPath = 'apps/' . $this->request->getController() . '/models/' . $model . '.php';
		
			
        //If the model is already loaded, return it
        if (isset($this->models[$model])) {
            return $this->models[$model];
        }
        
        if (is_readable($modelPath)) {
            require_once $modelPath;
            $modelObj = new $model();

            $this->models[$model] = $modelObj;

            return $modelObj;
        } else {
            throw new Exception(sprintf("Model '%s' could not be found", $model));
        }
    }

}
