<?php

namespace App\Model;

use Component\Model;

class User extends  Model
{
    public function  test(){
        $sql = "select * from `user` where (id = 1)";
        $result = $this->query($sql);
        var_dump($this->model->errno);
        var_dump($this->model->error);
        var_dump($result);
        return $result;
    }
}