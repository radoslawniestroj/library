<?php

namespace App\Config;

enum UserType: string
{
    case LIBRARIAN = 'LIBRARIAN';
    case MEMBER = 'MEMBER';
}
