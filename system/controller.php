<?php
#[\AllowDynamicProperties]
class BaseController
{
    private $registry = [];

    public function __construct($registry)
    {

        $this->registry = $registry;

        $this->onLoad();
    }

    public function __get($name)
    {
        // Fetch from the registry if it exists
        if (isset($this->registry->registry[$name])) {
            return $this->registry->registry[$name];
        }
        return null; // Or throw an error if you want strict behavior
    }

    public function onLoad()
    {

    }

    public function loadView($path, $data = [])
    {
        if (file_exists(CONTENT_DIR . 'views/' . $path)) {
            extract($data);
            global $admin, $analyst;

            ob_start();
            include CONTENT_DIR . 'views/' . $path;
            $output = ob_get_clean();

            return $output;
        } else {
            // TODO: Handle error better
            throw new Exception("View file not found: " . $path);
        }

    }

    public function loadController($path, $data = [])
    {
        global $DEGUB_MODE;

        $path = !empty($path) ? trim($path, '/') : 'common/home';

        $components = explode('/', $path);

        // construct the variables for the controller
        $folder = $components[0];
        $class = $components[1] ?? $folder;
        $function = $components[2] ?? 'index';
        $extra_components = array_slice($components, 3);

        $extra_components = array_merge($extra_components, $data);

        $file = CONTENT_DIR . 'controllers/' . $folder . '/' . $class . '.php';

        try {
            if (file_exists($file)) {
                extract($data);

                include_once $file;

                $class = preg_replace_callback(
                    '/_([a-z])/',
                    function ($matches) {
                        return strtoupper($matches[1]);
                    },
                    $class
                );

                $className = ucfirst($folder) . ucfirst($class) . 'Controller';


                if (class_exists($className)) {
                    $controller = new $className($this->registry, $extra_components);
                    $output = $controller->$function($extra_components);

                    return $output;
                } else {
                    require_once CONTENT_DIR . 'controllers/common/error.php';
                    $error_controller = new CommonErrorController($this->registry);
                    $output = $error_controller->index();
                    return $output;
                }
            } else {
                require_once CONTENT_DIR . 'controllers/common/error.php';
                $error_controller = new CommonErrorController($this->registry);
                $output = $error_controller->index();
                return $output;
            }
        } catch (Exception $e) {

            if ((int) ($DEBUG_MODE ?? 0) == 1) {
                require_once CONTENT_DIR . 'controllers/common/error.php';
                $error_controller = new CommonErrorController($this->registry);
                $output = $error_controller->index();
                return $output;
            }

            if ($this->request->server['REQUEST_METHOD'] == 'POST') {
                http_response_code(500);
                return json_encode(['error' => $e->getMessage()]);
            }
            return $e->getMessage();
        }

    }



    // Method to dynamically load a model based on a path (e.g., folder/file)
    public function loadModel($path)
    {
        $modelFile = CONTENT_DIR . 'models/' . $path . '.php'; // Construct the path for the model file

        // Replace slashes in the path for the property name
        $modelName = 'model_' . str_replace('/', '_', $path);

        if (file_exists($modelFile)) {
            include_once $modelFile;

            // Convert the path to a model class name (e.g., folder_file becomes FolderFileModel)
            $className = implode('', array_map('ucfirst', explode('/', $path))); // explode
            $className = implode('', array_map('ucfirst', explode('_', $className))) . 'Model'; // explode

            if (class_exists($className)) {
                // Store the instantiated model in the models array and make it accessible as a property

                $this->$modelName = new $className($this->registry);
            } else {
                return "Model class $className not found!";
            }
        } else {
            return "Model file $modelFile not found!";
        }
    }

}