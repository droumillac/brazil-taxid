<?php

namespace BrazilTaxId\Contracts;

use BrazilTaxId\Models\CompanyData;

interface TaxIdProviderInterface
{
    public function getName(): string;
    public function consultCnpj(string $cnpj): ?CompanyData;
}
