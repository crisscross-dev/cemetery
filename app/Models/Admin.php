<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Admin extends Model
{
    protected $fillable = [
        'username',
        'password',
        'name',
        'email',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Set the password attribute (automatically hash)
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    /**
     * Verify password
     */
    public function verifyPassword($password)
    {
        return Hash::check($password, $this->password);
    }
}
