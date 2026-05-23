<?php
namespace App\Enums;

interface NdrStatus
{
    const OPEN        = 'open';
    const IN_PROGRESS = 'in_progress';
    const RESOLVED    = 'resolved';
    const RETURNED    = 'returned';
}
