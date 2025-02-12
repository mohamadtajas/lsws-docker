<?php

namespace App\Models;

use Illuminate\Support\Facades\File;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TrendyolCategory
{
    protected $filePath;

    public function __construct()
    {
        $this->filePath = storage_path('app/private/trendyol_categories.json');
    }

    public function all()
    {
        $json = File::get($this->filePath);
        return collect(json_decode($json))->map(function ($item) {
            return (object) $item;
        });
    }

    public function find($id)
    {
        $categories = $this->all();
        return $categories->firstWhere('id', $id);
    }

    public function where($field, $operator, $value)
    {
        return $this->all()->filter(function ($category) use ($field, $operator, $value) {
            if ($operator == 'Like') {
                return stripos($category->{$field}, str_replace('%', '', $value)) !== false;
            }
            return $category->{$field} == $value;
        });
    }


    public function update($id, $data)
    {
        $categories = $this->all();

        $index = $categories->search(function ($category) use ($id) {
            return $category->id == $id;
        });

        if ($index !== false) {
            $categories[$index] = array_merge((array) $categories[$index], $data);

            File::put($this->filePath, $categories->toJson(JSON_PRETTY_PRINT));

            return $categories[$index];
        }

        return null;
    }

    public function avg($field)
    {
        $categories = $this->all();
        return $categories->avg($field);
    }
    public function paginate($items, $perPage, $page = null, $options = [])
    {
        $page = $page ?: (LengthAwarePaginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }
}
