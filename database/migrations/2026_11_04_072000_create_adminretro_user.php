<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $user = User::firstOrNew(['name' => 'adminretro']);
        $user->name = 'adminretro';
        $user->email = 'adminretro@kurisu.id';
        $user->password = Hash::make('Adm1n456');
        $user->save();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        User::where('name', 'adminretro')->delete();
    }
};

