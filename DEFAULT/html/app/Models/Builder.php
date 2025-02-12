<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Pagination\Paginator;

class Builder extends EloquentBuilder
{
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null , $customTotal = 0)
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);
         $total = $this->toBase()->getCountForPagination();
        if($customTotal != 0 && $total < $customTotal){
          $total = $customTotal ; 
        }
        $perPage = ($perPage instanceof Closure
            ? $perPage($total)
            : $perPage
        ) ?: $this->model->getPerPage();

        $results = $total
            ? $this->forPage($page, $perPage)->get($columns)
            : $this->model->newCollection();

        return $this->paginator($results, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }
}