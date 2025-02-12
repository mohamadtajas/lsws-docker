<?php

namespace App\Models;

use Illuminate\Support\Facades\File;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class Translation
{
    protected $filePath;
    public $lang;
    public $lang_key;
    public $lang_value;
    public $id;

    public function __construct()
    {
        $this->filePath = storage_path('app/private/translations.json');
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
        $translations = $this->all();
        return $translations->firstWhere('id', $id);
    }

    public function where($field, $operator, $value)
    {
        return $this->all()->filter(function ($translation) use ($field, $operator, $value) {
            if (strtolower($operator) == 'like') {
                return $translation->{$field} === $value;
            }
            return $translation->{$field} == $value;
        });
    }

    public function search($lang_keys, $value)
    {
        return $lang_keys->filter(function ($translation) use ($value) {
            $searchTerm = strtolower(str_replace(' ', '_', $value));

            // Case-insensitive search in both lang_key and lang_value
            return stripos($translation->lang_key, $searchTerm) !== false ||
                   stripos($translation->lang_value, $searchTerm) !== false;
        });
    }

    public function save()
    {
        $translations = $this->all();
        $maxId = $translations->max('id') ?? 0;
        $newId = $maxId + 1;

        $translations->push((object) [
            'id' => $newId,
            'lang' => $this->lang,
            'lang_key' => $this->lang_key,
            'lang_value' => $this->lang_value,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        File::put($this->filePath, $translations->toJson());
    }

    public function paginate($items, $perPage, $page = null, $options = [])
    {
        $page = $page ?: (LengthAwarePaginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

    public function update($id, $data)
    {
        $translations = $this->all();

        $index = $translations->search(function ($translation) use ($id) {
            return $translation->id == $id;
        });

        if ($index !== false) {
            $translations[$index] = array_merge((array) $translations[$index], $data);

            File::put($this->filePath, $translations->toJson(JSON_PRETTY_PRINT));

            return $translations[$index];
        }

        return null;
    }
}
