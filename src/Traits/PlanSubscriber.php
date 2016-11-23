<?php

namespace Codeassasin\Subscriptions\Traits;

use Codeassasin\Subscriptions\Models\Subscription;
use Codeassasin\Subscriptions\SubscriptionBuilder;

trait PlanSubscriber {

	/**
     * Get a subscription by name.
     *
     * @param  string $name
     * @return \Gerardojbaez\LaraPlans\Models\Subscription|null
     */
    public function subscription($name = 'default')
    {
        return $this->subscriptions->sortByDesc(function ($value) {
            return $value->created_at->getTimestamp();
        })
        ->first(function ($value, $key) use ($name) {
            return $value->name === $name;
        });
    }

    /**
     * Get user plan subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Check if the user has a given subscription.
     *
     * @param  string $subscription
     * @param  int $planId
     * @return bool
     */
    public function subscribed($subscription = 'default', $planId = null)
    {
        $subscription = $this->subscription($subscription);

        if (is_null($subscription))
            return false;

        if (is_null($planId))
            return $subscription->active();

        if ($planId == $subscription->plan_id && $subscription->active())
            return true;

        return false;
    }

    /**
     * Subscribe user to a new plan.
     *
     * @param string $subscription
     * @param mixed $plan
     * @return \Gerardojbaez\LaraPlans\Models\PlanSubscription
     */
    public function newSubscription($subscription = 'default', $plan)
    {
        return new SubscriptionBuilder($this, $subscription, $plan);
    }

}