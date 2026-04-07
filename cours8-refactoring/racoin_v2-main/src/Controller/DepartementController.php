<?php

namespace App\Controller;

use App\Model\Departement;

class DepartementController
{
    protected $departments = array();

    public function getAllDepartments()
    {
        return Departement::orderBy('nom_departement')->get()->toArray();
    }
}
