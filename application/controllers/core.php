<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class core extends MY_Controller {

    /**
     * Index Page for this controller.
     *
     * Maps to the following URL
     * 		http://example.com/index.php/welcome
     * 	- or -
     * 		http://example.com/index.php/welcome/index
     * 	- or -
     * Since this controller is set as the default controller in
     * config/routes.php, it's displayed at http://example.com/
     *
     * So any other public methods not prefixed with an underscore will
     * map to /index.php/welcome/<method_name>
     * @see https://codeigniter.com/user_guide/general/urls.html
     */
    public $data = 'processor';

    function __construct() {
        parent::__construct();
        date_default_timezone_set('Africa/Nairobi');

        $this->load->model('processor');
        $this->data = new processor();
    }
//runs every minute 
    function index() {
        $core = $this->data->receiver_processor();
        $third_uri = $this->uri->segment(3);
        if (empty($third_uri) && $third_uri !== "json") {
            $data["core"] = $core;
            $this->load->view('welcome_message', $data);
        } else {
            echo json_encode($core);
        }
    }
     function broadcast() {
      $this->data->broadcast();
    }
    function auto_broadcast() {
      $this->data->automated_broadcast();
    }        
    
    //runs every 5 mins-handles adherence messages
    function adhere() {
        $core = $this->data->adherence();
        $third_uri = $this->uri->segment(3);
        if (empty($third_uri) && $third_uri !== "json") {
            $data["core"] = $core;
            $this->load->view('welcome_message', $data);
        } else {
            echo json_encode($core);
        }
    }
   
    
    //runs every 5 mins handles responses sent to inbox 
    
    function responses() {
        $core = $this->data->responses_to_adherence();
        $third_uri = $this->uri->segment(3);
        if (empty($third_uri) && $third_uri !== "json") {
            $data["core"] = $core;
            $this->load->view('welcome_message', $data);
        } else {
            echo json_encode($core);
        }
    }
    
    //runs every 5 mins handles confirmatory message no.6 
    function confirm() {
        $core = $this->data->confirmatory_message();
        $third_uri = $this->uri->segment(3);
        if (empty($third_uri) && $third_uri !== "json") {
            $data["core"] = $core;
            $this->load->view('welcome_message', $data);
        } else {
            echo json_encode($core);
        }
    }
    
    function subcountypatients() {
        $subcountypatients = $this->data->getSubCountyPatients();
        $third_uri = $this->uri->segment(3);
        if (empty($third_uri) && $third_uri !== "json") {
            $data["subcountypatients"] = $subcountypatients;
            $this->load->view('subcountypatients', $data);
        } else {
            echo json_encode($subcountypatients);
        }
    }
    

}
