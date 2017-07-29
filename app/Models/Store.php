<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'partner_id', 'address', 'info', 'is_actived',
    ];

    protected $table = 'stores';

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function activeDevices()
    {
        return $this->devices()->where('devices.is_active', true);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function vegetables()
    {
        return $this->belongsToMany(Vegetable::class, 'vegetable_in_store')
            ->withPivot(['price']);
    }
}
