<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Jenssegers\Mongodb\Query\Builder as QueryBuilder;

class Crawler extends Eloquent
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'crawler';

    protected $fillable = ['url', 'parents'];

    /**
     * Store a new record or update an existing one with the same 'url' field.
     *
     * @param array $data
     * @return bool
     */
    public static function storeOrUpdate($data)
    {
        // Find an existing record with the same 'url' field
        $existingRecord = self::where('url', $data['url'])->first();

        if ($existingRecord) {
            $parent = $data['parent'] || '';
            // If the record exists, check if the new parent already exists in the 'parents' array
            if ($parent && $existingRecord->parents && !in_array($parent, $existingRecord->parents)) {
                // Add the new parent to the 'parents' array and save the record
                $existingRecord->push('parents', $parent);

                return true; // Updated an existing record
            }

            return false; // No update required, as the parent already exists
        }

        // If no existing record found, create a new one
        self::create($data);

        return true; // Created a new record
    }


    /**
     * Fetch records with a given parent value in their 'parents' field.
     *
     * @param string $parentValue
     * @return \Illuminate\Support\Collection
     */
    public static function fetchByParent($parentValue)
    {
        return self::where('parents', 'elemMatch', ['$eq' => $parentValue])->get();
    }

    /**
     * Get all unique non-null parent values from the 'parents' field.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getAllUniqueParents()
    {
        $result = self::raw(function ($collection) {
            return $collection->aggregate([
                [
                    '$unwind' => '$parents',
                ],
                [
                    '$match' => [
                        'parents' => ['$ne' => null],
                    ],
                ],
                [
                    '$group' => [
                        '_id' => '$parents',
                    ],
                ],
            ]);
        });

        // Extract the result into an array
        $uniqueParents = [];
        foreach ($result as $entry) {
            $uniqueParents[] = $entry->_id;
        }

        return collect($uniqueParents);
    }
}
