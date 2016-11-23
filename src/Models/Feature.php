<?php

namespace Codeassasin\Subscriptions\Models;

use Illuminate\Database\Eloquent\Model;
use Codeassasin\Subscriptions\Traits\BelongsToPlan;

class Feature extends Model
{
	use BelongsToPlan;
	
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'plan_id',
        'slug',
        'name',
        'description',
        'sort_order'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at'
    ];
}
