<?php

namespace Codeassasin\Subscriptions\Traits;

use Codeassasin\Subscriptions\Models\Plan;

trait BelongsToPlan {
	
	/**
     * Get plan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsToMany
     */
    function plan()
    {
        return $this->belongsToMany(Plan::class);
    }

    /**
     * Scope by plan id.
     *
     * @param  \Illuminate\Database\Eloquent\Builder
     * @param  int $plan_id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    function scopeByPlan($query, $plan_id)
    {
        return $query->where('plan_id', $plan_id);
    }
}