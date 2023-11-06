<?php

namespace tosdr\Unleash;


use Unleash\Client\ContextProvider\UnleashContextProvider;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\UnleashBuilder;


final class CustomContextProvider implements UnleashContextProvider 
{
    public function getContext(): UnleashContext
    {
        $context = new UnleashContext(
            currentUserId: $GLOBALS['guid'],
            ipAddress: '0.0.0.0', // Privacy
        );

        
        return $context;     
    }
}