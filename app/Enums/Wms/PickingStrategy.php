<?php
namespace App\Enums\Wms;

interface PickingStrategy
{
    const FIFO = 'FIFO';
    const FEFO = 'FEFO';   // First-Expired-First-Out
    const LIFO = 'LIFO';
}
