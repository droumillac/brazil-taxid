<?php

namespace BrazilTaxId\Contracts;

use BrazilTaxId\Models\PersonData;

interface CpfProviderInterface
{
    /**
     * @param string $cpf
     * @return PersonData|null
     */
    public function consultCpf(string $cpf): ?PersonData;

    /**
     * @return string
     */
    public function getName(): string;
}
