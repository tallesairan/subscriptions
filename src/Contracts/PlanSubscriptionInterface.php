<?php

namespace Codeassasin\Subscriptions\Contracts;

interface PlanSubscriptionInterface
{
    public function user();
    public function plan();
    public function getStatusAttribute();
    public function active();
    public function onTrial();
    public function suspended();
    public function canceled();
    public function ended();
    public function renew();
    public function unsuspend();
    public function suspend();
    public function cancel($immediately);
    public function changePlan($plan);
}
