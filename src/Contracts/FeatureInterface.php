<?php

namespace Codeassasin\Subscriptions\Contracts;

interface FeatureInterface {

	public function plan();
    public function usage();
    
}