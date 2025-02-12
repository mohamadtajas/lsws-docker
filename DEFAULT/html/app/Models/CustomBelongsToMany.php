<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Pagination\Paginator;

class CustomBelongsToMany extends BelongsToMany
{
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null, $customTotal = 0)
    {
        $this->query->addSelect($this->shouldSelect($columns));

        return tap($this->query->paginate($perPage, $columns, $pageName, $page, $customTotal), function ($paginator) {
            $this->hydratePivotRelation($paginator->items());
        });
    }
}
