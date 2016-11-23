<?php

namespace Codeassasin\Subscriptions\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Model;
use Codeassasin\Subscriptions\Contracts\PlanSubscriptionInterface;

class Subscription extends Model implements PlanSubscriptionInterface
{
    /**
     * Subscription statuses
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_CANCELED = 'canceled';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'plan_id',
        'active',
        'name',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'canceled_at'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at',
        'canceled_at', 'trial_ends_at', 'ends_at', 'starts_at'
    ];

    /**
     * Subscription Ability Manager instance.
     *
     * @var Gerardojbaez\LaraPlans\SubscriptionAbility
     */
    protected $ability;

    /**
     * Boot function for using with User Events.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function($model)
        {
            if (! $model->ends_at)
                $model->setNewPeriod();
        });
    }

    /**
     * Get user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(config('subscription.user'));
    }

    /**
     * Get plan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsToMany
     */
    function plan()
    {
        return $this->belongsTo(Plan::class);
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

    /**
     * Get status attribute.
     *
     * @return string
     */
    public function getStatusAttribute()
    {
        if ($this->active())
            return self::STATUS_ACTIVE;

        if ($this->suspended())
            return self::STATUS_SUSPENDED;

        if ($this->canceled())
            return self::STATUS_CANCELED;
    }

    /**
     * Check if subscription is active.
     *
     * @return bool
     */
    public function active()
    {
    	/**
    	 * If the user is suspended then we don't see them as active subscribers
    	 */
    	if($this->suspended())
    		return false;

        if (! $this->ended() || $this->onTrial())
            return true;

        return false;
    }

    /**
     * Check if subscription is trialling.
     *
     * @return bool
     */
    public function onTrial()
    {
        if (! is_null($trialEndsAt = $this->trial_ends_at))
            return Carbon::now()->lt(Carbon::instance($trialEndsAt));

        return false;
    }

     /**
     * Check if subscription is suspended.
     *
     * @return bool
     */
    public function suspended()
    {
        return $this->suspended;
    }

    /**
     * Check if subscription is canceled.
     *
     * @return bool
     */
    public function canceled()
    {
        return  ! is_null($this->canceled_at);
    }

    /**
     * Check if subscription period has ended.
     *
     * @return bool
     */
    public function ended()
    {
        $endsAt = Carbon::instance($this->ends_at);

        return Carbon::now()->gt($endsAt) OR Carbon::now()->eq($endsAt);
    }

    /**
     * Suspend subscription.
     *
     * @return $this
     */
    public function unsuspend()
    {
        $this->suspended = false;

        $this->save();

        return $this;
    }

    /**
     * Suspend subscription.
     *
     * @return $this
     */
    public function suspend()
    {
        $this->suspended = true;

        $this->save();

        return $this;
    }

    /**
     * Cancel subscription.
     *
     * @param  bool $immediately
     * @return $this
     */
    public function cancel($immediately = false)
    {
        $this->canceled_at = Carbon::now();

        if ($immediately)
            $this->ends_at = $this->canceled_at;

        $this->save();

        return $this;
    }

    /**
     * Change subscription plan.
     *
     * @param mixed $plan Plan Id or Plan Model Instance
     * @return $this
     */
    public function changePlan($plan)
    {
        if (is_numeric($plan))
            $plan = Plan::find($plan);

        // If plans doesn't have the same billing frequency (e.g., interval
        // and interval_count) we will update the billing dates starting
        // today... and sice we are basically creating a new billing cycle,
        // the usage data will be cleared.
        if (is_null($this->plan) || $this->plan->interval !== $plan->interval ||
                $this->plan->interval_count !== $plan->interval_count)
        {
            // Set period
            $this->setNewPeriod($plan->interval, $plan->interval_count);
        }

        // Attach new plan to subscription
        $this->plan_id = $plan->id;

        return $this;
    }

    /**
     * Renew subscription period.
     *
     * @throws  \LogicException
     * @return  $this
     */
    public function renew()
    {
        if ($this->ended() && $this->canceled()) {
            throw new \LogicException(
                'Unable to renew due to canceled and ended subscription.'
            );
        }

        $subscription = $this;

        DB::transaction(function() use ($subscription) {
            // Renew period
            $subscription->setNewPeriod();
            $subscription->canceled_at = null;
            $subscription->save();
        });

        return $this;
    }

    /**
     * Get Subscription Ability instance.
     *
     * @return \Gerardojbaez\LaraPlans\SubscriptionAbility
     */
    public function ability()
    {
        if (is_null($this->ability))
            return new SubscriptionAbility($this);

        return $this->ability;
    }

    /**
     * Find by user id.
     *
     * @param  \Illuminate\Database\Eloquent\Builder
     * @param  int $user_id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, $user_id)
    {
        return $query->where('user_id', $user_id);
    }

    /**
     * Find subscription with an ending trial.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindEndingTrial($query, $dayRange = 3)
    {
        $from = Carbon::now();
        $to = Carbon::now()->addDays($dayRange);

        $query->whereBetween('trial_ends_at', [$from, $to]);
    }

    /**
     * Find subscription with an ended trial.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindEndedTrial($query)
    {
        $query->where('trial_ends_at', '<=', date('Y-m-d H:i:s'));
    }

    /**
     * Find renewable subscriptions.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindRenewable($query)
    {
        $query->whereDate('ends_at', Carbon::today());
    }

    /**
     * Find ending subscriptions.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindEndingPeriod($query, $dayRange = 3, $from = null)
    {
        $from = $from ?? Carbon::now();
        $to = $from->copy()->addDays($dayRange);

        $query->whereBetween('ends_at', [$from, $to]);
    }

    /**
     * Find ended subscriptions.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindEndedPeriod($query)
    {
        $query->where('ends_at', '<=', date('Y-m-d H:i:s'));
    }

    /**
     * Set subscription period.
     *
     * @param  string $interval
     * @param  int $interval_count
     * @param  string $start Start date
     * @return  $this
     */
    protected function setNewPeriod($interval = '', $interval_count = '', $start = '')
    {
        if (empty($interval))
            $interval = $this->plan->interval;

        if (empty($interval_count))
            $interval_count = $this->plan->interval_count;

        $period = new Period($interval, $interval_count, $start);

        $this->starts_at = $period->getStartDate();
        $this->ends_at = $period->getEndDate();

        return $this;
    }
}
