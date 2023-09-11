<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MongoDBSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $databaseName = 'laravel'; // Replace with your database name
        $collectionName = 'results'; // Replace with your collection name

        // Check if the database exists
        $databaseExists = in_array($databaseName, DB::connection('mongodb')->listDatabases());

        if (!$databaseExists) {
            // Create the database if it doesn't exist
            DB::connection('mongodb')->createDatabase($databaseName);
        }

        // Switch to the specified database
        DB::connection('mongodb')->setDefaultDatabase($databaseName);

        // Check if the collection exists
        $collectionExists = DB::collection($collectionName)->exists();

        if (!$collectionExists) {
            // Create the collection if it doesn't exist
            DB::collection($collectionName)->insert([
                [
                    'url' => 'http://127.0.0.1:8000/api/crawler',
                    'parents' => '[http://127.0.0.1:8000]',
                ],
                // Add more data as needed
            ]);
        }
    }
}
