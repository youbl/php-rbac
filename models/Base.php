<?php

class Model_Base {

    protected $db;

    public function __construct() {
        // parent::__construct();
        $this->db = sqlhelper::getConnByConfig();
    }

    public function __destruct() {

    }

}
