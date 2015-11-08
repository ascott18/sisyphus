<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Bus\SelfHandling;
use Symfony\Component\Console\Input\ArrayInput;

class CommandBase extends Command implements SelfHandling
{
    /**
     * Sets the value of a command argument.
     *
     * @param  string  $key
     * @param  string  $value
     * @return void
     */
    public function setArgument($key, $value)
    {
        if ($this->input == null)
            $this->input = new ArrayInput([]);

        $this->input->setArgument($key, $value);
    }
}