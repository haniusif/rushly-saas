<?php
namespace App\Enums;

interface AbnormalSeverity
{
    const WARNING  = 'warning';   // 3–4 days stalled
    const DANGER   = 'danger';    // 5–6 days stalled
    const CRITICAL = 'critical';  // 7+ days stalled
}
