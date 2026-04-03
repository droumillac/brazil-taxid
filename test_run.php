<?php
// Script de demonstração
require 'vendor/autoload.php';

use BrazilTaxId\DocumentConsultant;
use BrazilTaxId\Validators\CnpjValidator;
use BrazilTaxId\Providers\MinhaReceitaProvider;
use BrazilTaxId\Providers\BrasilApiProvider;

echo "--- TESTE DE VALIDAÇÃO ---\n";
// O algoritmo javascript usa uma validação alfanumérica que não acerta "qualquer" um.
// Para testar, precisaremos de um válido futuramente ou rodar as rotinas básicas com numéricos por ora.
$cnpjNumericoValido = '00.000.000/0001-91'; // Banco do Brasil
echo "Testando CNPJ {$cnpjNumericoValido}: " . (CnpjValidator::isValid($cnpjNumericoValido) ? "VÁLIDO" : "INVÁLIDO") . "\n";

echo "\n--- TESTE DE CONSULTA COM FALLBACK PADRÃO ---\n";
$consultant = new DocumentConsultant();
$result = $consultant->consultCnpj($cnpjNumericoValido);
print_r($result);

echo "\n--- TESTE DE CONSULTA COM PRIORIDADE CUSTOM (--MinhaReceita-- PRIMEIRO) ---\n";
$customConsultant = new DocumentConsultant([
    new MinhaReceitaProvider(),
    new BrasilApiProvider()
]);
$customResult = $customConsultant->consultCnpj('06.990.590/0001-23'); // Google Brasil
print_r($customResult);
