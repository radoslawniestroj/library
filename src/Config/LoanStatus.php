<?php

namespace App\Config;

enum LoanStatus: string
{
    case BORROWED = 'BORROWED';
    case RETURNED = 'RETURNED';
}
