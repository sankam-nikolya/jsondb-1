<?php

class Api extends CI_Controller {

    // Verifies the token in every request
    public function __construct() {
        parent::__construct();
        $headers = getallheaders();
        if(!$headers['Token']) {
            $this->jsondb->log('(api_token_check) ERROR: Token header is not present!');
            $response = array(
                    'code' => 401,
                    'msg' => 'Token header not sent.'
                );
            $json = json_encode($response, JSON_PRETTY_PRINT);
            http_response_code(401);
            print_r($json);
            die;
        } elseif ($this->jsondb->api_token_check($headers['Token'])) {
            // Continues past the constructor to the requested method
        }
    }

    public function tables($table = false, $id = false) {
        $verb = $_SERVER['REQUEST_METHOD'];
        
        // THE GET REQUEST
        if ($verb == 'GET') {
            $field = $this->input->get('field');
            $value = $this->input->get('value');
            $sortfield = $value = $this->input->get('sorfield');
            if($table) {
                if ($id) {
                    $this->jsondb->api_get_record($table, 'id', $id);
                } else {
                    $this->jsondb->api_get_records($table, $field, $value, $sortfield);
                }
            } else {
                $this->jsondb->log($verb.' REQUEST FAILED WITH CODE 400: Missing table name in the request.');
                $response = array(
                    'code' => 400,
                    'msg' => 'Missing table name in the request.'
                );
                $json = json_encode($response, JSON_PRETTY_PRINT);
                http_response_code(400);
                print_r($json);
                die;
            }
        
        // THE POST REQUEST
        } elseif ($verb == 'POST') {
            if ($table) {
                if ($this->input->post()) {
                    $data = $this->input->post();
                    $this->jsondb->api_insert_record($table, $data);
                } else {
                    $this->log($verb.' REQUEST FAILED WITH CODE 400: Missing fields for insert.');
                    $response = array(
                        'code' => 400,
                        'msg' => 'Missing fields to insert.'
                    );
                    $json = json_encode($response, JSON_PRETTY_PRINT);
                    http_response_code(400);
                    print_r($json);
                    die;
                }
            } else {
               $this->jsondb->log($verb.' REQUEST FAILED WITH CODE 400: Missing table name in the request.');
                $response = array(
                    'code' => 400,
                    'msg' => 'Missing table name in the request.'
                );
                $json = json_encode($response, JSON_PRETTY_PRINT);
                http_response_code(400);
                print_r($json);
                die;
            }
                
        // THE DELETE REQUEST
        } elseif ($verb == 'DELETE') {
            if ($table && $id) {
                $this->jsondb->api_delete_record($table, 'id', $id);
            } else {
                $this->jsondb->log($verb.' REQUEST FAILED WITH CODE 400: Missing table name and/or id in the request.');
                $response = array(
                    'code' => 400,
                    'msg' => 'Missing table name and/or id in the request.'
                );
                $json = json_encode($response, JSON_PRETTY_PRINT);
                http_response_code(400);
                print_r($json);
                die;
            }
        
        // THE PUT REQUEST
        } elseif ($verb == 'PUT') {
            if ($table && $id) {
                parse_str(file_get_contents("php://input"),$post_vars);
                if ($post_vars) {
                    $this->jsondb->api_update_record($table, 'id', $id, $post_vars);
                } else {
                    $this->log($verb.' REQUEST FAILED WITH CODE 400: Missing fields for update.');
                    $response = array(
                        'code' => 400,
                        'msg' => 'Missing fields to update.'
                    );
                    $json = json_encode($response, JSON_PRETTY_PRINT);
                    http_response_code(400);
                    print_r($json);
                    die;
                }
            } else {
                $this->jsondb->log($verb.' REQUEST FAILED WITH CODE 400: Missing table name and/or id in the request.');
                $response = array(
                    'code' => 400,
                    'msg' => 'Missing table name and/or id in the request.'
                );
                $json = json_encode($response, JSON_PRETTY_PRINT);
                http_response_code(400);
                print_r($json);
                die;
            }
        }
    }

}