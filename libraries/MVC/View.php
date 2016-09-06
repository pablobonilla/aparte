<?php

/**
 * View class
 */
class View {

    /**
     * The actual request object
     * 
     * @var Request
     */
    private $request = null;

    /**
     * The controller name
     * 
     * @var string
     */
    private $controller = null;

    /**
     * List of javascript to include to the page
     * 
     * @var array 
     */
    private $scripts = array();

    /**
     * List of CSS styles to include to the page
     * 
     * @var array 
     */
    private $styles = array();

    /**
     * Loaded models
     * 
     * @var array
     */
    protected $models = array();

    /**
     * Constructor
     * 
     * @param Request $request
     */
    public function __construct(Request $request) {
        $this->request = $request;
        $this->controller = $request->getController();
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

    /**
     * Render the actual application html, styles and scripts
     * 
     * @param string $layout
     * 
     * @return void
     * @throws Exception
     */
    public function render($layout = null) {
        //Set the default layout
        if (empty($layout)) {
            $layout = $this->request->getLayout();
        }

        //Build the view path
        $viewPath = 'apps/' . $this->controller . '/views/' . $layout . '.php';
       
        if (is_readable($viewPath)) {
            //Include the header
            if ($this->request->getFormat() === 'report') {
                require_once 'header_report.php';
            } else {
                require_once 'header.php';
                
            }

            //Include the CSS style sheets
            if (count($this->styles)) {
                foreach ($this->styles as $file) {
                    echo '<link rel="stylesheet" type="text/css" href="' . $file . '"/>';
                }
            }

            //Include the javascript files
            if (count($this->scripts)) {
                foreach ($this->scripts as $file) {
                    echo '<script type="text/javascript" src="' . $file . '"></script>';
                }
            }

            //Include the view
            
            require_once $viewPath;
                
            //Include the footer
            if ($this->request->getFormat() === 'report') {
                require_once 'footer_report.php';
            } else {
                require_once 'footer.php';
            }
        } else {
            throw new Exception(sprintf("The view '%s' could not be found.", $layout));
        }
    }

    /**
     * Add the javascript file to the script collection to be include when the page get rendered
     * 
     * @param string $file
     * @param string $path
     * 
     * @return void
     */
    public function addScript($file, $path = null) {

        $filePath = ($path ? ($path . $file) : ('apps/' . $this->controller . '/js/' . $file));

        array_push($this->scripts, $filePath);
    }

    /**
     * Add the CSS file to the styles collection to be include when the page get rendered
     * 
     * @param string $file
     * @param string $path
     * 
     * @return void
     */
    public function addStyle($file, $path = null) {
        $filePath = ($path ? ($path . $file) : ('apps/' . $this->controller . '/css/' . $file));
        array_push($this->styles, $filePath);
    }

}
