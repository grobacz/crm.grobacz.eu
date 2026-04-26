<?php

namespace App\Entity;

trait GeneratesUuidTrait
{
    private function initializeUuid(): void
    {
        if ($this->id !== null) {
            return;
        }

        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        $this->id = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
