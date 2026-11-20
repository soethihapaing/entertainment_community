<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Genre;
use App\Models\User;
use App\Services\AdminLogger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with default Admin & Master Data
     */
    public function run(): void
    {
        // 1. Create Default Administrator
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'full_name'      => 'System Administrator',
                'email'          => 'admin@community.com',
                'password_hash'  => Hash::make('admin123'),
                'role'           => 'admin',
                'account_status' => 'active',
                'created_date'   => now(),
                'updated_date'   => now(),
            ]
        );

        // 2. Create Sample Normal User
        User::firstOrCreate(
            ['username' => 'john_doe'],
            [
                'full_name'      => 'John Doe',
                'email'          => 'john@example.com',
                'password_hash'  => Hash::make('user123'),
                'role'           => 'user',
                'account_status' => 'active',
                'created_date'   => now(),
                'updated_date'   => now(),
            ]
        );

        // 3. Seed Default Master Genres
        $genres = [
            ['genre_name' => 'Action', 'description' => 'Fast-paced action content'],
            ['genre_name' => 'Adventure', 'description' => 'Exciting journeys and explorations'],
            ['genre_name' => 'Fantasy', 'description' => 'Magical and supernatural elements'],
            ['genre_name' => 'Sci-Fi', 'description' => 'Science fiction and futuristic themes'],
            ['genre_name' => 'Romance', 'description' => 'Romantic relationships and emotions'],
            ['genre_name' => 'Drama', 'description' => 'Narrative realism and character development'],
            ['genre_name' => 'Mystery', 'description' => 'Investigations and unexplained puzzles'],
            ['genre_name' => 'Comedy', 'description' => 'Humorous and entertaining stories'],
        ];

        foreach ($genres as $genre) {
            Genre::firstOrCreate(['genre_name' => $genre['genre_name']], $genre);
        }

        // 4. Seed Default Forum Categories
        $categories = [
            [
                'category_name' => 'Anime Discussions',
                'category_type' => 'anime',
                'description'   => 'Discussions related to anime series and movies',
                'created_by'    => $admin->user_id,
            ],
            [
                'category_name' => 'Movie Theatrics',
                'category_type' => 'movie',
                'description'   => 'Cinema, blockbusters and film reviews',
                'created_by'    => $admin->user_id,
            ],
            [
                'category_name' => 'Book Club',
                'category_type' => 'book',
                'description'   => 'Novels, literature and reading discussions',
                'created_by'    => $admin->user_id,
            ],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['category_name' => $cat['category_name']], $cat);
        }

        // 5. Initial Log
        AdminLogger::log(
            'SYSTEM_INITIALIZATION',
            'Database seeded with initial administrator and taxonomy master data.',
            $admin->user_id
        );
    }
}
