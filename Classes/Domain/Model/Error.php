<?php

namespace Xima\XimaTypo3MetadataFixer\Domain\Model;

class Error
{
    public string $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }
}
