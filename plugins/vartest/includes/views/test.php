<?php

echo "I am rendered before the template";

$this->registerAfterRenderHook(function() {
    echo "I am rendered after the template";
});

const TEMPLATE_VARIABLES = array(
    "variable1" => "I am a Var"
);
