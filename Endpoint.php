<?php

// Import additionnal class into the global namespace
use \LaswitchTech\Core\Abstracts\Endpoint;
use \LaswitchTech\Core\Router;

class SearchEndpoint extends Endpoint {

    // Global Properties
    private $Log;

    /**
     * Constructor
     */
    public function __construct()
    {

        // Call Parent Constructor
        parent::__construct();

        // Retrieve the namespace
        $namespace = $this->Request->getNamespace();

        // Import Global Variables
        global $LOG;

        // Set Properties
        $this->Log = $LOG;

        // Configure the Log
        $this->Log->add('search');

        // Set Global access
        $this->Public = true;

        // Set Level
        switch($namespace){
            case "/search/index":
            case "/search/query":
                $this->Level = 1;
                break;
        }
    }

    /**
     * Update the search index
     */
    public function indexAction(): array
    {
        // Configure the Log
        $this->Log->set('search');

        // Import Global Variables
        global $CSRF;

        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => []];

        // Check the request method
        if($this->Request->getMethod() == "POST"){
            $message["data"]["CSRF"] = [
                "token" => $CSRF->token(),
                "key" => $CSRF->key()
            ];
        }

        // Check if the Note is accessible
        if($message['status'] == 200){

            // Check the request method
            if($this->Request->getMethod() == "POST"){

                // Retrieve the parameters
                $parameters = $this->Request->getParams('REQUEST');

                // Set Required Fields
                $required = ['title','route','segments','locale','origin','content','isPublic'];

                // Check if all required fields are set
                if(count(array_intersect_key(array_flip($required), $parameters)) == count($required)){

                    // Sanitize the isPublic field
                    $parameters['isPublic'] = intval(filter_var($parameters['isPublic'], FILTER_VALIDATE_BOOLEAN));

                    // Check if the route is an exclusive internal route
                    if(!in_array($parameters['route'], ['/css','/js','/img','/fonts','/favicon.ico'])){

                        // Update the index
                        if($this->Model->Search->save($parameters['title'], $parameters['route'], $parameters['isPublic'], $parameters['segments'], $parameters['locale'], $parameters['origin'], $parameters['content'])){

                            // Set the message
                            $message["data"] = "Index Saved";
                        } else {

                            // Set the message
                            $message["data"] = "Nothing to update";
                        }
                    } else {

                        // Set the message
                        $message["data"] = "Nothing to update";
                    }
                } else {
                    $message = ["status" => 400, "message" => "Bad Request", "data" => "Missing Required Fields"];
                }
            } else {
                $message = ["status" => 400, "message" => "Bad Request", "data" => "Invalid Request Method"];
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Search for a string
     */
    public function queryAction(): array
    {
        // Configure the Log
        $this->Log->set('search');

        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => []];

        // Check if the Note is accessible
        if($message['status'] == 200){

            // Check the request method
            if($this->Request->getMethod() == "GET"){

                // Retrieve the parameters
                $query = $this->Request->getParams('GET', 'query');

                // Check if all required fields are set
                if($query){

                    // Search the index
                    $indexes = $this->Model->Search->find($query, !$this->Auth->isAuthenticated());

                    // Initialize the router
                    $Router = new Router();

                    // Loop through the indexes
                    foreach($indexes as $key => $index){

                        // Retrieve the route object and add it to the index
                        $Route = $Router->routes($index['route']);

                        // Check if the route exists
                        if($Route){

                            // Add the route metadata
                            $indexes[$key]['route'] = $Route->metadata();

                            // Create an excerpt from the content
                            $indexes[$key]['excerpt'] = $this->Model->Search->excerpt($query, $index['content']);

                            // Set search score
                            $indexes[$key]['score'] = $this->Model->Search->score($query, $indexes[$key]);

                            // Unset some fields
                            unset($indexes[$key]['content']);
                            unset($indexes[$key]['searchable']);
                            unset($indexes[$key]['origin']);
                            unset($indexes[$key]['isPublic']);
                            unset($indexes[$key]['created']);
                            unset($indexes[$key]['owner']);
                            unset($indexes[$key]['locale']);
                        } else {

                            // Remove the index
                            unset($indexes[$key]);
                        }
                    }

                    // Sort the indexes by score and modified date
                    usort($indexes, static function ($a, $b) {
                        // primary: higher score first
                        $cmp = $b['score'] <=> $a['score'];
                        if ($cmp !== 0) {
                            return $cmp;
                        }
                        // secondary: newer modified date first
                        return strtotime($b['modified']) <=> strtotime($a['modified']);
                    });

                    $message["data"]["results"] = $indexes;
                } else {
                    $message = ["status" => 400, "message" => "Bad Request", "data" => "Missing Required Fields"];
                }
            } else {
                $message = ["status" => 400, "message" => "Bad Request", "data" => "Invalid Request Method"];
            }
        }

        // Return the message
        return $message;
    }
}
