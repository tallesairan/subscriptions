<?php

namespace Codeassasin\Subscriptions;

use Codeassasin\Subscriptions\Subscription;

/**
* SubscriptionAbility
*/
class SubscriptionAbility
{
	/**
     * Subscription model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $subscription;

    /**
     * Create a new Subscription instance.
     *
     * @return void
     */
    function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * Determine if the feature is enabled
     *
     * @param string $feature
     * @return boolean
     */
    public function canUse($feature)
    {
    	/**
    	 * If Subscription is not active, the user has no access to the features
    	 */
    	if(! $this->subscription->active())
    		return false;

        // Get features and usage
        $feature_value = $this->value($feature);

        if (is_null($feature_value))
            return false;

        // Match "booleans" type value
        if ($this->enabled($feature) === true)
            return true;

        return false;
    }

    /**
     * Check if subscription plan feature is enabled.
     *
     * @param string $feature
     * @return bool
     */
    public function enabled($feature)
    {
        $feature_value = $this->value($feature);

        if (is_null($feature_value))
            return false;

        return true;
    }

    /**
     * Get feature value.
     *
     * @param  string $feature
     * @param  mixed $default
     * @return mixed
     */
    public function value($feature, $default = null)
    {
        $feature = $this->subscription->plan->features->first(function ($value, $key) use ($feature) {
            return $value->slug === $feature;
        });

        if (is_null($feature))
            return $default;

        return $feature->value;
    }
}