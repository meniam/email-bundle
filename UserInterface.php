<?php

namespace Super\SuperEmailBundle;

interface UserInterface
{
    public function getEmail();
    public function getLocale();
}