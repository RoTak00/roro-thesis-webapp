<?php
class Response
{
    private $registry = [];

    public $styles = [];
    public $scripts = [];

    public function __construct($registry)
    {
        $this->registry = $registry;

    }

    public function __get($name)
    {
        // Fetch from the registry if it exists
        if (isset($this->registry->registry[$name])) {
            return $this->registry->registry[$name];
        }
        return null; // Or throw an error if you want strict behavior
    }

    public function redirect($controller)
    {
        header('Location: ' . BASE_LOCATION . $controller);
    }

    public function addStyle($path, $options = [])
    {
        // if styles with path is found, skip
        foreach ($this->styles as $style) {
            if ($style['path'] == $path) {
                return;
            }
        }

        $this->styles[] = [
            'path' => $path,
            'media' => $options['media'] ?? 'all',
            'version' => $options['version'] ?? null,
            'integrity' => $options['integrity'] ?? null,
            'crossorigin' => $options['crossorigin'] ?? null
        ];
    }

    public function addScript($path, $options = [], $add_first = 0)
    {
        // if scripts with path is found, skip
        foreach ($this->scripts as $script) {
            if ($script['path'] == $path) {
                return;
            }
        }

        if ($add_first) {
            array_unshift($this->scripts, [
                'path' => $path,
                'version' => $options['version'] ?? null,
                'async' => $options['async'] ?? false,
                'defer' => $options['defer'] ?? false,
                'crossorigin' => $options['crossorigin'] ?? null
            ]);
            return;
        }
        $this->scripts[] = [
            'path' => $path,
            'version' => $options['version'] ?? null,
            'async' => $options['async'] ?? false,
            'defer' => $options['defer'] ?? false,
            'crossorigin' => $options['crossorigin'] ?? null
        ];
    }

    public function getStyles()
    {
        ob_start();

        foreach ($this->styles as $style) {
            $path = $style['path'];
            $media = $style['media'];
            $version = $style['version'];
            $crossorigin = $style['crossorigin'];
            $integrity = $style['integrity'];
            echo '<link rel="stylesheet" type="text/css" href="' . $path . '?version=' . $version . '" media="' . $media . '"  ' . ($integrity ? 'integrity="' . $integrity . '"' : '') . '" ' . ($crossorigin ? 'crossorigin="' . $crossorigin . '"' : '') . '>';
        }

        return ob_get_clean();
    }

    public function getScripts()
    {
        ob_start();

        foreach ($this->scripts as $script) {
            $path = $script['path'];
            $version = $script['version'];
            $async = $script['async'];
            $defer = $script['defer'];
            $crossorigin = $script['crossorigin'];

            echo '<script src="' . $path . '?version=' . $version . '" ' . ($async ? 'async' : '') . ($defer ? 'defer' : '') . ' ' . ($crossorigin ? 'crossorigin="' . $crossorigin . '"' : '') . '></script>';

        }

        return ob_get_clean();
    }

    public function addMeta($key, $value)
    {
        $this->meta[$key] = $value;
    }

}