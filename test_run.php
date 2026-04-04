<?php
// Script de demonstração
require 'vendor/autoload.php';

use BrazilTaxId\DocumentConsultant;
use BrazilTaxId\Validators\CnpjValidator;
use BrazilTaxId\Validators\CpfValidator;
use BrazilTaxId\Providers\MinhaReceitaProvider;
use BrazilTaxId\Providers\BrasilApiProvider;
use BrazilTaxId\Providers\SerproProvider;

echo "--- TESTE DE VALIDAÇÃO DE DOCUMENTOS ---\n";
$cnpjNumericoValido = '00.000.000/0001-91'; // Banco do Brasil
echo "Testando CNPJ {$cnpjNumericoValido}: " . (CnpjValidator::isValid($cnpjNumericoValido) ? "VÁLIDO" : "INVÁLIDO") . "\n";

$cpfExemplo = '000.000.000-00'; // Você pode usar um gerador de CPF para testar
echo "Testando CPF {$cpfExemplo}: " . (CpfValidator::isValid($cpfExemplo) ? "VÁLIDO" : "INVÁLIDO") . "\n";

echo "\n--- TESTE DE CONSULTA CNPJ (FREE APIS) ---\n";
// Instância padrão rodará pelas APIs gratuitas de CNPJ. Para CPF, nenhuma gratuita vem de fábrica.
$consultant = new DocumentConsultant();
$resultCnpj = $consultant->consultCnpj($cnpjNumericoValido);
if ($resultCnpj['success']) {
    echo "CNPJ consultado com sucesso. Razão Social: " . $resultCnpj['data']['nomeEmpresarial'] . "\n";
} else {
    echo $resultCnpj['message'] . "\n";
}

echo "\n--- TESTE DE CONSULTA CPF COM SERPRO (FALLBACK CUSTOMIZADO) ---\n";
// Para rodar esse teste, você precisa de suas credenciais da API SERPRO
$consumerKey = 'SUA_CONSUMER_KEY'; 
$consumerSecret = 'SEU_CONSUMER_SECRET';

$customConsultant = new DocumentConsultant(
    [
        // CNPJ Providers prioridades
        new SerproProvider($consumerKey, $consumerSecret), // Oficial primeiro
        new MinhaReceitaProvider(),                        // Cai na grátis depois
        new BrasilApiProvider()
    ],
    [
        // CPF Providers prioridades
        new SerproProvider($consumerKey, $consumerSecret)  // Só Serpro consulta CPF oficialmente de modo prático
    ]
);

// Descomente usando um CPF/CNPJ válido quando tiver sua Consumer Key / Secret:
// $customResult = $customConsultant->consultCpf('11122233344');
// print_r($customResult);

